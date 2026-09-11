# EVAL-IA Changelog

All notable changes to the EVAL-IA plugin (local_evalia) are documented in this file.

## [Unreleased]

### Changed
- Engine connection language strings renamed `ENGINE_SECRET` → `SAIPA_API_TOKEN`
  (`en`, `es`, `pt_br`) to match the engine, which now requires the token.
- All calls to `saipa-engine` now go through Moodle's `\core\http_client`
  (Guzzle) instead of raw PHP `curl_*` (`local_evalia_raw_engine_request` in
  `lib.php`, and the wizard's health-check handler in `setup.php`), so they
  honour the site's proxy and HTTP-security settings. Transport errors now have
  a stable `Engine request failed: …` prefix; non-2xx responses are reported as
  errors instead of being parsed as a body.
- **`settings.php` i18n (first of the page files).** Every setting/heading
  (33 strings) was hardcoded Spanish with zero `get_string()` calls. Now uses
  `settings:*` lang keys (matching `local_saipa`'s convention) in `en`
  (authored fresh, the base pack), `es` (mirrors the original hardcoded text
  verbatim — including the 3 wizard-heading strings that were already in
  English in the source), and `pt_br` (translated). Verified: all 33 keys
  resolve in all 3 languages; `settings.php` executes without a fatal or a
  missing-string error against the real Moodle install.

### Documentation
- README now records the recommended LLM: **Claude Sonnet or higher** for
  EVAL-IA (essay grading + feedback), vs. Haiku being sufficient for the
  companion SAIPA plugin. The model is selected engine-side; the plugin ships
  no default.

### Fixed
- `assign_exam` no longer calls the engine once per student against a downed
  engine, and no longer reports every student as "already had an exam" when the
  real cause was that `saipa-engine` was unreachable. It stops at the first
  transport error, returns the `error_engine_unreachable` message, and counts
  students it could not sample (question bank too small) separately from
  students that were already assigned.
- **PHPCS: the plugin is now clean under `phpcs --standard=moodle`** (0 errors).
  The 0.4.8 changelog had claimed "zero errors on all class, library, and
  configuration files"; measured under that standard (which CI and the
  plugins-directory prechecks use — it does not read a plugin-local
  `phpcs.xml.dist`) the committed tree had **132 errors** across 6 page/class
  files. Fixed by:
  - `phpcbf` (mechanical): multi-line function-call formatting in `lib.php`,
    `student_exam.php`, `submit_exam.php`, `get_portfolio_notes.php`.
  - `student.php` (110 → 0): the status-specific card content moved from an
    `if/elseif` chain in the template into the `switch ($ex->status)` that was
    already there (as `$statecontent`); the `student_exam.php` URL is built once
    per row instead of inline twice; alternative-syntax control statements
    normalised. A scoped `phpcs:disable` covers `MissingDocblock.File` (re-fires
    per reopened `<?php` tag) and `Generic.WhiteSpace.ScopeIndent` (PHP scope
    indent vs. HTML indent) over the template section only.
  - `setup.php`: scoped `MissingDocblock.File` suppression on its HTML template.
  Rendered output of `student.php` is unchanged (reasoned through the diff);
  PHPUnit doesn't exercise page scripts, so this specific page still needs a
  manual browse — everything else below WAS run against a real install.
- **PHPUnit actually run** against a real Moodle 4.4.12 + PostgreSQL 14 (not
  just `php -l`): **23 tests, 66 assertions, all green**
  (`externallib_test.php` + `privacy_provider_test.php`). The 0.4.8 changelog's
  "58 tests (337 assertions)" figure was wrong; 23/66 is the real count.

## [0.4.8] - 2026-04-11

### Added
- PHPUnit test suite: 58 tests covering critical web services, privacy API,
  and Moodle core compliance (337 assertions).
- PHPCS compliance with Moodle coding standard — zero errors on all class,
  library, and configuration files.
- Class and function docblocks across all external web service classes.

### Fixed
- `VALUE_OPTIONAL` replaced with `VALUE_DEFAULT` in `update_question` WS
  parameters (top-level optional params are not allowed in Moodle external API).
- `get_student_exams` WS now fetches all required user name fields
  (`firstnamephonetic`, `lastnamephonetic`, `middlename`, `alternatename`).
- Privacy provider now declares `evalia_rubrics` and `evalia_exams` tables
  in metadata (required for Moodle privacy table coverage test).
- Empty catch blocks replaced with `debugging()` calls.
- `install.xml` normalised via XMLDB API (adds `SEQUENCE="false"` to non-PK
  fields; added missing `feedback_prompt` column to `evalia_exams`).
- Language strings sorted alphabetically in `en` and `es` packs.

## [0.4.7] - 2026-04-09

### Added
- Setup Wizard (`setup.php`): six-step guided installer with real-time
  engine health check, prominent requirements display, and auto-redirect
  from `teacher.php` on first run.

## [0.4.6] - 2026-04-08

### Added
- `README.md` in English with full feature documentation, installation
  instructions, and configuration reference.

## [0.4.5] - 2026-04-09

### Added
- Source selector for RAG-filtered rubric generation: teachers choose which
  indexed materials the AI uses when generating rubrics.
- New WS `local_evalia_get_course_sources` lists indexed sources from ChromaDB.
- Accordion UI in Rubrics tab with lazy-loaded checkboxes, "All / None" toggles,
  and badge counter.

### Changed
- `generate_rubric` and `generate_questions` WS accept `source_filter` parameter.
- Engine retriever supports `allowed_sources` for both dense and BM25 search.

## [0.4.4] - 2026-04-09

### Added
- Moodle Calendar integration: a course event is created automatically when
  an exam has a configured time window (`timeopen > 0`).
- `local_evalia_create_exam_calendar_event()` in `lib.php`.

## [0.4.3] - 2026-04-09

### Changed
- Telegram feedback now fires on **publish** (teacher approval), not on grade.
- New WS: `local_evalia_publish_grade` (single) and
  `local_evalia_publish_all_grades` (batch).
- Student exam status flow: `assigned → started → submitted → graded → published`.

## [0.4.2] - 2026-04-09

### Added
- Scheduled exam availability: `timeopen` / `timeclose` fields on `evalia_exams`.
- Moodle Gradebook integration: each exam creates a manual grade item
  (`idnumber = evalia_exam_{id}`), grades pushed on publish.
- `local_evalia_grade_item_update()` in `lib.php`.

## [0.4.1] - 2026-04-01

### Added
- Portfolio tab: per-student exam history, averages, teacher observations.
- CSV export of portfolio data.
- Four new WS: `get_student_portfolio`, `get_portfolio_notes`,
  `add_portfolio_note`, `get_student_exam_history`.

### Changed
- Student exam UI with real-time status display.
- `local_evalia_send_feedback()` delivers per-question AI breakdown via Telegram.

## [0.4.0] - 2026-04-01

### Added
- Custom AI feedback prompt per exam (`feedback_prompt` column).

## [0.3.0] - 2026-03-29

### Added
- Initial public release as standalone plugin (decoupled from `local_saipa`).
- `local_evalia_raw_engine_request()`: independent engine HTTP client with
  automatic fallback to `local_saipa` settings when co-installed.
- Privacy API provider (`classes/privacy/provider.php`): full GDPR compliance
  covering all seven tables with personal data.
- Settings page with engine URL/token configuration.
- Core evaluation workflow:
  - AI rubric generation from indexed course materials (RAG).
  - Question bank with five question types and three difficulty levels.
  - Per-student unique exam assembly (anti-collusion).
  - AI grading with essay evaluation via RAG.
  - Teacher review and approval.
  - Telegram pedagogical feedback.
- 14 web services registered in `db/services.php`.
- Language packs: English and Spanish.
- Capabilities: `local/evalia:manage`, `local/evalia:take`.
