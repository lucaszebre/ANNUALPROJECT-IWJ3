# ADR-0004: Authentication scaffolding: Fortify + our own Blade views

- **Status:** Proposed
- **Date:** 2026-10-21
- **Deciders:** Lucas, Pauline and Yohan

## Context
We need registration, login, password reset, email verification and, as a possible bonus, advanced authentication (TOTP, Passkey, OAuth 2.0/OIDC). The login/registration screens are part of the mockups and their integration is graded. Security (CSRF, XSS, session handling) is also graded.

## Options considered
1. **Official Livewire starter kit.** Fastest start, but it comes with its own UI layer and conventions (Flux UI components, predefined layouts and styling) that we would need to override.
2. **Laravel Fortify (headless) + our own Blade/Livewire views.** Back-end auth logic and routes provided and maintained, views fully ours.
3. **Handwritten authentication.** Full control, highest risk of security mistakes.

## Decision
We choose **option 2**. Fortify provides the vetted back-end flows (registration, reset, verification, two-factor TOTP) while the views are built from our own mockups and Tailwind tokens, avoiding a fight against a starter kit's design. It also leaves a clean path to the bonus (Socialite for OAuth, 2FA already in Fortify).

## Consequences
- **Positive:** pixel-fidelity to the mockups, no unwanted dependency on a UI kit, understanding of every layer (useful for the defense), maintained security code.
- **Negative / trade-offs:** more initial work than the starter kit (views and Livewire forms to write); we must wire Fortify actions ourselves.
- **Impact:** auth views in `resources/views/auth`, Fortify actions in `app/Actions/Fortify`; feature tests for each flow (see ADR-0009). If time becomes critical, the starter kit remains a fallback to be recorded in a superseding ADR.
