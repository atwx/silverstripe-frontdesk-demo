# Silverstripe frontdesk-kit Template

A minimal Silverstripe 6.2 starter project preconfigured for the
[`atwx/silverstripe-frontdesk-kit`](https://github.com/atwx/silverstripe-frontdesk-kit) module.

Comes with:

- DDEV (Apache + PHP 8.4 + MariaDB 10.11) with a headless Chrome service
- Vite 6 frontend build with Tailwind CSS 4 and DaisyUI 5 (single build, imports
  the frontdesk-kit preset — see [Frontend CSS](#frontend-css))
- SCSS support via `sass-embedded`
- Silverstripe Vite helper (`atwx/silverstripe-vitehelper`)
- Project info (`atwx/silverstripe-projectinfo`)
- Gate client (`atwx/silverstripe-gate-client`) for SSO logins from a central [Gate Manager](https://github.com/atwx/silverstripe-gate-manager)
- Gate API (`atwx/silverstripe-gate-api`), JWT authenticated content API (site role, enabled by the gate client)
- Gate tasks (`atwx/silverstripe-gate-tasks`, dev only): `sake tasks:pull-live` and `sake tasks:remote`
- Headless PDF rendering (`atwx/silverstripe-headless-pdf` + `chrome-php/chrome`, talks to the DDEV Chrome service)
- Rector (`wernerkrauss/silverstripe-rector`)
- PHPStan with Silverstan (`cambis/silverstan`)
- PHPCS (PSR-2 with Silverstripe-friendly exceptions)
- IDE Annotator (`silverleague/ideannotator`)

## Getting started

1. Use this template on GitHub or clone it locally.
2. `ddev start`
3. `ddev composer install`
4. `ddev yarn install`
5. `ddev yarn build` (or `ddev yarn dev` for the Vite dev server)
6. `ddev sake db:build --flush`

The site is available at `https://frontdesk-kit-template.ddev.site/`,
admin at `/admin` (default credentials: `admin` / `admin` — see `.env`).

## Vite dev server

To use Hot Module Replacement, copy `.env.example` to `.env` and uncomment
`VITE_DEV_SERVER_URL`, then run `ddev yarn dev` in a second terminal.
The dev server is exposed on port 5173 via DDEV's router.

## Frontend CSS

This template builds **one** Tailwind bundle that styles the whole page — the kit
and the app share a single DaisyUI runtime. `app/client/src/css/tailwind.css`
imports the frontdesk-kit preset:

```css
@import "tailwindcss";
@import "@fdk/preset.css";   /* daisyUI config + kit @source globs + .fdk-* chrome */

@source "../../../templates/**/*.ss";
@source "../../../src/**/*.php";
```

The `@fdk` alias points at the kit's CSS directory in `vendor/`. With
`@tailwindcss/vite` it **must be an absolute path** (see `vite.config.js`); a
relative alias is not resolved for CSS `@import`.

The kit's prebuilt `frontdesk.css` is intentionally **not** loaded in `Page.ss` —
loading it as a second stylesheet would create a second DaisyUI runtime that can
override the kit's component styles. Only the kit's `frontdesk.js` is loaded via
`resourceURL()`. For the alternative prebuilt-CSS mode and theming, see the
[frontdesk-kit frontend docs](https://github.com/atwx/silverstripe-frontdesk-kit/blob/main/docs/frontend.md).

> If you add another Composer module that ships Tailwind-using templates, add its
> template path as another `@source` in `tailwind.css` so its classes are emitted.

## Quality tooling

```sh
ddev composer phpstan       # static analysis
ddev composer rector-dry    # show pending refactors
ddev composer rector        # apply refactors
ddev composer lint          # phpcs
ddev composer fix           # phpcbf
```

## Project layout

```
app/
  _config/         # YAML config
  _config.php      # PHP bootstrap
  client/
    src/
      css/         # tailwind.css entry
      scss/        # main.scss entry
      js/          # main.js entry
    dist/          # Vite build output (gitignored)
  src/             # PHP source
  templates/       # .ss templates
public/            # docroot
```
