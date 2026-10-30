# Hosting, deploy pipeline and DNS

## How it works now
- GitHub repo (public): https://github.com/vinit2776/pgfoils-website
- Vercel project: `pgfoils-site`. Live: https://pgfoils-site.vercel.app
- CI (`.github/workflows/ci.yml`): tests, performance budget and Lighthouse on every push and pull request.
- Deploy (`.github/workflows/deploy.yml`), on every push to `main`:
  1. GitHub builds images, CSS/JS, the Filament assets and a read-only fallback database (`database/deploy.sqlite`).
  2. It publishes the result as the `release` branch.
  3. Vercel's Git connection deploys `release` to production (Production branch = `release`).
- Two pushes in quick succession: the older deploy is cancelled by design (`concurrency` in deploy.yml). The newer one contains both sets of changes, so nothing is lost; wait for it to finish.
- No Vercel token is needed. The old `VERCEL_TOKEN` GitHub secret is unused: delete it and revoke the token in Vercel.
- Pull requests do not get preview links.

## What runs live
The staff admin runs on Vercel too, not only the public site.
- **Database:** TiDB Cloud (MySQL-compatible). `api/index.php` uses it whenever `DB_HOST` is set in Vercel. Only when `DB_HOST` is missing does it fall back to the read-only `deploy.sqlite` snapshot, which is for previews and cannot be written to.
- **Files:** investor documents sit in a private Cloudflare R2 bucket. Staff uploads go straight from the browser to R2 (`LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK`), so the 4.5 MB Vercel request limit does not apply. Investors download through a stable address that hands out a short-lived signed link. Uploads are limited to 25 MB.
- **Function region:** Singapore (`sin1`), next to the database.
- **Settings:** the variables above are Production variables in Vercel (`DB_*`, `MYSQL_ATTR_SSL_CA`, `R2_*`, `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK`, session and cache drivers). `scripts/set-vercel-env.sh` sets them.
- **First-time data setup:** `scripts/go-live-setup.sh` is run once by the owner. It creates the tables, loads the Investors Corner list and roles, creates the first Admin account and copies the old investor files into R2. Deploys do not change the database; migrations are run by hand.

## Staff admin on the live site
- `/admin`: sign in. Admins must use two-step sign-in; Editors and Reviewers can opt in.
- **Add a document** (`/admin/add-document`): three steps: Upload, Details, Publish. Everyone with a staff role can use it. Reviewers can only save a draft; Editors and Admins can publish now, schedule, or save a draft.
- **Documents**: edit, replace a file (same public address, old file kept), unpublish. Drafts and unpublished items also have Publish and Schedule.
- **Investors menu**: add, order, hide or regroup sections. Nothing is deleted.
- Files uploaded in step 1 of the wizard are stored before the document is saved. If someone abandons the wizard, the file stays in R2 without a document. It is harmless but can be cleaned up from the bucket.
- To check a new deploy: open `/admin/add-document`. A signed-out visitor should be sent to the sign-in page; a 404 means the new build is not live yet.

## Still to do before launch
- Point the real domain (below).
- Set `APP_URL` to `https://pgfoils.in` in Vercel and redeploy.
- Decide whether Reviewers may publish (today they cannot; see `plan/ADMIN-SPEC.md`, section 1).
- Not built yet: virus scanning of uploads (listed in the admin spec).

## DNS: pointing pgfoils.in at Vercel (only when ready to launch)
1. Add the domains to the project:
   vercel domains add pgfoils.in pgfoils-site
   vercel domains add www.pgfoils.in pgfoils-site
2. At the registrar or DNS host, set:
   | Type  | Name | Value                | TTL |
   | A     | @    | 76.76.21.21          | 300 |
   | CNAME | www  | cname.vercel-dns.com | 300 |
   Remove other A, AAAA or CNAME records for @ and www (old host). Leave MX (email) and TXT (SPF, DKIM) alone.
3. Lower the TTL to 300 a day before, so a rollback is quick.
4. Check after propagation: dig +short pgfoils.in -> 76.76.21.21. Vercel issues HTTPS by itself.
5. Make pgfoils.in the primary and www redirect to it (Vercel -> Project -> Settings -> Domains).
6. vercel env add APP_URL production (value https://pgfoils.in), then push to main to redeploy.
7. Add pgfoils.co.in and pgfoils.co the same way and redirect them to pgfoils.in.
Rollback: put the old A record back; with TTL 300 it reverts in minutes.
Do not switch to Vercel nameservers unless every existing record (MX, TXT) is copied into Vercel first.
