# Filament Tenancy Starter Kit

A Laravel starter kit for multi-team applications built with [Filament](https://filamentphp.com). Users belong to teams, each team has an owner, and an admin panel manages the whole application.

## Features

- **Two panels**
  - `/app`: the team panel. Each team is a Filament tenant, and users switch between the teams they belong to.
  - `/admin`: the admin panel, restricted to users flagged `is_admin`.
- **Teams**: any user can create a team and becomes its owner. Members can view the team; only the owner can rename it, change its slug or delete it.
- **Invitations**: from the Members page, the owner invites people by email, removes members, and resends or revokes pending invitations. The emailed link works for new users, who create their account, and for existing users, who sign in first. Links expire after 7 days. Only a hash of the token is stored.
- **Administration**: Team and User resources manage every team, its owner and members, and each user's admin right. Admins cannot revoke their own right or delete themselves, and users who own teams cannot be deleted.
- **Private by default**: every 403 is rendered as a 404, so the app never confirms that a forbidden resource exists.
- **Consistent notifications**: every toast has a short title and, when useful, a one-sentence body.

There is no public sign-up. Accounts are created by an admin or by accepting an invitation.

## Stack

- PHP 8.3+
- Laravel 13
- Filament 5
- Pest 5
- SQLite by default

## Getting started

```bash
git clone https://github.com/cpuchalver/filament-tenancy-starter-kit.git
cd filament-tenancy-starter-kit

composer run setup
php artisan db:seed
composer run dev
```

`composer run setup` installs the dependencies, creates `.env`, generates the app key, runs the migrations and builds the assets.

The seeder creates an admin user who owns a demo team:

| Email              | Password   |
|--------------------|------------|
| `demo@example.com` | `password` |

Sign in at `/app` for the team panel or `/admin` for the admin panel.

### Mail, queue and scheduler

- Invitation emails are queued. `composer run dev` starts a queue worker; in production, run one with `php artisan queue:work`.
- Mail is written to the log by default (`MAIL_MAILER=log`). Set the `MAIL_*` variables in `.env` to send real emails.
- Expired invitations are deleted by `model:prune`, scheduled daily. In production, run the scheduler with `php artisan schedule:run` from cron.

## Tests

```bash
composer test
```

## License

Open-sourced under the [MIT license](https://opensource.org/licenses/MIT).
