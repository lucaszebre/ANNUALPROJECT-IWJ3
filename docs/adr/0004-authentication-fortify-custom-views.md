# ADR-0004: Authentication scaffolding: Fortify + our own Blade views

- **Status:** Proposed
- **Date:** 2026-10-21
- **Deciders:** Lucas, Pauline and Yohan

## Context
We need registration, login, password reset, email verification and, as a possible bonus, advanced authentication (TOTP, Passkey, OAuth 2.0/OIDC). The login/registration screens are part of the mockups and their integration is graded. Security (CSRF, XSS, session handling) is also graded.

## Options considered

### A. Authentication scaffolding
1. **Official Livewire starter kit.** Fastest start, but it comes with its own UI layer and conventions (Flux UI components, predefined layouts and styling) that we would need to override.
2. **Laravel Fortify (headless) + our own views.** Back-end auth logic and routes provided and maintained, views fully ours.
3. **Hand-written authentication.** Full control, highest risk of security mistakes.

### B. How the auth forms are built
1. **Plain Blade forms posting to the Fortify routes.** Standard `<form method="POST" action="{{ route('login') }}">` with `@csrf`.
2. **Livewire components for the auth forms.** Inline validation and no page reload, but they bypass Fortify's handling (throttling, redirects).

## Decision

**A. We choose Fortify + our own views.** Fortify provides the vetted back-end flows (registration, reset, verification, two-factor TOTP) while the views are built from our own mockups and Tailwind tokens, avoiding a fight against a starter kit's design. It also leaves a clean path to the bonus (Socialite for OAuth, 2FA already in Fortify).

**B. We choose plain Blade forms that post to Fortify.** The login, registration, password-reset, email-verification and two-factor forms are Blade views, not Livewire components, so Fortify's throttling, validation and redirects stay in force. Livewire and Alpine.js remain available for the rest of the interface, and Alpine can still add small enhancements to the plain forms (loading state, show/hide password).

## Consequences
- **Positive:**
    - Pixel fidelity to the mockups and no unwanted dependency on a UI kit.
    - An understanding of every layer, which is useful for the defense.
    - Fortify's security behaviour (rate limiting, validation, redirects) is kept as is, with fewer custom pieces to test.
- **Negative / trade-offs:**
    - More initial work than the starter kit (views to write, Fortify actions to wire ourselves).
    - No Livewire-style inline validation on these forms: server-side errors are displayed after the post.
- **Impact:**
    - Auth views live in `resources/views/auth` and Fortify actions in `app/Actions/Fortify`.
    - Feature tests cover each flow, including login throttling (see ADR-0009).
    - If time becomes critical, the starter kit remains a fallback to be recorded in a superseding ADR.
    - If a Livewire auth form is ever needed, throttling and validation must be reimplemented and tested explicitly (new ADR).