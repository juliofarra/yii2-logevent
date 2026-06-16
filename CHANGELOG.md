# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-06-16

### Changed

- `LogEventWidget`: when `customViewPath` is set, the widget now renders **only** that view — the built-in `<details>` shell and the bundled asset are skipped entirely, so the custom view owns the whole output (including any toggle button). The custom view now also receives `$initiallyOpen` and `$buttonLabel`.

### Added

- Documented global configuration of the widget via Yii's DI container (set defaults such as `customViewPath` once for every call).

## [1.1.0] - 2026-06-15

### Added

- `LogEventWidget`: optional widget that renders a model's change log inline using a native HTML5 `<details>` element — no popup, no Bootstrap, no jQuery. Configurable via `model`, `initiallyOpen`, `buttonLabel` and `customViewPath`.
- `LogEventWidgetAsset`: minimal, framework-free stylesheet for the widget (can be disabled to apply your own styles).
- Custom view support: point `customViewPath` to your own view (receives `$model` and `$logEvents`) to fully control the log presentation.

## [1.0.0] - 2026-06-12

### Added

- `LogEventBehavior`: audits INSERT (full snapshot), UPDATE (diff of old/new values) and DELETE (full snapshot) of any ActiveRecord model into a log table, as JSON.
- `exclude` option: attributes invisible to the log.
- `mask` option: attributes logged with their value replaced by `*****` (configurable via `maskValue`).
- Configurable log table per model through `logEventClass`.
- `LogEvent` ActiveRecord model and `LogEventQuery` with `forEntity()`, `ofEvent()` and `ordered()` scopes. The `entity` + `entity_id` columns form a polymorphic reference to the audited record.
- `logEvents` relation on audited models.
- Namespaced Yii2 migration creating the `log_event` table (PostgreSQL, MySQL/MariaDB, SQLite).
- PHPUnit test suite (SQLite in-memory).
