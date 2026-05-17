# Contact Import Worker

## How to Run

```bash
composer install
php import.php
```

Tests:

```bash
composer test
# or directly:
vendor/bin/phpunit --testdox
```

---

## What Was Implemented

### Architecture

The solution follows a **single-responsibility pipeline** with four distinct layers:

```
import.php
  └─ ContactImporter          (orchestrator — wires the pipeline, owns no logic)
       ├─ ContactNormalizer   (reads JSON, normalizes, deduplicates)
       │    └─ ContactValidator
       ├─ BatchProcessor      (iterates contacts in configurable batches)
       │    └─ RetryPolicy    (exponential back-off with jitter)
       └─ ImportReport        (assembles + persists output/import-results.json)
```

Each class has one reason to change. `ContactImporter` is intentionally thin — it's a composition root, not a god object.

### Key decisions

**`Contact` as a value object** — contacts have no identity before they reach the CRM. Email is the natural key for deduplication, so there's no need for a database-style ID at this stage.

**Field-level merge strategy** — when two records share an email, `Contact::merge()` picks the non-empty value field-by-field. This preserves the most complete data without arbitrarily discarding one record.

**`CrmResponse` with a typed enum** — instead of returning strings or booleans, the mock client returns a structured response. This makes the retry logic a simple `match` on the status, and adding a new status in the future is a one-line change.

**Retry with jitter** — exponential back-off without jitter causes synchronized retries in batch scenarios (thundering herd). Adding `random_int(0, 50ms)` is a small cost for a meaningful resilience improvement.

**Rate-limit-aware back-off** — rate limit responses use a longer base delay (500ms vs 100ms) to respect the upstream's signaling. In production this would read the `Retry-After` header.

**`CrmClientInterface`** — the importer depends on an interface, not the mock. This makes it trivial to inject a real HTTP client without touching any pipeline code.

---

## Assumptions

- The input JSON is a flat array of objects (not paginated, not nested).
- "Most complete" for merging means: prefer non-empty strings field-by-field; the first record's email is canonical.
- `usleep()` delays are acceptable in a CLI worker context. In a queue-based system, back-off would be handled by the queue infrastructure.
- The `MockCrmClient` is intentionally deterministic (based on call count + email hash) so tests can exercise all code paths without flakiness.
- PHP 8.1+ is assumed (enums, readonly properties, named arguments).

---

## What I Would Improve With More Time

**Observability** — add structured logging (Monolog or PSR-3) per contact so failures are traceable without re-running. Current output is a final JSON blob; production needs per-record audit trails.

**Idempotency** — track processed emails in a lightweight store (SQLite, Redis) so re-runs don't re-send contacts that already succeeded. Critical for resumable imports on large datasets.

**Real bulk endpoint** — `BatchProcessor` currently sends contacts one by one because `MockCrmClient` is per-contact. A real integration would use a bulk `POST /contacts/batch` endpoint, reducing HTTP overhead by the batch size factor.

**Config via environment** — `batchSize` and `maxAttempts` are constructor params today. For a production worker these should come from env vars or a config file so ops can tune them without code changes.

**Input validation schema** — currently we only validate the email field. A JSON Schema validator (e.g. `justinrainbow/json-schema`) would catch malformed payloads early and produce cleaner error messages.

**CI pipeline** — add a GitHub Actions workflow running `phpunit` and a static analysis tool (PHPStan level 8 or Psalm) on every PR. The codebase is typed enough to benefit from strict analysis.
