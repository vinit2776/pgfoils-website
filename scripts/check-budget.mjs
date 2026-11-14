#!/usr/bin/env node
// Performance budget gate (DESIGN.md "Performance budget").
//   npm run build && php artisan serve --port=8010 &   then   npm run budget
// Env: BUDGET_URL (default http://localhost:8010), BUDGET_PATHS (comma list, default "/").
import { gzipSync } from 'node:zlib';
import { readdirSync, readFileSync } from 'node:fs';

const BASE = process.env.BUDGET_URL ?? 'http://localhost:8010';
const PATHS = (process.env.BUDGET_PATHS ?? '/').split(',');
const KB = 1024;

// Limits (bytes). BUDGET_SCALE=0.1 tightens them 10x, used to prove the gate can fail.
const SCALE = parseFloat(process.env.BUDGET_SCALE ?? '1');
const LIMIT = {
  jsGzip: 30 * KB,          // all first-load JS, gzipped
  cssGzip: 30 * KB,         // all first-load CSS, gzipped
  htmlGzip: 40 * KB,        // document, gzipped
  fonts: 60 * KB,           // all web fonts together
  desktopTotal: 1200 * KB,  // first load on a wide screen
  mobileTotal: 700 * KB,    // first load on a phone
};
for (const k of Object.keys(LIMIT)) LIMIT[k] *= SCALE;

let failed = false;
const fmt = (n) => `${(n / KB).toFixed(1)} KB`;
function check(label, value, limit) {
  const ok = value <= limit;
  if (!ok) failed = true;
  console.log(`  ${ok ? 'PASS' : 'FAIL'}  ${label.padEnd(34)} ${fmt(value).padStart(10)}  (limit ${fmt(limit)})`);
}

// 1. Built assets on disk (no server needed)
const dir = new URL('../public/build/assets/', import.meta.url);
let js = 0, css = 0;
for (const f of readdirSync(dir)) {
  const gz = gzipSync(readFileSync(new URL(f, dir))).length;
  if (f.endsWith('.js')) js += gz;
  if (f.endsWith('.css')) css += gz;
}
console.log('Built assets');
check('JavaScript (gzip, all chunks)', js, LIMIT.jsGzip);
check('CSS (gzip, all files)', css, LIMIT.cssGzip);

// Fonts on disk
let fontBytes = 0;
for (const f of readdirSync(new URL('../public/fonts/', import.meta.url))) if (f.endsWith('.woff2')) fontBytes += readFileSync(new URL(`../public/fonts/${f}`, import.meta.url)).length;
check('Web fonts (all files)', fontBytes, LIMIT.fonts);

// 2. First-load weight per page, desktop and phone scenarios
async function bytes(url, gz) {
  const res = await fetch(url);
  if (!res.ok) throw new Error(`${url} -> ${res.status}`);
  const buf = Buffer.from(await res.arrayBuffer());
  return gz ? gzipSync(buf).length : buf.length;
}
const abs = (u) => new URL(u, BASE).toString();

for (const path of PATHS) {
  console.log(`\nPage ${path}`);
  const html = await (await fetch(abs(path))).text();
  const htmlGz = gzipSync(Buffer.from(html)).length;
  check('HTML (gzip)', htmlGz, LIMIT.htmlGzip);

  const assets = new Set();
  for (const m of html.matchAll(/<link[^>]+rel="stylesheet"[^>]+href="([^"]+)"/g)) assets.add(m[1]);
  for (const m of html.matchAll(/<script[^>]+src="([^"]+)"/g)) assets.add(m[1]);
  const fonts = [...html.matchAll(/<link[^>]+rel="preload"[^>]+as="font"[^>]+href="([^"]+)"|<link[^>]+href="([^"]+)"[^>]+as="font"/g)].map((m) => m[1] ?? m[2]);

  let fixed = htmlGz;
  for (const a of assets) fixed += await bytes(abs(a), a.endsWith('.css') || a.endsWith('.js'));
  for (const f of fonts) fixed += await bytes(abs(f), false);

  // Images the browser would pick: prefer AVIF; phone picks the smallest candidate >= 480w, desktop the largest <= 1600w
  const pickImages = async (target) => {
    let sum = 0;
    for (const pic of html.matchAll(/<picture>([\s\S]*?)<\/picture>/g)) {
      const eager = /fetchpriority="high"/.test(pic[1]);
      if (!eager && target === 'mobile' ) continue;   // lazy images are below the fold
      const set = pic[1].match(/type="image\/avif" srcset="([^"]+)"/)?.[1];
      if (!set) continue;
      const cands = set.split(',').map((s) => s.trim().split(/\s+/)).map(([u, w]) => ({ u, w: parseInt(w) }));
      const want = target === 'mobile' ? cands.sort((a, b) => a.w - b.w).find((c) => c.w >= 480) : cands.sort((a, b) => b.w - a.w)[0];
      sum += await bytes(abs(want.u), false);
    }
    return sum;
  };
  check('First load, desktop', fixed + (await pickImages('desktop')), LIMIT.desktopTotal);
  check('First load, phone', fixed + (await pickImages('mobile')), LIMIT.mobileTotal);
}

console.log(failed ? '\nBudget FAILED' : '\nBudget OK');
process.exit(failed ? 1 : 0);
