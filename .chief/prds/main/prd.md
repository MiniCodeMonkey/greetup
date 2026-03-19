# PRD: Greetup — Self-Hosted Community Events Platform

## Introduction

Greetup is an open source, self-hostable community events platform — a free alternative to Meetup.com built with Laravel. It provides group management, event scheduling, RSVPs with waitlists, attendee check-in, discussions, real-time event chat, direct messaging, and discovery — all without paywalls or feature gating.

This PRD covers the full v1 implementation, organized foundation-first: design system and Blade components are built first, then layout scaffolding and authentication, then core features (groups, events, RSVPs), then social features (discussions, messaging, chat), then discovery/search, and finally the admin panel.

The application spec lives at `greetup-spec.md` in the project root and is the authoritative source for all design tokens, database schemas, route definitions, and feature behavior.

## Goals

- Ship a complete, production-ready community events platform matching the full spec
- Build a reusable Blade component library following the Greetup design system before feature work begins
- Support self-hosting with Laravel Sail for local dev and flexible production deployment
- Achieve >= 90% overall test line coverage; >= 95% on models/services/policies; 100% route, authorization, and validation coverage
- All tests run against MySQL (not SQLite) to match production
- All location features powered by Geocodio with graceful degradation when API key is absent
- Real-time event chat and notifications via Laravel Reverb
- Full-text search via Laravel Scout with Meilisearch (database driver fallback)

## User Stories

### Phase 0: Development Environment Setup

#### US-116: Laravel Sail & Docker Configuration
**Priority:** 0.1
**Description:** As a developer, I need a working Laravel Sail / Docker environment so that all services (MySQL, Redis, Meilisearch, Mailpit, Reverb) are available for local development.

**Acceptance Criteria:**
- [ ] `docker-compose.yml` configured with all required services per spec section 2.4: MySQL 8.0, Redis, Meilisearch, Mailpit, Laravel Reverb, and a queue worker
- [ ] Laravel Sail installed and configured as a dev dependency (`laravel/sail`)
- [ ] `.env` file configured with correct service connection details: `DB_CONNECTION=mysql`, `DB_HOST=mysql`, `DB_PORT=3306`, `DB_DATABASE=greetup`, `DB_USERNAME=sail`, `DB_PASSWORD=password`, `REDIS_HOST=redis`, `SCOUT_DRIVER=meilisearch`, `MEILISEARCH_HOST=http://meilisearch:7700`, `MAIL_MAILER=smtp`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025`, `BROADCAST_CONNECTION=reverb`, `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`
- [ ] `./vendor/bin/sail up -d` starts all containers without errors
- [ ] `./vendor/bin/sail artisan migrate` runs successfully against the MySQL container
- [ ] `./vendor/bin/sail artisan db:show` confirms connection to the MySQL database
- [ ] Reverb configuration in `.env`: `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST=localhost`, `REVERB_PORT=8080`
- [ ] A `sail` shell alias is documented (e.g., `alias sail='./vendor/bin/sail'`) for convenience

#### US-117: Install & Configure Required PHP Packages
**Priority:** 0.2
**Description:** As a developer, I need all required Composer packages installed so that the application has its full dependency tree ready before feature work begins.

**Acceptance Criteria:**
- [ ] All required packages installed via Composer: `spatie/laravel-permission`, `spatie/laravel-sluggable`, `spatie/laravel-medialibrary`, `spatie/laravel-tags`, `league/commonmark`, `geocodio/geocodio-library-php`, `intervention/image`, `laravel/reverb`, `laravel/scout`, `laravel/dusk` (dev), `pestphp/pest` (dev), `larastan/larastan` (dev)
- [ ] Each package's service provider is registered (or auto-discovered) and any required config files are published (e.g., `php artisan vendor:publish` for medialibrary, permission, tags, scout, reverb)
- [ ] `composer install` completes without errors
- [ ] `php artisan` runs without errors after package installation

#### US-118: Install & Configure Frontend Dependencies
**Priority:** 0.3
**Description:** As a developer, I need the frontend toolchain (Node.js, Tailwind CSS 4, Vite) working so that CSS and JS assets can be compiled.

**Acceptance Criteria:**
- [ ] `package.json` includes Tailwind CSS v4, Vite, and any required Vite plugins
- [ ] `npm install` completes without errors
- [ ] `npm run build` compiles assets without errors
- [ ] `npm run dev` starts the Vite dev server and hot-reloads on file changes
- [ ] Tailwind CSS is processing `resources/css/app.css` (verified by adding a utility class and confirming it appears in compiled output)

#### US-119: Verify Full Development Environment
**Priority:** 0.4
**Description:** As a developer, I need to verify that the entire local dev environment works end-to-end so that subsequent feature work has a stable foundation.

**Acceptance Criteria:**
- [ ] `./vendor/bin/sail up -d` starts all services and they reach a healthy state
- [ ] `./vendor/bin/sail artisan migrate` runs all existing migrations without errors
- [ ] `./vendor/bin/sail artisan test` runs the default Pest test suite (at minimum the example test passes)
- [ ] MySQL is accessible from the app container (`sail artisan tinker --execute "DB::connection()->getPdo()"` returns without error)
- [ ] Redis is accessible (`sail artisan tinker --execute "Illuminate\Support\Facades\Redis::ping()"` returns PONG)
- [ ] Meilisearch is accessible (`sail artisan tinker --execute "Http::get('http://meilisearch:7700/health')->json()"` returns status available)
- [ ] Mailpit web UI is accessible at `http://localhost:8025`
- [ ] `npm run build` produces compiled assets and a page loads without Vite manifest errors
- [ ] `sail artisan reverb:start` starts the WebSocket server without errors
- [ ] `sail artisan queue:work --once` processes a queued job without errors (dispatch a test job to verify)
- [ ] `composer run dev` (or equivalent) starts the full dev stack (app server + Vite + queue worker + Reverb) if configured

### Phase 1: Design System & Blade Components

#### US-001: Tailwind 4 Theme Configuration
**Priority:** 1
**Description:** As a developer, I need the Tailwind 4 theme configured with all Greetup design tokens so that all components use consistent colors, spacing, typography, and radii.

