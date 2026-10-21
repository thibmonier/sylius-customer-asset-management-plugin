# Sylius Customer Asset Management Plugin

> Français : [README.fr.md](README.fr.md) <!-- vitrine:lang -->

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Status: design stage. Nothing functional is released yet.**

Planned: a B2B customer declares the equipment they own ("Truck model X, plate AB-123-CD") in
their account, and the catalogue can be filtered to the spare parts compatible with it.

## What exists today

- A Sylius 2.3 plugin skeleton that boots in the test application.
- [`SPEC.md`](SPEC.md): the planned behaviour and its limits.
- [`docs/adr/0001-modele-de-donnees-et-filtre.md`](docs/adr/0001-modele-de-donnees-et-filtre.md): data model and filter strategy.

Entities, administration, customer area and the catalogue filter are not implemented.

## Development

Requirements: PHP 8.3+, Composer.

```bash
composer install
php -d memory_limit=2G vendor/bin/ecs check
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/rector process --dry-run
vendor/bin/phpunit
```

<!-- vitrine:demo-b2b:start -->
## B2B demo

Status: **not present in the demo** yet. The [Sylius B2B demo](https://github.com/thibmonier/sylius-b2b-demo) does not include this plugin, and no demo page or data exists for it.

- Author: [Thibaut Monier](https://www.linkedin.com/in/thibautmonier/)
<!-- vitrine:demo-b2b:end -->

## License

MIT, see [LICENSE](LICENSE).
