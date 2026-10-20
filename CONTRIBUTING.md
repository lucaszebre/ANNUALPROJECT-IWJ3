# Contributing

How the team (Lucas, Sorya, Yohan) works on this project. The syllabus grades project management (agile + Git branch workflow), so please follow these rules.

## Branches

We use a lightweight Gitflow.

| Branch | Role | Created from | Merged into |
|---|---|---|---|
| `main` | Production. What is deployed. | – | – |
| `develop` | Integration. Default branch, always runnable. | `main` | `main` (release) |
| `feature/<issue>-<slug>` | New feature or task | `develop` | `develop` |
| `fix/<issue>-<slug>` | Bug fix on `develop` | `develop` | `develop` |
| `hotfix/<issue>-<slug>` | Urgent fix in production | `main` | `main` **and** `develop` |

Examples: `feature/20-job-queue`, `fix/57-hls-safari`, `hotfix/63-login-500`.

`main` and `develop` are protected: no direct push, every change goes through a pull request approved by another team member.

## Workflow for a task

1. Pick an issue from the **Todo** column of the [board](https://github.com/users/lucaszebre/projects/12), assign yourself if needed and move it to **In Progress**.
2. Create your branch from an up-to-date `develop`:
   ```sh
   git switch develop && git pull
   git switch -c feature/<issue>-<slug>
   ```
3. Commit often using [Conventional Commits](#commit-messages).
4. Push and open a pull request into `develop`. Fill in the template and write `Closes #<issue>`. Move the card to **In Review**.
5. Another member reviews. Address comments, then the author merges with **Squash and merge**. The branch is deleted automatically and the issue closes.
6. Keep your branch up to date with `git pull --rebase origin develop` rather than merging `develop` into it.

## Releases

At the end of each sprint (and for the MVP and V1 milestones), open a pull request `develop` → `main` titled `release: vX.Y.Z`, merge it with a **merge commit**, then tag it:

```sh
git switch main && git pull
git tag -a v0.1.0 -m "Sprint 1" && git push origin v0.1.0
```

Versions: `v0.x` during development, `v1.0.0` for the final delivery.

## Commit messages

Format: `<type>(<optional scope>): <short summary in the imperative>`

| Type | Use for |
|---|---|
| `feat` | A new feature |
| `fix` | A bug fix |
| `docs` | Documentation only |
| `style` | Formatting, no code change |
| `refactor` | Code change that neither fixes a bug nor adds a feature |
| `test` | Adding or fixing tests |
| `chore` | Tooling, dependencies, config |
| `ci` | CI pipeline |
| `build` | Docker, build system |

Scopes follow the app areas: `api`, `worker`, `web`, `db`, `sandbox`, `infra`, `docs`.

Examples:
```
feat(worker): add ffmpeg HLS transcoding job
fix(api): reject prerequisite edges that create a cycle
docs: add data model justification
```

## Definition of Done

An issue can be moved to **Done** only when:

- [ ] The acceptance criteria in the issue are met.
- [ ] The code is merged into `develop` through an approved pull request.
- [ ] CI is green (lint, tests, build).
- [ ] Business rules touched by the change have tests.
- [ ] No secret, credential or `.env` file is committed.
- [ ] User input is validated server-side; queries are parameterised; output is escaped.
- [ ] UI changes are responsive (mobile and desktop) and match the mockups.
- [ ] Documentation (README, `docs/`) is updated if needed.

## Agile organisation

We use **Scrum-like one-week sprints** tracked on the GitHub Project board.

- **Sprint planning** (Monday, ~20 min): move the issues for the week from **Backlog** to **Todo**, check everyone has work in frontend, backend and infra over time.
- **Async stand-up** (daily, team chat): what I did / what I'll do / what blocks me.
- **Review + retro** (Sunday, ~30 min): demo what was merged, release `develop` → `main` if it's stable, note one thing to improve.

Milestones:

| Milestone | Due |
|---|---|
| M0 - Scoping & design | 01/11/2026 |
| MVP | 29/11/2026 |
| V1 - Final delivery | 13/12/2026 |

### Board columns

| Column | Meaning |
|---|---|
| **Backlog** | Not planned for the current sprint |
| **Todo** | Planned for the current sprint |
| **In Progress** | Someone is working on it (branch exists) |
| **In Review** | Pull request open, waiting for review |
| **Done** | Merged into `develop` and meets the Definition of Done |

### Labels

- Area: `frontend`, `backend`, `infra`, `database`, `design`, `security`, `testing`, `docs`, `project-management`
- Technical axis from the project brief: `axe-A` to `axe-F`
- Priority: `P0` (core / blocking), `P1` (important), `P2` (nice to have)
- `bonus` for the syllabus bonus points
