---
name: auth-service
description: Work on this Laravel authentication service using the pragmatic architecture and canonical low-level design in README.md. Use for every implementation, review, diagnosis, configuration, testing, or documentation task in this repository.
---

# Auth service architecture and workflow

Use this document for work in this authentication service. Explicit user instructions take precedence.

## Mandatory LLD preflight

- At the start of every request, read `README.md` before planning, answering, or editing. It is the canonical LLD for service ownership, API contracts, security, persistence, Redis, Kafka, and testing.
- Re-read `README.md` whenever it may have changed. Do not rely on a remembered copy.
- Treat the LLD as intended design, not proof of implementation. Inspect current code, configuration, migrations, routes, and tests before describing behavior.
- Keep work consistent with the LLD. If an explicit request conflicts with it, follow the request and identify the conflict and documentation update.
- Keep detailed design in `README.md`; update this skill only when workflow or architectural rules change.

## Project context

- Framework: Laravel 12 on PHP 8.2 or later; Docker currently runs PHP 8.3.
- Namespace: `App\`, mapped to `app/` through Composer.
- Use Laravel and Eloquent directly unless a demonstrated need justifies another abstraction.
- Implement only requested features. Do not add empty scaffolds for possible future functionality.

### Service boundary

- This service owns identity authentication, passwords, access and refresh tokens, email verification, 2FA, identity-level roles and permissions, login protection, authentication audits, and authentication events.
- It does not own customer profiles or addresses, product or order data, payments, or business-specific preferences.

## Pragmatic architecture

```text
Route
  -> Middleware / FormRequest
  -> Controller
  -> Application Action
  -> Eloquent Models / focused integration services
```

```text
app/
|-- Application/Auth/
|   |-- Actions/       One class per implemented use case
|   |-- DTOs/          Typed action input/output when useful
|   `-- Exceptions/    Use-case failures independent of HTTP rendering
|-- Enums/             Shared application states
|-- Http/
|   |-- Controllers/Api/V1/Auth/
|   |-- Middleware/
|   |-- Requests/Auth/
|   `-- Resources/
|-- Models/            Eloquent persistence models
`-- Services/          JWT, Redis, Kafka, mail, or OAuth adapters when implemented
```

### Responsibilities

| Location | Responsibility |
| --- | --- |
| Routes and middleware | Expose endpoints, identify requests, throttle, and enforce authentication/authorization. |
| Form Requests | Normalize and validate HTTP input. |
| Controllers | Convert validated requests to action input and format resources/responses. |
| Application actions | Coordinate one use case, transactions, models, and focused services. |
| DTOs | Carry typed values when they make an action boundary clearer. |
| Eloquent models | Represent persisted state and relationships. |
| Services | Encapsulate substantial external or security-sensitive integrations. |

### Complexity rules

- Do not create a separate Domain entity for an Eloquent-backed record unless it contains substantial framework-independent business behavior that cannot live cleanly in an action, model, enum, or policy.
- Do not add repository interfaces around Eloquent by default. Add an interface only for multiple real implementations, a meaningful external boundary, or material testing benefit.
- Do not wrap Laravel hashing or database transactions in one-method interfaces. Use Laravel facilities directly inside actions.
- Keep controllers small and keep multi-step writes atomic with `DB::transaction()`.
- Create focused service classes for JWT, Kafka, Redis, mail, and external identity providers when those integrations are implemented.

### Security and integration invariants

- Never expose account existence during login or password-reset flows. Never log passwords, OTPs, access tokens, refresh tokens, or private keys.
- Keep access tokens short-lived and asymmetrically signed; validate issuer, audience, expiry, not-before, and token ID.
- Hash and rotate refresh tokens, detect reuse, and revoke the affected family.
- Keep durable refresh-token state in the database; use Redis for fast revocation, throttling, and temporary challenges.
- Record integration events in the transactional outbox alongside the state change. Consumers must be idempotent.
- Preserve the documented response envelope and request/correlation IDs.

## Development workflow

1. Read `README.md`, then inspect relevant behavior, routes, configuration, migrations, models, and tests.
2. Identify the use case, input, output, failures, security impact, and owning service.
3. Implement the smallest coherent vertical slice. Prefer Form Request, controller, action, Eloquent model, and resource.
4. Add middleware or integration services only when the use case requires them.
5. Add focused success and failure tests; run formatting and the relevant suite.
6. Report implemented behavior, verification, and intentionally deferred integrations.

### Local checks

Run from the repository root. If `php` is unavailable, use `C:\xampp\php\php.exe`.

```powershell
php -l path/to/changed-file.php
php vendor/bin/pint --test path/to/changed-file.php
php artisan test --filter=RelevantTest
```

Do not include generated `bootstrap/cache/*.php` files in Pint paths.

## Agreed feature workflows

| Feature | Agreed workflow |
| --- | --- |
| Registration | Accept normalized email and confirmed password, create a `pending` Eloquent user without profile fields, and record `auth.user.registered.v1` in the same transaction. Verification delivery is separate. |
| Login | Return generic invalid credentials; throttle by identity and IP; enforce status and 2FA before issuing tokens. |
| Logout | Support current-session and all-session revocation separately. |
| Token refresh | Hash stored tokens, rotate every use, detect reuse, and revoke compromised families. |
| Email verification | Rate-limit resends; token format and expiry require an explicit implementation decision. |
| Password reset | Do not reveal account existence; rate-limit requests; decide expiry and session invalidation when implemented. |
| Two-factor authentication | Encrypt secrets, hash recovery codes, rate-limit challenges, and require reauthentication for sensitive changes. |
| SSO | Put provider-specific behavior in a focused service; decide account-linking conflicts before implementation. |
| Events | Use versioned `auth.*.v1` events, correlation metadata, transactional outbox writes, retries, and idempotent consumers. |

## Architecture decisions

| Date | Decision | Reason |
| --- | --- | --- |
| 2026-09-13 | Use a pragmatic Laravel action architecture without a separate Domain or repository layer by default. | Preserve useful separation while avoiding abstractions that duplicate Eloquent and Laravel facilities. |
