# Changelog

All notable changes to this project are documented in this file. The format is based on
[Keep a Changelog](https://keepachangelog.com/).

## [0.1.0] - Unreleased

### Added

- Equipment models (code, translated name) managed in the administration, and the choice of the
  compatible equipment models on the form of a product variant (US-056).
- CI on PHP 8.2 to 8.5 (Sylius 2.2 and 2.3, Symfony 6.4 to 8.0, MySQL, MariaDB, PostgreSQL), actions
  pinned by SHA, minimal `permissions`, Dependabot for the GitHub Actions.
- Skeleton of the plugin (bundle), quality baseline (ECS, PHPStan max, Rector, PHPUnit), specification
  (`SPEC.md`) and ADR-0001.

### Changed

- The plugin requires PHP 8.2+ and Sylius 2.2.9+ (it required PHP 8.3+ and Sylius 2.3).
