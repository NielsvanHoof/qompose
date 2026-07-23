# Qompose Platform — Domain Glossary

Terms used across backend modules, queries, and the client portal.

## Core entities

- **Tenant / workspace** — an accounting or advisory firm; all staff data is scoped per tenant.
- **Dossier** — a case for one client (`draft → awaiting_client → in_review → completed`).
- **Document request** — one questionnaire item on a dossier. Typed: `file`, `text`, or `boolean`. Status: `pending → submitted → accepted | rejected`.
- **Client portal** — restricted session where clients answer document requests without a staff account.

## Workflow concepts

- **SubmissionContext** — who is submitting a document request answer or upload (`portal` or `staff`). Portal clients may submit only when an item is `pending` or `rejected` and the dossier is not completed. Staff may also replace items already in `submitted` status (e.g. file re-upload). Rules live in `DocumentRequestTransitions`; HTTP adapters and portal UI delegate to `canSubmit()`.
- **TenantContext** — single async/console entry point for running code under a tenant (`runForTenant`). HTTP requests set the tenant via middleware (`InitializeTenantFromSession`, `InitializeTenantFromRoute`, `ResolveClientPortalGrant`) before route model binding.
- **OcrOrchestrator** — wraps OCR driver `start()` and records whether completion is immediate (mock) or deferred (Textract via SQS). `ProcessUploadedDocumentJob` delegates to this module instead of inspecting adapter-specific side effects.
