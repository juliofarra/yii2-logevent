# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-06-12

### Added

- `LogEventBehavior`: audits INSERT (full snapshot), UPDATE (diff of old/new values) and DELETE (full snapshot) of any ActiveRecord model into a log table, as JSON.
- `exclude` option: attributes invisible to the log.
- `mask` option: attributes logged with their value replaced by `*****` (configurable via `maskValue`).
- Configurable log table per model through `logEventClass`.
- `LogEvent` ActiveRecord model and `LogEventQuery` with `forObject()`, `ofEvent()` and `ordered()` scopes.
- `logEvents` relation on audited models.
- Namespaced Yii2 migration creating the `log_event` table (PostgreSQL, MySQL/MariaDB, SQLite).
- PHPUnit test suite (SQLite in-memory).
