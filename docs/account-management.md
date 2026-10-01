# Account management

Database seeding creates the supported roles only. It does not create users or change existing accounts.

After applying migrations, run `php artisan db:seed`, then `php artisan app:create-admin` to create an administrator. The command prompts for account details and a confirmed password. Password entry is hidden, and non-interactive execution is rejected. No account credentials are stored in source code or command arguments.

Administrators can create and edit accounts from the dashboard's User Accounts section. Account forms validate identifiers, roles, and passwords before saving. Administrators cannot suspend or demote themselves. The dashboard profile and account directory use database records.

The profile password form verifies the current password, saves the new hash, clears the remember token, and records a password-change event. Administrative resets also clear the remember token. Authenticated requests and Livewire updates check account access and the session's password hash.

Removing the old seeder does not delete accounts already created in a database. Review and suspend obsolete accounts through User Accounts after establishing a replacement administrator. Other dashboard modules still contain prototype data and actions.
