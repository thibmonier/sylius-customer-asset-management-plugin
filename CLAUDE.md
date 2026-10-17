# CLAUDE.md

Sylius 2 plugin, design stage (slice 1 of the customer asset management plugin): a skeleton that
boots, plus the specification. No entity, no screen. Behaviour: `SPEC.md`. Decisions:
`docs/adr/`. Sylius Plus overlap: `docs/plus-overlap.md`.

## Commands

PHP needs more than 128 MB to compile the test application container: prefix console and
ECS calls with `php -d memory_limit=2G` when running locally.

```bash
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite non-unit   # smoke test: the kernel boots with the bundle
vendor/bin/phpstan analyse                # level max, no ignore
vendor/bin/ecs check
vendor/bin/rector process --dry-run
```

## Rules

- No functional code before the stories US-056 onwards (YAGNI).
- Do not promote the plugin if `docs/plus-overlap.md` says Sylius Plus covers the need.
- No push, repository creation or tag without the maintainer's explicit confirmation.
