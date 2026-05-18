# RioSlum Studio Backend Hiring Test

RioSlum Studio is hiring a Backend Engineer to join our distributed remote engineering team.

As part of the hiring process, we ask candidates to complete a short backend coding test. The goal is to evaluate how you structure code, handle real-world data, work with APIs, and communicate your implementation clearly.

This test is intentionally small. It should take around **45–60 minutes**.

---

## Context

In our day-to-day work, we often need to import contacts from external systems, normalize the data, merge duplicates, and send the cleaned data to third-party services such as CRMs, email platforms, or data APIs.

These integrations must handle imperfect data, failed requests, rate limits, retries, batching, and clear reporting.

Your task is to build a small PHP import worker that simulates this workflow.

---

## Task Overview

Build a PHP command that imports contacts from the provided JSON file, normalizes and deduplicates them, then sends the cleaned contacts to a mock CRM client.

The command should be runnable with:

```bash
php import.php
```

When finished, it should generate this file:

```bash
output/import-results.json
```

---

## Stack

Use the provided stack:

- PHP 8+
- Composer autoloading
- Plain PHP classes

You do **not** need to use Laravel or WordPress for this test.

---

## Provided Files

```bash
.
├── composer.json
├── import.php
├── data/
│   └── contacts.json
├── output/
│   └── .gitkeep
└── src/
    ├── ContactImporter.php
    └── MockCrmClient.php
```

---

## Requirements

Your implementation should:

1. Read contacts from `data/contacts.json`.
2. Normalize email addresses to lowercase.
3. Ignore contacts without a valid email address.
4. Merge duplicate contacts by email.
5. When duplicates exist, keep the most complete data.
6. Process contacts in batches.
7. Send contacts to the mock CRM client.
8. Handle failed CRM requests with retry logic.
9. Handle fake rate limit responses gracefully.
10. Generate `output/import-results.json` with a useful summary.

---

## Expected Output

Your `output/import-results.json` should include a summary like this:

```json
{
  "summary": {
    "total_records": 10,
    "valid_records": 8,
    "invalid_records": 2,
    "duplicates_merged": 2,
    "attempted_imports": 6,
    "successful_imports": 5,
    "failed_imports": 1
  },
  "imported": [],
  "failed": [],
  "skipped": []
}
```

The exact structure can differ, but it should clearly show what happened during the import.

---

## Mock CRM Behavior

The provided `MockCrmClient` simulates a third-party CRM service.

It may return:

- Success
- Temporary failure
- Rate limit response
- Permanent failure

You may modify or extend the mock client if needed, but the final implementation should demonstrate how you handle these real-world cases.

---

## What We Are Evaluating

We are looking for practical backend judgment, not over-engineering.

We will review:

- Code quality
- Class structure
- Error handling
- Retry and rate limit handling
- Data normalization
- Duplicate merge logic
- Readability
- Simplicity
- Communication in the PR description
- Ability to follow instructions

Bonus points for:

- Unit tests
- Clear comments where useful
- Small reusable services
- Clean output report
- Good assumptions documented in the PR

---

## Submission Process

1. Fork this repository.
2. Create a new branch for your solution.
3. Complete the implementation.
4. Open a Pull Request.
5. Send us the PR link.

---

## Pull Request Description

Your PR description must include:

```md
## How to Run

Explain the commands needed to install dependencies and run the import.

## What Was Implemented

Briefly summarize your solution.

## Assumptions

List any assumptions you made.

## What I Would Improve With More Time

Briefly explain what you would improve if this were a production system.
```

Example:

```bash
composer install
php import.php
```

---

## Notes

Keep the solution simple. We are not expecting a production-ready application. We want to see how you think, how you structure backend code, and how you handle real-world integration problems.
