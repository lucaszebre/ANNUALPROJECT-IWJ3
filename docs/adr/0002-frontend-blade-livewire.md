# ADR-0002: Frontend with Blade + Livewire (Alpine.js included)

- **Status:** Proposed
- **Date:** 2026-10-21
- **Deciders:** Lucas, Pauline and Yohan

## Context
The syllabus forbids React, Vue.js and Angular and suggests server-side templates (PHP, Blade for Laravel) with or without Livewire, or lesser-known frameworks (Svelte, Solid, Qwik) subject to validation. The UI must be responsive, interact with the back-end, and its fidelity to the mockups is graded. The team's back-end language is PHP.

## Options considered
1. **Blade + Inertia + Svelte/Solid.** Modern SPA feel, but requires validation and a JS build/runtime to master.
2. **Blade + Livewire (+ Alpine.js, bundled with Livewire).** Server-rendered, reactive components written in PHP.
3. **Plain Blade + handwritten JS.** Minimal, but costly for interactive features.

## Decision
We choose **option 2**. It is explicitly allowed by the syllabus, needs no validation, keeps a single language and codebase for all team members (which supports the requirement that everyone contributes to every technical phase), and Alpine.js covers purely client-side interactions (dropdowns, modals) without extra setup.

## Consequences
- **Positive:** one stack to learn, fewer containers, built-in CSRF protection and XSS escaping in Blade, fast iteration.
- **Negative / trade-offs:** each Livewire interaction is a server round-trip (needs care with performance and N+1 queries); less exposure to the modern JS ecosystem; front and back are coupled in one application.
- **Impact:** a REST API, if needed, is exposed via Laravel routes in the same application (to be covered by a dedicated ADR if we expose one). Frontend components live in `app/Livewire` and `resources/views`.
