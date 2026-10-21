# Sylius Customer Asset Management Plugin

> English: [README.md](README.md) <!-- vitrine:lang -->

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Statut : phase de conception. Rien de fonctionnel n'est encore publié.**

Prévu : un client B2B déclare l'équipement qu'il possède (« Camion modèle X, plaque AB-123-CD ») dans
son compte, et le catalogue peut être filtré sur les pièces détachées compatibles.

## Ce qui existe aujourd'hui

- Un squelette de plugin Sylius 2.3 qui démarre dans l'application de test.
- [`SPEC.md`](SPEC.md) : le comportement prévu et ses limites.
- [`docs/adr/0001-modele-de-donnees-et-filtre.md`](docs/adr/0001-modele-de-donnees-et-filtre.md) : modèle de données et stratégie de filtre.

Les entités, l'administration, l'espace client et le filtre du catalogue ne sont pas implémentés.

## Développement

Prérequis : PHP 8.3+, Composer.

```bash
composer install
php -d memory_limit=2G vendor/bin/ecs check
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/rector process --dry-run
vendor/bin/phpunit
```

<!-- vitrine:demo-b2b:start -->
## Démo B2B

Statut : **non présent dans la démo** pour l'instant. La [démo B2B Sylius](https://github.com/thibmonier/sylius-b2b-demo) n'intègre pas ce plugin, et aucune page ni donnée de démo n'existe pour lui.

- Auteur : [Thibaut Monier](https://www.linkedin.com/in/thibautmonier/)
<!-- vitrine:demo-b2b:end -->

## Licence

MIT, voir [LICENSE](LICENSE).
