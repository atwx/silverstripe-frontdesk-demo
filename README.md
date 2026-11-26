# silverstripe-frontdesk-kit demo

A public demo of [`atwx/silverstripe-frontdesk-kit`](https://github.com/atwx/silverstripe-frontdesk-kit):
a small agency back office (clients, contacts, projects, tasks) that shows
every feature of the kit live, next to the code that builds it.

- Start page and feature pages (`/`, `/features/<key>`) are public.
- The live examples need a login. The demo account is shown on the login page:
  `demo@frontdesk-demo.test` / `Try-Frontdesk-2026`.
- Everyone shares this account and may change anything. **All data is reset
  every night.**

## Features shown

Lists and columns · filters (text, select, catalog, multiple choice, date
range, tree) · views, saved views, column choice · Excel exports · bulk actions
(options, dialog with fields, confirmation) · row actions · forms with groups
and access levels · record view with cards and tabs · record pages in a sidebar
with a searchable switcher · sub-lists and "edit several" · record actions and
timeline · file uploads · pages without login · catalogs · navigation with
badges · charts · recently viewed.

## How the feature pages work

`app/_config/features.yml` lists the features: title, group, summary, the live
example and the code regions that build it. A region is cut from the project's
own files between markers, so the page always shows the code that runs:

```php
// [demo:filters]
protected function defineFilters(): FilterCollection
{
    …
}
// [/demo:filters]
```

YAML uses `# [demo:…]`, templates `<%-- [demo:…] --%>`. The bar "How is this
built?" below the header links the features whose `match` patterns fit the
current URL. See `App\Features\FeatureRegistry`.

To show a new feature: mark the code, add an entry to `features.yml`.

## Local setup

```sh
ddev start
ddev composer install
ddev exec yarn install
ddev exec yarn build
ddev exec vendor/bin/sake db:build --flush
ddev exec vendor/bin/sake demo:seed
```

The site runs at `https://frontdesk-demo.ddev.site/` (add the host with
`ddev hostname frontdesk-demo.ddev.site 127.0.0.1` if your system does not
resolve `*.ddev.site`). The CMS admin comes from `SS_DEFAULT_ADMIN_*` in `.env`.

## Server

Deploy (on every push to `main`):

```sh
composer install --no-dev --optimize-autoloader
yarn install && yarn build
vendor/bin/sake db:build --flush
vendor/bin/sake demo:seed        # creates what is missing, never overwrites
```

Nightly cron job:

```sh
vendor/bin/sake demo:reset       # removes all data, saved views, uploads; seeds again
```

Environment: `SS_ENVIRONMENT_TYPE="live"`, a strong `SS_DEFAULT_ADMIN_PASSWORD`
(the admin is never shown), database credentials.

## Safety of the public demo

- The demo account cannot change its name, email or password (`App\Demo\DemoAccount`),
  the profile page is closed for it, and wrong passwords of others never lock it.
- It has no CMS access, only the permissions of the group "Team".
- Uploads: images only (jpg, png, webp), at most 2 MB, stored protected
  (`app/_config/demo.yml`); they are deleted with the nightly reset.
- No mail leaves the site (null mailer), e.g. "lost password".
- The feedback page (`feedback/<token>`) only accepts a 32-character token and
  writes only the two feedback fields.
