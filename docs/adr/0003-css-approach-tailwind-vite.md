# ADR-0003: CSS approach: Tailwind CSS via Vite

- **Status:** Proposed
- **Date:** 2026-10-21
- **Deciders:** Lucas, Pauline and Yohan

## Context
We must faithfully integrate the Figma/Penpot mockups and ship a responsive UI. The mockups define design tokens (colors, typography, spacing, radii). Laravel ships with Vite by default, so a build step already exists.

## Options considered
1. **Tailwind CSS via Vite**, with the design tokens mapped to the Tailwind theme.
2. **Plain CSS** written from the design tokens (CSS custom properties).
3. **Bootstrap or another component CSS framework.**

## Decision
We choose **option 1**. Design tokens become the single source of truth in the Tailwind theme (CSS variables), responsive breakpoints are built in, unused CSS is purged at build time, and Tailwind is the de facto convention in the Laravel/Livewire ecosystem (examples and docs match our stack). Reusable UI parts are extracted as Blade components rather than `@apply` abuse.

## Consequences
- **Positive:** fast and consistent styling, small production CSS, easy responsive work, tokens enforced across the team.
- **Negative / trade-offs:** verbose class lists in templates; needs a Node/Vite build stage in the Docker image; the team must learn the utility-first approach.
- **Impact:** the Dockerfile includes an asset build stage; Prettier plugin for class sorting recommended.
