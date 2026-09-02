# GlobalSearch for Dolibarr

GlobalSearch is an external Dolibarr module that adds a permission-aware global search to the standard sidebar search and an optional central search palette.

## Compatibility

- Dolibarr 19.0.0 to 24.x
- PHP 7.0 or later

## Features

- Search previews for authorised third parties, contacts, products/services, invoices, orders, proposals, contracts, projects and tasks.
- Result limits and full native-list links for each category.
- Central keyboard search palette, configurable by an administrator.
- Respects active entity and existing user permissions. External users cannot access the global search.
- No database tables, no external communication and no modification of Dolibarr core files.

## Installation

1. Upload `module_globalsearch-1.3.0.zip` through **Home > Setup > Modules/Applications > Deploy an external module**.
2. Activate **Global search**.
3. Configure the optional palette from the module setup page.

The package contains `globalsearch/` at its root and can be installed by Dolibarr under either `htdocs/custom/globalsearch` or `htdocs/globalsearch`.

## Licence

GPL-3.0-or-later. See [docs/COPYING](docs/COPYING).
## Support the project

If GlobalSearch is useful to you, you can support its development by [buying me a virtual coffee on Ko-fi](https://ko-fi.com/yurexa). This is entirely optional.

## Soutenir le projet

Si GlobalSearch vous est utile, vous pouvez [m’offrir un café virtuel sur Ko-fi](https://ko-fi.com/yurexa). Cette contribution est entièrement facultative.

