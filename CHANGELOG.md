# Changelog

All notable changes to this project are documented in this file. The format is based on
[Keep a Changelog](https://keepachangelog.com/).

## [0.2.0] - 2026-10-30

### Added

- A page per machine in "My equipment" (`/{_locale}/account/equipment/{id}`), linked from the list, with the
  Twig hook `tmonier_equipment_shop.account.equipment_show.extensions` for other plugins (US-242).

## [0.1.1] - 2026-10-23

### Changed

- Deleting a machine of "My equipment" asks for a confirmation first: the Delete button of the list
  opens a page that names the machine, with a confirm button (POST, CSRF token) and a cancel link.
  It works without JavaScript; the `data-turbo-confirm` attribute of 0.1.0 had no effect on a shop
  without Turbo (US-057).

## [0.1.0] - 2026-10-22

### Added

- Equipment models (code, translated name) managed in the administration, and the choice of the
  compatible equipment models on the form of a product variant (US-056).
- "My equipment" in the customer account: list, add, edit and delete the machines of the customer
  (UUID, voter, 404 for a machine that is not the customer's, shared with the company when the company
  accounts plugin is installed, `max_assets_per_customer`, deleted with the customer) (US-057).
- An equipment model used by a machine cannot be deleted in the administration (US-057).
- "Compatible with my equipment" filter on the listing of the categories and their search: all the
  equipment or one machine, kept in the session and in the URL (`equipment` parameter), shown to a
  signed-in customer with a non-empty fleet only, `EXISTS` sub-select on the listing query of Sylius,
  no query when the filter is not selected (US-058).
- CI on PHP 8.2 to 8.5 (Sylius 2.2 and 2.3, Symfony 6.4 to 8.0, MySQL, MariaDB, PostgreSQL), actions
  pinned by SHA, minimal `permissions`, Dependabot for the GitHub Actions.
- Skeleton of the plugin (bundle), quality baseline (ECS, PHPStan max, Rector, PHPUnit), specification
  (`SPEC.md`) and ADR-0001.

### Fixed

- The `equipment` parameter of the listing links shows the selection that applies: a machine outside the fleet becomes `all`, an invalid value is dropped.

### Changed

- The plugin requires PHP 8.2+ and Sylius 2.2.9+ (it required PHP 8.3+ and Sylius 2.3).
