# Hosting, deploy pipeline and DNS

## How it works now
- GitHub repo (public): https://github.com/vinit2776/pgfoils-website
- Vercel project: `pgfoils-site`. Live: https://pgfoils-site.vercel.app
- CI (`.github/workflows/ci.yml`): tests, performance budget and Lighthouse on every push and pull request.
- Deploy (`.github/workflows/deploy.yml`), on every push to `main`:
  1. GitHub builds images, CSS/JS and a read-only content database.
  2. It publishes the result as the `release` branch.
  3. Vercel's Git connection deploys `release` to production (Production branch = `release`).
- No Vercel token is needed. The old `VERCEL_TOKEN` GitHub secret is unused: delete it and revoke the token in Vercel.
- Pull requests do not get preview links.

## Limits (read before pointing the real domain)
Vercel runs PHP as read-only serverless functions. This deploy serves the public site and a read-only
copy of the Investors Corner list. It cannot run the staff area:
- /admin sign-in, document uploads, the activity log and a writable database need a PHP server with MySQL.
- Uploaded investor files need persistent storage.
Use Vercel for previews and review. For launch, host the full app on a PHP server, or keep Vercel for the
public site and run the admin elsewhere.

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
