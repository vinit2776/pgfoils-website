# PG Foils website (pgfoils.in)

Laravel 13 + Filament 5. Public site is server-rendered Blade with a hand-written CSS layer (no Tailwind, no JS framework). Admin lives at `/admin` (Filament).

## Read first
- Design rules: `../plan/DESIGN.md` (tokens, type, motion, mobile rules, performance budget). Follow it; do not deviate without owner approval.
- Admin and investor-relations spec: `../plan/ADMIN-SPEC.md`
- Build order and milestones: `../plan/BUILD-PLAN.md`
- Approved visual mocks: `../plan/preview/index.html`

## Rules
- Public pages: no JS framework, first-load JS <= 30 KB gzip, home page <= 1.2 MB. `npm run budget` enforces this.
- Mobile first: 360-430 px. 44 px touch targets, 16 px inputs, no hover-only behaviour, no horizontal page scroll.
- Content waiting on the owner is shown with the `.tbs` marker ("to be supplied"), never invented.
- Motion is CSS-first and always optional (`prefers-reduced-motion`). No animation libraries.
- Never hard-delete investor documents from the UI. Every admin action is logged.
- Do not install tooling by piping remote scripts into a shell.

## Commands
- `composer setup` first-time setup; `composer dev` run app + Vite; `composer test` run tests
- `npm run build` build assets; `npm run budget` check performance budget
- `php artisan images:build` generate responsive AVIF/WebP images from `resources/images/source`
