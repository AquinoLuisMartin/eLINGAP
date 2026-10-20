# Hostinger KVM 2 deployment profile

These are reviewable Linux configuration templates for one 2 vCPU, 8 GB application server with Supabase PostgreSQL. They have not been installed. Read [the resource and acceptance plan](../../docs/hostinger-resource-plan.md) and the existing deployment/recovery runbook before use. The docs directory is locally ignored by Git; distribute the runbooks securely with the release.

Replace the example domain, certificate paths, PHP binary/version, operating-system account and application paths. Keep persistent `storage` at `/var/www/elingap/shared/storage` and link it into every release. Keep the protected production `.env` outside release artifacts and link it deliberately; FPM clears inherited environment variables, so installations that inject environment values need an explicit allowlist instead.

| Template | Destination or inclusion point |
| --- | --- |
| `php-fpm.conf` | PHP 8.4 FPM pool configuration, replacing or disabling the unused default pool |
| `opcache.ini` | FPM-only `conf.d` directory |
| `nginx-http.conf` | Include once inside the existing Nginx `http` block |
| `nginx-site.conf` | Nginx site configuration, after HTTPS certificates exist |
| `supervisor.conf` | Supervisor program configuration |
| `crontab` | `/etc/cron.d/elingap`, which includes the `www-data` account column |
| `logrotate.conf` | `/etc/logrotate.d/elingap-runtime` |

Keep the existing OS/Nginx log rotation. The custom access format excludes paths, query strings, IP addresses and cookies; the Nginx error log still requires restricted permissions and review for sensitive request details. Supervisor rotates its own worker log and Laravel rotates its daily application log. Log rotation is operational retention, separate from database business history.

Start with four FPM children and one queue worker. The worker's 60-second timeout is shorter than the 120-second queue reservation. Its 256 MB restart threshold is checked between jobs, while PHP's 512 MB limit bounds each CLI process. The web pool has a 256 MB per-request limit. These are starting budgets, not measured throughput guarantees. Keep persistent database connections disabled.

No Redis, Horizon, Octane, Realtime service or Edge Function is needed for this single-server profile. Keep `QUEUE_CONNECTION=database-atomic`; its message/job transaction guarantee depends on the same PostgreSQL database. File sessions and file cache avoid database churn. Do not change queue drivers independently of the application transaction design.

Before reload, validate with the installed versions of `php-fpm8.4 -t`, `nginx -t`, and `logrotate --debug /etc/logrotate.d/elingap-runtime`. Review Supervisor configuration differences using `supervisorctl reread` before an operator-approved update. Do not blindly copy files over working services. On release, restart the single worker with `php artisan queue:restart`, reload FPM after successful configuration checks, and verify HTTPS and `/up`. Run the queue worker and scheduler on the same persistent cache/storage as web traffic.

Use `.env.supabase.example` as a value-free reference, supply actual secrets privately, and run `php artisan app:check-deployment --database` on the target host. `CAPACITY_MONITOR_ENABLED=true` schedules an hourly aggregate capacity report; a nonzero result needs an operator or external monitor to act on it. It does not send alerts automatically.

Backups require an approved encrypted off-host destination and a restore drill. None of these templates schedules automatic production backups or deletes business data. The resource plan explains why frequent full Supabase dumps can exceed the 5 GB egress allowance.