**Acceptance Criteria:**
- [ ] `resources/css/app.css` imports Tailwind and defines the `@theme` block with all tokens exactly as specified in spec section 1A.7: green (50/100/200/400/500/700/900), coral (50/200/500/900), violet (50/200/500/900), gold (50/200/500/900), neutral (50/100/200/400/500/700/900), red (50/500/900), blue (50/500/900), font families (Instrument Sans, JetBrains Mono), radius tokens (sm=4px, md=8px, lg=12px, xl=16px, pill=100px)
- [ ] Google Fonts link for Instrument Sans (weights 400, 500) added to the app layout
- [ ] `npm run build` completes without errors
- [ ] Tailwind utilities like `bg-green-500`, `text-coral-900`, `rounded-xl` resolve to the correct hex values (e.g., green-500 = #1FAF63, coral-500 = #FF6B4A)

#### US-002: Decorative Blob Component
**Priority:** 2
**Description:** As a developer, I need a reusable `<x-blob>` Blade component so that decorative cloud/circle shapes can be placed on hero sections, card headers, and empty states throughout the UI.

**Acceptance Criteria:**
- [ ] Component at `resources/views/components/blob.blade.php` accepts `color`, `size`, `opacity`, and `shape` props
- [ ] Default shape is `cloud` using the exact SVG path from spec section 1A.8 (viewBox `0 0 80 80`); `circle` renders a `<circle cx="40" cy="40" r="38">` element
- [ ] SVG has `aria-hidden="true"`, `pointer-events-none`, and the `absolute` class
- [ ] Additional CSS classes can be passed via attributes (e.g., `class="-top-10 -right-8"`)
- [ ] Component test (`tests/Component/BlobTest.php`): asserts cloud SVG path rendered with correct color/size/opacity, circle shape renders `<circle>`, custom classes applied, `aria-hidden="true"` present

#### US-003: Avatar Component
**Priority:** 3
**Description:** As a developer, I need an `<x-avatar>` component that renders a user's initials on a deterministic colored circle so that users are visually identifiable throughout the app.

**Acceptance Criteria:**
- [ ] Component accepts a `user` (model or object with `id` and `name`) and `size` prop (sm=24px, md=32px, lg=44px, xl=96px; default md)
- [ ] Renders first letter of first name + first letter of last name as white text (dark text for gold background)
- [ ] Background color cycles deterministically: `id % 4` maps to 0=green-500, 1=coral-500, 2=violet-500, 3=gold-500
- [ ] Falls back to single initial when user has one name
- [ ] Fully rounded (radius-pill)
- [ ] Component test (`tests/Component/AvatarTest.php`): asserts all 4 size variants render correct pixel dimensions, color cycling for user IDs 0-3, single-name fallback, gold background uses dark text

#### US-004: Avatar Stack Component
**Priority:** 4
**Description:** As a developer, I need an `<x-avatar-stack>` component that renders overlapping avatars with a "+N" overflow indicator for attendee previews on event cards and group pages.

**Acceptance Criteria:**
- [ ] Component accepts `users` (collection), `max` (default 5), and `size` (default sm=22px)
- [ ] Renders up to `max` avatars with -6px to -8px negative left margin overlap and 2px white border ring
- [ ] When `users.count > max`, shows a "+N" badge with neutral-100 background and neutral-500 text
- [ ] Empty users collection renders nothing
- [ ] Component test (`tests/Component/AvatarStackTest.php`): asserts overlap margins, "+N" badge calculation, white border ring, empty state

#### US-005: Status Badge Component
**Priority:** 5
**Description:** As a developer, I need an `<x-badge>` component for event type badges, RSVP status badges, and capacity indicators with correct color mapping.

**Acceptance Criteria:**
- [ ] Component accepts `type` prop: `in_person`, `online`, `hybrid`, `going`, `waitlisted`, `cancelled`, `almost_full`
- [ ] Each type maps to exact color pairs: in_person=coral-50/coral-900, online=violet-50/violet-900, hybrid=green-50/green-700, going=green-50/green-700, waitlisted=gold-50/gold-900, cancelled=red-50/red-900, almost_full=gold-50/gold-900
- [ ] Border-radius is radius-sm (4px)
- [ ] Accepts optional `label` prop; defaults to humanized type name (e.g., "In person", "Almost full")
- [ ] Component test (`tests/Component/BadgeTest.php`): asserts correct bg/text color for all 7 types, radius-sm applied, custom label override

#### US-006: Interest Pill Component
**Priority:** 6
**Description:** As a developer, I need an `<x-pill>` component for rendering interest/topic tags with deterministic cycling colors.

**Acceptance Criteria:**
- [ ] Component accepts a `tag` (object with `id` and `name`) or standalone `name` and `id` props
- [ ] Background cycles deterministically: `id % 4` maps to 0=green-50, 1=coral-50, 2=violet-50, 3=gold-50
- [ ] Text color uses matching ramp: 0=green-700, 1=coral-900, 2=violet-900, 3=gold-900
- [ ] Fully rounded (radius-pill), padding 4-6px vertical, 10-14px horizontal, font 12-13px weight 500
- [ ] Component test (`tests/Component/PillTest.php`): asserts color cycling for IDs 0-3, correct text/bg pairing, pill radius

#### US-007: Date Block Component
**Priority:** 7
**Description:** As a developer, I need an `<x-date-block>` component for event lists that shows the month and day in the event type's accent color.

**Acceptance Criteria:**
- [ ] Component accepts `date` (Carbon instance) and `event_type` (in_person, online, hybrid)
- [ ] Renders 56px wide block with accent tint background (coral-50 for in_person, violet-50 for online/hybrid)
- [ ] Month abbreviation: 11px, uppercase, accent-500 text (coral-500 or violet-500); Day number: 24px, weight 500, accent-900 text
- [ ] Border-radius is radius-lg (12px), padding 8px
- [ ] Component test (`tests/Component/DateBlockTest.php`): asserts correct accent tint per event type, uppercase month, correct text colors

#### US-008: Stat Card Component
**Priority:** 8
**Description:** As a developer, I need an `<x-stat-card>` component for the homepage hero section showing platform statistics.

**Acceptance Criteria:**
- [ ] Component accepts `value` (number), `label` (string), and `color` (coral, violet, gold)
- [ ] Renders solid bold accent background (coral-500, violet-500, gold-500) with white text (dark text for gold)
- [ ] Number: 28px, weight 500, line-height 1; Label: 11px, 80% opacity
- [ ] Border-radius radius-xl (16px), padding 14px
- [ ] Component test (`tests/Component/StatCardTest.php`): asserts correct bg color for each variant, gold uses dark text, value/label sizing

#### US-009: Attendance Progress Bar Component
**Priority:** 9
**Description:** As a developer, I need an `<x-progress-bar>` component for event sidebars showing RSVP capacity.

**Acceptance Criteria:**
- [ ] Component accepts `current` (int), `max` (int), and optional `label` (string)
- [ ] Track: 4-6px height, neutral-100 background, fully rounded
- [ ] Fill: green-500, width proportional to current/max, fully rounded
- [ ] Text above: large number (20-28px, weight 500) + "/ N" (14px, neutral-500)
- [ ] "X spots remaining" text below: coral-500 if < 25% remaining, neutral-500 otherwise
- [ ] Handles null/unlimited max gracefully (no bar shown, just count)
- [ ] Component test (`tests/Component/ProgressBarTest.php`): asserts fill width proportional, 0% when current=0, 100% when current>=max, coral text when <25% remaining

#### US-010: Tab Bar Component
**Priority:** 10
**Description:** As a developer, I need an `<x-tab-bar>` component for the horizontal tab navigation used on group pages and event pages.

**Acceptance Criteria:**
- [ ] Component accepts `tabs` (array of `['label' => string, 'href' => string, 'active' => bool]`)
- [ ] Tab text: 13px, neutral-500 default; active tab: green-500 text, weight 500, 2px bottom border in green-500
- [ ] Tabs separated by 16px gap; bottom border of row: 0.5px solid neutral-200
- [ ] Horizontally scrollable on mobile (overflow-x-auto, no scrollbar visible)
- [ ] Component test (`tests/Component/TabBarTest.php`): asserts all tabs rendered, active tab has green-500 text + 2px border, inactive tabs have neutral-500 text

#### US-011: Event Card Component (Grid Layout)
**Priority:** 11
**Description:** As a developer, I need an `<x-event-card>` component for the explore page grid showing events with colored header bands and attendance info.

**Acceptance Criteria:**
- [ ] Component accepts an `event` model (with group, rsvps relationships)
- [ ] Header: 72-110px tall, dark accent color background (green-900 for hybrid, coral-900 for in_person, violet-900 for online), decorative blob at 0.1-0.2 opacity, event type pill in bottom-left with `background: rgba(255,255,255,0.15)` and white text
- [ ] Optional "Almost full" badge in gold on header top-right when >= 75% capacity filled
- [ ] Body: date (accent color, 11px, uppercase, weight 500), title (15px, weight 500), group name (13px, neutral-500), attendance row (avatar stack + "X going" left, spots remaining right)
- [ ] "X left" shown in coral when spots are limited
- [ ] Border-radius radius-xl, border 0.5px solid neutral-200
- [ ] Links to the event page URL
- [ ] Component test (`tests/Component/EventCardTest.php`): asserts correct header color per event type, "Almost full" badge at 75%+, avatar stack with going count, decorative blob rendered, link to event page

#### US-012: Event Row Component (List Layout)
**Priority:** 12
**Description:** As a developer, I need an `<x-event-row>` component for group page event lists with a date block, content, and RSVP button.

**Acceptance Criteria:**
- [ ] Component accepts an `event` model and optional `show_rsvp` boolean
- [ ] Renders date block (via `<x-date-block>`), title (15px, weight 500), meta line (day · time · venue), badges row (event type + attendance count + optional "Almost full")
- [ ] RSVP button right-aligned: secondary variant by default, primary when plenty of spots available
- [ ] Responsive: on mobile (<768px), RSVP button moves below content (full-width)
- [ ] Component test (`tests/Component/EventRowTest.php`): asserts date block rendered, secondary RSVP near capacity / primary otherwise, correct badges

#### US-013: Empty State Component
**Priority:** 13
**Description:** As a developer, I need a generic `<x-empty-state>` component with a decorative blob for when lists have no content (no events, no members, no discussions).

**Acceptance Criteria:**
- [ ] Component accepts `title`, `description`, and optional `action` slot (for a CTA button)
- [ ] Renders centered layout with a low-opacity decorative blob behind the text
- [ ] Component test (`tests/Component/EmptyStateTest.php`): asserts title/description rendered, blob present, optional action slot renders when provided

#### US-014: SEO Meta Component
**Priority:** 14
**Description:** As a developer, I need an `<x-seo>` Blade component that renders all meta tags (title, description, Open Graph, Twitter Card, canonical URL, JSON-LD) in the `<head>` section.

**Acceptance Criteria:**
- [ ] Component accepts `title`, `description`, `image`, `type` (default "website"), `canonicalUrl`, and optional `jsonLd` (array) props
- [ ] Renders `<title>` tag, `<meta name="description">`, `<link rel="canonical">`
- [ ] Renders Open Graph tags: `og:type`, `og:title`, `og:description`, `og:image`, `og:url`, `og:site_name` (from `site_name` platform setting)
- [ ] Renders Twitter Card tags: `twitter:card` (summary_large_image), `twitter:title`, `twitter:description`, `twitter:image`
- [ ] When `jsonLd` is provided, renders `<script type="application/ld+json">` block
- [ ] OG image fallback chain: page-specific image > entity cover photo > default OG image at `public/images/og-default.png`
- [ ] Component test verifies all meta tags rendered with correct attributes

### Phase 2: Layout Scaffolding & Authentication

#### US-015: Application Layout (Guest)
**Priority:** 15
**Description:** As a guest visitor, I need a clean app layout with navigation so I can browse the platform and find the login/register links.

**Acceptance Criteria:**
- [ ] Guest layout at `resources/views/components/layouts/app.blade.php` (or equivalent)
- [ ] Navbar: Greetup logo (left), nav links (Explore, Groups), login/register buttons (right)
- [ ] Logo loaded from `resources/images/greetup.png`
- [ ] Page background: neutral-50; font: Instrument Sans
- [ ] Mobile (<768px): hamburger menu toggle for nav links
- [ ] Footer with minimal branding
- [ ] `<x-seo>` component included in `<head>` with configurable title/description per page

#### US-016: Application Layout (Authenticated)
**Priority:** 16
**Description:** As a logged-in user, I need the app layout to show my avatar, notification bell, and account menu instead of login/register.

**Acceptance Criteria:**
- [ ] Navbar right side shows: notification bell (with unread count badge), user avatar dropdown
- [ ] Dropdown menu: Dashboard, My Groups, Messages, Settings, Logout
- [ ] Notification bell shows dropdown with 10 most recent notifications, "load more" button
- [ ] Mobile: hamburger menu includes all navigation and account links

#### US-017: User Registration
**Priority:** 17
**Description:** As a visitor, I want to create an account so that I can join groups and RSVP to events.

**Acceptance Criteria:**
- [ ] Registration form at `/register` with fields: name, email, password, password confirmation
- [ ] Validation: name required (max 255), email required/unique/valid, password min 8 chars with confirmation
- [ ] On success: account created, `user` role assigned via spatie/laravel-permission, verification email sent, redirect to email verification notice page
- [ ] Rate limited: 5 registrations per IP per hour
- [ ] Feature test (`tests/Feature/Auth/RegistrationTest.php`) covers happy path, validation errors (missing fields, short password, duplicate email), rate limiting (6th attempt returns 429)

#### US-018: Email Verification
**Priority:** 18
**Description:** As a new user, I need to verify my email before I can join groups or RSVP so that the platform can confirm my identity.

**Acceptance Criteria:**
- [ ] Verification notice page at `/email/verify` shown to unverified users
- [ ] Resend verification link button available
- [ ] Clicking the email link verifies the account and redirects to dashboard
- [ ] Unverified users can browse but see a banner prompting verification; cannot join groups or RSVP (enforced by `EnsureEmailIsVerified` middleware)
- [ ] Token expires after 60 minutes
- [ ] Feature test (`tests/Feature/Auth/EmailVerificationTest.php`): covers verify flow, expired token, resend, unverified user blocked from joining groups

#### US-019: Login
**Priority:** 19
**Description:** As a registered user, I want to log in so that I can access my dashboard and groups.

**Acceptance Criteria:**
- [ ] Login form at `/login` with email, password, "remember me" checkbox
- [ ] On success: redirect to `/dashboard`
- [ ] Rate limit: 5 failed attempts per minute per email, 1-minute lockout
- [ ] Suspended users see the suspended account page with reason (via `EnsureAccountNotSuspended` middleware) instead of dashboard
- [ ] Feature test (`tests/Feature/Auth/LoginTest.php`) covers happy path, invalid credentials, rate limiting (6th attempt returns 429), suspended user redirect, remember me

#### US-020: Password Reset
**Priority:** 20
**Description:** As a user who forgot my password, I want to reset it via email so that I can regain access to my account.

**Acceptance Criteria:**
- [ ] Forgot password form at `/forgot-password`, reset form at `/reset-password/{token}`
- [ ] Standard Laravel password reset flow; token expires after 60 minutes
- [ ] Feature test (`tests/Feature/Auth/PasswordResetTest.php`): covers request link, valid reset, expired token, invalid token

#### US-021: Logout
**Priority:** 21
**Description:** As a logged-in user, I want to log out securely.

**Acceptance Criteria:**
- [ ] POST `/logout` invalidates session and redirects to homepage
- [ ] Logout link available in the user dropdown menu

### Phase 3: Database Foundation & Models

#### US-022: User Model & Migration
**Priority:** 22
**Description:** As a developer, I need the User model and migration with all fields from the spec so that user accounts can be created and managed.

**Acceptance Criteria:**
- [ ] Migration creates `users` table with all columns from spec section 4.1: id, name, email (unique), email_verified_at, password, avatar_path, bio, location, latitude (decimal 10,7), longitude (decimal 10,7), timezone (default 'UTC'), looking_for (JSON), profile_visibility (enum: public/members_only, default public), is_suspended, suspended_at, suspended_reason, last_active_at, remember_token, timestamps, deleted_at (soft delete)
- [ ] User model: fillable fields, casts (looking_for as array, profile_visibility as enum, dates as Carbon, is_suspended as boolean), soft deletes
- [ ] Relationships: groups (belongsToMany via group_members with pivot), organizedGroups, rsvps, discussions, blocks, notifications
- [ ] Laravel Scout searchable: name (high weight), bio (low weight) — only indexed when profile_visibility = 'public'
- [ ] Uses spatie/laravel-tags for interests (type `interest`)
- [ ] Uses spatie/laravel-medialibrary for avatar with conversions: 44x44 (nav), 96x96 (profile card), 256x256 (profile page)
- [ ] UserFactory: realistic Faker data, verified email, random location from [Copenhagen, Berlin, London, NYC], 3-8 random interests
- [ ] Factory states: `unverified()`, `suspended()`, `admin()`
- [ ] Unit test (`tests/Unit/Models/UserTest.php`): covers factory creation, all relationships, casts, soft delete, searchable config

#### US-023: Middleware — TrackLastActivity
**Priority:** 23
**Description:** As a developer, I need middleware that updates `last_active_at` on every authenticated request for activity tracking.

**Acceptance Criteria:**
- [ ] `App\Http\Middleware\TrackLastActivity` updates `$user->last_active_at` to `now()` on each authenticated request
- [ ] Registered on all authenticated routes
- [ ] Does not block the request — uses a deferred/after-response update to avoid latency
- [ ] Feature test: authenticated request updates `last_active_at`, unauthenticated request does nothing

#### US-024: Middleware — EnsureAccountNotSuspended
**Priority:** 24
**Description:** As a developer, I need middleware that shows a suspension notice to suspended users instead of the requested page.

**Acceptance Criteria:**
- [ ] `App\Http\Middleware\EnsureAccountNotSuspended` checks `$user->is_suspended`
- [ ] Suspended users are redirected to the suspended account view (`resources/views/auth/suspended.blade.php`)
- [ ] The view shows the `suspended_reason` and a logout link
- [ ] Applied to all authenticated routes
- [ ] Feature test: suspended user sees suspension page with reason

#### US-025: Middleware — EnsureGroupMember & EnsureGroupRole
**Priority:** 25
**Description:** As a developer, I need middleware that gates group-scoped routes by membership and role.

**Acceptance Criteria:**
- [ ] `EnsureGroupMember`: returns 403 if user is not a member of the group resolved from the route
- [ ] `EnsureGroupRole`: accepts a minimum role parameter (e.g., `EnsureGroupRole:event_organizer`), returns 403 if user's group role is below the required level. Role hierarchy: member(0) < event_organizer(1) < assistant_organizer(2) < co_organizer(3) < organizer(4)
- [ ] Feature test: non-member gets 403, member with insufficient role gets 403, member with sufficient role proceeds

#### US-026: Group Model & Migration
**Priority:** 26
**Description:** As a developer, I need the Group model and migration with all fields from the spec so that community groups can be created.

**Acceptance Criteria:**
- [ ] Migration creates `groups` table with all columns from spec section 4.2: id, name, slug (unique), description, description_html, organizer_id (FK users), location, latitude (decimal 10,7), longitude (decimal 10,7), timezone (default 'UTC'), cover_photo_path, visibility (enum: public/private, default public), requires_approval (default false), max_members (nullable), welcome_message (nullable), is_active (default true), timestamps, deleted_at
- [ ] Indexes: (latitude, longitude), (visibility, is_active)
- [ ] Group model: fillable fields, casts, relationships (organizer belongsTo User, members belongsToMany User via group_members with pivot, events hasMany, discussions hasMany, tags via spatie/laravel-tags type 'interest'), soft deletes
- [ ] Uses spatie/laravel-sluggable for slug generation from name
- [ ] Uses spatie/laravel-medialibrary for cover photo with conversions: 400x200 (card), 1200x400 (header)
- [ ] Scopes: `scopeActive()`, `scopePublic()`, `scopeNearby($lat, $lng, $radiusKm = 50)` using Haversine formula per spec section 8.2
- [ ] Laravel Scout searchable: name (high weight), description (medium weight), location (low weight)
- [ ] GroupFactory: realistic data with hardcoded lat/lng; states: `private()`, `requiresApproval()`, `inactive()`
- [ ] Unit test (`tests/Unit/Models/GroupTest.php`): covers factory, relationships, scopes (including nearby with distance assertion), slug generation, casts, soft delete

#### US-027: Group Members Pivot & Membership Questions
**Priority:** 27
**Description:** As a developer, I need the group_members pivot table and membership questions tables so that users can join groups with role-based permissions.

**Acceptance Criteria:**
- [ ] `group_members` migration: id, group_id (FK), user_id (FK), role (enum: member/event_organizer/assistant_organizer/co_organizer/organizer), joined_at (timestamp), is_banned (default false), banned_at, banned_reason, timestamps. UNIQUE index (group_id, user_id), index (group_id, role), index (user_id)
- [ ] `group_membership_questions` migration per spec section 4.4: id, group_id (FK), question (string 500), is_required (default true), sort_order (default 0), timestamps
- [ ] `group_membership_answers` migration: id, question_id (FK), user_id (FK), answer (text), timestamps. UNIQUE (question_id, user_id)
- [ ] `group_join_requests` migration per spec section 4.5: id, group_id (FK), user_id (FK), status (enum: pending/approved/denied, default pending), reviewed_by (FK nullable), reviewed_at, denial_reason, timestamps. UNIQUE (group_id, user_id), index (group_id, status)
- [ ] GroupMember pivot model with role enum cast
- [ ] Unit tests: relationship definitions, pivot field access, role enum cast

#### US-028: Event Model & Migration
**Priority:** 28
**Description:** As a developer, I need the Event model and migration with all fields from the spec so that events can be created within groups.

**Acceptance Criteria:**
- [ ] Migration creates `events` table with all columns from spec section 4.6: id, group_id (FK), created_by (FK users), name, slug, description, description_html, event_type (enum: in_person/online/hybrid, default in_person), status (enum: draft/published/cancelled/past, default draft), starts_at, ends_at (nullable), timezone, venue_name, venue_address, venue_latitude (decimal 10,7), venue_longitude (decimal 10,7), online_link, cover_photo_path, rsvp_limit (nullable), guest_limit (default 0), rsvp_opens_at (nullable), rsvp_closes_at (nullable), is_chat_enabled (default true), is_comments_enabled (default true), cancelled_at, cancellation_reason, series_id (FK nullable), timestamps
- [ ] `event_series` migration per spec section 4.7: id, group_id (FK), recurrence_rule (string), timestamps
- [ ] `event_hosts` migration per spec section 4.8: id, event_id (FK), user_id (FK), timestamps. UNIQUE (event_id, user_id)
- [ ] Indexes: (group_id, status, starts_at), (starts_at, status), (venue_latitude, venue_longitude), (series_id), UNIQUE (group_id, slug)
- [ ] Event model: relationships (group, creator, hosts, rsvps, comments, chatMessages, feedback, series), scopes (`upcoming()`, `past()`, `published()`, `cancelled()`, `scopeNearby()`), slug via spatie/laravel-sluggable unique within group, medialibrary for cover photo (400x200, 1200x400)
- [ ] Laravel Scout searchable: name (high weight), description (medium weight), venue_name (low weight)
- [ ] EventFactory with states: `draft()`, `published()`, `cancelled()`, `past()`, `online()`, `hybrid()`, `withRsvpLimit(int)`
- [ ] Unit test (`tests/Unit/Models/EventTest.php`): covers factory, relationships, all scopes, slug uniqueness within group, casts

#### US-029: RSVP Model & Migration
**Priority:** 29
**Description:** As a developer, I need the RSVP model and migration so that users can register their attendance for events.

**Acceptance Criteria:**
- [ ] Migration creates `rsvps` table per spec section 4.9: id, event_id (FK), user_id (FK), status (enum: going/not_going/waitlisted, default going), guest_count (default 0), attendance_mode (enum: in_person/online, nullable — for hybrid events), checked_in (default false), checked_in_at (nullable), checked_in_by (FK users nullable), attended (enum: attended/no_show, nullable), waitlisted_at (nullable), timestamps
- [ ] Indexes: UNIQUE (event_id, user_id), (event_id, status), (user_id, status), (event_id, status, waitlisted_at) for FIFO waitlist ordering
- [ ] Rsvp model with relationships (event, user, checkedInBy), enum casts
- [ ] RsvpFactory with states: `going()`, `waitlisted()`, `notGoing()`, `checkedIn()`, `withGuests(int)`
- [ ] Unit test (`tests/Unit/Models/RsvpTest.php`): covers factory, relationships, casts, unique constraint enforcement

#### US-030: Discussion & Reply Models
**Priority:** 30
**Description:** As a developer, I need the Discussion and DiscussionReply models and migrations for group discussion threads.

**Acceptance Criteria:**
- [ ] `discussions` migration per spec section 4.14: id, group_id (FK), user_id (FK), title, slug, body, body_html, is_pinned (default false), is_locked (default false), last_activity_at, timestamps, deleted_at. UNIQUE (group_id, slug), index (group_id, is_pinned, last_activity_at)
- [ ] `discussion_replies` migration per spec section 4.15: id, discussion_id (FK), user_id (FK), body, body_html, timestamps, deleted_at. Index (discussion_id, created_at)
- [ ] Discussion model: relationships, slug generation (unique within group), scopes (pinned first, ordered by last_activity_at desc)
- [ ] DiscussionReply model: relationships, soft deletes
- [ ] Factories for both; unit tests pass

#### US-031: Event Comments, Likes & Feedback Models
**Priority:** 31
**Description:** As a developer, I need the EventComment, EventCommentLike, and EventFeedback models for event interaction.

**Acceptance Criteria:**
- [ ] `event_comments` migration per spec section 4.10: id, event_id (FK), user_id (FK), parent_id (FK event_comments nullable — one level threading only), body, body_html, timestamps, deleted_at. Index (event_id, created_at), index (parent_id)
- [ ] `event_comment_likes` migration per spec section 4.11: id, comment_id (FK), user_id (FK), created_at. UNIQUE (comment_id, user_id)
- [ ] `event_feedback` migration per spec section 4.12: id, event_id (FK), user_id (FK), rating (tinyint 1-5), body (nullable), timestamps. UNIQUE (event_id, user_id), index (event_id, rating)
- [ ] Models with relationships, casts, and factories; unit tests pass

#### US-032: Event Chat Messages Model
**Priority:** 32
**Description:** As a developer, I need the EventChatMessage model for real-time event chat.

**Acceptance Criteria:**
- [ ] `event_chat_messages` migration per spec section 4.13: id, event_id (FK), user_id (FK), body, reply_to_id (FK event_chat_messages nullable), timestamps, deleted_at. Index (event_id, created_at)
- [ ] EventChatMessage model with relationships (event, user, replyTo), soft deletes
- [ ] Factory; unit test passes

#### US-033: Direct Messaging Models
**Priority:** 33
**Description:** As a developer, I need the Conversation, ConversationParticipant, and DirectMessage models for 1:1 messaging.

**Acceptance Criteria:**
- [ ] `conversations` migration: id, timestamps
- [ ] `conversation_participants` migration: id, conversation_id (FK), user_id (FK), last_read_at (nullable), is_muted (default false), created_at. UNIQUE (conversation_id, user_id), index (user_id, last_read_at)
- [ ] `direct_messages` migration: id, conversation_id (FK), user_id (FK sender), body, timestamps, deleted_at. Index (conversation_id, created_at)
- [ ] Models with relationships; Conversation hasMany participants and messages; User hasMany conversations through participants
- [ ] Factories; unit tests pass

#### US-034: Reports & Blocks Models
**Priority:** 34
**Description:** As a developer, I need the Report and Block models for content moderation and user blocking.

**Acceptance Criteria:**
- [ ] `reports` migration per spec section 4.18: id, reporter_id (FK users), reportable_type, reportable_id (polymorphic), reason (enum: spam/harassment/hate_speech/impersonation/inappropriate_content/misleading/other), description (nullable), status (enum: pending/reviewed/resolved/dismissed, default pending), reviewed_by (FK users nullable), reviewed_at, resolution_notes, timestamps. Index (reportable_type, reportable_id), index (status, created_at)
- [ ] `blocks` migration per spec section 4.19: id, blocker_id (FK), blocked_id (FK), created_at. UNIQUE (blocker_id, blocked_id), index (blocked_id)
- [ ] Report model: polymorphic reportable relationship, scopes (pending, reviewed, resolved, dismissed)
- [ ] Block model: relationships (blocker, blocked)
- [ ] Factories; unit tests pass

#### US-035: Notification Preferences & Platform Settings Models
**Priority:** 35
**Description:** As a developer, I need the NotificationPreference, GroupNotificationMute, PendingNotificationDigest, and Setting models.

**Acceptance Criteria:**
- [ ] `notification_preferences` migration per spec section 4.21: id, user_id (FK), channel (enum: email/web/push), type (string — notification class name), enabled (default true), timestamps. UNIQUE (user_id, channel, type)
- [ ] `group_notification_mutes` migration per spec section 4.22: id, user_id (FK), group_id (FK), created_at. UNIQUE (user_id, group_id)
- [ ] `pending_notification_digests` migration per spec section 4.24: id, user_id (FK), notification_type (string), data (JSON — serialized notification payload), created_at. Index (user_id, notification_type, created_at)
- [ ] `settings` migration per spec section 4.23: id, key (string unique), value (text nullable), timestamps
- [ ] Models with relationships; factories; unit tests pass

#### US-036: Interests/Topics Seeder
**Priority:** 36
**Description:** As a developer, I need the interest taxonomy seeded so that groups and users can be tagged with topics.

**Acceptance Criteria:**
- [ ] `InterestSeeder` creates 30+ tags of type `interest` per spec section 9.2 across categories: Technology (Web Development, Mobile Development, Data Science, Machine Learning, DevOps, Cybersecurity, Open Source, Game Development), Languages & Frameworks (PHP, Laravel, JavaScript, Python, Rust, Go, React, Vue.js), Creative (Photography, Writing, Music, Art, Film), Lifestyle (Hiking, Running, Cycling, Cooking, Board Games, Book Club, Language Exchange, Parenting), Professional (Entrepreneurship, Marketing, Design, Product Management)
- [ ] Seeder is idempotent (uses firstOrCreate)
- [ ] Tags have English name and auto-generated slug

### Phase 4: Core Services & Policies

#### US-037: Markdown Service
**Priority:** 37
**Description:** As a developer, I need a MarkdownService that securely renders markdown to HTML for all user-supplied content.

**Acceptance Criteria:**
- [ ] `App\Services\MarkdownService` wraps `league/commonmark`
- [ ] Uses `DisallowedRawHtmlExtension` to block `<script>`, `<iframe>`, `<object>`, `<embed>`, and other dangerous elements — raw HTML in markdown input is stripped entirely, not escaped
- [ ] Adds `rel="nofollow noopener"` and `target="_blank"` to all rendered links
- [ ] Returns empty string for null/empty input
- [ ] Single point of configuration for all CommonMark extensions and security settings
- [ ] Unit test (`tests/Unit/Services/MarkdownServiceTest.php`): covers standard rendering (headings, lists, links, code blocks), HTML stripping (asserts `<script>` removed, not escaped), link attributes present, empty input returns empty string

#### US-038: Geocoding Service
**Priority:** 38
**Description:** As a developer, I need a GeocodingService wrapping the Geocodio API so that addresses can be resolved to coordinates for location-based features.

**Acceptance Criteria:**
- [ ] `App\Services\GeocodingService` wraps `geocodio/geocodio-library-php`
- [ ] `geocode(string $address): ?array` returns `['lat' => float, 'lng' => float, 'formatted_address' => string]` or null
- [ ] `reverse(float $lat, float $lng): ?string` returns formatted address or null
- [ ] `batch(array $addresses): array` for bulk operations (used during seeding)
- [ ] Returns null gracefully when API key is missing or API errors occur — no exceptions thrown
- [ ] Config at `config/services.php` with `'geocodio' => ['api_key' => env('GEOCODIO_API_KEY')]`
- [ ] `GeocodeLocation` queued job: dispatches from model observers on Group, Event, User when address text changes. Retries 3 times with exponential backoff. Silently skips if no API key. Stores resolved lat/lng on the model. Only re-resolves if address text actually changed.
- [ ] All tests use a mocked GeocodingService (bound in test service provider) — no real API calls in CI. `GEOCODIO_API_KEY` intentionally left empty in CI
- [ ] Unit test (`tests/Unit/Services/GeocodingServiceTest.php`): covers forward geocode (valid/invalid address), reverse geocode, batch geocode, missing API key returns null, API error returns null, Haversine nearby scope accuracy (correct results within radius, exclusion outside radius)

#### US-039: RSVP Service
**Priority:** 39
**Description:** As a developer, I need an RsvpService that handles the business logic of RSVPing to events including capacity checks, waitlisting, and guest validation.

**Acceptance Criteria:**
- [ ] `App\Services\RsvpService` with methods: `rsvpGoing(Event, User, guestCount, attendanceMode)`, `rsvpNotGoing(Event, User)`, `joinWaitlist(Event, User, guestCount)`
- [ ] Going RSVP: checks group membership, event is published, RSVP window open (rsvp_opens_at passed if set, rsvp_closes_at not passed if set), event not past (starts_at in future or ends_at not passed), spots available accounting for member + guests
- [ ] Auto-waitlist when event is full (sets waitlisted_at for FIFO ordering)
- [ ] Cancelling a Going RSVP dispatches `PromoteFromWaitlist` queued job
- [ ] Guest count validated: must be <= event's guest_limit; member + guests must not exceed available spots
- [ ] For hybrid events, attendance_mode (in_person/online) is required and captured
- [ ] Unit test (`tests/Unit/Services/RsvpServiceTest.php`): covers going with spots, auto-waitlist when full, cancel triggers waitlist job, not a member rejected, RSVP window closed rejected, past event rejected, cancelled event rejected, guest count exceeds limit rejected, hybrid requires attendance mode

#### US-040: Waitlist Service
**Priority:** 40
**Description:** As a developer, I need a WaitlistService that promotes waitlisted members when spots open, using FIFO ordering and accounting for guest counts.

**Acceptance Criteria:**
- [ ] `App\Services\WaitlistService` with `promoteNext(Event): ?Rsvp` method
- [ ] Promotes the next eligible waitlisted member ordered by waitlisted_at (FIFO)
- [ ] Skips members whose guest_count + 1 exceeds available spots; continues to next eligible member
- [ ] When multiple spots open (e.g., member with 2 guests cancels), promotes multiple waitlisted members and revisits previously skipped members
- [ ] No promotion when waitlist is empty or event is cancelled
- [ ] Sends `PromotedFromWaitlist` notification to promoted member
- [ ] `PromoteFromWaitlist` queued job wraps this service; dispatched by RsvpService
- [ ] Unit test (`tests/Unit/Services/WaitlistServiceTest.php`): covers FIFO promotion, guest-count skipping, revisiting skipped members when more spots open, empty waitlist, cancelled event, notification sent

#### US-041: Group Policy
**Priority:** 41
**Description:** As a developer, I need a GroupPolicy that enforces the permission matrix from the spec so that group actions are properly authorized.

**Acceptance Criteria:**
- [ ] `App\Policies\GroupPolicy` implements all actions from spec section 3.4 permission matrix
- [ ] Role hierarchy comparison: member(0) < event_organizer(1) < assistant_organizer(2) < co_organizer(3) < organizer(4) — higher roles inherit all lower role permissions
- [ ] `view`: any user; `join`: verified non-member non-banned; `leave`: any member (not organizer without transfer)
- [ ] `createEvent`, `editAnyEvent`, `cancelEvent`, `manageRsvps`, `checkInAttendees`, `sendGroupMessages`, `assignEventHosts`: event_organizer+
- [ ] `acceptRequests`, `removeMembers`, `banMembers`: assistant_organizer+
- [ ] `editSettings`, `manageLeadership`, `viewAnalytics`: co_organizer+
- [ ] `delete`, `transferOwnership`: organizer only
- [ ] Suspended users denied everything; non-members denied group actions
- [ ] Unit test (`tests/Unit/Policies/GroupPolicyTest.php`): tests EVERY action with EVERY role (both allow and deny), tests role inheritance (co_organizer can do everything assistant_organizer can), suspended user denied, non-member denied

#### US-042: Event Policy
**Priority:** 42
**Description:** As a developer, I need an EventPolicy for event-level authorization including event host permissions.

**Acceptance Criteria:**
- [ ] `App\Policies\EventPolicy` handles: view, create, update, cancel, manageAttendees, checkIn
- [ ] Event hosts can edit, view attendees, check in, and manage chat for their specific event only (not other events in the group)
- [ ] Event organizer+ within the group can perform all event actions on any group event
- [ ] Non-members cannot RSVP; unverified users cannot RSVP
- [ ] Unit test (`tests/Unit/Policies/EventPolicyTest.php`): tests all roles, host-specific scoping (host of event A cannot edit event B), non-member denied RSVP

#### US-043: Discussion Policy
**Priority:** 43
**Description:** As a developer, I need a DiscussionPolicy for discussion thread authorization.

**Acceptance Criteria:**
- [ ] Any group member can create discussions, post replies
- [ ] Pin/unpin, lock/unlock, delete any discussion/reply: co_organizer+
- [ ] Authors can delete their own replies; cannot delete others'
- [ ] Replying to a locked discussion is denied
- [ ] Unit test (`tests/Unit/Policies/DiscussionPolicyTest.php`): covers all role/action combinations, locked discussion denial

#### US-044: Event Chat Policy
**Priority:** 44
**Description:** As a developer, I need an EventChatPolicy for real-time chat authorization.

**Acceptance Criteria:**
- [ ] Sending messages: requires RSVP Going or group membership (non-RSVP members can optionally join)
- [ ] Editing/deleting own messages: allowed
- [ ] Deleting others' messages: leadership role (event_organizer+) in the group
- [ ] Chat disabled when `is_chat_enabled` is false — all send attempts return 403
- [ ] Non-owner cannot edit another user's message
- [ ] Unit test (`tests/Unit/Policies/EventChatPolicyTest.php`): covers send (RSVP'd, non-RSVP member, non-member), edit own, delete own, leadership delete others, chat disabled

#### US-045: Notification Service
**Priority:** 45
**Description:** As a developer, I need a NotificationService that dispatches notifications while respecting group mutes, user blocks, per-type preferences, and digest batching.

**Acceptance Criteria:**
- [ ] `App\Services\NotificationService` wraps notification dispatch
- [ ] Checks group notification mutes: muted groups suppress all non-critical notifications. Critical notifications (PromotedFromWaitlist, JoinRequestApproved, MemberRemoved, MemberBanned, AccountSuspended) are never suppressed
- [ ] Checks user blocks: blocked users don't generate notifications to the blocker
- [ ] Checks per-type notification preferences: disabled channels are skipped
- [ ] Digest batching (email only): when 5+ of the same notification type fire for the same recipient within 15 minutes, subsequent notifications are stored in `pending_notification_digests` instead of sending individual emails. Web notifications are never batched — always fire individually
- [ ] Suspended users do not receive notifications
- [ ] Unit test (`tests/Unit/Services/NotificationServiceTest.php`): covers muted group suppression, critical notification exemption from muting, blocked user filtering, per-type preferences, digest threshold (4 sends individually, 5th triggers batching), suspended user skipped

#### US-046: Additional Service Classes
**Priority:** 46
**Description:** As a developer, I need service classes for group membership, event series, search, export, and account operations.

**Acceptance Criteria:**
- [ ] `GroupMembershipService`: join/leave groups, handle approval workflow, role changes
- [ ] `EventSeriesService`: generate recurring event instances from RRULE, manage series edits (single vs all future)
- [ ] `SearchService`: coordinate Scout search across Group, Event, User models with proper field weighting (spec section 8.1: name=high, description=medium, location/venue/bio=low)
- [ ] `ExportService`: generate CSV exports for members (columns: name, email, joined date, attendance stats) and attendees (columns: name, RSVP status, guest count, checked-in)
- [ ] `AccountService`: handle account deletion (soft delete), data export (JSON), suspension
- [ ] Each service has unit tests

### Phase 5: User Profile & Account Settings

#### US-047: Account Settings Page
**Priority:** 47
**Description:** As a logged-in user, I want to update my name, email, and password from a settings page.

**Acceptance Criteria:**
- [ ] Settings page at `/settings` with tabs or sections for Profile, Account, Notifications, Privacy
- [ ] Account section: update email (triggers re-verification), update password (requires current password confirmation)
- [ ] Form Request validation for each update
- [ ] Feature test (`tests/Feature/Profile/ProfileUpdateTest.php`): covers name update, email change triggers re-verification, password change requires current password, validation errors

#### US-048: Profile Settings
**Priority:** 48
**Description:** As a logged-in user, I want to edit my profile (bio, location, avatar, interests, looking for) so that other members can learn about me.

**Acceptance Criteria:**
- [ ] Profile section: bio (textarea), location (text input — geocoded asynchronously via `GeocodeLocation` queued job, form submission is not blocked), timezone (dropdown of IANA identifiers), avatar upload (2MB max, JPEG/PNG/WebP, thumbnails 44x44/96x96/256x256 via medialibrary), interests (multi-select from existing interest tags), "looking for" (checkboxes: "practicing hobbies", "making friends", "networking", "professional development", "learning new things")
- [ ] Location geocoded to lat/lng via `GeocodeLocation` job when saved — asserts job dispatched, not that geocoding completed
- [ ] Feature test: covers profile update, avatar upload (valid file stored, oversized rejected with 422, non-image rejected with 422), geocoding job dispatched on location change, interests saved

#### US-049: Privacy Settings
**Priority:** 49
**Description:** As a user, I want to control my profile visibility so that I can choose who sees my information.

**Acceptance Criteria:**
- [ ] Toggle `profile_visibility` between `public` and `members_only`
- [ ] `members_only` profiles only visible to users who share at least one group
- [ ] Scout search index updated when visibility changes (removed from index when members_only)
- [ ] Feature test (`tests/Feature/Profile/ProfileVisibilityTest.php`): covers both modes, verifies non-shared-group user gets 403 for members_only profile

#### US-050: Notification Preferences
**Priority:** 50
**Description:** As a user, I want to configure which notifications I receive via email and web so that I'm not overwhelmed.

**Acceptance Criteria:**
- [ ] List of all 22 notification types with toggles for email and web channels
- [ ] Preferences stored in `notification_preferences` table
- [ ] Feature test (`tests/Feature/Profile/NotificationPreferencesTest.php`): covers toggling preferences, verifying they affect notification delivery

#### US-051: Account Deletion
**Priority:** 51
**Description:** As a user, I want to delete my account with a 30-day grace period so that I can leave the platform while having time to change my mind.

**Acceptance Criteria:**
- [ ] Delete account from settings (requires password confirmation)
- [ ] Soft delete with 30-day grace period
- [ ] Feature test (`tests/Feature/Auth/AccountDeletionTest.php`): covers deletion, wrong password rejected, soft-deleted user cannot log in

#### US-052: Personal Data Export
**Priority:** 52
**Description:** As a user, I want to download all my personal data as a JSON file.

**Acceptance Criteria:**
- [ ] GET `/settings/data-export` generates a JSON file with user profile, groups, RSVPs, discussions, messages, and notification preferences
- [ ] File is streamed as a download
- [ ] Feature test: covers export contains expected data sections

#### US-053: Public Profile Page
**Priority:** 53
**Description:** As a user, I want to view other members' profiles to learn about them and find common interests.

**Acceptance Criteria:**
- [ ] Profile page at `/members/{id}` shows avatar, name, bio, location, interests, "looking for" tags, groups in common with viewer
- [ ] Respects `profile_visibility` setting (members_only returns 403 for non-shared-group users)
- [ ] "Message" button (opens DM), "Report" and "Block" in dropdown
- [ ] SEO: `<title>` "{User Name} — {site_name}", meta description from bio (first 160 chars) or "{Name} is a member of {site_name}." if no bio, OG image from avatar
- [ ] Feature test: covers public profile, members_only visibility, blocked user cannot view blocker's profile

### Phase 6: Groups

#### US-054: Group Creation
**Priority:** 54
**Description:** As a verified user, I want to create a group so that I can organize a community around a shared interest.

**Acceptance Criteria:**
- [ ] Form at `/groups/create` with fields: name, description (markdown editor), location, cover photo, topics/interests, visibility, requires_approval, max_members, welcome_message
- [ ] Optional: membership questions (add/remove/reorder dynamically)
- [ ] Slug auto-generated from name via spatie/laravel-sluggable; slug collisions handled automatically
- [ ] Description rendered to HTML via MarkdownService on save (stored in `description_html`)
- [ ] Creator becomes organizer role in group_members; location geocoded asynchronously via queued job (form submission not blocked)
- [ ] Form Request validation; feature test (`tests/Feature/Groups/CreateGroupTest.php`): covers happy path, validation errors (missing required fields), slug generation, geocoding job dispatched

#### US-055: Group Profile Page
**Priority:** 55
**Description:** As a visitor or member, I want to view a group's profile page to see its events, members, and information.

**Acceptance Criteria:**
- [ ] Page at `/groups/{slug}` with cover photo (decorative blob header if no cover), name, description, location, member count with avatar stack, interest pills (cycling colors), organizer info
- [ ] Tab bar: Upcoming Events, Past Events, Discussions, Members, About
- [ ] "Join Group" button for non-members (or "Request to Join" if approval required)
- [ ] Members see "Leave Group" option; leadership team displayed in About tab
- [ ] Private groups: non-members see limited info (name, description, member count) but not events or discussions
- [ ] SEO: `<title>` "{Group Name} — {site_name}", meta description from first 160 chars of description (plain text), OG image from cover photo or default
- [ ] Feature test (`tests/Feature/Groups/ViewGroupTest.php`): covers public view, member view, private group restrictions, correct tab content

#### US-056: Joining a Group (Open)
**Priority:** 56
**Description:** As a verified user, I want to join an open group instantly so that I can start participating.

**Acceptance Criteria:**
- [ ] Click "Join Group" on an open group immediately creates group_members record with `member` role and `joined_at` timestamp
- [ ] Welcome message sent via `WelcomeToGroup` notification (web + email)
- [ ] Cannot join if: banned, already a member, group is at max_members, user is unverified
- [ ] Feature test (`tests/Feature/Groups/JoinGroupTest.php`): covers happy path, already member, banned, at capacity, unverified rejected

#### US-057: Joining a Group (Approval Required)
**Priority:** 57
**Description:** As a verified user, I want to request to join an approval-required group and answer membership questions.

**Acceptance Criteria:**
- [ ] Click "Request to Join" shows membership questions form (if questions exist); questions marked `is_required` must be answered
- [ ] Creates `group_join_requests` record with `pending` status and saves membership answers in `group_membership_answers`
- [ ] Organizer/Assistant+ receive `JoinRequestReceived` notification
- [ ] User sees "Request Pending" status on group page
- [ ] Re-requesting updates the existing record (unique constraint on group_id, user_id)
- [ ] On approval: user becomes member, `JoinRequestApproved` notification sent, welcome message sent
- [ ] On denial: `JoinRequestDenied` notification sent with optional reason
- [ ] Feature test (`tests/Feature/Groups/MembershipApprovalTest.php`): covers request with answers, required question validation, approval flow, denial flow with reason, re-request

#### US-058: Leaving a Group
**Priority:** 58
**Description:** As a group member, I want to leave a group when I'm no longer interested.

**Acceptance Criteria:**
- [ ] POST `/groups/{slug}/leave` removes the group_members record
- [ ] Upcoming RSVPs for the group's events are cancelled (changed to not_going); triggers waitlist promotion for each
- [ ] Primary organizer cannot leave without transferring ownership first (returns error)
- [ ] Feature test (`tests/Feature/Groups/LeaveGroupTest.php`): covers leave, RSVPs cancelled, organizer blocked

#### US-059: Group Settings (Co-Organizer+)
**Priority:** 59
**Description:** As a co-organizer, I want to edit group settings so that I can keep the group information up to date.

**Acceptance Criteria:**
- [ ] Settings page at `/groups/{slug}/manage/settings` (authorized co_organizer+ via `EnsureGroupRole` middleware)
- [ ] Editable: name, slug, description (re-renders description_html), location (geocoded via job), cover photo, topics, visibility, requires_approval, max_members, welcome_message
- [ ] Manage membership questions (add, edit, reorder via sort_order, delete)
- [ ] Feature test (`tests/Feature/Groups/GroupSettingsTest.php`): covers authorization (member rejected, co_organizer allowed), updates persisted, slug change, question CRUD

#### US-060: Member Management (Assistant Organizer+)
**Priority:** 60
**Description:** As an assistant organizer, I want to manage group members so that I can maintain a healthy community.

**Acceptance Criteria:**
- [ ] Page at `/groups/{slug}/manage/members` with member list: name, role, joined date, attendance stats (events attended, no-shows). Pagination: 20 per page
- [ ] Search/filter members by name
- [ ] Actions: remove (with optional reason, `MemberRemoved` notification), ban (prevents rejoin, `MemberBanned` notification with reason), unban
- [ ] Export member list as CSV (columns: name, email, joined date, events attended, no-shows)
- [ ] Join request management at `/groups/{slug}/manage/requests`: approve/deny with notifications
- [ ] Feature test (`tests/Feature/Groups/MemberManagementTest.php`): covers all actions, authorization (member rejected), CSV export content, ban prevents rejoin

#### US-061: Leadership Team Management (Co-Organizer+)
**Priority:** 61
**Description:** As a co-organizer, I want to promote and demote group leadership members.

**Acceptance Criteria:**
- [ ] Page at `/groups/{slug}/manage/team` showing current leadership
- [ ] Promote member to event_organizer, assistant_organizer, or co_organizer
- [ ] Demote leadership member to lower role
- [ ] Co-organizers cannot promote anyone to co_organizer or demote other co_organizers (only primary organizer can)
- [ ] `RoleChanged` notification sent to affected member
- [ ] Feature test (`tests/Feature/Groups/LeadershipTeamTest.php`): covers all promotion/demotion rules, co_organizer limitations, notification sent

#### US-062: Ownership Transfer (Primary Organizer)
**Priority:** 62
**Description:** As the primary organizer, I want to transfer group ownership to a co-organizer so that someone else can take over.

**Acceptance Criteria:**
- [ ] Form at `/groups/{slug}/manage/transfer` (organizer only)
- [ ] Select from existing co-organizers; requires password confirmation
- [ ] New owner gets `organizer` role; previous owner becomes `co_organizer`
- [ ] `OwnershipTransferred` notification sent to new owner
- [ ] Feature test (`tests/Feature/Groups/OwnershipTransferTest.php`): covers transfer, wrong password rejected, non-co-organizer target rejected

#### US-063: Group Deletion (Primary Organizer)
**Priority:** 63
**Description:** As the primary organizer, I want to delete a group when it's no longer active.

**Acceptance Criteria:**
- [ ] DELETE `/groups/{slug}` (organizer only, requires password confirmation)
- [ ] Soft-deletes the group; cancels all upcoming events with `EventCancelled` notifications
- [ ] `GroupDeleted` notification sent to all members via email
- [ ] 90-day grace period before hard purge (via `groups:purge-deleted` scheduled command)
- [ ] Feature test (`tests/Feature/Groups/GroupDeletionTest.php`): covers deletion, events cancelled, members notified, wrong password rejected

#### US-064: Group Analytics (Co-Organizer+)
**Priority:** 64
**Description:** As a co-organizer, I want to see group analytics so that I can understand member engagement.

**Acceptance Criteria:**
- [ ] Page at `/groups/{slug}/manage/analytics` (co_organizer+)
- [ ] Shows: member growth over time (new members per week/month), event count over time, average attendance rate (attended vs no-show), most active members (by attendance count), average event rating
- [ ] Data from Eloquent aggregation queries (no separate analytics tables)
- [ ] Feature test (`tests/Feature/Groups/GroupAnalyticsTest.php`): covers authorization and data accuracy with seeded test data

### Phase 7: Events

#### US-065: Event Creation
**Priority:** 65
**Description:** As an event organizer, I want to create events for my group so that members can attend.

**Acceptance Criteria:**
- [ ] Form at `/groups/{slug}/events/create` (event_organizer+ authorized via middleware)
- [ ] Fields: name, description (markdown), starts_at, ends_at (optional), event_type (in_person/online/hybrid)
- [ ] Conditional fields: venue_name + venue_address (in_person/hybrid, geocoded via queued job), online_link (online/hybrid)
- [ ] Optional: cover_photo (5MB max, JPEG/PNG/WebP, conversions 400x200/1200x400), rsvp_limit, guest_limit, rsvp_opens_at, rsvp_closes_at, is_chat_enabled, is_comments_enabled
- [ ] Timezone inherited from group by default, overridable per event. Date/time input accepted in the event's timezone; backend converts to UTC for storage
- [ ] Save as draft or publish immediately; publishing sends `NewEvent` notification to group members via queue
- [ ] Creator auto-assigned as event host; slug generated from name (unique within group — collisions handled)
- [ ] Description rendered to HTML via MarkdownService on save
- [ ] Form Request validation; feature test (`tests/Feature/Events/CreateEventTest.php`): covers happy path, validation (missing required fields, invalid event_type), draft save, publish with notification, geocoding job dispatched for venue

#### US-066: Recurring Events
**Priority:** 66
**Description:** As an event organizer, I want to create recurring events so that I don't have to manually create each instance.

**Acceptance Criteria:**
- [ ] "Make this recurring" checkbox on event creation form
- [ ] Recurrence options: weekly, biweekly, monthly, custom RRULE string
- [ ] Creates `event_series` record with RRULE; generates individual event records for next 3 months via `EventSeriesService`
- [ ] Editing a recurring event prompts: "Edit this event only" or "Edit this and all future events"
- [ ] Cancelling: same prompt for single vs. all future
- [ ] Feature test (`tests/Feature/Events/RecurringEventTest.php`): covers series creation, correct number of instances generated, edit single, edit all future, cancel single, cancel all future

#### US-067: Event Page
**Priority:** 67
**Description:** As a visitor or member, I want to view an event page with all details, attendees, and interaction options.

**Acceptance Criteria:**
- [ ] Page at `/groups/{group_slug}/events/{event_slug}`
- [ ] Cover band with event-type dark accent color and decorative blobs
- [ ] Left column: date block, title, host name(s), CTA row (RSVP button, "Add to Calendar", "Share"), tab bar (Details, Attendees, Comments, Chat)
- [ ] Right sidebar: attendance card with progress bar, venue card with Leaflet map using OpenStreetMap tiles (for in_person — free, no API key), hosts card. All sidebar cards: neutral-100 bg, radius-xl, 16px padding
- [ ] Time displayed in event's timezone as primary (e.g., "Tuesday, March 24 at 18:30 CET"); if authenticated user's timezone differs, secondary line shows "9:30 AM your time (PST)"
- [ ] "Add to Calendar" generates .ics file download
- [ ] For hybrid events: show both venue and online link; let member choose attendance mode at RSVP
- [ ] Responsive: sidebar collapses below main content on tablet (<1024px); single column on mobile (<768px)
- [ ] SEO: `<title>` "{Event Name} · {Group Name} — {site_name}", meta description from first 160 chars of description (plain text), OG image from event cover or group cover or default, JSON-LD Event schema per spec section 15.5 (Event type, startDate, endDate, eventStatus, eventAttendanceMode, location, organizer, image, offers with price=0 and availability based on RSVP capacity)
- [ ] Feature test (`tests/Feature/Events/ViewEventTest.php`): covers page content, timezone display, sidebar data, cancelled event shows cancellation notice

#### US-068: RSVP Flow
**Priority:** 68
**Description:** As a group member, I want to RSVP to events so that organizers know I'm attending.

**Acceptance Criteria:**
- [ ] Livewire `RsvpButton` component: shows Going/Not Going/Join Waitlist based on event capacity
- [ ] RSVP only available if: user is group member, event is published, rsvp_opens_at has passed (if set), rsvp_closes_at has not passed (if set), event starts_at in future (or ends_at not passed if set), event not cancelled
- [ ] Going: optional guest count (up to event's guest_limit); for hybrid, choose attendance mode (in_person/online)
- [ ] Waitlisted: automatic when event is full; records waitlisted_at for FIFO ordering
- [ ] Changing Going to Not Going: frees spot, dispatches `PromoteFromWaitlist` queued job
- [ ] `RsvpConfirmation` notification sent on Going/Waitlisted
- [ ] Feature test (`tests/Feature/Events/RsvpTest.php`): covers going, not going, waitlist auto-assign, guest count, hybrid attendance mode, RSVP window enforcement (all 4 time conditions), non-member rejected, unverified rejected, cancelled event rejected

#### US-069: Waitlist Management
**Priority:** 69
**Description:** As a system, I need to automatically promote waitlisted members when spots open so that events fill to capacity.

**Acceptance Criteria:**
- [ ] When a Going RSVP is cancelled, `PromoteFromWaitlist` job runs `WaitlistService::promoteNext()`
- [ ] FIFO ordering by waitlisted_at; skips members whose guest_count + 1 exceeds available spots
- [ ] When multiple spots open simultaneously, promotes multiple members and revisits previously skipped members
- [ ] `PromotedFromWaitlist` notification sent to each promoted member
- [ ] No promotion for cancelled events or empty waitlists
- [ ] Feature test (`tests/Feature/Events/WaitlistTest.php`): covers FIFO ordering, guest skipping with next-eligible promotion, multi-spot opening revisits skipped members, promotion notification, no promotion on cancelled event

#### US-070: Attendee Management
**Priority:** 70
**Description:** As an event host or organizer, I want to manage attendees, check them in, and track attendance.

**Acceptance Criteria:**
- [ ] Page at `/groups/{slug}/events/{event_slug}/attendees` (host or event_organizer+)
- [ ] Tabs: Going, Waitlisted, Not Going with columns: name, guest count, checked-in status. Pagination: 20 per page
- [ ] Actions: manually change RSVP status, move waitlisted to Going (manual override bypassing queue), remove RSVP, check in individual attendees (sets checked_in=true, checked_in_at=now, checked_in_by=current user)
- [ ] After event: mark attendance (attended / no_show) per attendee
- [ ] Export attendee list as CSV (columns: name, RSVP status, guest count, checked-in)
- [ ] Feature test (`tests/Feature/Events/AttendeeManagementTest.php`) and (`tests/Feature/Events/CheckInTest.php`): cover all actions and authorization

#### US-071: Event Editing
**Priority:** 71
**Description:** As an event host or organizer, I want to edit event details after publication.

**Acceptance Criteria:**
- [ ] Edit form at `/groups/{slug}/events/{event_slug}/edit` (host or event_organizer+)
- [ ] Editable: name, description (re-renders description_html), date/time, venue (geocoded via job), online link, cover photo, RSVP settings
- [ ] Editing a published event sends `EventUpdated` notification to Going/Waitlisted members
- [ ] Editable up until 24 hours after ends_at; if no ends_at, up until 24 hours after starts_at
- [ ] Feature test (`tests/Feature/Events/EditEventTest.php`): covers edit, notification sent, editing window enforcement (allowed within window, rejected after)

#### US-072: Event Cancellation
**Priority:** 72
**Description:** As an event organizer, I want to cancel an event and notify all attendees.

**Acceptance Criteria:**
- [ ] POST cancel endpoint (event_organizer+); optional cancellation reason
- [ ] Sets status to `cancelled`, records cancelled_at and cancellation_reason
- [ ] Sends `EventCancelled` notification to Going/Waitlisted members
- [ ] RSVPs retained but inactive (no waitlist promotions); cancelled events shown in past events list marked as "Cancelled"
- [ ] Feature test (`tests/Feature/Events/CancelEventTest.php`): covers cancellation, notification, cancelled badge on past events list

#### US-073: Event Comments
**Priority:** 73
**Description:** As a group member, I want to comment on events to ask questions or share thoughts.

**Acceptance Criteria:**
- [ ] Threaded comments (Livewire `CommentThread` component) on event page: one level of nesting (parent + replies) when `is_comments_enabled`
- [ ] Markdown supported; rendered via MarkdownService
- [ ] Like/unlike a comment (toggles event_comment_likes record)
- [ ] Soft delete by author or co_organizer+
- [ ] Notifications: `NewEventComment` to hosts/Going members (web only), `EventCommentReply` to parent comment author (web + email), `EventCommentLiked` to comment author (web only)
- [ ] Pagination: 15 comments per page
- [ ] Feature test (`tests/Feature/Events/EventCommentsTest.php`): covers create, reply, like/unlike, delete own, leadership delete, comments disabled returns 403, notifications dispatched

#### US-074: Event Chat (Real-time)
**Priority:** 74
**Description:** As an event attendee, I want to chat in real-time with other attendees before and during the event.

**Acceptance Criteria:**
- [ ] Livewire `EventChat` component on chat tab when `is_chat_enabled`, powered by Laravel Reverb
- [ ] Auto-enrolled: members with RSVP Going; non-RSVP group members can view and optionally join
- [ ] Features: send message, reply to message (sets reply_to_id), edit own message, delete own message (soft delete)
- [ ] Messages broadcast in real-time via private channel `event.{eventId}.chat`
- [ ] Leadership (event_organizer+) can delete any message
- [ ] Non-owner cannot edit/delete another user's message (returns 403)
- [ ] Rate limited: 10 messages per 15 seconds per user per event via Laravel RateLimiter; 11th message returns 429
- [ ] Feature test (`tests/Feature/Events/EventChatTest.php`): covers send as RSVP'd member, send as non-RSVP group member who joined, chat disabled returns 403, non-group-member returns 403, reply (assert reply_to_id set), edit own, delete own (soft delete), leadership delete, non-owner edit/delete returns 403, rate limiting (11th message returns 429), broadcast event dispatched on send

#### US-075: Event Feedback
**Priority:** 75
**Description:** As an attendee, I want to leave feedback after an event so that organizers can improve.

**Acceptance Criteria:**
- [ ] Feedback available after event ends: determined by `ends_at`, or `starts_at + 3 hours` if no `ends_at`; only for users who RSVP'd Going
- [ ] Rating: 1-5 stars (required); written feedback: optional text
- [ ] One feedback per user per event (unique constraint)
- [ ] Organizer+ sees all feedback with attribution; members see aggregate only (average rating + count; individual feedback text is anonymous to non-organizers)
- [ ] `NewEventFeedback` notification to event host + Organizer+ (web only)
- [ ] Feature test (`tests/Feature/Events/EventFeedbackTest.php`): covers feedback submission, duplicate rejected, not-yet-ended event rejected, non-attendee rejected, organizer sees attributed feedback, member sees anonymous aggregate

#### US-076: Calendar Export (.ics)
**Priority:** 76
**Description:** As an attendee, I want to add an event to my calendar via an .ics file download.

**Acceptance Criteria:**
- [ ] GET `/groups/{slug}/events/{event_slug}/calendar` returns a valid .ics file
- [ ] Includes: event name as SUMMARY, description as plain text DESCRIPTION, start/end time (DTSTART/DTEND in UTC), LOCATION (venue address for in_person, online link for online), ORGANIZER (group name)
- [ ] Feature test (`tests/Feature/Events/EventCalendarExportTest.php`): validates .ics format, correct timestamps, location field

### Phase 8: Discussions

#### US-077: Discussion Threads
**Priority:** 77
**Description:** As a group member, I want to create and browse discussion threads to communicate with other members.

**Acceptance Criteria:**
- [ ] Discussions tab on group page lists threads ordered by pinned first, then last_activity_at descending
- [ ] Create discussion form: title, body (markdown, rendered to body_html via MarkdownService)
- [ ] Slug auto-generated from title (unique within group — collisions handled)
- [ ] `NewDiscussion` notification to group members (web only)
- [ ] Pagination: 15 per page, standard pagination
- [ ] Feature test (`tests/Feature/Discussions/CreateDiscussionTest.php`): covers creation, slug generation, pinned discussions sort first, notification dispatched, pagination

#### US-078: Discussion Replies
**Priority:** 78
**Description:** As a group member, I want to reply to discussions to participate in the conversation.

**Acceptance Criteria:**
- [ ] Flat chronological replies (no nested replies — Livewire `DiscussionThread` component)
- [ ] Markdown supported; replying updates discussion's `last_activity_at` to now
- [ ] Cannot reply to a locked discussion
- [ ] `NewDiscussionReply` notification to discussion author + previous repliers (web + email)
- [ ] Pagination: 20 replies per page, standard pagination
- [ ] Feature test (`tests/Feature/Discussions/DiscussionRepliesTest.php`): covers reply, last_activity_at updated, locked discussion rejected, notification recipients correct

#### US-079: Discussion Moderation
**Priority:** 79
**Description:** As a co-organizer, I want to pin, lock, and delete discussions to moderate group content.

**Acceptance Criteria:**
- [ ] Pin/unpin discussion (co_organizer+)
- [ ] Lock/unlock discussion — prevents new replies (co_organizer+)
- [ ] Delete discussion (soft delete, co_organizer+)
- [ ] Authors can delete their own replies (soft delete); co_organizer+ can delete any reply
- [ ] Feature test (`tests/Feature/Discussions/DiscussionModerationTest.php`): covers pin/unpin, lock/unlock (verify replies blocked), delete by author, delete by leadership, member cannot pin/lock

### Phase 9: Direct Messages

#### US-080: Starting a Conversation
**Priority:** 80
**Description:** As a verified user, I want to send direct messages to other users.

**Acceptance Criteria:**
- [ ] "Message" button on user profile creates or reopens a 1:1 conversation (if existing conversation between two users, reopen it)
- [ ] Creates conversation + two conversation_participants if new
- [ ] Cannot message blocked users (returns error)
- [ ] DM rate limit: 20 messages per minute per user; 21st message returns 429
- [ ] Feature test (`tests/Feature/Messages/ConversationTest.php`): covers start new, reopen existing, blocked user rejected, rate limiting

#### US-081: Conversation View
**Priority:** 81
**Description:** As a user, I want to view my conversations and read/send messages in real-time.

**Acceptance Criteria:**
- [ ] Conversation list at `/messages` sorted by most recent message. Pagination: 20 conversations per page. Unread indicator based on `last_read_at` vs latest message timestamp
- [ ] Conversation thread at `/messages/{conversation}` with Livewire `ConversationView` component: message history, sender name, avatar, timestamp
- [ ] Real-time updates via Reverb on private channel `conversation.{conversationId}`
- [ ] `NewDirectMessage` notification sent (web + email) — respects conversation mute setting
- [ ] Cursor pagination: 30 messages, load older on scroll up
- [ ] Soft delete own messages
- [ ] Feature test (`tests/Feature/Messages/DirectMessageTest.php`): covers send, receive, unread indicator, muted conversation suppresses notification, soft delete

#### US-082: Blocking Users
**Priority:** 82
**Description:** As a user, I want to block another user so they cannot contact me or see my profile.

**Acceptance Criteria:**
- [ ] POST `/members/{id}/block` creates a block record
- [ ] Blocked user cannot: send DMs to blocker, see blocker's profile (403), trigger notifications to blocker
- [ ] Existing conversation hidden for both users when blocked
- [ ] DELETE `/members/{id}/block` unblocks; conversation reappears but messages sent during blocked period are not retroactively shown
- [ ] Feature test (`tests/Feature/Messages/BlockingTest.php`): covers block, DM rejected, profile hidden, unblock, conversation reappears

### Phase 10: Search & Discovery

#### US-083: Explore Page
**Priority:** 83
**Description:** As a user, I want to browse and filter upcoming events to find things to attend.

**Acceptance Criteria:**
- [ ] Page at `/explore` (also serves as homepage for guests)
- [ ] Header: "Events near [location]" with search bar and rounded filter chips (pill-shaped)
- [ ] Filters: topic/interest, date range, event type (in_person/online/hybrid), distance radius (default 50km)
- [ ] Results: event card grid — 2-column featured (top), 3-column rest. Cursor pagination: 12 per page with infinite scroll via Livewire
- [ ] Online events shown in a separate section, not filtered by location
- [ ] Guest: popular events (most RSVPs); with browser geolocation, nearby events via Geocodio reverse geocode
- [ ] Authenticated with location: nearby events matching interests first, then group events not yet RSVP'd, then popular
- [ ] Authenticated without location: group events first, then popular; prompt to set location
- [ ] SEO: `<title>` "Explore Events — {site_name}", meta description "Discover local meetups, events, and community groups near you."
- [ ] Feature test (`tests/Feature/Discovery/ExplorePageTest.php`): covers event listing, filtering, authenticated with/without location logic

#### US-084: Group Search
**Priority:** 84
**Description:** As a user, I want to search and filter groups to find communities to join.

**Acceptance Criteria:**
- [ ] Page at `/groups` with search bar and filter controls
- [ ] Search by name, description keywords via Scout (field weights: name=high, description=medium, location=low)
- [ ] Filter by topic/interest, location/distance
- [ ] Sort by: relevance, newest, most members, most active (recent events)
- [ ] Cursor pagination: 12 per page
- [ ] SEO: `<title>` "Browse Groups — {site_name}"
- [ ] Feature test (`tests/Feature/Groups/GroupSearchTest.php`): covers search, filters, sort options

#### US-085: Global Search
**Priority:** 85
**Description:** As a user, I want a global search bar in the navbar to quickly find groups, events, or members.

**Acceptance Criteria:**
- [ ] Livewire `GlobalSearch` component in navbar; results page at `/search`
- [ ] Searches across Group (name, description), Event (name, description), User (name, bio — public profiles only)
- [ ] Powered by Laravel Scout with Meilisearch (database driver fallback)
- [ ] Results grouped by type with relevance ranking
- [ ] SEO: `<title>` "Search: \"{query}\" — {site_name}"
- [ ] Feature test (`tests/Feature/Discovery/GlobalSearchTest.php`): covers cross-model search, public-only user filtering, results grouping

#### US-086: Location-Based Discovery
**Priority:** 86
**Description:** As a user with a location, I want events sorted by distance so I see nearby events first.

**Acceptance Criteria:**
- [ ] Haversine formula in database scope (`scopeNearby`) on Group and Event models per spec section 8.2 — works on MySQL and PostgreSQL without extensions
- [ ] Default radius: 50km, adjustable by user
- [ ] Events inherit location from venue (in_person) or group (fallback when venue lat/lng is null)
- [ ] Online events shown in a separate section, not filtered by location
- [ ] Feature test (`tests/Feature/Discovery/NearbyEventsTest.php`): covers events within radius returned, events outside radius excluded, online events in separate section, null lat/lng handled gracefully

#### US-087: Dashboard & Recommendations
**Priority:** 87
**Description:** As a logged-in user, I want a dashboard showing my upcoming events, groups, suggestions, and notifications.

**Acceptance Criteria:**
- [ ] Page at `/dashboard` (authenticated home); guests see `/explore` instead
- [ ] Sections: Upcoming Events (RSVP Going, sorted by date), Your Groups (with next event per group), Suggested Events (events in user's groups not yet RSVP'd + events in interest-matching groups within location radius, ordered by starts_at soonest), Recent Notifications (unread)
- [ ] SEO: `<title>` "Dashboard — {site_name}"
- [ ] Feature test: covers data display, empty states, recommendation query correctness

### Phase 11: Notifications

#### US-088: Notification Types Implementation
**Priority:** 88
**Description:** As a developer, I need all notification classes from the spec implemented so that users receive timely, relevant notifications.

**Acceptance Criteria:**
- [ ] All 22 notification types implemented as Laravel Notification classes: WelcomeToGroup (web+email), JoinRequestReceived (web+email), JoinRequestApproved (web+email), JoinRequestDenied (web+email), NewEvent (web+email), EventUpdated (web+email), EventCancelled (web+email), RsvpConfirmation (web+email), PromotedFromWaitlist (web+email), NewEventComment (web), EventCommentReply (web+email), EventCommentLiked (web), NewEventFeedback (web), NewDiscussion (web), NewDiscussionReply (web+email), NewDirectMessage (web+email), MemberRemoved (web+email), MemberBanned (web+email), RoleChanged (web+email), OwnershipTransferred (web+email), GroupDeleted (email only), ReportReceived (web+email), AccountSuspended (email only)
- [ ] Each notification: correct `via()` channels matching the above, `toMail()` with appropriate subject/content, `toArray()` for database/web channel with link to relevant content
- [ ] Dispatched via NotificationService to respect mutes, blocks, preferences, and batching
- [ ] Feature tests (`tests/Feature/Notifications/EventNotificationsTest.php`, `GroupNotificationsTest.php`, `MessageNotificationsTest.php`): verify dispatch for each notification type with correct recipients and channels

#### US-089: Notification Bell & Web Notifications
**Priority:** 89
**Description:** As a user, I want to see unread notifications in a bell icon dropdown with real-time updates.

**Acceptance Criteria:**
- [ ] Livewire `NotificationDropdown` component: bell icon in navbar with unread count badge
- [ ] Dropdown shows 10 most recent notifications with "load more" button
- [ ] Each notification: icon, message, timestamp, link to relevant content
- [ ] Mark as read on click; "Mark all as read" button
- [ ] Real-time count updates via Reverb on private channel `user.{userId}.notifications`

#### US-090: Notification Digest Batching
**Priority:** 90
**Description:** As a system, I need to batch high-frequency email notifications into digests so users aren't flooded.

**Acceptance Criteria:**
- [ ] When 5+ of the same notification type fire for the same recipient within 15 minutes, the 5th and subsequent are stored in `pending_notification_digests` (columns: user_id, notification_type, data as JSON, created_at) instead of sending individual emails
- [ ] Fewer than 5 notifications of same type in 15 minutes: each sends individual email normally
- [ ] `notifications:send-digests` scheduled command (every 5 minutes): groups pending items by (user_id, notification_type), renders a single digest email per group, sends it, deletes the pending records
- [ ] Web (in-app) notifications are NEVER batched — always fire individually regardless of frequency
- [ ] Feature test (`tests/Feature/Notifications/NotificationDigestTest.php`): covers 4 notifications send individually, 5th triggers batching, digest command groups and sends, pending records deleted after send, web notifications not batched

#### US-091: Group Notification Muting
**Priority:** 91
**Description:** As a user, I want to mute notifications from a specific group.

**Acceptance Criteria:**
- [ ] Mute/unmute toggle on group page for members
- [ ] Creates/deletes `group_notification_mutes` record
- [ ] Muted groups suppress non-critical notifications; critical notifications (PromotedFromWaitlist, MemberRemoved, etc.) still delivered
- [ ] Feature test (`tests/Feature/Notifications/NotificationMutingTest.php`): covers mute suppresses NewEvent, mute does NOT suppress PromotedFromWaitlist

### Phase 12: Reporting & Content Moderation

#### US-092: Content Reporting
**Priority:** 92
**Description:** As a user, I want to report inappropriate content so that admins can review it.

**Acceptance Criteria:**
- [ ] Report button/option on: user profiles, groups, events, event comments, discussions, discussion replies, chat messages
- [ ] Report form: select reason (spam, harassment, hate_speech, impersonation, inappropriate_content, misleading, other), optional description text
- [ ] One active report per reporter per item (unique constraint on reporter + reportable — prevents spam reports)
- [ ] Reporter receives confirmation; `ReportReceived` notification sent to all platform admins (web + email)
- [ ] Feature test (`tests/Feature/Reporting/ReportContentTest.php`): covers report creation, duplicate rejected, notification to admins

### Phase 13: Admin Panel

#### US-093: Admin Dashboard
**Priority:** 93
**Description:** As a platform admin, I want a dashboard with platform statistics and quick actions.

**Acceptance Criteria:**
- [ ] Page at `/admin` (admin role required via spatie/laravel-permission; non-admin returns 403)
- [ ] Stats: total users, total groups, total events, events this month, new users this week
- [ ] Recent reports needing review, recently created groups
- [ ] Quick links to manage users, groups, reports, settings, interests
- [ ] SEO: `<title>` "Admin: Dashboard — {site_name}"
- [ ] Feature test (`tests/Feature/Admin/AdminDashboardTest.php`): covers access (admin allowed, regular user 403), stats accuracy

#### US-094: Admin User Management
**Priority:** 94
**Description:** As a platform admin, I want to search, view, suspend, and delete users.

**Acceptance Criteria:**
- [ ] Page at `/admin/users` with searchable/filterable list. Pagination: 25 per page
- [ ] View user details, groups they are in, events attended
- [ ] Suspend: sets is_suspended=true, suspended_at=now, suspended_reason; sends `AccountSuspended` email notification
- [ ] Unsuspend: clears suspension fields
- [ ] Delete user (hard delete with confirmation)
- [ ] Feature test (`tests/Feature/Admin/AdminUserManagementTest.php`): covers suspend/unsuspend, delete, search, non-admin rejected

#### US-095: Admin Group Management
**Priority:** 95
**Description:** As a platform admin, I want to view and delete groups.

**Acceptance Criteria:**
- [ ] Page at `/admin/groups` with searchable/filterable list. Pagination: 25 per page
- [ ] View group details; delete group (hard delete with confirmation)
- [ ] Feature test (`tests/Feature/Admin/AdminGroupManagementTest.php`): covers listing, delete, non-admin rejected

#### US-096: Admin Report Management
**Priority:** 96
**Description:** As a platform admin, I want to review, resolve, and act on content reports.

**Acceptance Criteria:**
- [ ] Page at `/admin/reports` with pending reports sorted by newest. Pagination: 25 per page
- [ ] Each report: reporter, reported item (linked to content), reason, description, date
- [ ] If an item has multiple reports, show them grouped with count
- [ ] Actions: mark as reviewed, resolve (with resolution_notes), dismiss
- [ ] Direct actions from report view: suspend user, delete group/event/comment
- [ ] Status transitions: pending -> reviewed -> resolved/dismissed
- [ ] Feature test (`tests/Feature/Admin/AdminReportManagementTest.php`): covers review, resolve, dismiss, direct suspend, grouped view

#### US-097: Admin Interest/Topic Management
**Priority:** 97
**Description:** As a platform admin, I want to manage the interest taxonomy.

**Acceptance Criteria:**
- [ ] Page at `/admin/interests` with CRUD for interest tags
- [ ] Merge duplicate interests (reassign all taggable relationships to target tag, delete source tag)
- [ ] Usage count per interest (number of groups + users using it)
- [ ] Feature test (`tests/Feature/Admin/AdminInterestManagementTest.php`): covers CRUD, merge, usage count

#### US-098: Admin Platform Settings
**Priority:** 98
**Description:** As a platform admin, I want to configure platform-wide settings.

**Acceptance Criteria:**
- [ ] Page at `/admin/settings` with configurable settings: site_name (default "Greetup"), site_description (tagline for the instance), registration_enabled (toggle, default true), require_email_verification (toggle, default true), max_groups_per_user (nullable, default null=unlimited), default_timezone (IANA identifier), default_locale
- [ ] Stored in `settings` table (key/value); cached for performance (cache invalidated on update)
- [ ] Site name used in page titles, OG tags, and email templates
- [ ] Feature test (`tests/Feature/Admin/AdminSettingsTest.php`): covers update, cache invalidation, non-admin rejected

### Phase 14: Error Pages & SEO

#### US-099: Custom Error Pages
**Priority:** 99
**Description:** As a user, I want friendly error pages that match the Greetup design instead of generic Laravel defaults.

**Acceptance Criteria:**
- [ ] Create custom error pages per spec section 14, all sharing common layout: centered content, neutral-50 background, large decorative blob (green-500, opacity 0.06), error code (44px, weight 500, neutral-400), headline (22px, weight 500, neutral-900), body text (16px, neutral-500), primary CTA button, navbar remains visible
- [ ] `resources/views/errors/403.blade.php`: "You don't have access to this page" / "You might need to join this group or have a different role to view this content." / CTA "Go to Explore" -> `/explore`
- [ ] `resources/views/errors/404.blade.php`: "We couldn't find that page" / "The page you're looking for might have been moved, deleted, or never existed." / CTA "Go to Explore" -> `/explore`
- [ ] `resources/views/errors/419.blade.php`: "This page has expired" / "Your session timed out. Please go back and try again." / CTA "Go back" -> `javascript:history.back()`
- [ ] `resources/views/errors/429.blade.php`: "Slow down" / "You're making requests too quickly. Please wait a moment and try again." / CTA "Go to Explore" -> `/explore`
- [ ] `resources/views/errors/500.blade.php`: "Something went wrong" / "We hit an unexpected error. If this keeps happening, please let the site administrator know." / CTA "Go to homepage" -> `/`
- [ ] `resources/views/errors/503.blade.php`: "We'll be right back" / "Greetup is undergoing maintenance. Please check back shortly." / No CTA — page auto-refreshes after 60 seconds via meta refresh tag

#### US-100: Suspended Account Page
**Priority:** 100
**Description:** As a suspended user, I need to see a clear explanation of my suspension instead of the normal app.

**Acceptance Criteria:**
- [ ] `resources/views/auth/suspended.blade.php`: uses error page layout but with red-500 accent
- [ ] Headline: "Your account has been suspended"
- [ ] Body: displays `suspended_reason` from the user model
- [ ] Minimal nav: only Greetup logo and logout link
- [ ] Optional "Contact support" link (configurable via platform settings)

#### US-101: SEO Implementation
**Priority:** 101
**Description:** As a developer, I need proper page titles, meta descriptions, and canonical URLs on all public pages.

**Acceptance Criteria:**
- [ ] Page title format per spec section 15.1: Homepage "{site_name} — Find your people", Explore "Explore Events — {site_name}", Group page "{Group Name} — {site_name}", Event page "{Event Name} · {Group Name} — {site_name}", User profile "{User Name} — {site_name}", Dashboard "Dashboard — {site_name}", Search "Search: \"{query}\" — {site_name}", Group search "Browse Groups — {site_name}", Admin pages "Admin: {Section} — {site_name}", Error pages "{Error Code} — {site_name}"
- [ ] Meta descriptions per spec section 15.2: Homepage from site_description setting (fallback: "A free, open source community events platform."), Group/Event first 160 chars of description (plain text), Profile first 160 chars of bio (fallback: "{Name} is a member of {site_name}."), Explore/Search static text
- [ ] Canonical URLs on all public pages (clean URL without pagination/filter query params)
- [ ] Default OG image: branded 1200x630 image at `public/images/og-default.png` (green-900 background, Greetup logo, decorative blobs)
- [ ] OG image selection per spec section 15.3: Event page -> event cover > group cover > default; Group page -> group cover > default; Profile -> avatar > default

#### US-102: JSON-LD Structured Data for Events
**Priority:** 102
**Description:** As a developer, I need Schema.org Event structured data on event pages for search engine rich results.

**Acceptance Criteria:**
- [ ] Event pages include `<script type="application/ld+json">` with Schema.org Event per spec section 15.5
- [ ] Fields: name, description (plain text, max 300 chars), startDate/endDate (ISO 8601), eventStatus (EventScheduled or EventCancelled), eventAttendanceMode (OfflineEventAttendanceMode for in_person, OnlineEventAttendanceMode for online, MixedEventAttendanceMode for hybrid), location (Place for in_person with address, VirtualLocation for online), organizer (Organization with group name and URL), image, offers (price=0, priceCurrency=USD, availability: InStock if spots available, SoldOut if full, PreOrder if RSVP not yet open)

### Phase 15: Homepage

#### US-103: Homepage (Unauthenticated)
**Priority:** 103
**Description:** As a visitor, I want a compelling homepage that explains Greetup and encourages me to sign up.

**Acceptance Criteria:**
- [ ] Hero section with multi-line colored headline: "Find your" [green-500]"people."[/] / "Do the" [coral-500]"thing."[/] / "Keep" [violet-500]"showing up."[/] — Display size (44px, weight 500, letter-spacing -0.03em)
- [ ] Subtitle text, CTA buttons ("Get started" primary, "Explore events" secondary), three decorative blobs at low opacity in background
- [ ] Stat cards (coral, violet, gold) anchored bottom-right of hero showing live platform stats (total groups, total events, total members)
- [ ] Popular interests pill cloud below hero
- [ ] Upcoming events preview grid below
- [ ] SEO: `<title>` "{site_name} — Find your people"
- [ ] Authenticated users redirected to `/dashboard` instead

### Phase 16: Scheduled Commands & Artisan

#### US-104: Scheduled Commands
**Priority:** 104
**Description:** As a developer, I need all scheduled commands implemented and registered in the Laravel scheduler.

**Acceptance Criteria:**
- [ ] `events:generate-recurring` (daily): generates next batch of recurring event instances from event_series RRULE strings, up to 3 months ahead. Skips series that already have sufficient future events
- [ ] `events:mark-past` (hourly): transitions events whose ends_at (or starts_at + 3 hours if no ends_at) has passed from `published` to `past` status
- [ ] `accounts:purge-deleted` (daily): hard-deletes user records where deleted_at is more than 30 days ago. Cascades to related records
- [ ] `groups:purge-deleted` (daily): hard-deletes group records where deleted_at is more than 90 days ago. Cascades to related records
- [ ] `notifications:send-digests` (every 5 minutes): groups pending_notification_digests by (user_id, notification_type), sends one digest email per group, deletes pending records
- [ ] All commands registered in `app/Console/Kernel.php` (or `routes/console.php`) with correct frequency
- [ ] Feature tests for each command verify correct behavior (e.g., purge only past-grace-period records, mark-past transitions correct events)

#### US-105: Utility Artisan Commands
**Priority:** 105
**Description:** As a developer/admin, I need utility commands for setup, geocoding, and stats.

**Acceptance Criteria:**
- [ ] `greetup:install`: interactive first-time setup wizard — create admin user, set site name, optionally configure Geocodio API key. Uses `--no-interaction` safe defaults when run non-interactively
- [ ] `greetup:geocode-missing`: batch geocode any groups/events/users with addresses but missing lat/lng coordinates. Useful after adding a Geocodio API key to an existing instance. Skips if no API key configured
- [ ] `greetup:stats`: prints platform statistics to console (total users, groups, events, active events this month, etc.)

### Phase 17: WebSocket Channels & Broadcasting

#### US-106: WebSocket Channel Definitions
**Priority:** 106
**Description:** As a developer, I need the three private WebSocket channels defined and authorized for real-time features.

**Acceptance Criteria:**
- [ ] `routes/channels.php` defines three private channels per spec section 6.6:
  - `event.{eventId}.chat` — authorized for group members of the event's group
  - `conversation.{conversationId}` — authorized for conversation participants only
  - `user.{userId}.notifications` — authorized for the user themselves only
- [ ] Reverb configured as broadcast driver via `config/broadcasting.php`
- [ ] Feature test: verifies channel authorization (allowed user passes, unauthorized user rejected)

### Phase 18: Seed Data & CI

#### US-107: Complete Seed Data
**Priority:** 107
**Description:** As a developer, I need realistic seed data so that I can demo and test the full application.

**Acceptance Criteria:**
- [ ] All seeders from spec section 9.1: DatabaseSeeder orchestrates InterestSeeder, UserSeeder, GroupSeeder, EventSeeder, RsvpSeeder, DiscussionSeeder, EventCommentSeeder, EventFeedbackSeeder, DirectMessageSeeder, ReportSeeder, SettingsSeeder
- [ ] Users (50): admin@greetup.test (admin, password: "password"), user@greetup.test (regular, password: "password"), 8 organizers (named realistically, diverse locations), 40 regular users (mix of active/less active). All have verified emails, profile bios, 3-8 interests, locations spread across Copenhagen, Berlin, London, NYC
- [ ] Groups (8) per spec table: Copenhagen Laravel Meetup (tech, 35 members, open), Berlin JavaScript Community (tech, 28, open), London Book Club (lifestyle, 20, requires approval with 2-3 questions), NYC Hiking Adventures (lifestyle, 25, open), Copenhagen Photography Walks (creative, 15, open), Remote Workers Denmark (professional, 22, open), Board Game Nights CPH (lifestyle, 18, open), Women in Tech Berlin (tech, 20, requires approval). Each with realistic markdown descriptions, 2-5 interests, leadership team, welcome message for approval groups
- [ ] Events (40+) per group: 2-3 past (1-6 months ago, with attendance marked + feedback), 2-3 upcoming (next 1-4 weeks), 1 draft, 1 cancelled, 1 recurring series (4+ instances). Realistic titles, markdown descriptions, real venue addresses, mix of with/without RSVP limits, at least 2 events at capacity with waitlists
- [ ] RSVPs (200+): distributed so some events are lightly attended, some full. At least 2 with waitlisted members. Past events have attended/no_show marked for ~80% of Going RSVPs. Some with guests
- [ ] Discussions (15+): 2-3 per active group, 3-10 replies each, 1 pinned per group, 1 locked in largest group
- [ ] Comments (50+): 3-8 on upcoming events, some with replies, some with likes
- [ ] Feedback (30+): on past events, mostly 4-5 stars, ~50% with written text
- [ ] DMs (10 conversations): 2-5 messages each, mix of read/unread
- [ ] Reports (5): 3 pending, 1 resolved, 1 dismissed
- [ ] Settings: default platform settings (site_name="Greetup", registration_enabled=true, etc.)
- [ ] All locations use hardcoded lat/lng coordinates — NO Geocodio API key required for seeding
- [ ] Idempotent seeders (firstOrCreate or truncate-and-reseed)
- [ ] `php artisan migrate:fresh --seed` produces a fully functional demo instance

#### US-108: CI Environment Files
**Priority:** 108
**Description:** As a developer, I need CI-specific environment files so tests run correctly in GitHub Actions.

**Acceptance Criteria:**
- [ ] `.env.ci.mysql`: APP_ENV=testing, DB_CONNECTION=mysql, DB_HOST=127.0.0.1, DB_DATABASE=greetup_test, DB_USERNAME=root, DB_PASSWORD=password, QUEUE_CONNECTION=sync, MAIL_MAILER=array, CACHE_STORE=array, SESSION_DRIVER=array, SCOUT_DRIVER=collection, BROADCAST_CONNECTION=null, GEOCODIO_API_KEY= (empty)
- [ ] `.env.dusk.ci`: same as above but APP_URL=http://127.0.0.1:8000, DB_DATABASE=greetup_dusk, BROADCAST_CONNECTION=reverb with Reverb config (host=127.0.0.1, port=8080)
- [ ] All geocoding tests use mocked GeocodingService — zero third-party API keys needed in CI

#### US-109: GitHub Actions CI Workflow
**Priority:** 109
**Description:** As a developer, I need a CI pipeline that runs all quality checks on every push and PR.

**Acceptance Criteria:**
- [ ] `.github/workflows/ci.yml` with three jobs per spec section 11.1:
  - `lint`: Pint code style check (`vendor/bin/pint --test`) + Larastan static analysis (`vendor/bin/phpstan analyse --memory-limit=512M`)
  - `test`: MySQL 8.0 service container, PHP 8.5, runs `vendor/bin/pest --parallel --coverage --min=90`. Uses `.env.ci.mysql`. Uploads coverage report artifact
  - `dusk`: MySQL 8.0 service, PHP 8.5, Node.js, npm ci + npm run build, seed database, install Chrome driver, start Reverb + artisan serve in background, run `php artisan dusk`. Uses `.env.dusk.ci`. Uploads screenshots/console on failure
- [ ] `test` and `dusk` jobs run in parallel (both depend on `lint` or run independently)
- [ ] All tests against MySQL — no SQLite test matrix
- [ ] Triggered on push to main and pull requests to main

### Phase 19: Documentation

#### US-110: README.md
**Priority:** 110
**Description:** As a developer or self-hoster, I need a comprehensive README with setup instructions.

**Acceptance Criteria:**
- [ ] `README.md` content per spec section 12: features list, requirements, Quick Start with Docker (step-by-step Sail setup), Manual Installation (without Docker), Running Tests, Configuration (Geocodio, DB, Mail, Search, Storage, Queue, WebSocket), Deployment (production checklist, scheduled commands table with frequencies, example Nginx config with WebSocket proxy, Supervisor configs for queue worker and Reverb), Contributing link, License
- [ ] Demo accounts table: admin@greetup.test / password, user@greetup.test / password
- [ ] Useful Sail commands reference

#### US-111: CONTRIBUTING.md
**Priority:** 111
**Description:** As a potential contributor, I need guidelines for contributing to the project.

**Acceptance Criteria:**
- [ ] `CONTRIBUTING.md` content per spec section 13: Getting Started, Branch Naming (feature/, fix/, refactor/, docs/, test/), Making Changes guidelines, Before Submitting a PR (pint, phpstan, pest), Commit Messages (imperative mood, short summary, optional why explanation), Pull Request Process, Reporting Bugs, Code of Conduct

### Phase 20: Pagination & Cross-Cutting

#### US-112: Pagination Configuration
**Priority:** 112
**Description:** As a developer, I need all list views paginated with the correct items-per-page and pagination style per the spec.

**Acceptance Criteria:**
- [ ] Per spec section 5.9.3, verify each view uses the correct pagination:
  - Explore page (events): 12 items, cursor pagination (infinite scroll via Livewire)
  - Group search results: 12 items, cursor pagination
  - Event attendee list: 20 items, standard pagination
  - Group member list: 20 items, standard pagination
  - Discussion list: 15 items, standard pagination
  - Discussion replies: 20 items, standard pagination
  - Event comments: 15 items, standard pagination
  - DM conversation list: 20 items, standard pagination
  - DM messages within conversation: 30 items, cursor pagination (load older on scroll)
  - Admin user/group/report lists: 25 items, standard pagination
  - Notification dropdown: 10 items, load more button

#### US-113: Timezone Handling
**Priority:** 113
**Description:** As a developer, I need consistent timezone handling across the application.

**Acceptance Criteria:**
- [ ] All timestamps stored in UTC in the database
- [ ] Event page: time displayed in event's timezone as primary (e.g., "Tuesday, March 24 at 18:30 CET"); if authenticated user's timezone differs, secondary line shows the time in the user's local timezone (e.g., "9:30 AM your time (PST)")
- [ ] Dashboard and explore page: event times shown in user's timezone (or instance default_timezone setting for guests)
- [ ] Event creation/editing forms: date/time input accepted in the event's timezone (inherited from group, overridable); backend converts to UTC for storage
- [ ] All timezone fields use IANA identifiers (e.g., `Europe/Copenhagen`, `America/New_York`)

#### US-114: Image Upload Handling
**Priority:** 114
**Description:** As a developer, I need consistent image upload handling across avatars, group covers, and event covers.

**Acceptance Criteria:**
- [ ] All uploads via spatie/laravel-medialibrary with intervention/image for resizing:
  - Avatar: max 2MB, JPEG/PNG/WebP, thumbnails 44x44 (nav), 96x96 (profile card), 256x256 (profile page)
  - Group cover: max 5MB, JPEG/PNG/WebP, conversions 400x200 (card), 1200x400 (group page header)
  - Event cover: max 5MB, JPEG/PNG/WebP, conversions 400x200 (card), 1200x400 (event page header)
- [ ] Original files stored but never served directly — only generated conversions are public
- [ ] Exceeding size limit returns 422 validation error; non-image/unsupported format returns 422
- [ ] Feature test (`tests/Feature/Profile/ImageUploadTest.php`): covers valid upload (stored via medialibrary), oversized returns 422, non-image returns 422, group cover conversions generated, event cover conversions generated

### Phase 21: Browser Tests

#### US-115: Browser Tests (Dusk)
**Priority:** 115
**Description:** As a developer, I need Dusk browser tests verifying complete user flows with JavaScript and Livewire interactions.

**Acceptance Criteria:**
- [ ] Auth flows (`tests/Browser/Auth/`): Registration (fill form, submit, see verification notice, verify email, redirected to dashboard), Login (fill form, submit, see dashboard with user name), Password Reset (request link, follow link, set new password, login)
- [ ] Group flows (`tests/Browser/Groups/`): Browse groups (visit /groups, see list, filter by interest, click into group), Join group (click Join, see confirmation, appear in member list), Approval flow (request, answer questions, simulate approval, access group), Create group (fill all fields, set topics, publish, see group page), Manage group (navigate management pages, change settings)
- [ ] Event flows (`tests/Browser/Events/`): Browse events (visit explore, see events, filter, click into event), RSVP flow (view event, click RSVP Going, see confirmation, appear in attendee list, change to Not Going, re-RSVP), Create event (fill form, publish, see event page, edit, see updates), Event chat (open chat tab, send message, see it appear — tests WebSocket via Reverb), Feedback (leave rating on past event)
- [ ] Discovery flows (`tests/Browser/Discovery/`): Explore page search/filter, Global search across models
- [ ] Message flows (`tests/Browser/Messages/`): Visit profile, click Message, send message, see in conversation list
- [ ] Admin flows (`tests/Browser/Admin/`): Login as admin, see dashboard with stats, review report, resolve it
- [ ] All browser tests use MySQL via DatabaseMigrations (migrates fresh per test class)

## Functional Requirements

- FR-0: Development environment fully operational via Laravel Sail / Docker with MySQL 8.0, Redis, Meilisearch, Mailpit, Reverb, and queue worker before any feature work begins. All required Composer packages and frontend dependencies installed and verified
- FR-1: Registration requires name, email (unique), password (min 8), and email verification before group/RSVP access
- FR-2: Groups support public/private visibility and optional approval-required joining with membership questions
- FR-3: Group roles (member, event_organizer, assistant_organizer, co_organizer, organizer) are stored on the pivot table with hierarchy values (0-4) and enforce the permission matrix from spec section 3.4; higher roles inherit all lower role permissions
- FR-4: Events support three types (in_person, online, hybrid) with type-specific fields and four statuses (draft, published, cancelled, past)
- FR-5: RSVPs enforce capacity limits (accounting for member + guests), auto-waitlist when full with FIFO ordering by waitlisted_at, and promotion when spots open (skipping members with too many guests, revisiting when more spots open)
- FR-6: Event Chat uses Laravel Reverb for real-time messaging on private channel `event.{eventId}.chat` with rate limiting (10 messages/15 seconds/user/event; 429 on exceed)
- FR-7: Direct messages are 1:1 only, real-time via Reverb on private channel `conversation.{conversationId}`, with muting and blocking support; rate limited to 20/minute
- FR-8: All user-supplied markdown rendered via MarkdownService with `DisallowedRawHtmlExtension` (strips dangerous HTML), `rel="nofollow noopener" target="_blank"` on links; pre-rendered to `*_html` columns on save
- FR-9: Location features use Geocodio for geocoding (queued job, 3 retries with exponential backoff, graceful degradation) and Haversine formula for proximity queries (works on MySQL and PostgreSQL without extensions)
- FR-10: Full-text search via Laravel Scout (Meilisearch primary, database driver fallback) with field weighting (name=high, description=medium, location/bio=low)
- FR-11: All 22 notification types dispatch via NotificationService respecting group mutes (with critical notification exemptions), user blocks, per-type preferences, and email digest batching (5+ same type in 15 min)
- FR-12: Admin panel provides user/group/report management, interest taxonomy CRUD with merge, and platform settings (cached, invalidated on update)
- FR-13: Image uploads use spatie/laravel-medialibrary with exact conversion sizes per entity type, size/format validation at 422
- FR-14: All list views paginated per spec section 5.9.3 with exact items-per-page and pagination style (cursor vs standard) per view
- FR-15: Recurring events use RRULE strings stored in event_series, generate instances 3 months ahead via scheduled command, support single/bulk editing and cancellation
- FR-16: Five scheduled commands run at specified frequencies: events:generate-recurring (daily), events:mark-past (hourly), accounts:purge-deleted (daily, 30-day grace), groups:purge-deleted (daily, 90-day grace), notifications:send-digests (every 5 min)
- FR-17: Three private WebSocket channels with proper authorization in routes/channels.php
- FR-18: Custom error pages (403, 404, 419, 429, 500, 503) and suspended account page matching the Greetup design system
- FR-19: SEO meta tags (title, description, OG, Twitter Card, canonical URL) on all public pages with JSON-LD Event structured data on event pages

## Non-Goals

- No payment processing, ticketing, or paid tiers
- No native video conferencing (link to external tools)
- No mobile apps (web-first, responsive design; PWA consideration for v2)
- No federation / ActivityPub
- No dark mode (deferred to v2; color system designed for easy retrofit per spec section 1A.9)
- No separate JSON API (all interactivity via Livewire and form submissions; API for v2)
- No ML/AI-powered recommendations (basic query filtering only)
- No group direct messages (1:1 only in v1)
- No custom emoji system for chat (Unicode only)
- No SQLite test support — all tests run against MySQL

## Design Considerations

- **Design system:** All UI uses the Greetup design system (spec section 1A) with Instrument Sans typography (weights 400/500 only), the green/coral/violet/gold accent palette, green-tinted neutral grays, and the decorative cloud blob motif
- **Component library:** 13 reusable Blade components + 1 SEO component built in Phase 1, used throughout
- **Livewire components:** 14 interactive components (RsvpButton, EventChat, ConversationView, GlobalSearch, NotificationDropdown, CommentThread, DiscussionThread, AttendeeList, AttendeeManager, MemberList, JoinRequestList, InterestPicker, LocationPicker, ImageUpload)
- **Responsive breakpoints:** Desktop (>= 1024px) full layout with sidebars; Tablet (768-1023px) sidebar below content, 2-col grid; Mobile (< 768px) single column, hamburger nav, scrollable tabs, full-width buttons
- **Homepage mockup:** Reference `homepage-mockup.pen` if available for visual direction
- **Color determinism:** Avatar colors assigned by user.id % 4, pill colors by tag.id % 4 — same user/tag always gets same color

## Technical Considerations

- **Framework:** Laravel 12.x with PHP 8.5+, Livewire 3, Alpine.js, Tailwind CSS 4
- **Database:** MySQL 8.0 (dev via Sail), MySQL 8.0+ / PostgreSQL 15+ (production)
- **Key packages:** spatie/laravel-permission (platform roles), spatie/laravel-sluggable, spatie/laravel-medialibrary, spatie/laravel-tags (interests), league/commonmark, geocodio/geocodio-library-php, intervention/image, laravel/reverb, laravel/scout, laravel/dusk
- **Local dev:** Laravel Sail with MySQL, Redis, Meilisearch, Mailpit, Reverb, queue worker services per docker-compose.yml in spec section 2.4
- **Testing:** All tests against MySQL (matching production). Pest for unit/feature/component (parallel, RefreshDatabase). Dusk for browser/E2E (DatabaseMigrations). Geocoding mocked in all tests — zero API keys in CI
- **Performance:** Eager loading to prevent N+1, cursor pagination for scroll-heavy views, queued jobs for geocoding/notifications/waitlist, Redis for cache/queue/session, platform settings cached
- **5 middleware:** TrackLastActivity, EnsureAccountNotSuspended, EnsureEmailIsVerified, EnsureGroupMember, EnsureGroupRole
- **10 service classes:** RsvpService, WaitlistService, GroupMembershipService, EventSeriesService, NotificationService, SearchService, GeocodingService, ExportService, AccountService, MarkdownService

## Success Metrics

- All 119 user stories implemented with acceptance criteria verified
- >= 90% overall test line coverage; >= 95% on models, services, policies; 100% route, authorization, and validation coverage
- `php artisan migrate:fresh --seed` produces a fully functional demo instance without any API keys
- All Blade components render correctly at desktop (>=1024px), tablet (768-1023px), and mobile (<768px) breakpoints
- Event RSVP + waitlist promotion completes within 2 seconds of RSVP change
- Explore page loads within 1 second for 50+ events with location filtering
- Chat messages delivered within 500ms via Reverb
- CI pipeline (lint + test + dusk) completes in under 10 minutes

## Open Questions

- Should the homepage hero stats (groups, events, members) be cached or queried live on each page load?
- What should the "Add to Calendar" .ics file include for online events with no physical location — the online_link as LOCATION?
- For recurring events, should RSVPs carry over to future instances or require fresh RSVPs each time?
- What is the desired behavior when an organizer edits a recurring event's time — should "edit all future" update times for instances that already have RSVPs?
- Should the `greetup:install` wizard run automatically on first `artisan` command if no admin user exists?
