# Greetup -- Application Specification

**Version:** 1.0.0-draft
**Status:** Pre-implementation
**License:** MIT

> An open source, self-hostable community events platform. A free alternative to Meetup.com, built with Laravel.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
   - 1A. [Design System](#1a-design-system)
2. [Tech Stack](#2-tech-stack)
3. [Roles & Permissions](#3-roles--permissions)
4. [Database Schema](#4-database-schema)
5. [Feature Specifications](#5-feature-specifications)
6. [URL Structure & Routes](#6-url-structure--routes)
7. [Notifications](#7-notifications)
8. [Search & Discovery](#8-search--discovery)
9. [Seed Data Plan](#9-seed-data-plan)
10. [Test Plan](#10-test-plan)
11. [GitHub Actions CI](#11-github-actions-ci)
12. [README / Self-Hosting Guide](#12-readme--self-hosting-guide)
13. [CONTRIBUTING.md](#13-contributingmd)
14. [Error Pages](#14-error-pages)
15. [SEO & Social Sharing](#15-seo--social-sharing)

---

## 1. Project Overview

### 1.1 What is Greetup?

Greetup is a self-hostable, open source community events platform for organizing local meetups and interest-based groups. It provides the core functionality people rely on from Meetup.com -- group management, event scheduling, RSVPs, waitlists, attendee check-in, discussions, and discovery -- without paywalls, tiered subscriptions, or feature gating.

### 1.2 Design Principles

- **Free and open**: Every feature is available to every user. No paid tiers, no premium gating.
- **Self-hostable**: A single Laravel application that anyone can deploy on their own server.
- **Organizer-friendly**: Reduce friction for people running community events. The platform should make organizing easier, not harder.
- **Privacy-respecting**: Members control their own visibility. No tracking beyond what the platform needs to function.
- **Opinionated but extensible**: Ship sensible defaults. Use standard Laravel patterns so the community can extend it.

### 1.3 What Greetup Does NOT Include

- Payment processing (no event fees, no member dues, no ticketing charges)
- Premium/paid membership tiers
- Advertising or sponsor placement features
- Native video conferencing (link to external tools like Zoom, Jitsi, etc.)
- Mobile apps (web-first, responsive design; PWA consideration for v2)
- Federation / ActivityPub (future consideration)

### 1.4 Logo & Branding

The Greetup logo features an organic cloud/flower-shaped "g" mark in emerald green, paired with a clean lowercase wordmark. The logo should be placed at `resources/images/greetup.png`.

The cloud shape from the logo mark is used throughout the design as a decorative motif -- appearing as oversized, low-opacity background blobs on hero sections, card headers, and empty states.

---

## 1A. Design System

### 1A.1 Design Philosophy

The visual language combines two qualities: **bold, energetic personality** with **Vercel/Stripe-level precision**. The result should feel like a polished product with real character -- not a generic SaaS template, and not a chaotic indie project.

Key principles:
- **Color is structural, not decorative.** Each accent color encodes meaning (event type, status, category). Color choices are never random.
- **Organic shapes ground the brand.** The logo's cloud silhouette appears as decorative blobs throughout the UI, giving warmth and personality to what would otherwise be a clinical layout.
- **Precision in the details.** 0.5px borders, tight letter-spacing on headings, consistent 8px spacing grid, restrained font weights (400 and 500 only).
- **White space is generous.** Let content breathe. Density is reserved for data-rich views (attendee lists, analytics).

### 1A.2 Color Palette

#### Primary: Green (derived from logo)

The primary green (#1FAF63) is used for CTAs, active states, brand moments, and the primary navigation accent.

| Token | Hex | Usage |
|-------|-----|-------|
| `green-50` | #E8F8F0 | Tinted backgrounds, pill fills, date blocks |
| `green-100` | #B0EACC | Hover states, light fills |
| `green-200` | #6FDBA2 | Secondary fills, decorative |
| `green-400` | #3CC87E | Mid-tone, charts |
| `green-500` | #1FAF63 | **Primary brand color.** Buttons, links, active tabs, logo |
| `green-700` | #178A4F | Text on green-50 backgrounds, dark accents |
| `green-900` | #0D5C34 | Dark backgrounds (card headers, hero sections), text on light greens |

#### Accent: Coral

Warm, energetic. Used for in-person events, urgency indicators ("6 spots left", "Almost full"), and warmth moments.

| Token | Hex | Usage |
|-------|-----|-------|
| `coral-50` | #FFF0EC | Tinted backgrounds, pill fills |
| `coral-200` | #FFA994 | Light fills, hover |
| `coral-500` | #FF6B4A | **In-person event accent.** Badges, urgency text, avatar backgrounds |
| `coral-900` | #8C2E18 | Text on coral-50, dark card headers for in-person events |

#### Accent: Violet

Creative, digital. Used for online events, hybrid events, and creative/tech categories.

| Token | Hex | Usage |
|-------|-----|-------|
| `violet-50` | #F0EBFF | Tinted backgrounds, pill fills |
| `violet-200` | #B4A0FC | Light fills, hover |
| `violet-500` | #7C5CFC | **Online event accent.** Badges, date blocks, avatar backgrounds |
| `violet-900` | #3D2396 | Text on violet-50, dark card headers for online events |

#### Accent: Gold

Warm highlight. Used for featured content, "almost full" badges, fun/lifestyle categories, and celebratory moments.

| Token | Hex | Usage |
|-------|-----|-------|
| `gold-50` | #FFF6E0 | Tinted backgrounds, pill fills |
| `gold-200` | #FFD580 | Light fills, hover |
| `gold-500` | #FFB938 | **Highlight accent.** Featured badges, stat cards, avatar backgrounds |
| `gold-900` | #7A5500 | Text on gold-50 |

#### Neutral (green-tinted gray)

The neutral ramp has a subtle green undertone to feel cohesive with the brand rather than cold.

| Token | Hex | Usage |
|-------|-----|-------|
| `neutral-50` | #FAFBFA | Page background |
| `neutral-100` | #F0F3F1 | Card backgrounds, secondary surfaces |
| `neutral-200` | #D8DDD9 | Borders, dividers |
| `neutral-400` | #9BA59E | Placeholder text, disabled states |
| `neutral-500` | #6B7B73 | Secondary text, meta information |
| `neutral-700` | #3D4A43 | Primary text (light mode) |
| `neutral-900` | #0A0F0D | Headings, high-emphasis text |

#### Semantic Colors

| Purpose | Color | Token |
|---------|-------|-------|
| Success | Green 500 | `green-500` |
| Warning | Gold 500 | `gold-500` |
| Danger / Error | #EF4444 | `red-500` (with `red-50` #FEE8E8 and `red-900` #7F1D1D) |
| Info | #4285F4 | `blue-500` (with `blue-50` #E8F0FE and `blue-900` #1A3F7A) |

#### Color Assignment Rules

**Event type badges** always use the same color:
- In-person: coral
- Online: violet
- Hybrid: green (uses green-50/green-700 to distinguish from online; badge label reads "Hybrid" with a combined pin+video icon)
- Cancelled: red

**Interest/topic pills** cycle through all four accent tint backgrounds (green-50, coral-50, violet-50, gold-50) to create a vibrant, varied appearance. Text uses the 700/900 stop of the same ramp. Assignment is deterministic based on the tag's ID modulo 4.

**Avatar circles** rotate through the four accent colors (green-500, coral-500, violet-500, gold-500) with white text (dark text for gold). Assignment is deterministic based on the user's ID modulo 4 so a given user always gets the same color.

**Date blocks** on event lists use the event type's accent tint (coral-50 for in-person, violet-50 for online/hybrid) with text in the 900 stop of the same ramp.

### 1A.3 Typography

**Font family:** Instrument Sans (Google Fonts). Used for both display and body text. It is geometric and clean like the logo wordmark but has enough character to avoid feeling generic.

Fallback stack: `'Instrument Sans', system-ui, -apple-system, sans-serif`

**Type scale:**

| Name | Size | Weight | Letter-spacing | Line-height | Usage |
|------|------|--------|---------------|-------------|-------|
| Display | 44px | 500 | -0.03em | 1.08 | Homepage hero headline only |
| Page title | 22-28px | 500 | -0.02em | 1.15 | Page titles, group names, event names |
| Section heading | 20px | 500 | -0.01em | 1.3 | Section labels ("Upcoming events near you") |
| Body | 16px | 400 | normal | 1.6-1.7 | Descriptions, paragraphs, discussion content |
| Meta | 13-14px | 400 | normal | 1.4 | Secondary info (location, member count, timestamps) |
| Small / Label | 11-12px | 500 | 0.05em (uppercase) | 1.2 | Category labels, section dividers, badge text |

**Weight rules:**
- Only two weights: 400 (regular) and 500 (medium). Never use 600 or 700.
- Headings and interactive labels use 500.
- Body text and meta information use 400.

**Uppercase usage:**
- Section divider labels ("UPCOMING NEAR YOU", "VENUE", "HOSTS") use 11-12px, weight 500, uppercase, letter-spacing 0.05em.
- Date abbreviations on event cards ("TUE, MAR 24") use uppercase.
- Nothing else should be uppercase.

### 1A.4 Spacing & Layout

**Base grid:** 8px. All spacing values should be multiples of 4 or 8.

| Token | Value | Usage |
|-------|-------|-------|
| `space-1` | 4px | Tight gaps (between badge and text) |
| `space-2` | 8px | Small gaps (between pills, between small elements) |
| `space-3` | 12px | Card internal gaps, grid gaps |
| `space-4` | 16px | Section padding, medium gaps |
| `space-5` | 20px | Page padding, large card padding |
| `space-6` | 24px | Section spacing |
| `space-8` | 32px | Major section breaks |
| `space-12` | 48px | Hero padding top |

**Border radius:**

| Token | Value | Usage |
|-------|-------|-------|
| `radius-sm` | 4px | Status badges, small tags |
| `radius-md` | 8px | Buttons, inputs, logo mark |
| `radius-lg` | 12px | Date blocks, sidebar cards |
| `radius-xl` | 16px | Cards, card headers, stat blocks |
| `radius-pill` | 100px | Interest pills, filter chips, avatar circles |

**Borders:**
- Default: `0.5px solid` using neutral-200.
- Cards: 0.5px border with radius-xl.
- Active tabs: 2px bottom border in green-500.
- Focused inputs: standard browser focus ring.

### 1A.5 Components

#### Buttons

| Variant | Background | Text | Border | Usage |
|---------|-----------|------|--------|-------|
| Primary | green-500 | white | none | Main CTAs ("RSVP going", "Join group", "Get started") |
| Secondary | transparent | green-500 | 1.5px solid green-500 | Secondary actions ("RSVP" in event lists) |
| Ghost | transparent | neutral-500 | 0.5px solid neutral-200 | Tertiary actions ("Not going", "Add to calendar", "Share") |

All buttons: padding 8-12px vertical, 16-28px horizontal. Font size 13-15px, weight 500. Border-radius radius-md (8px).

#### Event Cards (Grid Layout -- Explore Page)

Vertical card with colored header band, content below.
- Header: 72-110px tall, dark accent color (green-900, coral-900, violet-900, or gold-900) background. Semi-transparent decorative blob (logo cloud shape) bleeding off one edge at 0.1-0.2 opacity. Event type pill in bottom-left with `background: rgba(255,255,255,0.15)` and white text.
- Optional "Almost full" badge in gold on the header top-right.
- Body: Date (accent color, 11px, uppercase, weight 500), event title (15px, weight 500), group name (13px, neutral-500), attendance row (avatar stack + "X going" left, spots remaining right).
- Border-radius: radius-xl (16px). Border: 0.5px solid neutral-200.

#### Event Rows (List Layout -- Group Page)

Horizontal layout with date block, content, RSVP button.
- Date block: 56px wide, accent tint background (coral-50 for in-person, violet-50 for online/hybrid), radius-lg (12px), padding 8px. Month (11px, uppercase, accent-500), day number (24px, weight 500, accent-900).
- Content: Title (15px, weight 500), meta line (13px, "Tuesday · 18:30 · Pleo HQ"), badges row (event type badge + attendance count + optional "Almost full" in gold).
- RSVP button: right-aligned, secondary variant. Switches to primary when plenty of spots available.

#### Card Headers with Decorative Blobs

Event card headers, group covers, and hero sections use the logo cloud shape as a decorative element:
- SVG path positioned `absolute`, bleeding off one or two edges.
- Opacity: 0.1-0.2 on dark backgrounds, 0.04-0.08 on light backgrounds.
- Size: 80-280px depending on container size.
- Optionally paired with a large circle (different accent color, even lower opacity) for visual layering.

The SVG cloud path (viewbox `0 0 80 80`):
```
M40 5 C55 5, 70 15, 72 30 C78 32, 80 38, 78 45
C80 55, 72 68, 58 70 C52 78, 40 80, 32 74
C18 76, 5 66, 5 52 C0 42, 5 32, 15 28
C12 15, 25 5, 40 5Z
```

Blade component: `<x-blob color="green-500" size="200" opacity="0.1" class="-top-10 -right-8" />`

#### Interest Pills

Full-rounded (radius-pill), tinted background, dark text from the same ramp.
- Padding: 4-6px vertical, 10-14px horizontal.
- Font: 12-13px, weight 500.
- Background cycles: green-50, coral-50, violet-50, gold-50.
- Text cycles: green-700, coral-900, violet-900, gold-900.
- Deterministic color based on tag ID.

#### Avatar Circles

Solid accent color backgrounds with white initials (or dark text for gold).
- Sizes: 22px (inline stacks), 24px (small), 28px (nav), 32px (sidebar), 44px (profile cards).
- In stacked groups: overlap by 6-8px using negative margin-left, each with a 2px white border.
- "+N" overflow indicator uses neutral-100 background with neutral-500 text.
- Deterministic color based on user ID.

#### Status Badges

Compact, rectangular (radius-sm, 4px), tinted background, dark text.

| Status | Background | Text |
|--------|-----------|------|
| In-person | coral-50 | coral-900 |
| Online | violet-50 | violet-900 |
| Hybrid | green-50 | green-700 |
| Going | green-50 | green-700 |
| Waitlisted | gold-50 | gold-900 |
| Cancelled | red-50 | red-900 |
| Almost full | gold-50 | gold-900 |

#### Stat Cards (Homepage Hero)

Solid bold accent backgrounds (coral-500, violet-500, gold-500) with white text (dark text for gold). Border-radius radius-xl (16px). Padding 14px.
- Number: 28px, weight 500, line-height 1.
- Label: 11px, 80% opacity.
- One color per card, three cards side by side.

#### Tab Bar

Horizontal tabs below page headers.
- Tab text: 13px, neutral-500 default.
- Active tab: green-500 text, weight 500, 2px bottom border in green-500.
- Tabs separated by 16px gap.
- Bottom border of the tab row: 0.5px solid neutral-200.

#### Attendance Progress Bar

Used in event sidebars to show capacity.
- Track: 4-6px height, neutral-100 background, fully rounded.
- Fill: green-500, fully rounded, width proportional to attendance/capacity.
- Text above: large number (20-28px, weight 500) + "/ N" (14px, neutral-500).
- Text below: "X spots remaining" in coral-500 if < 25% remaining, neutral-500 otherwise.

### 1A.6 Page Layouts

#### Homepage (Unauthenticated)

Hero section with large headline, subtitle, CTA buttons, and three decorative blobs at low opacity in the background. Stat cards (coral, violet, gold) anchored bottom-right. Popular interests pill cloud below.

Headline uses multi-line color-per-phrase pattern:
```
Find your [green-500]people.[/]
Do the [coral-500]thing.[/]
Keep [violet-500]showing up.[/]
```

#### Explore Page (Authenticated)

Header: "Events near [green]Copenhagen[/green]" + "This week and beyond". Search bar + rounded filter chips (pill-shaped). Event card grid: 2-column for featured (top), 3-column for the rest. Subtle decorative blob in top-right of the page header area.

#### Group Page

Cover band (green-900 background, decorative blobs). Group info: name (22px), location + member count with avatar stack, interest pills (cycling colors). Tab bar. Content area per tab. Event list uses the date block row layout.

#### Event Page

Cover band (event-type dark accent, decorative blobs, topic pills overlaid). Left column: date block beside title + host name, CTA row, tab bar, content. Right column (sidebar): attendance card (with progress bar), venue card (with Leaflet map placeholder), hosts card. All sidebar cards use neutral-100 background, radius-xl, 16px padding.

### 1A.7 Tailwind 4 Theme Configuration

```css
@import "tailwindcss";

@theme {
  /* Primary green */
  --color-green-50: #E8F8F0;
  --color-green-100: #B0EACC;
  --color-green-200: #6FDBA2;
  --color-green-400: #3CC87E;
  --color-green-500: #1FAF63;
  --color-green-700: #178A4F;
  --color-green-900: #0D5C34;

  /* Coral accent */
  --color-coral-50: #FFF0EC;
  --color-coral-200: #FFA994;
  --color-coral-500: #FF6B4A;
  --color-coral-900: #8C2E18;

  /* Violet accent */
  --color-violet-50: #F0EBFF;
  --color-violet-200: #B4A0FC;
  --color-violet-500: #7C5CFC;
  --color-violet-900: #3D2396;

  /* Gold accent */
  --color-gold-50: #FFF6E0;
  --color-gold-200: #FFD580;
  --color-gold-500: #FFB938;
  --color-gold-900: #7A5500;

  /* Neutral (green-tinted grays) */
  --color-neutral-50: #FAFBFA;
  --color-neutral-100: #F0F3F1;
  --color-neutral-200: #D8DDD9;
  --color-neutral-400: #9BA59E;
  --color-neutral-500: #6B7B73;
  --color-neutral-700: #3D4A43;
  --color-neutral-900: #0A0F0D;

  /* Semantic */
  --color-red-50: #FEE8E8;
  --color-red-500: #EF4444;
  --color-red-900: #7F1D1D;
  --color-blue-50: #E8F0FE;
  --color-blue-500: #4285F4;
  --color-blue-900: #1A3F7A;

  /* Typography */
  --font-display: 'Instrument Sans', system-ui, -apple-system, sans-serif;
  --font-body: 'Instrument Sans', system-ui, -apple-system, sans-serif;
  --font-mono: 'JetBrains Mono', ui-monospace, monospace;

  /* Radius */
  --radius-sm: 4px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-xl: 16px;
  --radius-pill: 100px;
}
```

### 1A.8 Blade Component: Decorative Blob

```blade
{{-- resources/views/components/blob.blade.php --}}
@props(['color' => '#1FAF63', 'size' => 200, 'opacity' => 0.1, 'shape' => 'cloud'])

<svg
    {{ $attributes->merge(['class' => 'absolute pointer-events-none']) }}
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 80 80"
    style="opacity: {{ $opacity }};"
    aria-hidden="true"
>
    @if($shape === 'cloud')
    <path
        d="M40 5 C55 5, 70 15, 72 30 C78 32, 80 38, 78 45
           C80 55, 72 68, 58 70 C52 78, 40 80, 32 74
           C18 76, 5 66, 5 52 C0 42, 5 32, 15 28
           C12 15, 25 5, 40 5Z"
        fill="{{ $color }}"
    />
    @else
    <circle cx="40" cy="40" r="38" fill="{{ $color }}" />
    @endif
</svg>
```

Usage examples:
```blade
{{-- Large green cloud blob bleeding off top-right of a hero --}}
<x-blob color="#1FAF63" size="280" opacity="0.1" class="-top-10 -right-8" />

{{-- Small gold circle as secondary depth layer --}}
<x-blob color="#FFB938" size="100" opacity="0.05" shape="circle" class="top-5 left-1/3" />

{{-- Coral cloud on an event card header --}}
<x-blob color="#FF6B4A" size="130" opacity="0.15" class="-right-4 -top-4" />
```

### 1A.9 Dark Mode

Dark mode support is deferred to v2. The initial release ships light mode only. The color system is designed with dark mode in mind (every ramp has both light and dark stops), so retrofitting will be straightforward:
- Swap neutral-50/100 backgrounds to neutral-900/700.
- Swap neutral-700/900 text to neutral-100/50.
- Accent tint backgrounds (green-50, coral-50, etc.) swap to their 900 counterparts with 200 text.
- Card headers and hero sections already use dark backgrounds, so they work in both modes.

### 1A.10 Responsive Behavior

- **Desktop (>= 1024px):** Full layout with sidebars, 2-3 column event grids, stat cards side by side.
- **Tablet (768-1023px):** Sidebar collapses below main content. Event grid drops to 2 columns.
- **Mobile (< 768px):** Single column. Event cards stack vertically. Navigation collapses to hamburger. Tab bars become horizontally scrollable. Stat cards in the hero stack vertically. Date blocks in event rows shrink, RSVP button moves below content (full-width).

---

## 2. Tech Stack

### 2.1 Core

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 12.x |
| Language | PHP 8.5+ |
| Database | MySQL 8.0 (local dev via Sail), MySQL 8.0+ / PostgreSQL 15+ (production) |
| Frontend | Blade templates + Livewire 3 |
| CSS | Tailwind CSS 4 |
| JavaScript | Alpine.js (bundled with Livewire) |
| Search | Laravel Scout with Meilisearch (local dev via Sail), database driver (fallback) |
| Queue | Redis (local dev via Sail), `database` driver (fallback) |
| Cache | Redis (local dev via Sail), `file` driver (fallback) |
| Mail | Mailpit (local dev via Sail), SMTP (production) |
| File Storage | `local` driver (default), S3-compatible (recommended for production) |
| Geocoding | Geocodio (address to lat/lng, reverse geocoding) |
| WebSocket | Laravel Reverb (local dev via Sail) |
| Testing | Pest PHP |
| Browser Testing | Laravel Dusk |

### 2.2 Key Packages

| Package | Purpose |
|---------|---------|
| `livewire/livewire` | Interactive UI components without heavy JS |
| `laravel/scout` | Full-text search for groups, events, members |
| `spatie/laravel-permission` | Role and permission management (platform-level roles) |
| `spatie/laravel-sluggable` | URL-friendly slugs for groups and events |
| `spatie/laravel-medialibrary` | Image uploads (group photos, event photos, avatars) |
| `spatie/laravel-tags` | Interest/topic tagging for groups and members |
| `league/commonmark` | Markdown rendering for descriptions and discussions |
| `laravel/reverb` | WebSocket server for real-time Event Chat |
| `geocodio/geocodio-library-php` | Forward and reverse geocoding via the Geocodio API |
| `intervention/image` | Image resizing and optimization |
| `laravel/dusk` | Browser testing |

### 2.3 Development Tools

| Tool | Purpose |
|------|---------|
| Laravel Sail | Docker-based local dev environment (primary) |
| Pest | Test framework |
| Laravel Pint | Code formatting (PSR-12 + Laravel preset) |
| Larastan (PHPStan) | Static analysis |
| Laravel Dusk | Browser/E2E testing |
| Mailpit | Local email testing (via Sail) |

### 2.4 Local Development Environment (Laravel Sail)

Sail is the primary local development environment. The project ships with a `docker-compose.yml` that starts all required services with a single command.

#### Services

| Service | Image | Port | Purpose |
|---------|-------|------|---------|
| `laravel.test` | Custom (PHP 8.5, Node 20) | 80 | Laravel application |
| `mysql` | `mysql:8.0` | 3306 | Primary database |
| `redis` | `redis:alpine` | 6379 | Cache, queue, session |
| `meilisearch` | `getmeili/meilisearch:latest` | 7700 | Full-text search |
| `mailpit` | `axllent/mailpit` | 8025 (UI), 1025 (SMTP) | Email testing |
| `reverb` | (runs inside laravel.test) | 8080 | WebSocket server |

#### docker-compose.yml

The `docker-compose.yml` is generated by Sail's installer, but should be configured to include these services. The `sail:install` command will be run with:

```bash
php artisan sail:install --with=mysql,redis,meilisearch,mailpit
```

Additionally, a custom Sail command or documentation should cover starting Reverb inside the app container. The `docker-compose.yml` should include a dedicated Reverb service:

```yaml
services:
    laravel.test:
        build:
            context: ./vendor/laravel/sail/runtimes/8.5
            dockerfile: Dockerfile
            args:
                WWWGROUP: '${WWWGROUP}'
        image: sail-8.5/app
        extra_hosts:
            - 'host.docker.internal:host-gateway'
        ports:
            - '${APP_PORT:-80}:80'
            - '${VITE_PORT:-5173}:${VITE_PORT:-5173}'
        environment:
            WWWUSER: '${WWWUSER}'
            LARAVEL_SAIL: 1
            XDEBUG_MODE: '${SAIL_XDEBUG_MODE:-off}'
            XDEBUG_CONFIG: '${SAIL_XDEBUG_CONFIG:-client_host=host.docker.internal}'
            IGNITION_LOCAL_SITES_PATH: '${PWD}'
            GEOCODIO_API_KEY: '${GEOCODIO_API_KEY}'
        volumes:
            - '.:/var/www/html'
        networks:
            - sail
        depends_on:
            - mysql
            - redis
            - meilisearch
            - mailpit

    queue:
        image: sail-8.5/app
        command: php artisan queue:work --sleep=3 --tries=3
        volumes:
            - '.:/var/www/html'
        environment:
            LARAVEL_SAIL: 1
            GEOCODIO_API_KEY: '${GEOCODIO_API_KEY}'
        networks:
            - sail
        depends_on:
            - mysql
            - redis

    reverb:
        image: sail-8.5/app
        command: php artisan reverb:start --host=0.0.0.0 --port=8080
        ports:
            - '${REVERB_PORT:-8080}:8080'
        volumes:
            - '.:/var/www/html'
        environment:
            LARAVEL_SAIL: 1
        networks:
            - sail
        depends_on:
            - mysql
            - redis

    mysql:
        image: 'mysql:8.0'
        ports:
            - '${FORWARD_DB_PORT:-3306}:3306'
        environment:
            MYSQL_ROOT_PASSWORD: '${DB_PASSWORD}'
            MYSQL_ROOT_HOST: '%'
            MYSQL_DATABASE: '${DB_DATABASE}'
            MYSQL_USER: '${DB_USERNAME}'
            MYSQL_PASSWORD: '${DB_PASSWORD}'
            MYSQL_ALLOW_EMPTY_PASSWORD: 1
        volumes:
            - 'sail-mysql:/var/lib/mysql'
        networks:
            - sail
        healthcheck:
            test: ["CMD", "mysqladmin", "ping", "-p${DB_PASSWORD}"]
            retries: 3
            timeout: 5s

    redis:
        image: 'redis:alpine'
        ports:
            - '${FORWARD_REDIS_PORT:-6379}:6379'
        volumes:
            - 'sail-redis:/data'
        networks:
            - sail
        healthcheck:
            test: ["CMD", "redis-cli", "ping"]
            retries: 3
            timeout: 5s

    meilisearch:
        image: 'getmeili/meilisearch:latest'
        ports:
            - '${FORWARD_MEILISEARCH_PORT:-7700}:7700'
        environment:
            MEILI_NO_ANALYTICS: '${MEILISEARCH_NO_ANALYTICS:-true}'
            MEILI_MASTER_KEY: '${MEILISEARCH_KEY:-masterKey}'
        volumes:
            - 'sail-meilisearch:/meili_data'
        networks:
            - sail
        healthcheck:
            test: ["CMD", "wget", "--no-verbose", "--spider", "http://127.0.0.1:7700/health"]
            retries: 3
            timeout: 5s

    mailpit:
        image: 'axllent/mailpit:latest'
        ports:
            - '${FORWARD_MAILPIT_PORT:-8025}:8025'
            - '${FORWARD_MAILPIT_SMTP_PORT:-1025}:1025'
        networks:
            - sail

networks:
    sail:
        driver: bridge

volumes:
    sail-mysql:
        driver: local
    sail-redis:
        driver: local
    sail-meilisearch:
        driver: local
```

#### .env.example (Local Development Defaults)

```ini
APP_NAME=Greetup
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Database (MySQL via Sail)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=greetup
DB_USERNAME=sail
DB_PASSWORD=password

# Redis (via Sail -- used for cache, queue, and session)
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379

# Search (Meilisearch via Sail)
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=masterKey

# Mail (Mailpit via Sail -- UI at http://localhost:8025)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@greetup.test"
MAIL_FROM_NAME="${APP_NAME}"

# WebSocket (Reverb via Sail)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=greetup
REVERB_APP_KEY=greetup-key
REVERB_APP_SECRET=greetup-secret
REVERB_HOST=reverb
REVERB_PORT=8080
REVERB_SCHEME=http

# Vite will connect to Reverb from the browser, so this must be localhost
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http

# File Storage
FILESYSTEM_DISK=local

# -------------------------------------------------------------------
# Third-Party API Keys (see Section 2.5 for details)
# -------------------------------------------------------------------

# Geocodio -- REQUIRED for address geocoding
# Free tier: 2,500 lookups/day. Sign up at https://www.geocod.io
# If you have GEOCODIO_API_KEY set in your host environment, Sail will pass it through automatically.
GEOCODIO_API_KEY=

# -------------------------------------------------------------------
# Optional Third-Party Services (not required for basic functionality)
# -------------------------------------------------------------------

# S3-Compatible Storage (optional, for production file uploads)
# AWS_ACCESS_KEY_ID=
# AWS_SECRET_ACCESS_KEY=
# AWS_DEFAULT_REGION=us-east-1
# AWS_BUCKET=greetup-uploads
# AWS_URL=
```

### 2.5 Third-Party API Keys

Greetup is designed to minimize external dependencies. Only one third-party API key is **required** for core functionality. All others are optional and only needed in specific deployment scenarios.

#### Required

| Service | Key | Purpose | Free Tier | Sign Up |
|---------|-----|---------|-----------|---------|
| **Geocodio** | `GEOCODIO_API_KEY` | Forward geocoding (address to lat/lng) for groups, events, and user profiles. Reverse geocoding for location-based discovery. | 2,500 free lookups/day | https://www.geocod.io |

Geocodio is used whenever a user enters an address or location in a group, event, or profile form. The resolved coordinates are stored in the database and used for proximity-based search and map display. Without a Geocodio API key, location-based features (nearby events, distance sorting, map embeds) will be disabled but the rest of the application functions normally.

**Geocodio integration details:**
- Package: `geocodio/geocodio-library-php`
- Config: `config/services.php` with `geocodio.api_key` pulled from env
- Geocoding is performed asynchronously via a queued job (`GeocodeLocation`) to avoid blocking form submissions
- Results are cached in the database (lat/lng columns on `users`, `groups`, `events`) so the API is only called when an address changes
- Batch geocoding is used during seeding to minimize API calls
- The `GeocodingService` wraps the Geocodio client and provides: `geocode(string $address): ?array` returning `['lat' => float, 'lng' => float, 'formatted_address' => string]`, `reverse(float $lat, float $lng): ?string` returning a formatted address, and `batch(array $addresses): array` for bulk operations
- Graceful degradation: if the API key is missing or a request fails, the location fields remain null and location-based features are silently skipped

#### Optional (Production)

| Service | Key(s) | Purpose | When Needed |
|---------|--------|---------|-------------|
| **SMTP Provider** | `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD` | Transactional email (notifications, password resets) | Production deployment. Any SMTP provider works: Mailgun, Postmark, SES, Resend, etc. Mailpit handles this in local dev. |
| **S3-Compatible Storage** | `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET` | File uploads (avatars, cover photos) | Production if you want uploads stored off-server. Works with AWS S3, DigitalOcean Spaces, MinIO, Cloudflare R2, etc. Local disk is used by default. |
| **Meilisearch Cloud** | `MEILISEARCH_HOST`, `MEILISEARCH_KEY` | Hosted search | Only if you don't want to self-host Meilisearch. Sail includes Meilisearch locally. The `database` Scout driver works as a zero-dependency fallback. |

#### Not Required

These services are **self-contained** and require no API keys:

| Service | How It Works |
|---------|-------------|
| Database (MySQL) | Self-hosted via Sail or your own server |
| Redis | Self-hosted via Sail or your own server |
| Meilisearch | Self-hosted via Sail or your own server |
| WebSocket (Reverb) | Built into Laravel, runs as a process alongside the app |
| Maps | Leaflet.js with OpenStreetMap tiles (free, no API key) |
| Email (local dev) | Mailpit runs locally via Sail |

---

## 3. Roles & Permissions

### 3.1 Platform Roles

Managed via `spatie/laravel-permission`. These are global roles assigned to the `User` model.

| Role | Description |
|------|-------------|
| `user` | Default role for all registered users |
| `admin` | Platform administrator with access to admin panel |

### 3.2 Group Roles

Group roles are NOT managed by spatie/laravel-permission. They are stored on the `group_members` pivot table as an enum column, because a user can have different roles in different groups.

| Role (enum value) | Hierarchy | Description |
|--------------------|-----------|-------------|
| `member` | 0 | Standard group member |
| `event_organizer` | 1 | Can create/edit events, send group messages, manage RSVPs |
| `assistant_organizer` | 2 | Everything above + accept/remove/ban members |
| `co_organizer` | 3 | Everything above + edit group settings, manage leadership team |
| `organizer` | 4 | Group owner. Everything above + delete group, transfer ownership |

### 3.3 Event Host

Event Host is NOT a group role. It is a per-event assignment stored on the `event_hosts` table. Any group member can be assigned as a host for a specific event. The event creator is automatically assigned as host.

### 3.4 Permission Matrix -- Group Context

| Action | Member | Event Org | Asst. Org | Co-Org | Organizer |
|--------|--------|-----------|-----------|--------|-----------|
| View group & events | x | x | x | x | x |
| RSVP to events | x | x | x | x | x |
| Post in discussions | x | x | x | x | x |
| Comment on events | x | x | x | x | x |
| Create discussions | x | x | x | x | x |
| Pin/unpin discussions | | | | x | x |
| Lock/unlock discussions | | | | x | x |
| Delete any discussion/reply | | | | x | x |
| Create events | | x | x | x | x |
| Edit any event | | x | x | x | x |
| Cancel events | | x | x | x | x |
| Manage RSVPs | | x | x | x | x |
| Check in attendees | | x | x | x | x |
| Send group messages | | x | x | x | x |
| Assign event hosts | | x | x | x | x |
| Accept/deny join requests | | | x | x | x |
| Remove members | | | x | x | x |
| Ban members | | | x | x | x |
| Edit group settings | | | | x | x |
| Manage leadership roles | | | | x | x |
| View group analytics | | | | x | x |
| Delete group | | | | | x |
| Transfer ownership | | | | | x |

### 3.5 Permission Matrix -- Event Host Context

An event host can perform the following actions ONLY on the specific event they are hosting:

| Action | Event Host |
|--------|-----------|
| Edit event details | x |
| View full attendee list | x |
| Check in attendees | x |
| Manage Event Chat | x |

### 3.6 Permission Matrix -- Platform Admin

| Action | Admin |
|--------|-------|
| Access admin dashboard | x |
| View all groups, events, users | x |
| Suspend/unsuspend users | x |
| Delete groups | x |
| Delete events | x |
| Remove reported content | x |
| Review and act on reports | x |
| View platform statistics | x |
| Manage platform settings (site name, registration, etc.) | x |
| Manage interest/topic taxonomy | x |

---

## 4. Database Schema

### 4.1 Users

```
users
├── id                  bigint, PK
├── name                string(255)
├── email               string(255), unique
├── email_verified_at   timestamp, nullable
├── password            string(255)
├── avatar_path         string(255), nullable  -- via medialibrary
├── bio                 text, nullable
├── location            string(255), nullable  -- free text, e.g. "Copenhagen, Denmark"
├── latitude            decimal(10,7), nullable
├── longitude           decimal(10,7), nullable
├── timezone            string(50), default 'UTC'
├── looking_for         json, nullable          -- ["practicing hobbies", "making friends", "networking"]
├── profile_visibility  enum('public','members_only'), default 'public'
├── is_suspended        boolean, default false
├── suspended_at        timestamp, nullable
├── suspended_reason    text, nullable
├── last_active_at      timestamp, nullable  -- updated by TrackLastActivity middleware
├── remember_token      string(100), nullable
├── created_at          timestamp
├── updated_at          timestamp
├── deleted_at          timestamp, nullable  -- soft delete with 30-day grace period
```

### 4.2 Groups

```
groups
├── id                      bigint, PK
├── name                    string(255)
├── slug                    string(255), unique
├── description             text
├── description_html        text  -- pre-rendered markdown
├── organizer_id            bigint, FK -> users.id
├── location                string(255), nullable
├── latitude                decimal(10,7), nullable
├── longitude               decimal(10,7), nullable
├── timezone                string(50), default 'UTC'
├── cover_photo_path        string(255), nullable
├── visibility              enum('public','private'), default 'public'
├── requires_approval       boolean, default false
├── max_members             integer, nullable  -- null = unlimited
├── welcome_message         text, nullable
├── is_active               boolean, default true
├── created_at              timestamp
├── updated_at              timestamp
├── deleted_at              timestamp, nullable  -- soft delete

INDEX: (latitude, longitude) -- for geo queries
INDEX: (visibility, is_active) -- for discovery
```

### 4.3 Group Members (Pivot)

```
group_members
├── id                  bigint, PK
├── group_id            bigint, FK -> groups.id
├── user_id             bigint, FK -> users.id
├── role                enum('member','event_organizer','assistant_organizer','co_organizer','organizer')
├── joined_at           timestamp
├── is_banned           boolean, default false
├── banned_at           timestamp, nullable
├── banned_reason       text, nullable
├── created_at          timestamp
├── updated_at          timestamp

UNIQUE: (group_id, user_id)
INDEX: (group_id, role)
INDEX: (user_id)
```

### 4.4 Membership Questions & Answers

```
group_membership_questions
├── id                  bigint, PK
├── group_id            bigint, FK -> groups.id
├── question            string(500)
├── is_required         boolean, default true
├── sort_order          integer, default 0
├── created_at          timestamp
├── updated_at          timestamp

group_membership_answers
├── id                  bigint, PK
├── question_id         bigint, FK -> group_membership_questions.id
├── user_id             bigint, FK -> users.id
├── answer              text
├── created_at          timestamp
├── updated_at          timestamp

UNIQUE: (question_id, user_id)
```

### 4.5 Join Requests

```
group_join_requests
├── id                  bigint, PK
├── group_id            bigint, FK -> groups.id
├── user_id             bigint, FK -> users.id
├── status              enum('pending','approved','denied'), default 'pending'
├── reviewed_by         bigint, FK -> users.id, nullable
├── reviewed_at         timestamp, nullable
├── denial_reason       text, nullable
├── created_at          timestamp
├── updated_at          timestamp

UNIQUE: (group_id, user_id) -- one request per user per group; re-requests update the existing record
INDEX: (group_id, status)
```

### 4.6 Events

```
events
├── id                      bigint, PK
├── group_id                bigint, FK -> groups.id
├── created_by              bigint, FK -> users.id
├── name                    string(255)
├── slug                    string(255)
├── description             text
├── description_html        text
├── event_type              enum('in_person','online','hybrid'), default 'in_person'
├── status                  enum('draft','published','cancelled','past'), default 'draft'
├── starts_at               timestamp
├── ends_at                 timestamp, nullable
├── timezone                string(50)  -- inherited from group, can be overridden
├── venue_name              string(255), nullable
├── venue_address           string(500), nullable
├── venue_latitude          decimal(10,7), nullable
├── venue_longitude         decimal(10,7), nullable
├── online_link             string(500), nullable  -- Zoom, Jitsi, etc.
├── cover_photo_path        string(255), nullable
├── rsvp_limit              integer, nullable  -- null = unlimited
├── guest_limit             integer, default 0  -- max +N guests per RSVP
├── rsvp_opens_at           timestamp, nullable
├── rsvp_closes_at          timestamp, nullable
├── is_chat_enabled         boolean, default true
├── is_comments_enabled     boolean, default true
├── cancelled_at            timestamp, nullable
├── cancellation_reason     text, nullable
├── series_id               bigint, FK -> event_series.id, nullable
├── created_at              timestamp
├── updated_at              timestamp

UNIQUE: (group_id, slug)
INDEX: (group_id, status, starts_at)
INDEX: (starts_at, status)  -- for discovery queries
INDEX: (venue_latitude, venue_longitude)
INDEX: (series_id)
```

### 4.7 Event Series (Recurring Events)

```
event_series
├── id                      bigint, PK
├── group_id                bigint, FK -> groups.id
├── recurrence_rule         string(255)  -- RRULE string, e.g. "FREQ=WEEKLY;BYDAY=TU"
├── created_at              timestamp
├── updated_at              timestamp
```

### 4.8 Event Hosts

```
event_hosts
├── id                  bigint, PK
├── event_id            bigint, FK -> events.id
├── user_id             bigint, FK -> users.id
├── created_at          timestamp
├── updated_at          timestamp

UNIQUE: (event_id, user_id)
```

### 4.9 RSVPs

```
rsvps
├── id                  bigint, PK
├── event_id            bigint, FK -> events.id
├── user_id             bigint, FK -> users.id
├── status              enum('going','not_going','waitlisted'), default 'going'
├── guest_count         integer, default 0
├── attendance_mode     enum('in_person','online'), nullable  -- for hybrid events
├── checked_in          boolean, default false
├── checked_in_at       timestamp, nullable
├── checked_in_by       bigint, FK -> users.id, nullable
├── attended            enum('attended','no_show'), nullable  -- set after event
├── waitlisted_at       timestamp, nullable  -- for FIFO ordering
├── created_at          timestamp
├── updated_at          timestamp

UNIQUE: (event_id, user_id)
INDEX: (event_id, status)
INDEX: (user_id, status)
INDEX: (event_id, status, waitlisted_at)  -- for waitlist ordering
```

### 4.10 Event Comments

```
event_comments
├── id                  bigint, PK
├── event_id            bigint, FK -> events.id
├── user_id             bigint, FK -> users.id
├── parent_id           bigint, FK -> event_comments.id, nullable  -- for threading
├── body                text
├── body_html           text
├── created_at          timestamp
├── updated_at          timestamp
├── deleted_at          timestamp, nullable  -- soft delete

INDEX: (event_id, created_at)
INDEX: (parent_id)
```

### 4.11 Event Comment Likes

```
event_comment_likes
├── id                  bigint, PK
├── comment_id          bigint, FK -> event_comments.id
├── user_id             bigint, FK -> users.id
├── created_at          timestamp

UNIQUE: (comment_id, user_id)
```

### 4.12 Event Feedback

```
event_feedback
├── id                  bigint, PK
├── event_id            bigint, FK -> events.id
├── user_id             bigint, FK -> users.id
├── rating              tinyint  -- 1-5
├── body                text, nullable
├── created_at          timestamp
├── updated_at          timestamp

UNIQUE: (event_id, user_id)
INDEX: (event_id, rating)
```

### 4.13 Event Chat Messages

```
event_chat_messages
├── id                  bigint, PK
├── event_id            bigint, FK -> events.id
├── user_id             bigint, FK -> users.id
├── body                text
├── reply_to_id         bigint, FK -> event_chat_messages.id, nullable
├── created_at          timestamp
├── updated_at          timestamp
├── deleted_at          timestamp, nullable  -- soft delete

INDEX: (event_id, created_at)
```

### 4.14 Group Discussions

```
discussions
├── id                  bigint, PK
├── group_id            bigint, FK -> groups.id
├── user_id             bigint, FK -> users.id
├── title               string(255)
├── slug                string(255)
├── body                text
├── body_html           text
├── is_pinned           boolean, default false
├── is_locked           boolean, default false
├── last_activity_at    timestamp  -- updated on new reply
├── created_at          timestamp
├── updated_at          timestamp
├── deleted_at          timestamp, nullable

UNIQUE: (group_id, slug)
INDEX: (group_id, is_pinned, last_activity_at)
```

### 4.15 Discussion Replies

```
discussion_replies
├── id                  bigint, PK
├── discussion_id       bigint, FK -> discussions.id
├── user_id             bigint, FK -> users.id
├── body                text
├── body_html           text
├── created_at          timestamp
├── updated_at          timestamp
├── deleted_at          timestamp, nullable

INDEX: (discussion_id, created_at)
```

### 4.16 Direct Messages

```
conversations
├── id                  bigint, PK
├── created_at          timestamp
├── updated_at          timestamp

conversation_participants
├── id                  bigint, PK
├── conversation_id     bigint, FK -> conversations.id
├── user_id             bigint, FK -> users.id
├── last_read_at        timestamp, nullable
├── is_muted            boolean, default false
├── created_at          timestamp

UNIQUE: (conversation_id, user_id)
INDEX: (user_id, last_read_at)

direct_messages
├── id                  bigint, PK
├── conversation_id     bigint, FK -> conversations.id
├── user_id             bigint, FK -> users.id  -- sender
├── body                text
├── created_at          timestamp
├── updated_at          timestamp
├── deleted_at          timestamp, nullable

INDEX: (conversation_id, created_at)
```

### 4.17 Interests / Topics

Using `spatie/laravel-tags` with a `type` of `interest` for both users and groups.

```
-- Managed by spatie/laravel-tags
tags
├── id                  bigint, PK
├── name                json       -- {"en": "Web Development"}
├── slug                json
├── type                string     -- 'interest'
├── order_column        integer, nullable
├── created_at          timestamp
├── updated_at          timestamp

taggables
├── tag_id              bigint, FK -> tags.id
├── taggable_type       string     -- 'App\Models\User' or 'App\Models\Group'
├── taggable_id         bigint

UNIQUE: (tag_id, taggable_type, taggable_id)
INDEX: (taggable_type, taggable_id)
```

### 4.18 Reports

```
reports
├── id                  bigint, PK
├── reporter_id         bigint, FK -> users.id
├── reportable_type     string  -- User, Group, Event, EventComment, Discussion, etc.
├── reportable_id       bigint
├── reason              enum('spam','harassment','hate_speech','impersonation','inappropriate_content','misleading','other')
├── description         text, nullable
├── status              enum('pending','reviewed','resolved','dismissed'), default 'pending'
├── reviewed_by         bigint, FK -> users.id, nullable
├── reviewed_at         timestamp, nullable
├── resolution_notes    text, nullable
├── created_at          timestamp
├── updated_at          timestamp

INDEX: (reportable_type, reportable_id)
INDEX: (status, created_at)
```

### 4.19 Blocks

```
blocks
├── id                  bigint, PK
├── blocker_id          bigint, FK -> users.id
├── blocked_id          bigint, FK -> users.id
├── created_at          timestamp

UNIQUE: (blocker_id, blocked_id)
INDEX: (blocked_id)
```

### 4.20 Notifications

Using Laravel's built-in `notifications` table (database channel).

```
notifications (Laravel default)
├── id                  uuid, PK
├── type                string
├── notifiable_type     string
├── notifiable_id       bigint
├── data                json
├── read_at             timestamp, nullable
├── created_at          timestamp

INDEX: (notifiable_type, notifiable_id, read_at)
```

### 4.21 Notification Preferences

```
notification_preferences
├── id                  bigint, PK
├── user_id             bigint, FK -> users.id
├── channel             enum('email','web','push')
├── type                string  -- notification class name
├── enabled             boolean, default true
├── created_at          timestamp
├── updated_at          timestamp

UNIQUE: (user_id, channel, type)
```

### 4.22 Group-Level Notification Mutes

```
group_notification_mutes
├── id                  bigint, PK
├── user_id             bigint, FK -> users.id
├── group_id            bigint, FK -> groups.id
├── created_at          timestamp

UNIQUE: (user_id, group_id)
```

### 4.23 Platform Settings

```
settings
├── id                  bigint, PK
├── key                 string(255), unique
├── value               text, nullable
├── created_at          timestamp
├── updated_at          timestamp
```

### 4.24 Pending Notification Digests

```
pending_notification_digests
├── id                  bigint, PK
├── user_id             bigint, FK -> users.id
├── notification_type   string(255)  -- notification class name
├── data                json         -- serialized notification payload
├── created_at          timestamp

INDEX: (user_id, notification_type, created_at)
```

---

## 5. Feature Specifications

### 5.1 Authentication & Accounts

#### 5.1.1 Registration

- Standard email/password registration.
- Fields: name, email, password, password confirmation.
- Email verification required before the user can RSVP or join groups.
- After registration, redirect to a profile setup wizard (optional, skippable): set location, interests, bio, avatar.
- Rate limit registration to prevent abuse (5 per IP per hour).

#### 5.1.2 Login

- Email/password login with "remember me" checkbox.
- Rate limit: 5 failed attempts per minute per email, then lockout for 1 minute.
- After login, redirect to the user's dashboard (upcoming events).

#### 5.1.3 Password Reset

- Standard Laravel password reset flow via email link.
- Token expires after 60 minutes.

#### 5.1.4 Email Verification

- Sent on registration.
- Resend link available on a verification notice page.
- Unverified users can browse but cannot join groups or RSVP.

#### 5.1.5 Account Settings

- Update name, email (re-verification required), password.
- Update profile: bio, location (geocoded to lat/lng via Geocodio), timezone, avatar, interests, "looking for" tags.
- Privacy: toggle profile visibility (`public` vs `members_only` -- visible only to people in shared groups).
- Download personal data (JSON export).
- Delete account (soft delete with 30-day grace period, then hard purge via scheduled command).

#### 5.1.6 Account Suspension (Admin)

- Admin can suspend a user, providing a reason.
- Suspended users see a "Your account has been suspended" page on login with the reason.
- Suspended users cannot perform any actions.
- Admin can unsuspend.

### 5.2 Groups

#### 5.2.1 Group Creation

- Any verified user can create a group.
- Required fields: name, description (markdown), location.
- Optional fields: cover photo, topics/interests, visibility, requires_approval, max_members, welcome_message, membership questions.
- Slug auto-generated from name, editable.
- Creator becomes the Primary Organizer (`organizer` role).
- Description rendered from Markdown to HTML on save (stored in `description_html`).

#### 5.2.2 Group Profile Page

- Public-facing page at `/groups/{slug}`.
- Displays: cover photo, name, description, location, member count, upcoming events, organizer info, topics.
- Tabs: Upcoming Events, Past Events, Discussions, Members, About.
- "Join Group" button for non-members (or "Request to Join" if approval required).
- Members see "Leave Group" in a menu.
- Leadership team displayed in the About tab.

#### 5.2.3 Joining a Group

**Open groups (`requires_approval = false`):**
- Click "Join Group" and immediately become a member.
- Welcome message sent via notification.

**Approval-required groups (`requires_approval = true`):**
- Click "Request to Join".
- If membership questions exist, user must answer them.
- Creates a `group_join_requests` record with `pending` status.
- Organizer/Assistant+ receive a notification of the new request.
- Organizer/Assistant+ approve or deny from a "Pending Requests" management page.
- On approval: user becomes a member, welcome message sent.
- On denial: user notified with optional reason.

#### 5.2.4 Leaving a Group

- Any member can leave at any time.
- If the Primary Organizer wants to leave, they must first transfer ownership to another Co-Organizer or delete the group.
- Leaving removes the `group_members` record.
- Any upcoming RSVPs for the group's events are cancelled.

#### 5.2.5 Group Settings (Co-Organizer+)

- Edit: name, slug, description, location, cover photo, topics, visibility, requires_approval, max_members, welcome_message.
- Manage membership questions (add, edit, reorder, delete).
- View and manage join requests (approve/deny).

#### 5.2.6 Member Management (Assistant Organizer+)

- View member list: name, role, joined date, attendance stats (events attended, no-shows).
- Search/filter members.
- Remove a member (with optional reason, member is notified).
- Ban a member (prevents rejoin; with reason).
- Unban a member.
- Export member list as CSV (name, email, joined date, attendance stats).

#### 5.2.7 Leadership Team Management (Co-Organizer+)

- View current leadership team.
- Promote a member to Event Organizer, Assistant Organizer, or Co-Organizer.
- Demote a leadership member back to a lower role.
- Co-Organizers cannot promote anyone to Co-Organizer -- only the Primary Organizer can.
- Co-Organizers cannot demote other Co-Organizers -- only the Primary Organizer can.

#### 5.2.8 Ownership Transfer (Primary Organizer only)

- Transfer ownership to an existing Co-Organizer.
- Previous owner becomes a Co-Organizer.
- Requires password confirmation.

#### 5.2.9 Group Deletion (Primary Organizer only)

- Soft-deletes the group.
- All upcoming events are cancelled (notifications sent).
- Members are notified.
- Group data retained for 90 days, then hard-purged via scheduled command.
- Requires password confirmation.

#### 5.2.10 Group Analytics (Co-Organizer+)

A simple analytics page showing:
- Member growth over time (chart: new members per week/month).
- Event count over time.
- Average attendance rate (RSVPs who actually attended vs no-shows).
- Most active members (by attendance count).
- Average event rating.

Data sourced from existing tables via Eloquent aggregation queries. No separate analytics tables needed for v1.

### 5.3 Events

#### 5.3.1 Event Creation (Event Organizer+)

- Required fields: name, description (markdown), starts_at, event_type.
- Conditional fields:
  - In-person: venue_name, venue_address (geocoded to lat/lng via Geocodio queued job).
  - Online: online_link.
  - Hybrid: all of the above + separate info note in description.
- Optional fields: ends_at, cover_photo, rsvp_limit, guest_limit, rsvp_opens_at, rsvp_closes_at, is_chat_enabled, is_comments_enabled.
- Slug auto-generated from name.
- Can save as draft or publish immediately.
- Publishing triggers notifications to all group members (via queue).
- Creator is automatically assigned as Event Host.

#### 5.3.2 Recurring Events

- When creating an event, organizer can check "Make this recurring".
- Configure recurrence: weekly, biweekly, monthly, custom RRULE.
- Creates an `event_series` record.
- Generates individual event records for the next 3 months (configurable).
- A scheduled command generates additional future events as time passes.
- Editing a recurring event prompts: "Edit this event only" or "Edit this and all future events".
- Cancelling: same prompt for single vs. all future.

#### 5.3.3 Event Page

- Public-facing page at `/groups/{group_slug}/events/{event_slug}`.
- Displays: cover photo, name, description, date/time (in user's timezone with original timezone noted), venue/online link, host(s), attendee count, RSVP button.
- Tabs: Details, Attendees (Going count, Waitlist count), Comments, Chat (if enabled).
- Map embed for in-person events (using Leaflet.js with OpenStreetMap tiles -- free, no API key required).
- "Add to Calendar" button (generates .ics file download).
- For hybrid events: show both venue and online link, let member choose attendance mode at RSVP.

#### 5.3.4 RSVP

- **Going**: Member confirms attendance. If `guest_limit > 0`, can specify number of guests (up to `guest_limit`).
- **Not Going**: Member declines.
- **Waitlisted**: Automatic when RSVP limit is reached. Member clicks "Join Waitlist".
- RSVP only available if:
  - User is a member of the group.
  - Event status is `published`.
  - `rsvp_opens_at` has passed (if set).
  - `rsvp_closes_at` has not passed (if set).
  - Event `starts_at` is in the future (or event has not ended if `ends_at` is set).
- Changing RSVP from Going to Not Going: frees a spot, next waitlisted member is automatically promoted (FIFO) via a queued job. The promoted member is notified.
- Waitlist promotion for members with guests: skip if not enough spots for member + guests, promote the next eligible person. Revisit skipped members if more spots open.
- RSVP confirmation notification sent on Going/Waitlisted.

#### 5.3.5 Attendee Management (Event Host / Event Organizer+)

- View attendee list with columns: name, RSVP status, guest count, checked-in status.
- Tabs: Going, Waitlisted, Not Going.
- Manually change a member's RSVP status.
- Move a waitlisted member to Going (manual override, skipping queue).
- Remove an RSVP.
- Check-in: mark individual attendees as checked in (check-in button per row).
- After event: mark attendance (attended / no-show).
- Export attendee list as CSV.

#### 5.3.6 Event Editing

- Event Organizer+ or assigned Event Host can edit.
- Editable fields: name, description, date/time, venue, online link, cover photo, RSVP settings.
- Editing a published event sends an "Event Updated" notification to all Going/Waitlisted members.
- Events can be edited even after they start, up until 24 hours after `ends_at` (or 24 hours after `starts_at` if no `ends_at`).

#### 5.3.7 Event Cancellation

- Event Organizer+ can cancel an event.
- Sets status to `cancelled`, records `cancelled_at` and optional `cancellation_reason`.
- Sends cancellation notification to all Going/Waitlisted members.
- RSVPs are retained for record-keeping but become inactive.
- Cancelled events appear in Past Events list marked as "Cancelled".

#### 5.3.8 Event Comments

- Threaded comments on the event page (one level of nesting: parent + replies).
- Any group member can comment (if `is_comments_enabled`).
- Markdown supported in comment body.
- Like/unlike a comment.
- Soft delete by author or leadership.
- Notifications: new comment to hosts/Going members, reply to parent comment author, like to comment author.

#### 5.3.9 Event Chat (Real-time)

- Available when `is_chat_enabled` is true.
- Powered by Laravel Reverb (WebSocket).
- Auto-enrolled: members who are RSVP Going.
- Non-RSVP group members can view and optionally join the chat.
- Features: send message, reply to a specific message, edit own message, delete own message (soft delete), emoji reactions (using Unicode, no custom emoji system).
- Messages rendered in real-time via Reverb broadcasting.
- Chat history persisted in `event_chat_messages` table.
- Leadership can delete any message.
- **Rate limiting:** Chat messages are rate-limited to 10 messages per 15 seconds per user per event. Exceeding the limit returns a throttle error and the message is not sent. Implemented via Laravel's `RateLimiter` in the Livewire component. DM rate limit: 20 messages per minute per user.

#### 5.3.10 Event Feedback

- After an event ends (determined by `ends_at` or `starts_at + 3 hours` if no `ends_at`), attendees who were Going can leave feedback.
- Rating: 1-5 stars (required).
- Written feedback: optional text.
- One feedback per user per event.
- Feedback visible to:
  - Organizer+: all feedback with attribution.
  - Members: aggregate rating (average + count). Individual feedback text is anonymous to non-organizers.
- Organizer can respond to feedback (visible alongside the feedback).

### 5.4 Discussions

#### 5.4.1 Discussion Threads

- Group members can create discussion threads within a group.
- Fields: title, body (markdown).
- Slug auto-generated from title.
- Displayed on the group page under the Discussions tab, ordered by `last_activity_at` (pinned first).

#### 5.4.2 Discussion Replies

- Any group member can reply.
- Markdown supported.
- Flat threading (replies are chronological, no nested replies on discussions -- keep it simple).
- Replying updates `last_activity_at` on the parent discussion.

#### 5.4.3 Discussion Moderation

- Co-Organizer+ can pin/unpin a discussion.
- Co-Organizer+ can lock a discussion (prevents new replies).
- Co-Organizer+ can delete a discussion (soft delete).
- Any user can delete their own replies (soft delete).
- Co-Organizer+ can delete any reply.

### 5.5 Direct Messages

#### 5.5.1 Starting a Conversation

- Any verified user can initiate a DM with any other user (unless blocked).
- Navigate to a user's profile, click "Message".
- Creates a `conversation` with two `conversation_participants`.
- 1:1 conversations only (no group DMs in v1).
- If a conversation already exists between two users, reopen it instead of creating a new one.

#### 5.5.2 Conversation View

- List of conversations sorted by most recent message.
- Unread indicator based on `last_read_at` vs latest message timestamp.
- Message thread with sender name, avatar, timestamp.
- Real-time updates via Reverb.
- Soft delete own messages.

#### 5.5.3 Muting & Blocking

- Mute a conversation: stop receiving notifications but conversation remains accessible.
- Block a user: prevents them from sending new messages (existing conversation is hidden for both). Blocked user cannot see your profile.
- Unblock: conversation reappears but blocked period messages are not retroactively shown.

### 5.6 Profiles

#### 5.6.1 Public Profile Page

- URL: `/members/{user_id}` (using ID, not slug, for simplicity and uniqueness).
- Displays: avatar, name, bio, location, interests, "looking for" tags, groups in common (with viewer).
- If `profile_visibility = members_only`, only visible to users who share at least one group.
- "Message" button (opens DM).
- "Report" and "Block" options in a dropdown menu.

#### 5.6.2 User Dashboard (Authenticated Home)

- URL: `/dashboard`
- Sections:
  - **Upcoming Events**: events the user has RSVP'd Going to, sorted by date. Includes group name, event name, date/time, venue.
  - **Your Groups**: groups the user is a member of, with next upcoming event per group.
  - **Suggested Events**: algorithmically recommended events (see Search & Discovery).
  - **Notifications**: recent unread notifications.

### 5.7 Search & Discovery

#### 5.7.1 Explore Page

- URL: `/explore`
- Default view: events near the user's location (if set), or popular events.
- Filters: topic/interest, date range, event type (in-person/online/hybrid), distance radius.
- Search bar: keyword search across event names and descriptions.
- Results sorted by relevance (keyword match) or date (upcoming first).
- Unauthenticated users see the Explore page as the homepage.

#### 5.7.2 Group Search

- URL: `/groups` with search/filter controls.
- Search by name, description keywords.
- Filter by topic/interest, location/distance.
- Sort by: relevance, newest, most members, most active (recent events).

#### 5.7.3 Full-Text Search

- Powered by Laravel Scout.
- Searchable models: `Group` (name, description), `Event` (name, description), `User` (name, bio -- only if profile is public).
- Default driver: `meilisearch` (included in Sail, good relevance ranking, fast).
- Fallback driver: `database` (works everywhere, no external dependency, slower on large datasets).
- Search accessible from a global search bar in the navbar.

#### 5.7.4 Location-Based Discovery

- Users with a location set get geo-sorted results.
- Distance calculated using Haversine formula in database queries.
- Default radius: 50km, adjustable by user.
- Events inherit location from their venue (in-person) or group (fallback).
- Online events are not filtered by location but shown in a separate section.

#### 5.7.5 Recommendations (Simple, v1)

- On the dashboard, show "Suggested Events" based on:
  - Events in groups the user is a member of (that they haven't RSVP'd to yet).
  - Events in groups that share topics with the user's interests, within their location radius.
- Ordered by: starts_at ascending (soonest first).
- No ML/AI -- just basic query filtering.

### 5.8 Reporting & Moderation

#### 5.8.1 User Reporting

- Any user can report: another user, a group, an event, an event comment, a discussion, a discussion reply, a chat message.
- Report form: select reason (from enum list), optional description text.
- One active report per reporter per item (prevent duplicate reports).
- Reporter receives a confirmation that the report has been submitted.

#### 5.8.2 Admin Moderation Dashboard

- URL: `/admin/reports`
- List of pending reports, sorted by newest first.
- Each report shows: reporter, reported item (with link), reason, description, date.
- Admin actions: view the content, mark as reviewed, resolve (with notes), dismiss.
- Admin can also directly: suspend a user, delete a group/event/comment from the report view.
- Aggregate view: if an item has multiple reports, show them grouped.

#### 5.8.3 Admin Dashboard

- URL: `/admin`
- Platform stats: total users, total groups, total events, events this month, new users this week.
- Recent reports needing review.
- Recently created groups.
- Quick links to manage users, groups, reports, settings, interests.

#### 5.8.4 Admin User Management

- URL: `/admin/users`
- Searchable/filterable list of all users.
- View user details, groups they are in, events attended.
- Suspend / unsuspend user.
- Delete user (hard delete -- requires confirmation).

#### 5.8.5 Admin Group Management

- URL: `/admin/groups`
- Searchable/filterable list of all groups.
- View group details.
- Delete group (hard delete -- requires confirmation).

#### 5.8.6 Admin Interest/Topic Management

- URL: `/admin/interests`
- CRUD for the interest/topic taxonomy.
- Merge duplicate interests.
- See usage count per interest (how many groups/users use it).

#### 5.8.7 Admin Platform Settings

- URL: `/admin/settings`
- Configurable settings stored in the `settings` table:
  - `site_name`: Display name of the instance (default: "Greetup").
  - `site_description`: Tagline/description for the instance.
  - `registration_enabled`: Toggle open registration on/off (default: true).
  - `require_email_verification`: Toggle email verification requirement (default: true).
  - `max_groups_per_user`: Maximum groups a user can create (default: null / unlimited).
  - `default_timezone`: Instance default timezone.
  - `default_locale`: Instance default locale.

### 5.9 Cross-Cutting Concerns

#### 5.9.1 Image Uploads

All image uploads use `spatie/laravel-medialibrary` and share these constraints:

| Context | Max File Size | Accepted Formats | Generated Thumbnails |
|---------|--------------|-----------------|---------------------|
| User avatar | 2 MB | JPEG, PNG, WebP | 44x44 (nav), 96x96 (profile card), 256x256 (profile page) |
| Group cover photo | 5 MB | JPEG, PNG, WebP | 400x200 (card), 1200x400 (group page header) |
| Event cover photo | 5 MB | JPEG, PNG, WebP | 400x200 (card), 1200x400 (event page header) |

- Images are resized and optimized on upload via `intervention/image`.
- Original files are stored but never served directly — only generated conversions are public.
- Uploads exceeding the size limit return a 422 validation error.
- Non-image files or unsupported formats are rejected at the validation layer.

#### 5.9.2 Markdown Rendering & Sanitization

All user-supplied markdown (group descriptions, event descriptions, discussion bodies, comments) is rendered to HTML using `league/commonmark` with the following security measures:

- HTML tags in markdown input are **stripped** (not rendered). Users cannot inject raw HTML.
- Output is sanitized via CommonMark's `DisallowedRawHtmlExtension` to block `<script>`, `<iframe>`, `<object>`, `<embed>`, and other dangerous elements.
- Links are rendered with `rel="nofollow noopener"` and `target="_blank"`.
- The rendered HTML is stored in `*_html` columns (e.g., `description_html`, `body_html`) and served directly — markdown is not re-rendered on every page view.
- The `MarkdownService` handles rendering and is the single point of configuration for CommonMark extensions and security settings.

#### 5.9.3 Pagination

All list views are paginated using Laravel's built-in pagination:

| View | Items Per Page | Pagination Style |
|------|---------------|-----------------|
| Explore page (events) | 12 | Cursor pagination (infinite scroll via Livewire) |
| Group search results | 12 | Cursor pagination |
| Event attendee list | 20 | Standard pagination |
| Group member list | 20 | Standard pagination |
| Discussion list | 15 | Standard pagination |
| Discussion replies | 20 | Standard pagination |
| Event comments | 15 | Standard pagination |
| Direct message conversations | 20 | Standard pagination |
| Direct messages (within conversation) | 30 | Cursor pagination (load older on scroll) |
| Admin user/group/report lists | 25 | Standard pagination |
| Notification dropdown | 10 | Load more button |

#### 5.9.4 Timezone Handling

- All timestamps are stored in UTC in the database.
- The **event's timezone** is the canonical display timezone for that event (inherited from the group by default, overridable per event).
- On the event page, date/time is displayed in the event's timezone as the primary format (e.g., "Tuesday, March 24 at 18:30 CET").
- If the authenticated user's timezone differs from the event's timezone, a secondary line shows the time in the user's local timezone (e.g., "9:30 AM your time (PST)").
- On the dashboard and explore page, event times are shown in the user's timezone (or the instance default for guests).
- Event creation/editing forms accept date/time input in the event's timezone (i.e., the group's timezone). The backend converts to UTC for storage.
- The `timezone` field on users, groups, and events stores IANA timezone identifiers (e.g., `Europe/Copenhagen`, `America/New_York`).

#### 5.9.5 Notification Batching / Digest

When a notification type fires more than 5 times for the same recipient within 15 minutes, subsequent notifications are batched into a single digest email instead of individual emails:

- A `pending_notification_digests` table tracks pending digest items: `user_id`, `notification_type`, `data` (JSON), `created_at`.
- A scheduled command `notifications:send-digests` runs every 5 minutes. It groups pending items by `(user_id, notification_type)`, renders a single digest email for each group, sends it, and deletes the pending records.
- Web (in-app) notifications are never batched — each fires individually for real-time accuracy.
- Only email channel is affected by batching.

---

## 6. URL Structure & Routes

### 6.1 Public / Guest Routes

```
GET   /                           Homepage (Explore page if guest, Dashboard if auth)
GET   /explore                    Explore events and groups
GET   /search                     Global search results
GET   /groups                     Browse groups
GET   /groups/{slug}              Group profile page
GET   /groups/{slug}/events/{event_slug}  Event page
GET   /members/{id}               User profile
GET   /login                      Login form
POST  /login                      Login
GET   /register                   Registration form
POST  /register                   Register
GET   /forgot-password            Password reset request
POST  /forgot-password            Send reset link
GET   /reset-password/{token}     Password reset form
POST  /reset-password             Reset password
GET   /email/verify               Verification notice
GET   /email/verify/{id}/{hash}   Verify email
POST  /email/verification-notification  Resend verification
```

### 6.2 Authenticated Member Routes

```
GET   /dashboard                  User dashboard
POST  /logout                     Logout

-- Profile & Account
GET   /settings                   Account settings
PUT   /settings/profile           Update profile
PUT   /settings/account           Update email/password
PUT   /settings/notifications     Update notification preferences
GET   /settings/data-export       Download personal data
DELETE /settings/account          Delete account

-- Groups
POST  /groups                     Create group
GET   /groups/create              Group creation form
GET   /groups/{slug}/join         Join or request to join
POST  /groups/{slug}/join         Submit join request (with answers)
POST  /groups/{slug}/leave        Leave group
GET   /groups/{slug}/discussions  Discussions list
POST  /groups/{slug}/discussions  Create discussion
GET   /groups/{slug}/discussions/{disc_slug}  View discussion
POST  /groups/{slug}/discussions/{disc_slug}/replies  Post reply

-- Events
GET   /groups/{slug}/events/{event_slug}/rsvp   RSVP form (Livewire component)
POST  /groups/{slug}/events/{event_slug}/rsvp   Submit RSVP
DELETE /groups/{slug}/events/{event_slug}/rsvp   Cancel RSVP
POST  /groups/{slug}/events/{event_slug}/comments  Post comment
GET   /groups/{slug}/events/{event_slug}/calendar  Download .ics file
POST  /groups/{slug}/events/{event_slug}/feedback  Submit feedback

-- Messages
GET   /messages                   Conversation list
GET   /messages/{conversation}    View conversation
POST  /messages                   Start new conversation
POST  /messages/{conversation}    Send message

-- Social
POST  /members/{id}/block         Block user
DELETE /members/{id}/block        Unblock user
POST  /reports                    Submit report
```

### 6.3 Group Management Routes (Role-Gated)

```
-- Event Organizer+ within group
GET   /groups/{slug}/events/create          Create event form
POST  /groups/{slug}/events                 Store event
GET   /groups/{slug}/events/{event_slug}/edit   Edit event form
PUT   /groups/{slug}/events/{event_slug}    Update event
POST  /groups/{slug}/events/{event_slug}/cancel   Cancel event
GET   /groups/{slug}/events/{event_slug}/attendees  Manage attendees
POST  /groups/{slug}/events/{event_slug}/checkin/{user}  Check in attendee

-- Assistant Organizer+
GET   /groups/{slug}/manage/members         Member management
GET   /groups/{slug}/manage/requests        Join requests
POST  /groups/{slug}/manage/requests/{id}/approve  Approve request
POST  /groups/{slug}/manage/requests/{id}/deny     Deny request
POST  /groups/{slug}/manage/members/{id}/remove    Remove member
POST  /groups/{slug}/manage/members/{id}/ban       Ban member
POST  /groups/{slug}/manage/members/{id}/unban     Unban member

-- Co-Organizer+
GET   /groups/{slug}/manage/settings        Group settings
PUT   /groups/{slug}/manage/settings        Update settings
GET   /groups/{slug}/manage/team            Leadership team
POST  /groups/{slug}/manage/team/{id}/role  Change member role
GET   /groups/{slug}/manage/analytics       Group analytics

-- Primary Organizer
GET   /groups/{slug}/manage/transfer        Transfer ownership
POST  /groups/{slug}/manage/transfer        Confirm transfer
DELETE /groups/{slug}                        Delete group
```

### 6.4 Admin Routes

```
GET   /admin                          Dashboard
GET   /admin/users                    User list
GET   /admin/users/{id}               User detail
POST  /admin/users/{id}/suspend       Suspend user
POST  /admin/users/{id}/unsuspend     Unsuspend user
DELETE /admin/users/{id}              Delete user

GET   /admin/groups                   Group list
GET   /admin/groups/{id}              Group detail
DELETE /admin/groups/{id}             Delete group

GET   /admin/reports                  Report list
GET   /admin/reports/{id}             Report detail
POST  /admin/reports/{id}/resolve     Resolve report
POST  /admin/reports/{id}/dismiss     Dismiss report

GET   /admin/interests                Interest list
POST  /admin/interests                Create interest
PUT   /admin/interests/{id}           Update interest
DELETE /admin/interests/{id}          Delete interest
POST  /admin/interests/merge          Merge interests

GET   /admin/settings                 Platform settings
PUT   /admin/settings                 Update settings
```

### 6.5 API Routes (Optional, v1)

No separate API in v1. All interactivity handled via Livewire and standard form submissions. A JSON API can be considered for v2.

### 6.6 WebSocket Channels (Reverb)

```
private   event.{eventId}.chat        Event Chat messages
private   conversation.{conversationId}  Direct messages
private   user.{userId}.notifications  Real-time notification count updates
```

---

## 7. Notifications

### 7.1 Notification Types

| Notification | Recipient(s) | Channels |
|-------------|-------------|----------|
| `WelcomeToGroup` | New member | web, email |
| `JoinRequestReceived` | Group Organizer/Assistant+ | web, email |
| `JoinRequestApproved` | Requesting user | web, email |
| `JoinRequestDenied` | Requesting user | web, email |
| `NewEvent` | All group members | web, email |
| `EventUpdated` | Going/Waitlisted members | web, email |
| `EventCancelled` | Going/Waitlisted members | web, email |
| `RsvpConfirmation` | RSVPing user | web, email |
| `PromotedFromWaitlist` | Promoted user | web, email |
| `NewEventComment` | Event host + Going members | web |
| `EventCommentReply` | Parent comment author | web, email |
| `EventCommentLiked` | Comment author | web |
| `NewEventFeedback` | Event host + Organizer+ | web |
| `NewDiscussion` | Group members | web |
| `NewDiscussionReply` | Discussion author + previous repliers | web, email |
| `NewDirectMessage` | Conversation participant | web, email |
| `MemberRemoved` | Removed member | web, email |
| `MemberBanned` | Banned member | web, email |
| `RoleChanged` | Affected member | web, email |
| `OwnershipTransferred` | New owner | web, email |
| `GroupDeleted` | All group members | email |
| `ReportReceived` | Platform admins | web, email |
| `AccountSuspended` | Suspended user | email |

### 7.2 Delivery Rules

- Email notifications are queued and sent via the queue worker.
- Web notifications appear in the bell icon dropdown in the navbar (badge with unread count).
- Users can mute notifications per group (suppresses all non-critical notifications from that group).
- Users can configure per-type preferences (enable/disable email and/or web per notification type).
- Rate limiting: batch similar notifications (e.g., multiple RSVPs to an event host) into digest-style emails if more than 5 occur within 15 minutes.
- Blocked users never generate notifications to the blocker.
- Suspended users do not receive notifications.

---

## 8. Search & Discovery

### 8.1 Scout Configuration

```php
// Searchable models and their indexed fields:

Group::class => [
    'name',          // weight: high
    'description',   // weight: medium
    'location',      // weight: low
]

Event::class => [
    'name',          // weight: high
    'description',   // weight: medium
    'venue_name',    // weight: low
]

User::class => [
    'name',          // weight: high
    'bio',           // weight: low
    // Only indexed if profile_visibility = 'public'
]
```

### 8.2 Geocoding & Geo Queries

**Address Resolution (Geocodio):**

When a user saves an address on a group, event, or profile, a `GeocodeLocation` queued job dispatches to resolve the address to coordinates via the Geocodio API. The resolved lat/lng is stored on the model. This means:

- Form submissions are never blocked by geocoding latency.
- If Geocodio is unreachable, the job retries (3 attempts, exponential backoff).
- If the API key is missing, the job is silently skipped and coordinates remain null.
- Coordinates are only re-resolved if the address text actually changes.

```php
// Example: dispatched from Group observer on address change
GeocodeLocation::dispatch($group, $group->location);
```

**Proximity Sorting (Haversine):**

For location-based sorting, use a raw Haversine query scope on models with lat/lng:

```php
// Example scope on Group model
public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 50)
{
    $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude))
        * cos(radians(longitude) - radians(?)) + sin(radians(?))
        * sin(radians(latitude))))";

    return $query
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->whereRaw("$haversine < ?", [$lat, $lng, $lat, $radiusKm])
        ->orderByRaw("$haversine", [$lat, $lng, $lat]);
}
```

Works on MySQL and PostgreSQL without extensions. For production performance on large datasets, consider adding a spatial index on the `(latitude, longitude)` columns in MySQL using `SPATIAL INDEX`.

### 8.3 Homepage / Explore Logic

**Guest visitor:**
- Show Explore page with popular upcoming events (most RSVPs) and featured groups (most members, most active).
- If browser geolocation is available (via JS prompt), reverse-geocode via Geocodio and show nearby events.

**Authenticated user with location:**
- Show nearby events matching their interests first.
- Then events in their groups they have not RSVP'd to.
- Then popular events in their area.

**Authenticated user without location:**
- Show events in their groups first.
- Then popular events.
- Prompt to set location for better recommendations.

---

## 9. Seed Data Plan

The seeder should create a realistic, well-populated demo instance. All seeders should be idempotent (use `firstOrCreate` or truncate-and-reseed pattern).

### 9.1 Seeder Structure

```
database/seeders/
├── DatabaseSeeder.php          -- orchestrates all seeders
├── InterestSeeder.php          -- topic taxonomy
├── UserSeeder.php              -- demo users
├── GroupSeeder.php             -- demo groups with members
├── EventSeeder.php             -- events across groups
├── RsvpSeeder.php              -- RSVPs and waitlist entries
├── DiscussionSeeder.php        -- group discussions with replies
├── EventCommentSeeder.php      -- event comments
├── EventFeedbackSeeder.php     -- ratings for past events
├── DirectMessageSeeder.php     -- sample conversations
├── ReportSeeder.php            -- sample reports
├── SettingsSeeder.php          -- default platform settings
```

### 9.2 Seed Data Inventory

#### Interests (30+)

Seeded categories spanning common meetup topics:

Technology: "Web Development", "Mobile Development", "Data Science", "Machine Learning", "DevOps", "Cybersecurity", "Open Source", "Game Development"

Languages & Frameworks: "PHP", "Laravel", "JavaScript", "Python", "Rust", "Go", "React", "Vue.js"

Creative: "Photography", "Writing", "Music", "Art", "Film"

Lifestyle: "Hiking", "Running", "Cycling", "Cooking", "Board Games", "Book Club", "Language Exchange", "Parenting"

Professional: "Entrepreneurship", "Marketing", "Design", "Product Management"

#### Users (50)

| User | Role | Notes |
|------|------|-------|
| Admin User | Platform admin | `admin@greetup.test`, password: `password` |
| Demo User | Regular user | `user@greetup.test`, password: `password` |
| 8 Organizers | Create/own groups | Named realistically, diverse locations |
| 40 Regular Users | Members of various groups | Mix of active and less active |

All demo users have:
- Realistic names (using Faker)
- Locations spread across 3-4 cities (e.g., Copenhagen, Berlin, London, New York)
- Random subset of interests (3-8 each)
- Profile bios
- Verified emails

#### Groups (8)

| # | Group | Type | Members | Approval | Notes |
|---|-------|------|---------|----------|-------|
| 1 | Copenhagen Laravel Meetup | Tech | 35 | No | Most active group, lots of events |
| 2 | Berlin JavaScript Community | Tech | 28 | No | Active, online + in-person |
| 3 | London Book Club | Lifestyle | 20 | Yes | Requires approval with questions |
| 4 | NYC Hiking Adventures | Lifestyle | 25 | No | Outdoor events |
| 5 | Copenhagen Photography Walks | Creative | 15 | No | Smaller group |
| 6 | Remote Workers Denmark | Professional | 22 | No | Mix of online/hybrid |
| 7 | Board Game Nights CPH | Lifestyle | 18 | No | Frequent recurring events |
| 8 | Women in Tech Berlin | Tech | 20 | Yes | Approval required |

Each group has:
- Realistic description (2-3 paragraphs of markdown)
- Cover photo placeholder (generated solid-color image with group name text)
- 2-5 interests tagged
- Leadership team (1 organizer, 1-2 co-organizers, 1-2 assistant organizers, 1-2 event organizers)
- Welcome message (for approval-required groups)
- Membership questions (for approval-required groups, 2-3 questions each)

#### Events (40+)

Per group, seed a mix of:

- 2-3 past events (1-6 months ago) with attendance marked and feedback submitted.
- 2-3 upcoming events (next 1-4 weeks).
- 1 draft event.
- 1 cancelled event.
- 1 recurring event series (weekly or monthly) with 4+ instances.

Event details:
- Realistic titles ("March Laravel Meetup: Livewire Deep Dive", "Saturday Morning Hike: Dyrehaven Trail").
- Markdown descriptions with agenda, speaker info, or activity details.
- Venue addresses for in-person events (real addresses in the group's city).
- Online links for online/hybrid events (placeholder Zoom/Jitsi URLs).
- Mix of events with and without RSVP limits.
- Events with guest allowances.
- At least 2 events at capacity with waitlists.

#### RSVPs (200+)

- Distribute RSVPs across events so some are lightly attended and some are full.
- At least 2 events should have waitlisted members.
- Past events should have `attended` / `no_show` marked for ~80% of Going RSVPs.
- Some members RSVP with guests.

#### Discussions (15+)

- 2-3 discussions per active group.
- Each with 3-10 replies from different members.
- 1 pinned discussion per group.
- 1 locked discussion (in the largest group).

#### Event Comments (50+)

- 3-8 comments on upcoming events.
- Some with replies (1 level of threading).
- Some with likes.

#### Event Feedback (30+)

- Feedback on past events from attending members.
- Ratings distributed: mostly 4-5 stars, some 3s, rare 1-2s.
- ~50% include written feedback text.

#### Direct Messages (10 conversations)

- 5-10 conversations between various users.
- 2-5 messages per conversation.
- Mix of read and unread states.

#### Reports (5)

- 3 pending reports (spam comment, harassment via DM, inappropriate group).
- 1 resolved report.
- 1 dismissed report.

### 9.3 Geocoding in Seed Data

Seed data includes **hardcoded lat/lng coordinates** for all groups, events, and user locations. This means seeding works without a Geocodio API key. The coordinates are realistic values for the cities used in the seed data (Copenhagen, Berlin, London, New York).

If a `GEOCODIO_API_KEY` is configured, the seeder can optionally run `greetup:geocode-missing` after seeding to re-resolve addresses via Geocodio, but this is not required.

### 9.4 Seed Command

```bash
php artisan db:seed                    # Run all seeders
php artisan db:seed --class=UserSeeder # Run specific seeder
php artisan migrate:fresh --seed       # Fresh DB with seed data
```

---

## 10. Test Plan

### 10.0 Test Strategy Overview

All tests run against **MySQL** — both locally (via Sail) and in CI. This ensures test behavior matches production and catches MySQL-specific issues (e.g., strict mode, collation, JSON column handling) that would be missed with SQLite.

The test suite has three tiers:

| Tier | Tool | Purpose | Runs In CI | Speed |
|------|------|---------|-----------|-------|
| **Unit** | Pest | Pure logic: models, services, policies, rules. No HTTP, no database (mocked where needed). | Yes (parallel) | Fast |
| **Feature (Integration)** | Pest | Full HTTP request/response cycle: routes, controllers, validation, authorization, database, queued jobs, notifications. Uses `RefreshDatabase`. | Yes (parallel) | Medium |
| **Component** | Pest | Blade component rendering in isolation. Asserts HTML output. | Yes (parallel) | Fast |
| **Browser (E2E)** | Laravel Dusk | Complete user flows including JavaScript, Livewire, and WebSocket interactions via a real browser. | Yes (separate job) | Slow |

**Test database:** All tiers that touch the database use MySQL. Feature tests use the `RefreshDatabase` trait (wraps each test in a transaction). Browser tests use `DatabaseMigrations` (migrates fresh per test class).

### 10.1 Test Organization

```
tests/
├── Unit/
│   ├── Models/
│   │   ├── UserTest.php
│   │   ├── GroupTest.php
│   │   ├── EventTest.php
│   │   ├── RsvpTest.php
│   │   ├── DiscussionTest.php
│   │   ├── EventCommentTest.php
│   │   ├── EventChatMessageTest.php
│   │   ├── ConversationTest.php
│   │   ├── ReportTest.php
│   │   └── BlockTest.php
│   ├── Services/
│   │   ├── WaitlistServiceTest.php
│   │   ├── RsvpServiceTest.php
│   │   ├── GeocodingServiceTest.php
│   │   ├── MarkdownServiceTest.php
│   │   ├── NotificationServiceTest.php
│   │   └── SearchServiceTest.php
│   ├── Policies/
│   │   ├── GroupPolicyTest.php
│   │   ├── EventPolicyTest.php
│   │   ├── DiscussionPolicyTest.php
│   │   ├── EventChatPolicyTest.php
│   │   └── ReportPolicyTest.php
│   └── Rules/
│       └── ... (custom validation rules)
├── Feature/
│   ├── Auth/
│   │   ├── RegistrationTest.php
│   │   ├── LoginTest.php
│   │   ├── PasswordResetTest.php
│   │   ├── EmailVerificationTest.php
│   │   └── AccountDeletionTest.php
│   ├── Profile/
│   │   ├── ProfileUpdateTest.php
│   │   ├── ProfileVisibilityTest.php
│   │   ├── ImageUploadTest.php
│   │   └── NotificationPreferencesTest.php
│   ├── Groups/
│   │   ├── CreateGroupTest.php
│   │   ├── ViewGroupTest.php
│   │   ├── JoinGroupTest.php
│   │   ├── LeaveGroupTest.php
│   │   ├── GroupSettingsTest.php
│   │   ├── MemberManagementTest.php
│   │   ├── LeadershipTeamTest.php
│   │   ├── OwnershipTransferTest.php
│   │   ├── GroupDeletionTest.php
│   │   ├── MembershipApprovalTest.php
│   │   ├── GroupAnalyticsTest.php
│   │   └── GroupSearchTest.php
│   ├── Events/
│   │   ├── CreateEventTest.php
│   │   ├── EditEventTest.php
│   │   ├── ViewEventTest.php
│   │   ├── CancelEventTest.php
│   │   ├── RecurringEventTest.php
│   │   ├── RsvpTest.php
│   │   ├── WaitlistTest.php
│   │   ├── AttendeeManagementTest.php
│   │   ├── CheckInTest.php
│   │   ├── EventCommentsTest.php
│   │   ├── EventChatTest.php
│   │   ├── EventFeedbackTest.php
│   │   ├── EventCalendarExportTest.php
│   │   └── EventSearchTest.php
│   ├── Discussions/
│   │   ├── CreateDiscussionTest.php
│   │   ├── DiscussionRepliesTest.php
│   │   └── DiscussionModerationTest.php
│   ├── Messages/
│   │   ├── ConversationTest.php
│   │   ├── DirectMessageTest.php
│   │   └── BlockingTest.php
│   ├── Notifications/
│   │   ├── EventNotificationsTest.php
│   │   ├── GroupNotificationsTest.php
│   │   ├── MessageNotificationsTest.php
│   │   ├── NotificationMutingTest.php
│   │   └── NotificationDigestTest.php
│   ├── Discovery/
│   │   ├── ExplorePageTest.php
│   │   ├── GlobalSearchTest.php
│   │   ├── NearbyEventsTest.php
│   │   └── RecommendationsTest.php
│   ├── Reporting/
│   │   ├── ReportContentTest.php
│   │   └── ReportHandlingTest.php
│   └── Admin/
│       ├── AdminDashboardTest.php
│       ├── AdminUserManagementTest.php
│       ├── AdminGroupManagementTest.php
│       ├── AdminReportManagementTest.php
│       ├── AdminInterestManagementTest.php
│       └── AdminSettingsTest.php
├── Component/
│   ├── BlobTest.php
│   ├── AvatarTest.php
│   ├── AvatarStackTest.php
│   ├── BadgeTest.php
│   ├── PillTest.php
│   ├── DateBlockTest.php
│   ├── EventCardTest.php
│   ├── EventRowTest.php
│   ├── StatCardTest.php
│   ├── ProgressBarTest.php
│   ├── TabBarTest.php
│   └── EmptyStateTest.php
└── Browser/
    ├── Auth/
    │   ├── LoginFlowTest.php
    │   ├── RegistrationFlowTest.php
    │   └── PasswordResetFlowTest.php
    ├── Groups/
    │   ├── BrowseGroupsTest.php
    │   ├── JoinGroupFlowTest.php
    │   ├── CreateGroupFlowTest.php
    │   └── ManageGroupFlowTest.php
    ├── Events/
    │   ├── BrowseEventsTest.php
    │   ├── RsvpFlowTest.php
    │   ├── CreateEventFlowTest.php
    │   ├── EventChatTest.php
    │   └── EventFeedbackFlowTest.php
    ├── Discovery/
    │   ├── ExplorePageTest.php
    │   └── SearchFlowTest.php
    ├── Messages/
    │   └── DirectMessageFlowTest.php
    └── Admin/
        ├── AdminDashboardTest.php
        └── ModerationFlowTest.php
```

### 10.2 Test Coverage Targets

| Layer | Metric | Target |
|-------|--------|--------|
| Unit | Line coverage on models, services, policies | >= 95% |
| Component | Blade component rendering | 100% of components listed in Appendix A |
| Feature | Route/controller coverage | 100% of routes tested (both happy path and error cases) |
| Feature | Every authorization check | 100% (both allow and deny cases for every role) |
| Feature | Every validation rule | 100% (valid input, each invalid case) |
| Feature | Notification dispatch | 100% of notification types (assert sent AND assert correct recipients) |
| Feature | Queue job dispatch | 100% of jobs (assert dispatched with correct payload) |
| Browser (Dusk) | Critical user flows | 100% of flows listed below |
| Overall | Minimum combined coverage | >= 90% line coverage |

### 10.3 Unit Test Specifications

#### Models

Each model test covers:
- Factory creation (ensure factory produces valid models).
- Relationship definitions (e.g., `Group->members()`, `Event->rsvps()`).
- Scopes (e.g., `Event::upcoming()`, `Group::active()`, `Group::nearby()`).
- Accessors and mutators.
- Casts (enums, dates, JSON fields).
- Soft delete behavior (where applicable).
- Slug generation (Group, Event, Discussion).

#### Services

**WaitlistServiceTest:**
- Promoting the next eligible member when a spot opens.
- Skipping members with guests when insufficient spots.
- Revisiting skipped members when enough spots open.
- No promotion when waitlist is empty.
- No promotion for cancelled events.

**RsvpServiceTest:**
- Creating a Going RSVP when spots available.
- Creating a Waitlisted RSVP when event is full.
- Cancelling RSVP and triggering waitlist promotion.
- Preventing RSVP when not a group member.
- Preventing RSVP when RSVP window is closed.
- Preventing RSVP on cancelled/past events.
- Guest count validation (within guest_limit).

**GeocodingServiceTest:**
- Forward geocoding returns lat/lng for a valid address (mocked Geocodio client).
- Forward geocoding returns null for an invalid/unresolvable address.
- Reverse geocoding returns formatted address for valid coordinates.
- Batch geocoding processes multiple addresses in a single API call.
- Graceful degradation: returns null when API key is not configured.
- Graceful degradation: returns null when Geocodio API returns an error.
- Haversine distance calculation accuracy (nearby scope).
- Nearby scope returns correct results within radius.
- Nearby scope excludes results outside radius.
- Handling null lat/lng gracefully.

**MarkdownServiceTest:**
- Renders markdown to HTML correctly (headings, lists, links, code blocks).
- Strips raw HTML tags from input (no `<script>`, `<iframe>`, etc.).
- Adds `rel="nofollow noopener"` and `target="_blank"` to links.
- Returns empty string for null/empty input.

**NotificationServiceTest:**
- Dispatches notification to correct recipients.
- Respects group notification mutes (suppresses notifications for muted groups).
- Respects user blocks (blocked users don't generate notifications to the blocker).
- Respects per-type notification preferences (disabled channels are skipped).
- Triggers digest batching when threshold exceeded (5+ of same type in 15 minutes).

#### Policies

Each policy test verifies the permission matrix from Section 3.4:
- Test every action with every role (both allowed and denied).
- Test edge cases: suspended users denied everything, non-members denied group actions, event host permissions scoped to their event only.
- `EventChatPolicyTest`: sending messages requires RSVP Going or group membership, deleting own messages allowed, deleting others' messages requires leadership role.

### 10.4 Component Test Specifications (Blade)

Component tests render Blade components in isolation and assert their HTML output. These use Pest with `$this->blade()` or `$this->component()`.

**BlobTest:**
- Renders cloud shape SVG with correct color, size, and opacity.
- Renders circle shape when `shape="circle"`.
- Applies additional CSS classes from attributes.
- Sets `aria-hidden="true"` for accessibility.

**AvatarTest:**
- Renders user initials from name (first letter of first and last name).
- Applies deterministic color based on user ID (mod 4 cycling through green, coral, violet, gold).
- Renders at each size variant (sm, md, lg, xl) with correct pixel dimensions.
- Falls back to single initial when user has only one name.

**AvatarStackTest:**
- Renders up to `max` avatars with overlapping negative margins.
- Shows "+N" overflow badge when users exceed `max`.
- Each avatar has a 2px white border ring.
- Empty users array renders nothing.

**BadgeTest:**
- Renders correct background and text color for each type (in_person, online, hybrid, going, waitlisted, cancelled, almost_full).
- Applies radius-sm (4px).
- Renders provided label text.

**PillTest:**
- Renders interest tag name as text.
- Cycles background color through green-50, coral-50, violet-50, gold-50 based on tag ID.
- Uses matching dark text color from the same ramp.
- Applies radius-pill (100px).

**DateBlockTest:**
- Renders month abbreviation (uppercase, 11px) and day number (24px).
- Applies accent tint background matching the provided accent color.
- Uses accent-900 for day number text.

**EventCardTest:**
- Renders full event card with header, title, group name, date, and attendance.
- Shows correct event type badge (in-person/online/hybrid).
- Displays "Almost full" gold badge when < 25% capacity remaining.
- Shows avatar stack with going count.
- Shows "X left" in coral when spots are limited.
- Renders decorative blob on the card header.
- Links to the event page URL.

**EventRowTest:**
- Renders horizontal row with date block, event details, and RSVP button.
- Shows secondary RSVP button when event is near capacity, primary otherwise.
- Displays correct badges (event type + attendance count).

**StatCardTest:**
- Renders value and label with correct colors for each variant (coral, violet, gold).
- Uses 28px weight-500 for value, 11px 80% opacity for label.

**ProgressBarTest:**
- Renders fill width proportional to current/max.
- Shows green-500 fill color.
- Shows 0% width when current is 0, 100% width when current >= max.

**TabBarTest:**
- Renders all provided tabs.
- Active tab has green-500 text with 2px bottom border.
- Inactive tabs have neutral-500 text.

**EmptyStateTest:**
- Renders title and description text.
- Shows decorative blob.
- Renders optional action button/link.

### 10.5 Feature Test Specifications

Feature tests make HTTP requests and assert responses. Each test file follows this pattern:

```php
// Example structure for a feature test
it('allows an event organizer to create an event', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $group->members()->attach($user, ['role' => 'event_organizer']);

    $response = $this->actingAs($user)
        ->post("/groups/{$group->slug}/events", [
            'name' => 'Test Event',
            'description' => 'A test event',
            'starts_at' => now()->addWeek()->toIso8601String(),
            'event_type' => 'in_person',
            'venue_name' => 'Test Venue',
            'venue_address' => '123 Test St',
            'status' => 'published',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('events', ['name' => 'Test Event']);
});

it('prevents a regular member from creating an event', function () {
    // ...
    $response->assertForbidden();
});
```

Key feature test scenarios per area:

**Auth:** Registration with valid/invalid data. Login with correct/incorrect credentials. Rate limiting. Password reset flow. Email verification enforcement. Account deletion with grace period.

**Groups:** Create group (validates required fields, generates slug). View group as guest/member/non-member. Join open group (instant). Join approval group (creates pending request, answers questions). Approval/denial workflow. Leave group (cancels RSVPs). Edit settings (only co-organizer+). Member management CRUD. Role changes with hierarchy enforcement. Ownership transfer (requires password). Deletion (requires password, notifies members).

**Events:** Full CRUD lifecycle. Draft/publish/cancel states. RSVP limit enforcement. Waitlist creation and promotion. Guest count enforcement. RSVP window enforcement. Recurring event creation and management. Calendar .ics export. Comment CRUD with threading and likes. Feedback submission (only after event, only by attendees). Check-in flow.

**Event Chat (Feature/Events/EventChatTest.php):**
- Send a chat message as an RSVP'd member (assert persisted in `event_chat_messages`).
- Send a chat message as a non-RSVP group member who has joined the chat.
- Prevent sending when `is_chat_enabled` is false (assert 403).
- Prevent sending when user is not a group member (assert 403).
- Reply to a specific message (assert `reply_to_id` is set).
- Edit own message (assert updated body in database).
- Delete own message (assert soft deleted).
- Leadership can delete any message.
- Non-owner cannot edit/delete another user's message (assert 403).
- Rate limiting: 11th message within 15 seconds returns 429 Too Many Requests.
- Assert broadcast event is dispatched on message send (using `Event::fake()`).

**Discussions:** Create thread. Post replies. Pin/lock (co-organizer+). Soft delete. Discussion moderation permissions (only co-organizer+ can pin/lock/delete).

**Messages:** Start conversation. Send messages. Block prevents new messages. Mute stops notifications. Rate limiting on DMs (21st message in a minute returns 429).

**Notifications:** Assert correct notifications dispatched for every trigger event. Assert muted groups suppress notifications. Assert blocked users don't generate notifications.

**Notification Digest (Feature/Notifications/NotificationDigestTest.php):**
- Fewer than 5 notifications in 15 minutes: each sends individual email.
- 5+ notifications of same type in 15 minutes: subsequent ones are added to `pending_notification_digests` instead of sending immediately.
- `notifications:send-digests` command groups pending items and sends a single digest email per (user, type).
- Pending records are deleted after digest is sent.
- Web notifications are never batched (always fire individually).

**Image Uploads (Feature/Profile/ImageUploadTest.php):**
- Upload valid JPEG avatar within size limit (assert stored via medialibrary).
- Upload exceeding max size returns 422.
- Upload non-image file returns 422.
- Upload valid group cover photo (assert correct conversions generated).
- Upload valid event cover photo.

**Admin:** Dashboard access (admin only, 403 for regular users). User suspend/unsuspend. Report review workflow. Interest CRUD and merge. Settings update.

### 10.6 Browser Test Specifications (Dusk)

Browser tests verify complete user flows including JavaScript/Livewire interactions.

**Auth Flows:**
- Registration: fill form, submit, see verification notice, verify email, redirected to dashboard.
- Login: fill form, submit, see dashboard with user name.
- Password reset: request link, follow link, set new password, login with new password.

**Group Flows:**
- Browse groups: visit /groups, see list, filter by interest, click into group.
- Join group: visit group page, click Join, see confirmation, appear in member list.
- Approval flow: request to join, answer questions, wait for approval (simulate admin approving), then access group.
- Create group: fill form with all fields, set topics, publish, see group page.
- Manage group: navigate to management pages, change settings, see updates reflected.

**Event Flows:**
- Browse events: visit explore page, see events, filter, click into event.
- RSVP flow: view event, click RSVP Going, see confirmation, appear in attendee list. Change to Not Going. Re-RSVP.
- Create event: fill form with all fields, publish, see event page. Edit event, see updated details.
- Event chat: open event page, navigate to chat tab, send a message, see it appear. (Tests WebSocket via Reverb.)
- Feedback: attend event (simulate past event), leave rating and feedback, see it recorded.

**Discovery Flows:**
- Explore page: see events, use search bar, filter by topic, see results update.
- Global search: type in search bar, see results from groups and events.

**Message Flows:**
- Visit user profile, click Message, type and send a message, see it in conversation list.

**Admin Flows:**
- Login as admin, see admin dashboard with stats.
- Review a report, resolve it, see it move from pending to resolved.

---

## 11. GitHub Actions CI

### 11.1 Workflow File

```yaml
# .github/workflows/ci.yml
name: CI

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  lint:
    name: Code Style & Static Analysis
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.5'
          extensions: mbstring, xml, pdo_mysql
          tools: composer:v2

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Run Pint (code style)
        run: vendor/bin/pint --test

      - name: Run Larastan (static analysis)
        run: vendor/bin/phpstan analyse --memory-limit=512M

  test:
    name: Tests (PHP 8.5 - MySQL)
    runs-on: ubuntu-latest
    services:
      mysql:
        image: 'mysql:8.0'
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: greetup_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.5'
          extensions: mbstring, xml, pdo_mysql, gd
          coverage: xdebug
          tools: composer:v2

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Prepare environment
        run: |
          cp .env.ci.mysql .env
          php artisan key:generate

      - name: Run migrations
        run: php artisan migrate --force

      - name: Run Pest (Unit + Feature tests)
        run: vendor/bin/pest --parallel --coverage --min=90

      - name: Upload coverage report
        uses: actions/upload-artifact@v4
        with:
          name: coverage-report
          path: coverage/

  dusk:
    name: Browser Tests
    runs-on: ubuntu-latest
    services:
      mysql:
        image: 'mysql:8.0'
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: greetup_dusk
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.5'
          extensions: mbstring, xml, pdo_mysql, gd
          tools: composer:v2

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Install Node dependencies
        run: npm ci

      - name: Build assets
        run: npm run build

      - name: Prepare environment
        run: |
          cp .env.dusk.ci .env
          php artisan key:generate
          php artisan migrate --force
          php artisan db:seed

      - name: Install Chrome driver
        run: php artisan dusk:chrome-driver --detect

      - name: Start Reverb (WebSocket)
        run: php artisan reverb:start &

      - name: Start application
        run: php artisan serve --port=8000 &

      - name: Run Dusk
        run: php artisan dusk

      - name: Upload Dusk screenshots on failure
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: dusk-screenshots
          path: tests/Browser/screenshots/

      - name: Upload Dusk console logs on failure
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: dusk-console
          path: tests/Browser/console/
```

### 11.2 CI Environment Files

```ini
# .env.ci.mysql
APP_ENV=testing
APP_KEY=
APP_DEBUG=true
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=greetup_test
DB_USERNAME=root
DB_PASSWORD=password
QUEUE_CONNECTION=sync
MAIL_MAILER=array
CACHE_STORE=array
SESSION_DRIVER=array
SCOUT_DRIVER=collection
BROADCAST_CONNECTION=null
GEOCODIO_API_KEY=
```

```ini
# .env.dusk.ci
APP_ENV=testing
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=greetup_dusk
DB_USERNAME=root
DB_PASSWORD=password
QUEUE_CONNECTION=sync
MAIL_MAILER=array
CACHE_STORE=array
SESSION_DRIVER=array
SCOUT_DRIVER=collection
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=greetup-test
REVERB_APP_KEY=test-key
REVERB_APP_SECRET=test-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
GEOCODIO_API_KEY=
```

**Note on Geocodio in CI:** The `GEOCODIO_API_KEY` is intentionally left empty in CI environments. All tests that exercise geocoding use a mocked `GeocodingService` (bound in the test service provider) so no real API calls are made during testing. This means CI runs require zero third-party API keys.

**Note on database in CI:** Both the unit/feature test job and the Dusk browser test job use MySQL 8.0, matching the local Sail environment. There is no SQLite test matrix — all tests run against MySQL to ensure parity with production.

---

## 12. README / Self-Hosting Guide

The following should be the contents of the project's `README.md`.

---

### README.md Content

```markdown
<p align="center">
  <img src="resources/images/greetup.png" alt="Greetup" width="200">
</p>

<h1 align="center">Greetup</h1>

<p align="center">
  A free, open source, self-hostable community events platform.<br>
  Organize local meetups and interest-based groups without paywalls.
</p>

<p align="center">
  <a href="#features">Features</a> •
  <a href="#quick-start-with-docker">Quick Start</a> •
  <a href="#manual-installation">Manual Install</a> •
  <a href="#configuration">Configuration</a> •
  <a href="#deployment">Deployment</a> •
  <a href="#contributing">Contributing</a>
</p>

---

## Features

- **Groups**: Create and join interest-based community groups
- **Events**: Schedule in-person, online, or hybrid events with RSVP management
- **Waitlists**: Automatic waitlist with fair FIFO promotion when spots open
- **Recurring events**: Weekly, biweekly, monthly, or custom recurrence
- **Discussions**: Threaded group discussions for community conversation
- **Real-time chat**: Per-event chat powered by WebSockets
- **Direct messages**: Private 1:1 messaging between members
- **Discovery**: Search and explore groups/events by interest, keyword, and location
- **Attendee check-in**: Mark attendance at events
- **Event feedback**: Post-event ratings and reviews
- **Moderation**: Report content, block users, admin dashboard for platform management
- **Role-based permissions**: Organizer, Co-Organizer, Assistant Organizer, Event Organizer, Event Host
- **No payments, no paywalls**: Every feature is free for every user

## Requirements

- Docker Desktop (for local development via Laravel Sail)
- **OR** for manual installation: PHP 8.5+, Composer, Node.js 20+, MySQL 8.0+, Redis

**Third-party API key required:**
- Geocodio API key (free tier: 2,500 lookups/day) -- [sign up at geocod.io](https://www.geocod.io)

## Quick Start with Docker

The fastest way to get Greetup running locally. Uses Laravel Sail to start all services in Docker.

```bash
# Clone the repo
git clone https://github.com/MiniCodeMonkey/greetup.git
cd greetup

# Copy environment file
cp .env.example .env

# Set your Geocodio API key (get one free at https://www.geocod.io)
# Option A: Set it in .env directly
# Option B: Export it in your shell and Sail picks it up automatically:
#   export GEOCODIO_API_KEY=your-key-here

# Install PHP dependencies (using a temporary Docker container)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# Start all services (app, MySQL, Redis, Meilisearch, Mailpit, queue worker, Reverb)
./vendor/bin/sail up -d

# Generate app key
./vendor/bin/sail artisan key:generate

# Install frontend dependencies and build assets
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# Run migrations and seed the demo data
./vendor/bin/sail artisan migrate --seed

# Import search indexes
./vendor/bin/sail artisan scout:import "App\\Models\\Group"
./vendor/bin/sail artisan scout:import "App\\Models\\Event"
./vendor/bin/sail artisan scout:import "App\\Models\\User"
```

**Your Greetup instance is now running at:**

| Service | URL |
|---------|-----|
| Application | http://localhost |
| Mailpit (email UI) | http://localhost:8025 |
| Meilisearch | http://localhost:7700 |

**Demo accounts after seeding:**

| Account | Email | Password |
|---------|-------|----------|
| Admin | admin@greetup.test | password |
| Regular user | user@greetup.test | password |

**Useful Sail commands:**

```bash
./vendor/bin/sail up -d          # Start all services
./vendor/bin/sail down            # Stop all services
./vendor/bin/sail artisan ...     # Run artisan commands
./vendor/bin/sail composer ...    # Run composer commands
./vendor/bin/sail npm ...         # Run npm commands
./vendor/bin/sail test            # Run tests
./vendor/bin/sail artisan queue:restart  # Restart queue worker after code changes
./vendor/bin/sail logs reverb     # View Reverb (WebSocket) logs
./vendor/bin/sail mysql           # Open MySQL CLI
./vendor/bin/sail redis           # Open Redis CLI
```

## Manual Installation (Without Docker)

For developers who prefer not to use Docker, or for production servers.

**Prerequisites:** PHP 8.5+, Composer, Node.js 20+, npm, MySQL 8.0+, Redis (recommended).

```bash
# Clone the repository
git clone https://github.com/MiniCodeMonkey/greetup.git
cd greetup

# Install PHP dependencies
composer install

# Install frontend dependencies and build assets
npm install
npm run build

# Set up environment
cp .env.example .env
php artisan key:generate

# Edit .env to configure:
# - Database credentials (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)
# - Redis connection (REDIS_HOST, REDIS_PORT)
# - Geocodio API key (GEOCODIO_API_KEY) -- get one at https://www.geocod.io
# - Mail settings for production (MAIL_HOST, MAIL_USERNAME, etc.)

# Run migrations and seed demo data
php artisan migrate --seed

# Start the development server
php artisan serve

# In a separate terminal -- start the queue worker
php artisan queue:work

# In a separate terminal -- start the WebSocket server (for real-time chat)
php artisan reverb:start

# In a separate terminal -- start Vite dev server (for hot-reload during development)
npm run dev
```

Visit `http://localhost:8000` to access Greetup.

## Running Tests

```bash
# Using Sail (recommended)
./vendor/bin/sail test                           # Run all tests
./vendor/bin/sail test --parallel                 # Run tests in parallel (faster)
./vendor/bin/sail test --coverage --min=90        # Run with coverage report
./vendor/bin/sail test --testsuite=Unit           # Run only unit tests
./vendor/bin/sail test --testsuite=Feature        # Run only feature tests
./vendor/bin/sail artisan dusk                    # Run browser tests
./vendor/bin/sail test tests/Feature/Groups/      # Run a specific directory

# Without Sail
vendor/bin/pest
vendor/bin/pest --parallel
vendor/bin/pest --coverage --min=90
php artisan dusk
```

## Configuration

All configuration is done via the `.env` file. Key settings:

### Third-Party API Keys

```ini
# Geocodio -- REQUIRED for location features
# Free: 2,500 lookups/day. Sign up: https://www.geocod.io
GEOCODIO_API_KEY=your-api-key-here
```

Without a Geocodio key, the app works normally but location-based features (nearby events, distance sorting, map pins) are disabled. Addresses are stored as text but not geocoded.

### Database

```ini
# MySQL (default for Sail)
DB_CONNECTION=mysql
DB_HOST=mysql          # Use 'mysql' in Sail, '127.0.0.1' otherwise
DB_PORT=3306
DB_DATABASE=greetup
DB_USERNAME=sail
DB_PASSWORD=password

# PostgreSQL (alternative for production)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=greetup
DB_USERNAME=greetup
DB_PASSWORD=secret
```

### Mail

```ini
# Local dev (Mailpit via Sail -- UI at http://localhost:8025)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025

# Production -- use any SMTP provider (Mailgun, Postmark, SES, Resend, etc.)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-greetup-instance.com
MAIL_FROM_NAME="Greetup"
```

### Search

```ini
# Meilisearch (default, included in Sail)
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700  # Use 'meilisearch' in Sail
MEILISEARCH_KEY=masterKey

# Database fallback (no external dependency, slower)
SCOUT_DRIVER=database
```

### File Storage

```ini
# Default: local disk (fine for small instances)
FILESYSTEM_DISK=local

# Production: S3-compatible storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=greetup-uploads
AWS_URL=https://your-cdn.com
```

### Queue

```ini
# Redis (default for Sail, recommended)
QUEUE_CONNECTION=redis
REDIS_HOST=redis       # Use 'redis' in Sail, '127.0.0.1' otherwise

# Database fallback (no Redis dependency)
QUEUE_CONNECTION=database
```

### WebSocket (Real-time Chat)

```ini
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=greetup
REVERB_APP_KEY=greetup-key
REVERB_APP_SECRET=greetup-secret
REVERB_HOST=reverb          # Use 'reverb' in Sail
REVERB_PORT=8080

# Browser connects to Reverb via localhost (not the Docker hostname)
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

## Deployment

### Production Checklist

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Generate a strong `APP_KEY` with `php artisan key:generate`
3. Configure a production database (MySQL or PostgreSQL recommended)
4. Set up Redis for queue, cache, and session
5. Configure SMTP for transactional email
6. Set `GEOCODIO_API_KEY` for location features (get a key at https://www.geocod.io)
7. Set up S3 or equivalent for file uploads (optional, local disk works for small instances)
8. Build frontend assets: `npm run build`
9. Run migrations: `php artisan migrate --force`
10. Import search indexes: `php artisan scout:import`
11. Optimize: `php artisan optimize`
12. Set up a queue worker (Supervisor or systemd)
13. Set up Reverb as a service for WebSocket support
14. Configure a web server (Nginx or Caddy) with HTTPS
15. Set up scheduled tasks: `* * * * * php /path/to/greetup/artisan schedule:run`

### Scheduled Commands

Add this to your crontab:

```
* * * * * cd /path/to/greetup && php artisan schedule:run >> /dev/null 2>&1
```

Greetup registers these scheduled tasks:

| Command | Frequency | Purpose |
|---------|-----------|---------|
| `events:generate-recurring` | Daily | Generate upcoming instances of recurring event series |
| `events:mark-past` | Hourly | Move ended events to `past` status |
| `accounts:purge-deleted` | Daily | Hard-delete accounts past their 30-day grace period |
| `groups:purge-deleted` | Daily | Hard-delete groups past their 90-day grace period |
| `notifications:send-digests` | Every 5 min | Send batched notification digest emails |

### Example Nginx Configuration

```nginx
server {
    listen 80;
    server_name greetup.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name greetup.example.com;
    root /var/www/greetup/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/greetup.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/greetup.example.com/privkey.pem;

    client_max_body_size 10M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # WebSocket proxy for Reverb
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### Supervisor Configuration (Queue Worker)

```ini
[program:greetup-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/greetup/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/greetup/storage/logs/worker.log
stopwaitsecs=3600
```

### Supervisor Configuration (Reverb)

```ini
[program:greetup-reverb]
command=php /var/www/greetup/artisan reverb:start --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/greetup/storage/logs/reverb.log
```

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

```bash
# Set up local development (using Sail is recommended -- see Quick Start above)
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev  # in one terminal
php artisan serve  # in another terminal

# Before submitting a PR
vendor/bin/pint          # Fix code style
vendor/bin/phpstan analyse  # Static analysis
vendor/bin/pest --parallel  # Run tests
```

## License

Greetup is open-source software licensed under the [MIT License](LICENSE).
```

---

## 13. CONTRIBUTING.md

The following should be the contents of the project's `CONTRIBUTING.md`.

---

### CONTRIBUTING.md Content

```markdown
# Contributing to Greetup

Thank you for your interest in contributing to Greetup! This guide will help you get set up and ensure your contributions can be merged smoothly.

## Getting Started

1. Fork the repository and clone your fork.
2. Set up your local environment using the [Quick Start guide](README.md#quick-start-with-docker).
3. Create a new branch from `main` for your work.

## Branch Naming

Use descriptive branch names with a prefix:

- `feature/` — new functionality (e.g., `feature/event-recurring-series`)
- `fix/` — bug fixes (e.g., `fix/waitlist-promotion-race-condition`)
- `refactor/` — code improvements without behavior changes
- `docs/` — documentation changes
- `test/` — adding or improving tests

## Making Changes

1. **Search before building.** Check existing issues and PRs to avoid duplicating work.
2. **Keep PRs focused.** One feature or fix per pull request. Small PRs are reviewed faster.
3. **Write tests.** All new features need feature tests. Bug fixes should include a test that reproduces the bug.
4. **Follow existing patterns.** Check sibling files for naming conventions, structure, and approach before creating something new.

## Before Submitting a PR

Run the full quality check suite:

```bash
# Fix code style (required — CI will reject style violations)
vendor/bin/pint

# Static analysis
vendor/bin/phpstan analyse

# Run the full test suite
vendor/bin/pest --parallel
```

All three must pass. CI runs these automatically on every PR.

## Commit Messages

Write clear, concise commit messages:

- Use the imperative mood ("Add waitlist promotion" not "Added waitlist promotion")
- First line: short summary (under 72 characters)
- Optionally: blank line followed by a longer explanation of *why*, not *what*

Good:
```
Add automatic waitlist promotion when RSVP is cancelled

When a Going RSVP is cancelled, the next eligible waitlisted member
is promoted via a queued job. Members with guests are skipped if
there aren't enough spots for their full party.
```

## Pull Request Process

1. Fill in the PR template with a summary and test plan.
2. Ensure CI passes (lint, tests, browser tests).
3. A maintainer will review your PR. Address feedback and push updates to the same branch.
4. Once approved, a maintainer will merge via squash-and-merge.

## Reporting Bugs

Open an issue with:
- Steps to reproduce
- Expected behavior
- Actual behavior
- Environment details (PHP version, database, browser if relevant)

## Code of Conduct

Be respectful and constructive. We're building a community platform — let's model the community we want to see.
```

---

## 14. Error Pages

Custom error pages should match the Greetup design system and provide a helpful, branded experience rather than showing Laravel defaults.

### 14.1 Error Page Design

All error pages share a common layout:
- Centered content on a neutral-50 background.
- Large decorative blob (green-500, opacity 0.06) in the background.
- Error code displayed large (44px, weight 500, neutral-400).
- Headline (22px, weight 500, neutral-900) explaining the error.
- Body text (16px, neutral-500) with a helpful message.
- Primary CTA button linking back to the homepage or a sensible destination.
- Navigation bar remains visible so the user can navigate elsewhere.

### 14.2 Error Pages to Create

| File | HTTP Status | Headline | Body | CTA |
|------|------------|----------|------|-----|
| `resources/views/errors/403.blade.php` | 403 Forbidden | "You don't have access to this page" | "You might need to join this group or have a different role to view this content." | "Go to Explore" → `/explore` |
| `resources/views/errors/404.blade.php` | 404 Not Found | "We couldn't find that page" | "The page you're looking for might have been moved, deleted, or never existed." | "Go to Explore" → `/explore` |
| `resources/views/errors/419.blade.php` | 419 Page Expired | "This page has expired" | "Your session timed out. Please go back and try again." | "Go back" → `javascript:history.back()` |
| `resources/views/errors/429.blade.php` | 429 Too Many Requests | "Slow down" | "You're making requests too quickly. Please wait a moment and try again." | "Go to Explore" → `/explore` |
| `resources/views/errors/500.blade.php` | 500 Server Error | "Something went wrong" | "We hit an unexpected error. If this keeps happening, please let the site administrator know." | "Go to homepage" → `/` |
| `resources/views/errors/503.blade.php` | 503 Service Unavailable | "We'll be right back" | "Greetup is undergoing maintenance. Please check back shortly." | *(no CTA — page auto-refreshes after 60 seconds)* |

### 14.3 Suspended Account Page

Not a standard HTTP error but shown to suspended users via the `EnsureAccountNotSuspended` middleware:

- **File:** `resources/views/auth/suspended.blade.php`
- **Layout:** Same error page layout but with a red-500 accent instead of green.
- **Headline:** "Your account has been suspended"
- **Body:** Shows the suspension reason from `suspended_reason` column.
- **CTA:** "Contact support" link (configurable via platform settings, or omitted if not set).
- **Nav:** Minimal — only the Greetup logo and a logout link.

---

## 15. SEO & Social Sharing

### 15.1 Page Titles

Every page should have a descriptive `<title>` tag following this pattern:

| Page | Title Format |
|------|-------------|
| Homepage | `Greetup — Find your people` |
| Explore | `Explore Events — Greetup` |
| Group page | `{Group Name} — Greetup` |
| Event page | `{Event Name} · {Group Name} — Greetup` |
| User profile | `{User Name} — Greetup` |
| Dashboard | `Dashboard — Greetup` |
| Search results | `Search: "{query}" — Greetup` |
| Group search | `Browse Groups — Greetup` |
| Admin pages | `Admin: {Section} — Greetup` |
| Error pages | `{Error Code} — Greetup` |

The site name portion ("Greetup") should use the `site_name` platform setting so self-hosted instances can customize it.

### 15.2 Meta Description

Each public page should include a `<meta name="description">` tag:

| Page | Description Source |
|------|-------------------|
| Homepage | Platform setting `site_description`, fallback: "A free, open source community events platform." |
| Group page | First 160 characters of the group's description (plain text, stripped of markdown). |
| Event page | First 160 characters of the event's description (plain text). |
| User profile | User's bio (first 160 characters), or "{Name} is a member of Greetup." if no bio. |
| Explore / Search | Static: "Discover local meetups, events, and community groups near you." |

### 15.3 Open Graph & Twitter Card Tags

All public pages (group, event, profile, explore, homepage) should include Open Graph and Twitter Card meta tags for rich link previews:

```html
<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:title" content="{page title}">
<meta property="og:description" content="{meta description}">
<meta property="og:image" content="{cover photo URL or default OG image}">
<meta property="og:url" content="{canonical URL}">
<meta property="og:site_name" content="{site_name setting}">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{page title}">
<meta name="twitter:description" content="{meta description}">
<meta name="twitter:image" content="{cover photo URL or default OG image}">
```

**Image selection:**
- **Event page:** Event cover photo if set, otherwise the group's cover photo, otherwise the default OG image.
- **Group page:** Group cover photo if set, otherwise the default OG image.
- **User profile:** User's avatar if set, otherwise the default OG image.
- **All other pages:** Default OG image.

**Default OG image:** A branded 1200x630 image stored at `public/images/og-default.png` featuring the Greetup logo on a green-900 background with decorative blobs. This should be created as part of the initial asset setup.

### 15.4 Canonical URLs

Every public page should include a `<link rel="canonical">` tag pointing to its clean URL (without query parameters for pagination, filters, etc.) to prevent duplicate content issues.

### 15.5 Structured Data (JSON-LD)

Event pages should include [Schema.org Event](https://schema.org/Event) structured data for search engine rich results:

```html
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Event",
    "name": "{event name}",
    "description": "{event description, plain text, max 300 chars}",
    "startDate": "{ISO 8601 datetime}",
    "endDate": "{ISO 8601 datetime, if set}",
    "eventStatus": "https://schema.org/EventScheduled",
    "eventAttendanceMode": "{based on event_type: OfflineEventAttendanceMode / OnlineEventAttendanceMode / MixedEventAttendanceMode}",
    "location": {
        "@type": "{Place for in-person, VirtualLocation for online}",
        "name": "{venue_name or 'Online'}",
        "address": "{venue_address, if in-person}"
    },
    "organizer": {
        "@type": "Organization",
        "name": "{group name}",
        "url": "{group URL}"
    },
    "image": "{cover photo URL or default OG image}",
    "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "USD",
        "availability": "{based on RSVP status: InStock / SoldOut / PreOrder}"
    }
}
</script>
```

For cancelled events, `eventStatus` should be `EventCancelled`.

### 15.6 Blade Implementation

SEO tags should be managed via a reusable Blade component and a `@section`-based approach:

- **`<x-seo>` component:** Accepts `title`, `description`, `image`, `type`, and optional `jsonLd` props. Renders all meta tags in the `<head>`.
- **Each page view** sets its SEO data via the component in the layout's head section.
- **`SeoService`** (optional helper): generates meta description from markdown content (strips tags, truncates), resolves the OG image URL (with fallback chain), and builds JSON-LD arrays for events.

---

## Appendix A: Livewire Components

Key interactive components that will use Livewire for reactivity without full-page reloads:

| Component | Location | Purpose |
|-----------|----------|---------|
| `RsvpButton` | Event page | RSVP Going/Not Going/Waitlist toggle |
| `AttendeeList` | Event page | Real-time attendee list with search |
| `AttendeeManager` | Event management | Check-in, status changes |
| `EventChat` | Event page | Real-time chat with Reverb |
| `CommentThread` | Event page | Comments with threading and likes |
| `DiscussionThread` | Discussion page | Discussion with replies |
| `ConversationView` | Messages page | DM conversation with real-time updates |
| `MemberList` | Group management | Searchable member list with role management |
| `JoinRequestList` | Group management | Approve/deny join requests |
| `GlobalSearch` | Navbar | Search-as-you-type dropdown |
| `NotificationDropdown` | Navbar | Real-time notification bell |
| `InterestPicker` | Profile/Group settings | Tag selection with autocomplete |
| `LocationPicker` | Profile/Group/Event forms | Location input with address autocomplete and Geocodio resolution |
| `ImageUpload` | Various forms | Drag-and-drop image upload with preview |

### Blade Components (Non-Interactive)

Reusable Blade components for the design system. These are not Livewire-powered -- they are simple `@props`-based partials.

| Component | Usage |
|-----------|-------|
| `<x-blob>` | Decorative logo cloud/circle shape (see Section 1A.8) |
| `<x-avatar>` | User avatar circle with deterministic color. Props: `user`, `size` (sm/md/lg/xl) |
| `<x-avatar-stack>` | Overlapping avatar group with "+N" overflow. Props: `users`, `max` |
| `<x-badge>` | Status badge (In-person, Online, Going, etc.). Props: `type`, `label` |
| `<x-pill>` | Interest/topic pill with cycling accent colors. Props: `tag` |
| `<x-date-block>` | Calendar-style date display for event rows. Props: `date`, `accent` |
| `<x-event-card>` | Grid-layout event card with header blob. Props: `event` |
| `<x-event-row>` | List-layout event row with date block. Props: `event` |
| `<x-stat-card>` | Bold stat card (hero section). Props: `value`, `label`, `color` |
| `<x-progress-bar>` | Attendance fill bar. Props: `current`, `max` |
| `<x-tab-bar>` | Tab navigation with active indicator. Props: `tabs`, `active` |
| `<x-empty-state>` | Friendly empty state with blob decoration. Props: `title`, `description`, `action` |

## Appendix B: Service Classes

Business logic should be extracted into service classes rather than living in controllers or Livewire components:

| Service | Responsibility |
|---------|---------------|
| `RsvpService` | Create/cancel RSVPs, enforce limits, manage guest counts |
| `WaitlistService` | Promote waitlisted members, handle guest-count skipping logic |
| `GroupMembershipService` | Join/leave groups, handle approval workflow, role changes |
| `EventSeriesService` | Generate recurring event instances, manage series edits |
| `NotificationService` | Dispatch notifications respecting mutes, blocks, and preferences |
| `SearchService` | Coordinate Scout search across multiple models |
| `GeocodingService` | Forward/reverse geocode via Geocodio API. Handles caching, error handling, batch operations, and graceful degradation when API key is missing. |
| `ExportService` | Generate CSV exports for members and attendees |
| `AccountService` | Handle account deletion, data export, suspension |
| `MarkdownService` | Render and sanitize markdown to HTML |

## Appendix C: Artisan Commands

| Command | Purpose |
|---------|---------|
| `greetup:install` | Interactive first-time setup wizard (create admin user, set site name, configure Geocodio key, etc.) |
| `greetup:geocode-missing` | Batch geocode any groups/events/users with addresses but missing lat/lng (useful after adding a Geocodio key) |
| `events:generate-recurring` | Generate next batch of recurring event instances |
| `events:mark-past` | Transition ended events to past status |
| `accounts:purge-deleted` | Hard-delete soft-deleted accounts past grace period |
| `groups:purge-deleted` | Hard-delete soft-deleted groups past grace period |
| `notifications:send-digests` | Send pending notification digest emails |
| `greetup:stats` | Print platform statistics to console |

## Appendix D: Middleware

| Middleware | Applied To | Purpose |
|-----------|-----------|---------|
| `EnsureEmailIsVerified` | All authenticated routes except settings | Redirect unverified users |
| `EnsureAccountNotSuspended` | All authenticated routes | Show suspension notice |
| `EnsureGroupMember` | Group-scoped routes | 403 if not a member |
| `EnsureGroupRole` | Group management routes | 403 if insufficient role |
| `TrackLastActivity` | All authenticated routes | Update user's `last_active_at` for activity tracking |

## Appendix E: Queued Jobs

| Job | Dispatched By | Purpose |
|-----|---------------|---------|
| `GeocodeLocation` | Model observers on Group, Event, User (when address changes) | Forward-geocode an address via Geocodio and store lat/lng on the model. Retries 3 times with exponential backoff. Silently skips if no API key is configured. |
| `PromoteFromWaitlist` | `RsvpService` when a Going RSVP is cancelled | Find the next eligible waitlisted member (FIFO, respecting guest counts) and promote them to Going. Sends `PromotedFromWaitlist` notification. |
| `SendEventNotification` | Event creation/update/cancellation | Batch-send notifications to group members. Respects mute preferences and blocks. |
| `GenerateRecurringEvents` | Scheduled command (`events:generate-recurring`) | Create individual event records for the next 3 months of a recurring series. |
| `PurgeDeletedAccounts` | Scheduled command (`accounts:purge-deleted`) | Hard-delete user data for accounts past the 30-day soft-delete grace period. |
| `PurgeDeletedGroups` | Scheduled command (`groups:purge-deleted`) | Hard-delete group data for groups past the 90-day soft-delete grace period. |

## Appendix F: Configuration Files

### config/services.php (Geocodio)

```php
'geocodio' => [
    'api_key' => env('GEOCODIO_API_KEY'),
],
```

### config/scout.php

Meilisearch is the default driver. The `database` driver is available as a zero-dependency fallback by changing `SCOUT_DRIVER=database` in `.env`.

### config/broadcasting.php

Reverb is the default broadcast driver. Channel authentication is handled via Laravel's standard `Broadcast::channel()` definitions in `routes/channels.php`.
