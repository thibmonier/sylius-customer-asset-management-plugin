# CLAUDE.md

Sylius 2 plugin, 0.1.0 in preparation: equipment models and variant compatibility (administration),
customer fleet (customer account), "compatible parts" catalogue filter. Behaviour: `SPEC.md`.
Decisions: `docs/adr/`.

## Commands

PHP needs more than 128 MB to compile the test application container: prefix console and
ECS calls with `php -d memory_limit=2G` when running locally.

```bash
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite non-unit   # needs the test database: DATABASE_URL, migrations, fixtures
vendor/bin/phpstan analyse                # level max, no ignore
vendor/bin/ecs check
vendor/bin/rector process --dry-run
```

## Rules

- YAGNI: only what `SPEC.md` and the stories ask for.
- A customer sees and changes only the fleet of their company or account; a foreign id answers 404.
- A plate or serial number is personal data: never logged, never in a URL.
- No push, repository creation or tag without the maintainer's explicit confirmation.
