# Annual Project - E-learning SaaS Platform

## Project Overview

This project is an e-learning SaaS platform designed to help students learn through structured courses that combine video lessons, written content, and discussion spaces.

The goal is to build a complete web application that respects the annual project syllabus requirements while solving a clear business problem: making online learning easier to follow, more interactive, and more organized for students and teachers.

## Business Context

Many online learning resources are fragmented across different platforms. Students may have videos in one place, written notes somewhere else, and no simple way to ask questions or discuss the course content.

Our platform centralizes the learning experience by providing:

- Courses organized by subject or category.
- Video lessons for visual learning.
- Text lessons for detailed explanations and revision.
- Comments so students can ask questions and teachers can answer.
- Progress tracking so learners know what they have completed.

## Problem

Students need a simple and structured way to access learning content. Teachers need a platform where they can publish lessons, manage course content, and interact with learners.

Without a dedicated platform, learning becomes less efficient because content, questions, and progress are not centralized.

## Proposed Solution

The application will be a SaaS-style e-learning platform where teachers can create courses and students can follow them.

Each course can contain multiple lessons. A lesson may include a video, written content, or both. Students can comment on lessons to ask questions, share feedback, or discuss the topic.

## Main Actors

- Visitor: can view public information about the platform.
- Student: can register, log in, browse courses, follow lessons, post comments, and track progress.
- Teacher: can create and manage courses, lessons, videos, text content, and answer comments.
- Administrator: can manage users, courses, content moderation, and platform rules.

## Core Features

- User authentication and role management.
- Course catalog with categories and search.
- Course detail pages with description, teacher, lessons, and progression.
- Video lessons.
- Text-based lessons.
- Comments on lessons.
- Teacher replies to student comments.
- Student progress tracking.
- Admin moderation for users, courses, and comments.
- Responsive web interface for desktop and mobile.

## Comment System

The comment feature is important because the teacher said comments could be included in the project.

Rules for comments:

- Only authenticated users can comment.
- Comments are attached to a specific lesson.
- Students can ask questions or give feedback.
- Teachers can reply to comments on their courses.
- Administrators can delete inappropriate comments.
- Comments must respect basic moderation rules: no insults, spam, hate speech, or unrelated content.

## Business Rules

- A user must be authenticated to access private course content.
- A course must have at least one lesson before being published.
- A lesson can contain video content, text content, or both.
- A student can mark a lesson as completed.
- Progress is calculated from completed lessons.
- A teacher can only edit their own courses unless they are an administrator.
- An administrator can moderate all courses, users, and comments.
- Private data and secrets must never be exposed in the source code.

## Technical Requirements From The Syllabus

The project must respect the annual project constraints:

- Use Git for source code management.
- Use a branch workflow such as Gitflow or a similar structured workflow.
- Use an agile project management method such as Scrum or Kanban.
- Track tasks with a tool such as GitHub Projects, Trello, Jira, or equivalent.
- Create wireframes and mockups with Figma, Penpot, or another design tool.
- Design and justify a SQL database model.
- Build a responsive web interface.
- Do not use major frontend frameworks seen in class such as React, Vue, or Angular.
- Use an allowed or validated frontend approach such as templates, Livewire, Svelte, Solid, Qwik, or another approved solution.
- Build the backend business logic with a server/API.
- Avoid GraphQL, WebSocket, or similar technologies unless validated by the pedagogical director.
- Containerize the application with Docker.
- Provide Dockerfiles and a `compose.yml` file.
- Deploy the application online with a public domain name and SSL certificate.
- Apply basic security rules against CSRF, SQL injection, XSS, and secret exposure.

## Expected Technical Architecture

The final architecture may include:

- Frontend application or server-rendered templates.
- Backend server/API.
- SQL database.
- Docker containers for each service.
- Reverse proxy for production deployment.
- SSL certificate for HTTPS.

## Database Entities

The database model should include at least:

- Users.
- Roles.
- Courses.
- Lessons.
- Lesson videos.
- Lesson text content.
- Comments.
- Progress records.
- Categories.

## Security Rules

- Passwords must be hashed.
- Forms must be protected against CSRF.
- SQL queries must be protected against injection.
- User-generated content, including comments, must be protected against XSS.
- Environment variables must be used for secrets.
- Secrets must not be committed to Git.
- Admin actions must require proper authorization.

## Bonus Ideas

Possible bonus features from the syllabus:

- OAuth2 login with Google, GitHub, or another provider.
- TOTP or passkey authentication.
- Monitoring and logs with tools such as Prometheus, Grafana, Loki, Sentry, or GlitchTip.
- SEO optimization with semantic HTML, meta tags, sitemap, and server-side rendering if relevant.
- Automated deployment with Ansible, Terraform, Pulumi, or another infrastructure tool.
- High availability with Docker Swarm or Kubernetes.

## Deliverables

- Business specification document.
- Wireframes and mockups.
- SQL database model.
- Source code repository.
- Docker configuration.
- Deployed application with public URL and SSL.
- Final presentation and live demonstration.

## Presentation Goal

During the final presentation, the team should demonstrate:

- The business problem and proposed solution.
- The main user journeys.
- Course creation and lesson management.
- Student learning flow with video, text, comments, and progress tracking.
- The database model and technical choices.
- Dockerized environment and deployed production version.
- Security measures implemented in the application.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for the branch workflow, commit conventions, Definition of Done and sprint organisation. Tasks are tracked on the [project board](https://github.com/users/lucaszebre/projects/12).
