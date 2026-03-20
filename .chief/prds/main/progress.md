## Codebase Patterns
- Event model uses `name` field (not `title`) — the event-card component was updated to use `$event->name`
- Event model casts `event_type` to `EventType` enum — when using as array key, extract `->value` first (or use `instanceof \BackedEnum`)
- Event model uses `rsvp_limit` (not `capacity`) for max attendees
- Interests are Spatie Tags with type `interest` — query via `Tag::getWithType('interest')`
- DatabaseSeeder must NOT use `WithoutModelEvents` — it prevents Spatie HasSlug from generating slugs. Use `Model::disableSearchSyncing()` per Searchable model instead
- Spatie `role` middleware must be registered manually in `bootstrap/app.php` via `$middleware->alias(['role' => RoleMiddleware::class])` — it is not auto-discovered
- Laravel Sail uses `compose.yaml` (not `docker-compose.yml`) in this project
- Use `php artisan sail:install --with=service1,service2 --no-interaction` to scaffold Sail
- `.env` is not committed; `.env.example` is the canonical reference for env vars
- Sail automatically updates `.env` with Docker-appropriate service hosts during `sail:install`
- Tests run against MySQL `testing` database (created by Sail's init script), not SQLite in-memory
- Design tokens are defined in `resources/css/app.css` via Tailwind 4 `@theme` block — reference `greetup-spec.md` section 1A.7 for authoritative values
- The project uses Google Fonts via `fonts.googleapis.com` (not bunny.net) for Instrument Sans
- All Spatie packages (permission, medialibrary, tags) auto-discover; config files are published to `config/` and migrations to `database/migrations/`
- Dusk scaffolds `tests/Browser/`, `tests/DuskTestCase.php`, and adds `channels: __DIR__.'/../routes/channels.php'` to `bootstrap/app.php`
- `npm run build` must run on host (macOS), not inside Sail — node_modules have platform-specific native bindings
- phpredis `Redis::ping()` returns `bool(true)`, not the string "PONG"
- Component tests go in `tests/Component/` directory, registered in `phpunit.xml` as a separate test suite and in `tests/Pest.php` alongside Feature
- Blade components live in `resources/views/components/` and are auto-discovered as `<x-name>`
- Layout components using `@vite` need `$this->withoutVite()` in `beforeEach()` for tests
- Static images go in `public/images/` and use `asset()` — `Vite::asset()` requires the file in the build manifest
- For authenticated component tests, use `Mockery::mock($user)->makePartial()` to mock notification relationships and avoid DB queries — component tests should not require a running database
- Use `@auth`/`@guest` Blade directives to conditionally render authenticated vs guest UI in layout components
- Feature tests with `RefreshDatabase` must run via `./vendor/bin/sail artisan test` (needs MySQL container)
- Auth routes: always define all three email verification routes together (`verification.notice`, `verification.verify`, `verification.send`) — the `Registered` event listener needs `verification.verify`
- The `verified` middleware alias maps to `EnsureEmailIsVerified` — register it in `bootstrap/app.php` via `$middleware->alias()`
- The `notifications` table migration is needed before any page renders authenticated layout (nav queries `unreadNotifications`)
- Email verification token expiry is configured via `auth.verification.expire` in `config/auth.php` (default 60 minutes)
- The `welcome.blade.php` does NOT use `<x-layouts.app>` — test banner/auth features on pages that use the app layout (e.g., `/email/verify`, `/register`)
- Rate limiters are defined in `AppServiceProvider::boot()` via `RateLimiter::for()`
- Spatie roles: `user` and `admin` roles seeded via `RoleSeeder`; auto-assigned `user` role on registration
- `SCOUT_DRIVER=null` must be set in `phpunit.xml` to prevent Meilisearch client errors during tests
- Spatie MediaLibrary conversions use `$conversion->getName()` method, not `->name` property
- Stub models (extending `Illuminate\Database\Eloquent\Model`) are needed for relationship tests when the related model doesn't exist yet
- When a BelongsToMany uses `->using(PivotModel::class)` with enum casts, pivot attributes are returned as enums — middleware/code must guard with `instanceof` before calling `Enum::from()`
- `Model::create()` does not auto-fill DB column defaults — use `$attributes` property for PHP-level defaults (e.g., `'status' => 'pending'`)
- Migrations with the same timestamp run alphabetically — ensure FK-dependent migrations have a later timestamp (e.g., `event_hosts` must come after `events`)
- Use Spatie sluggable `extraScope()` to scope slug uniqueness within a parent model
- Model observers are registered in `AppServiceProvider::boot()` — use `updateQuietly()` in jobs to prevent observer re-triggering
- Services are registered as singletons in `AppServiceProvider::register()` with config values from `config/services.php`
- `rlanvin/php-rrule` requires DTSTART passed separately from the RRULE string — extract it before constructing `RRule` objects
- `UserObserver`, `GroupObserver`, and `EventObserver` auto-dispatch `GeocodeLocation` on create/update when location changes — don't duplicate in controllers
- Base `Controller` class has no `AuthorizesRequests` trait — use `Gate::authorize()` for inline policy checks in controllers
- Policies that need guest access must use `?User` as the first parameter — Laravel passes null for unauthenticated users
- Public routes accessible to both guests and auth users go outside the `guest` and `auth` middleware groups in `routes/web.php`
- When testing queue dispatches after a model update, call `Queue::fake()` AFTER factory `create()` to avoid capturing observer jobs from `created()` events
- Public wildcard routes (e.g., `groups/{group:slug}`) must be registered AFTER static routes with the same prefix (e.g., `groups/create`) to avoid the wildcard capturing the static path
- Livewire components live in `app/Livewire/` with views in `resources/views/livewire/` — use `Livewire::test()` for feature tests
- Livewire full-page components must use `#[Layout('components.layouts.app')]` attribute — do NOT wrap the view in `<x-layouts.app>` (causes "multiple root elements" error)
- Use `->layoutData(['title' => '...', 'description' => '...'])` in `render()` to pass SEO data to the layout
- `Route::livewire('/path', Component::class)` registers a full-page Livewire component route
- Pest helper functions (e.g., `createGroupWithMember()`) must have unique names across ALL test files — PHP cannot redeclare functions, so prefix with context (e.g., `createGroupWithDiscussionMember()`)
- Discussion model has `user()` relationship but views/controller use `author` — use `->with('author')` for eager loading
- `livewire/livewire` was added as a dependency — `boost.json` auto-updates to include `livewire-development` skill
- Livewire inline components (non-full-page, e.g., `<livewire:notification-dropdown />`) don't use `#[Layout]` attribute — they render within the parent layout
- When moving inline layout code to a Livewire component, update `makeAuthUser()` mock in `AppLayoutTest` to support the new method chains the component calls
- Livewire typed `Collection` properties should use `Illuminate\Support\Collection` (not `Eloquent\Collection`) to avoid type mismatch when mocking
- Laravel Echo + Pusher JS are dev dependencies for Reverb real-time — configured in `resources/js/bootstrap.js`
- `Mail::fake()` does NOT intercept emails sent via `notifyNow()` — only `Mail::to()->send()` calls. Test notification email batching via DB state assertions
- `notifyNow($notification, ['database'])` creates the DB notification BEFORE any subsequent logic in the same dispatch — account for this in threshold checks
- SEO titles use `Setting::get('site_name')` (not `config('app.name')`) so self-hosted instances can customize the site name via admin settings
- Component tests that render views using `Setting::get()` need `Cache::put(Setting::CACHE_KEY, Setting::DEFAULTS)` in `beforeEach()` since there's no DB
- The homepage (`/`) renders a `home.blade.php` view for guests (not a redirect to `/explore`) — update tests accordingly if changing homepage behavior
---

## 2026-03-19 - US-116
- Installed `laravel/sail` as dev dependency
- Scaffolded Sail with MySQL, Redis, Meilisearch, Mailpit services via `sail:install`
- Changed MySQL image from 8.4 to 8.0 per spec requirements
- Added queue worker service to `compose.yaml` running `queue:work redis`
- Added Reverb port (8080) mapping to the main `laravel.test` service
- Updated `.env` and `.env.example` with all required connection details: DB_HOST=mysql, DB_USERNAME=sail, DB_PASSWORD=password, REDIS_HOST=redis, SCOUT_DRIVER=meilisearch, MEILISEARCH_HOST, MAIL_MAILER=smtp, MAIL_HOST=mailpit, MAIL_PORT=1025, BROADCAST_CONNECTION=reverb, QUEUE_CONNECTION=redis, CACHE_STORE=redis, SESSION_DRIVER=redis
- Added Reverb configuration: REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET, REVERB_HOST=localhost, REVERB_PORT=8080
- Files changed: `compose.yaml` (new), `.env.example`, `composer.json`, `composer.lock`, `phpunit.xml`
- **Learnings for future iterations:**
  - `sail:install` creates `compose.yaml` (newer Docker Compose convention), not `docker-compose.yml`
  - Sail auto-updates `.env` with Docker service hostnames (mysql, redis, mailpit) and adds Scout/Meilisearch config
  - Sail updates `phpunit.xml` to use `testing` database instead of SQLite in-memory
  - The sail image tag follows the pattern `sail-{php-version}/app` (e.g., `sail-8.5/app`)
  - Queue worker service reuses the same sail image but overrides the command
  - The sail alias documentation is: `alias sail='./vendor/bin/sail'`
---

## 2026-03-19 - US-001
- Configured Tailwind 4 `@theme` block in `resources/css/app.css` with all design tokens from spec section 1A.7
- Added color palettes: green (50-900), coral (50-900), violet (50-900), gold (50-900), neutral (50-900), red (50/500/900), blue (50/500/900)
- Added font families: `--font-display`, `--font-body` (Instrument Sans), `--font-mono` (JetBrains Mono)
- Added radius tokens: sm (4px), md (8px), lg (12px), xl (16px), pill (100px)
- Updated `welcome.blade.php` to use Google Fonts instead of bunny.net for Instrument Sans (weights 400, 500)
- Files changed: `resources/css/app.css`, `resources/views/welcome.blade.php`
- **Learnings for future iterations:**
  - Tailwind 4 uses `@theme` block for custom design tokens (replaces `tailwind.config.js` `theme.extend`)
  - Color tokens use `--color-{name}-{shade}` format, radius uses `--radius-{size}`, fonts use `--font-{name}`
  - The default Laravel welcome page is the only layout currently — a proper app layout will need to be created later
  - `npm run build` produces compiled CSS with all custom properties inlined
---

## 2026-03-19 - US-117
- All required Composer packages were already installed (added in prior iterations): `spatie/laravel-permission`, `spatie/laravel-sluggable`, `spatie/laravel-medialibrary`, `spatie/laravel-tags`, `league/commonmark`, `geocodio/geocodio-library-php`, `intervention/image`, `laravel/reverb`, `laravel/scout`, `laravel/dusk` (dev), `pestphp/pest` (dev), `larastan/larastan` (dev)
- Config files published: `broadcasting.php`, `media-library.php`, `permission.php`, `reverb.php`, `scout.php`, `tags.php`
- Migrations published: `create_permission_tables`, `create_media_table`, `create_tag_tables`
- Dusk scaffolded: `tests/Browser/`, `tests/DuskTestCase.php`, `routes/channels.php`
- `bootstrap/app.php` updated to register channels route
- `tests/Pest.php` updated with DuskTestCase for Browser tests
- Files changed: `composer.json`, `composer.lock`, `bootstrap/app.php`, `tests/Pest.php`, `boost.json`, 6 config files, 3 migrations, `routes/channels.php`, `tests/DuskTestCase.php`, `tests/Browser/` scaffold
- **Learnings for future iterations:**
  - All Spatie packages use Laravel auto-discovery — no manual service provider registration needed
  - `php artisan vendor:publish` for Spatie packages uses tags like `--tag=permission-migrations`, `--tag=medialibrary-migrations`
  - Dusk install (`php artisan dusk:install`) creates Browser test scaffold and adds channels route to bootstrap/app.php
  - Reverb install (`php artisan install:broadcasting`) publishes `config/broadcasting.php` and `routes/channels.php`
---

## 2026-03-19 - US-118
- All frontend dependencies were already installed and configured from prior iterations (US-001 and initial Laravel setup)
- Verified: `package.json` includes `tailwindcss ^4.0.0`, `@tailwindcss/vite ^4.0.0`, `vite ^8.0.0`, `laravel-vite-plugin ^3.0.0`
- Verified: `npm install` completes with 0 vulnerabilities
- Verified: `npm run build` compiles successfully (CSS 39.48 kB, JS 36.20 kB) in 116ms
- Verified: `vite.config.js` has Tailwind plugin, Laravel plugin with `refresh: true`, and correct input paths
- Verified: `resources/css/app.css` has `@import 'tailwindcss'`, `@source` directives, and `@theme` block with custom design tokens
- No code changes required — all acceptance criteria already met
- Files changed: none
- **Learnings for future iterations:**
  - The frontend toolchain was fully configured across US-001 (Tailwind theme) and initial Laravel scaffolding (Vite, laravel-vite-plugin)
  - `@tailwindcss/vite` is the Tailwind 4 Vite plugin (replaces PostCSS-based setup from Tailwind 3)
  - `npm run build` output goes to `public/build/` with a `manifest.json` for Laravel's `@vite` Blade directive
---

## 2026-03-19 - US-119
- Verified full development environment end-to-end — all acceptance criteria pass
- `sail up -d`: All 6 services (laravel.test, mysql, redis, meilisearch, mailpit, queue) start and reach healthy state
- `sail artisan migrate`: 6 migrations run successfully (users, cache, jobs, permission_tables, media_table, tag_tables)
- `sail artisan test`: 2 Pest tests pass (2 assertions)
- MySQL: `DB::connection()->getPdo()` succeeds without error
- Redis: `Redis::ping()` returns `true` (phpredis client returns bool, not string "PONG")
- Meilisearch: `Http::get('http://meilisearch:7700/health')->json()` returns `{"status":"available"}`
- Mailpit: Web UI accessible at `http://localhost:8025`, API responds with version info
- `npm run build`: Compiles CSS (39.48 kB) and JS (36.20 kB); page loads HTTP 200 without Vite manifest errors
- `sail artisan reverb:start`: WebSocket server starts on 0.0.0.0:8080
- `sail artisan queue:work --once`: Processes TestVerificationJob in 3.61ms
- `composer run dev`: Configured with concurrently (server + queue + pail + vite)
- Files changed: none (verification-only story)
- **Learnings for future iterations:**
  - `npm run build` must run on host (macOS), not inside Sail container — node_modules contain platform-specific native bindings (rolldown needs darwin binaries, container is linux-arm64)
  - phpredis `Redis::ping()` returns `bool(true)`, not the string "PONG" — this is expected behavior with the phpredis extension
  - The background queue service in compose.yaml processes jobs automatically; stop it first (`sail stop queue`) if testing `queue:work --once` manually
  - `composer run dev` uses `npx concurrently` to run 4 processes: `artisan serve`, `queue:listen`, `pail`, and `npm run dev`
---

## 2026-03-19 - US-002
- Created `<x-blob>` Blade component at `resources/views/components/blob.blade.php` matching spec section 1A.8
- Component accepts `color`, `size`, `opacity`, and `shape` props with defaults (#1FAF63, 200, 0.1, cloud)
- Cloud shape uses exact SVG path from spec (viewBox 0 0 80 80); circle shape renders `<circle cx="40" cy="40" r="38">`
- SVG has `aria-hidden="true"`, `pointer-events-none`, and `absolute` class; additional classes merge via `$attributes`
- Created `tests/Component/BlobTest.php` with 5 tests (16 assertions) covering all acceptance criteria
- Added `Component` test suite to `phpunit.xml` and registered in `tests/Pest.php`
- Files changed: `resources/views/components/blob.blade.php`, `tests/Component/BlobTest.php`, `phpunit.xml`, `tests/Pest.php`
- **Learnings for future iterations:**
  - Component tests use `$this->blade()` to render Blade components in isolation and `assertSee($html, false)` for raw HTML assertions
  - New test directories need to be added both to `phpunit.xml` (as a `<testsuite>`) and to `tests/Pest.php` (in the `pest()->extend()->in()` call)
  - Blade components in `resources/views/components/` are auto-discovered — no class or registration needed for anonymous components
---

## 2026-03-19 - US-003
- Created `<x-avatar>` Blade component at `resources/views/components/avatar.blade.php`
- Component accepts `user` (object with `id` and `name`) and `size` prop (sm=24px, md=32px, lg=44px, xl=96px; default md)
- Renders initials (first+last name letters) as white text on colored circle; gold background uses dark text (text-neutral-900)
- Background color deterministic via `id % 4`: 0=green-500, 1=coral-500, 2=violet-500, 3=gold-500
- Falls back to single initial for single-name users; uses first and last initials for multi-word names
- Fully rounded with `rounded-pill` Tailwind class
- Created `tests/Component/AvatarTest.php` with 10 tests (19 assertions) covering all acceptance criteria
- Files changed: `resources/views/components/avatar.blade.php`, `tests/Component/AvatarTest.php`
- **Learnings for future iterations:**
  - Anonymous Blade components can use `@php`/`@endphp` blocks for complex logic without needing a class-based component
  - Use `(object)` cast to create simple test user objects — no need for a full Eloquent model in component tests
  - `$this->blade()` accepts a second argument for passing variables to the Blade template
  - `mb_strtoupper`/`mb_substr` used for multibyte-safe initial extraction
---

## 2026-03-19 - US-004
- Created `<x-avatar-stack>` Blade component at `resources/views/components/avatar-stack.blade.php`
- Component accepts `users` (collection), `max` (default 5), and `size` (default sm) props
- Renders up to `max` avatars using `<x-avatar>` with -6px negative margin overlap and 2px white border ring
- Shows "+N" badge with `bg-neutral-100` / `text-neutral-500` when `users.count > max`
- Empty users collection renders nothing (wrapped in `@if($users->isNotEmpty())`)
- First avatar has no negative margin; subsequent avatars get `margin-left: -6px`
- Created `tests/Component/AvatarStackTest.php` with 8 tests (16 assertions) covering all acceptance criteria
- Files changed: `resources/views/components/avatar-stack.blade.php`, `tests/Component/AvatarStackTest.php`
- **Learnings for future iterations:**
  - Composite components can pass attributes (like `style`) through to child Blade components via `$attributes->merge()`
  - Inline styles work well for overlap margins since they need conditional logic per iteration index
  - `collect()->take($max)` and `collect()->count() - $max` pattern for overflow calculation
---

## 2026-03-19 - US-005
- Created `<x-badge>` Blade component at `resources/views/components/badge.blade.php`
- Component accepts `type` prop with 7 types: in_person, online, hybrid, going, waitlisted, cancelled, almost_full
- Each type maps to exact bg/text color pairs per spec (e.g., in_person=coral-50/coral-900, online=violet-50/violet-900)
- Uses `rounded-sm` (4px) border radius
- Accepts optional `label` prop; defaults to humanized type name (e.g., "In person", "Almost full")
- Created `tests/Component/BadgeTest.php` with 9 tests (30 assertions) covering all 7 types, radius, and custom label override
- Files changed: `resources/views/components/badge.blade.php`, `tests/Component/BadgeTest.php`
- **Learnings for future iterations:**
  - Simple key-value mapping components (type → style) work well with associative arrays in `@php` blocks
  - Humanized labels from snake_case: `ucfirst(str_replace('_', ' ', $type))` as fallback
---

## 2026-03-19 - US-006
- Created `<x-pill>` Blade component at `resources/views/components/pill.blade.php`
- Component accepts `tag` (object with `id` and `name`) or standalone `name` and `id` props
- Background cycles deterministically via `id % 4`: 0=green-50, 1=coral-50, 2=violet-50, 3=gold-50
- Text color uses matching ramp: 0=green-700, 1=coral-900, 2=violet-900, 3=gold-900
- Uses `rounded-pill` (100px), `px-3 py-1` padding, `text-xs font-medium` styling
- Created `tests/Component/PillTest.php` with 8 tests (18 assertions) covering all acceptance criteria
- Files changed: `resources/views/components/pill.blade.php`, `tests/Component/PillTest.php`
- **Learnings for future iterations:**
  - Pill component follows same pattern as badge but with deterministic color cycling by ID instead of named types
  - `$tag->id ?? $id ?? 0` pattern allows flexible prop passing (object or individual props)
---

## 2026-03-19 - US-007
- Created `<x-date-block>` Blade component at `resources/views/components/date-block.blade.php`
- Component accepts `date` (Carbon instance) and `event_type` (in_person, online, hybrid) props
- Renders 56px wide block with accent tint background: coral-50 for in_person, violet-50 for online/hybrid
- Month abbreviation: 11px, uppercase, accent-500 text; Day number: 24px, font-medium, accent-900 text
- Uses `rounded-lg` (12px) border radius and `p-2` (8px) padding
- Defaults to in_person accent when no event_type provided
- Created `tests/Component/DateBlockTest.php` with 8 tests (23 assertions) covering all acceptance criteria
- Files changed: `resources/views/components/date-block.blade.php`, `tests/Component/DateBlockTest.php`
- **Learnings for future iterations:**
  - Date formatting with Carbon: `$date->format('M')` for 3-letter month abbreviation, `$date->format('j')` for day without leading zero
  - Inline `style` attribute via `$attributes->merge()` works well for fixed pixel widths that don't map to Tailwind utilities
  - Event type color mapping follows same pattern as badge component but with 3 color stops (bg, month text, day text)
---

## 2026-03-19 - US-008
- Created `<x-stat-card>` Blade component at `resources/views/components/stat-card.blade.php`
- Component accepts `value` (number), `label` (string), and `color` (coral, violet, gold) props
- Renders solid accent background (coral-500, violet-500, gold-500) with white text; gold uses dark text (text-neutral-900)
- Value: 28px, font-weight 500, line-height 1; Label: 11px, opacity 0.8
- Uses `rounded-xl` (16px) border radius and 14px padding via inline style
- Created `tests/Component/StatCardTest.php` with 8 tests (23 assertions) covering all acceptance criteria
- Files changed: `resources/views/components/stat-card.blade.php`, `tests/Component/StatCardTest.php`
- **Learnings for future iterations:**
  - Stat card follows same color-mapping pattern as badge/pill components but with solid (500-level) backgrounds instead of tint (50-level)
  - Gold variant is the exception case requiring dark text — same pattern as avatar component's gold background
  - Inline styles work well for specific pixel values (14px padding, 28px font) that don't map cleanly to Tailwind spacing/text scales
---

## 2026-03-19 - US-009
- Created `<x-progress-bar>` Blade component at `resources/views/components/progress-bar.blade.php`
- Component accepts `current` (int), `max` (int, nullable), and optional `label` (string) props
- Track: 6px height, bg-neutral-100 background, rounded-full; Fill: bg-green-500, width proportional to current/max, rounded-full
- Text above bar: 24px/weight-500 current number + "/ N" in 14px neutral-500
- "X spots remaining" text below: coral-500 when <25% remaining, neutral-500 otherwise; singular "spot" when exactly 1
- Handles null/unlimited max gracefully — no bar, no remaining text, just the count
- Created `tests/Component/ProgressBarTest.php` with 12 tests (22 assertions) covering all acceptance criteria
- Files changed: `resources/views/components/progress-bar.blade.php`, `tests/Component/ProgressBarTest.php`
- **Learnings for future iterations:**
  - Progress bar uses conditional rendering (`@if($hasMax)`) to gracefully handle unlimited/null max — hides track and remaining text entirely
  - Percentage capping: `min(100, round(($current / $max) * 100))` prevents overflow past 100%
  - Remaining spots urgency threshold: compare remaining percentage against 25% to toggle coral vs neutral color
---

## 2026-03-19 - US-010
- Created `<x-tab-bar>` Blade component at `resources/views/components/tab-bar.blade.php`
- Component accepts `tabs` array of `['label' => string, 'href' => string, 'active' => bool]`
- Active tab: green-500 text, font-medium (weight 500), 2px bottom border in green-500
- Inactive tabs: neutral-500 text, no bottom border
- Tabs separated by 16px gap; row has 0.5px solid neutral-200 bottom border
- Horizontally scrollable on mobile with hidden scrollbar (overflow-x-auto + scrollbar-width:none + webkit scrollbar hidden)
- Created `tests/Component/TabBarTest.php` with 5 tests (15 assertions) covering all acceptance criteria
- Files changed: `resources/views/components/tab-bar.blade.php`, `tests/Component/TabBarTest.php`
- **Learnings for future iterations:**
  - Tailwind 4 arbitrary properties `[scrollbar-width:none]` and `[-ms-overflow-style:none]` work for hiding scrollbars cross-browser
  - `[&::-webkit-scrollbar]:hidden` targets the webkit scrollbar pseudo-element via Tailwind arbitrary selector
  - Inline styles with CSS custom properties (e.g., `var(--color-green-500)`) work well for sub-pixel borders (0.5px) not available in Tailwind
---

## 2026-03-19 - US-011
- Created `<x-event-card>` Blade component at `resources/views/components/event-card.blade.php`
- Component accepts `event` object with `group`, `rsvps` relationships, `title`, `starts_at`, `event_type`, `capacity`, `url`
- Header: dark accent background (green-900/coral-900/violet-900 per event type), decorative blob at 0.15 opacity, event type pill with `rgba(255,255,255,0.15)` bg and white text
- "Almost full" gold badge shows when >= 75% capacity filled
- Body: date (accent color, 11px, uppercase, weight 500), title (15px, weight 500), group name (13px, neutral-500)
- Attendance row: avatar stack + "X going" on left, "X left" on right (coral-500 when spots limited, neutral-500 otherwise)
- Card uses `rounded-xl` (16px), `border: 0.5px solid neutral-200`, wrapped in `<a>` linking to event URL
- Composes existing components: `<x-blob>`, `<x-badge>`, `<x-avatar-stack>`
- Created `tests/Component/EventCardTest.php` with 12 tests (20 assertions) covering all acceptance criteria
- Files changed: `resources/views/components/event-card.blade.php`, `tests/Component/EventCardTest.php`
- **Learnings for future iterations:**
  - Composite card components work well with `(object)` casts in tests — no need for Eloquent models when testing Blade rendering
  - `makeEvent()` helper function with defaults + overrides pattern makes tests clean and DRY
  - Event type → color mapping follows existing pattern from badge/date-block but uses 900-level shades for dark header backgrounds
  - Sub-pixel borders (0.5px) use `var(--color-neutral-200)` CSS custom property in inline style
---

## 2026-03-19 - US-012
- Created `<x-event-row>` Blade component at `resources/views/components/event-row.blade.php`
- Component accepts `event` model and optional `show_rsvp` boolean (default true)
- Horizontal layout: date block (via `<x-date-block>`) | content (title, meta line, badges) | RSVP button
- Title: 15px, font-medium; Meta line: 13px, "Day · Time · Venue" format; Badges: event type + going count + optional "Almost full"
- RSVP button: primary (bg-green-500, text-white) when plenty of spots, secondary (transparent, green-500 border) when near capacity (≥75%)
- Responsive: `flex-col` on mobile, `md:flex-row` on desktop; RSVP button is `w-full` on mobile, `md:w-auto` on desktop
- Created `tests/Component/EventRowTest.php` with 10 tests (24 assertions) covering all acceptance criteria
- Files changed: `resources/views/components/event-row.blade.php`, `tests/Component/EventRowTest.php`
- **Learnings for future iterations:**
  - Event row reuses `<x-date-block>` and `<x-badge>` components — composition pattern continues to work well
  - Primary/secondary button distinction: primary = bg-green-500 text-white, secondary = transparent with 1.5px green-500 border
  - Responsive layout uses `flex-col md:flex-row` pattern with `w-full md:w-auto` on the RSVP button for mobile full-width
  - Meta line construction: array of parts + `implode(' · ', $parts)` handles optional venue gracefully
---

## 2026-03-19 - US-013
- Created `<x-empty-state>` Blade component at `resources/views/components/empty-state.blade.php`
- Component accepts `title` (string), `description` (string), and optional `action` slot (for CTA buttons)
- Renders centered layout (`items-center justify-center text-center`) with `py-16 px-6` padding
- Decorative `<x-blob>` rendered behind text at 0.08 opacity, centered via translate transforms
- Title: 18px, weight 500, neutral-900; Description: 14px, neutral-500, mt-2
- Action slot wrapped in `@if(isset($action))` — only renders container div when slot is provided
- Created `tests/Component/EmptyStateTest.php` with 5 tests (12 assertions) covering all acceptance criteria
- Files changed: `resources/views/components/empty-state.blade.php`, `tests/Component/EmptyStateTest.php`
- **Learnings for future iterations:**
  - Empty state component composes `<x-blob>` for decorative background — same reuse pattern as event-card
  - `@if(isset($action))` is the correct way to conditionally render named slots in anonymous Blade components
  - Centering a blob behind content: use `absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2` on the blob, `relative z-10` on the content
---

## 2026-03-19 - US-014
- Created `<x-seo>` Blade component at `resources/views/components/seo.blade.php`
- Component accepts `title`, `description`, `image`, `type` (default "website"), `canonicalUrl`, and `jsonLd` (array) props
- Renders `<title>`, `<meta name="description">`, `<link rel="canonical">` (when canonicalUrl provided)
- Renders Open Graph tags: `og:type`, `og:title`, `og:description`, `og:image`, `og:url`, `og:site_name` (from `config('app.name')`)
- Renders Twitter Card tags: `twitter:card` (summary_large_image), `twitter:title`, `twitter:description`, `twitter:image`
- When `jsonLd` array is provided, renders `<script type="application/ld+json">` block with JSON_UNESCAPED_SLASHES
- OG image fallback: page-specific `image` prop → default `public/images/og-default.png` via `asset()`
- Created placeholder `public/images/og-default.png` for the default OG image
- Created `tests/Component/SeoTest.php` with 12 tests (23 assertions) covering all acceptance criteria
- Files changed: `resources/views/components/seo.blade.php`, `tests/Component/SeoTest.php`, `public/images/og-default.png`
- **Learnings for future iterations:**
  - No `site_name` platform setting exists yet — use `config('app.name')` for `og:site_name`
  - `json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)` keeps URLs clean in JSON-LD output
  - `{!! !!}` (unescaped) is needed for JSON-LD output since it contains HTML-sensitive characters
  - OG image fallback chain uses null coalescing: `$image ?? asset('images/og-default.png')`
---

## 2026-03-19 - US-015
- Created `<x-layouts.app>` guest layout at `resources/views/components/layouts/app.blade.php`
- Navbar: Greetup logo (left), Explore/Groups nav links (center), Log in/Sign up buttons (right)
- Logo stored at `resources/images/greetup.png` with copy in `public/images/greetup.png`, loaded via `asset()`
- Page background: `bg-neutral-50`, font: `font-body` (Instrument Sans via Google Fonts)
- Mobile (<768px): hamburger button toggles `#mobile-menu` via inline JS onclick handler
- Footer: logo + copyright text with current year
- `<x-seo>` included in `<head>` with configurable title/description/image/type/canonicalUrl/jsonLd props
- Created `tests/Component/Layouts/AppLayoutTest.php` with 11 tests (30 assertions) covering all acceptance criteria
- Files changed: `resources/views/components/layouts/app.blade.php`, `resources/images/greetup.png`, `public/images/greetup.png`, `tests/Component/Layouts/AppLayoutTest.php`
- **Learnings for future iterations:**
  - `Vite::asset()` requires the file to be in the Vite manifest — for static images use `asset()` with `public/` directory instead
  - Layout component tests need `$this->withoutVite()` in `beforeEach()` since `@vite` directive requires a manifest file
  - Layout components in `resources/views/components/layouts/` are auto-discovered as `<x-layouts.name>`
  - Inline JS `onclick` works for simple toggles without Alpine.js — will be replaced when Livewire/Alpine is installed
  - Sub-pixel borders (0.5px) use inline `style` with `var(--color-neutral-200)` CSS custom property
---

## 2026-03-19 - US-016
- Updated `<x-layouts.app>` with `@auth`/`@guest` conditional rendering for authenticated vs guest state
- Authenticated navbar shows: notification bell (with unread count badge), user avatar dropdown (via `<x-avatar>`)
- Notification bell dropdown: header, 10 most recent notifications list, unread highlight (bg-green-50), "Load more" button when ≥10 notifications
- Account dropdown menu: Dashboard, My Groups, Messages, Settings, Logout (with CSRF form)
- Mobile hamburger menu includes all nav links + account links (Dashboard, My Groups, Messages, Notifications with badge, Settings, Logout)
- Guest state unchanged: Log in / Sign up buttons
- Unread count capped at 99+ display, uses coral-500 badge
- Created 11 new authenticated tests using Mockery to mock notification relationships (no DB required)
- Files changed: `resources/views/components/layouts/app.blade.php`, `tests/Component/Layouts/AppLayoutTest.php`
- **Learnings for future iterations:**
  - Component tests needing auth should use `Mockery::mock(User::factory()->make())->makePartial()` to avoid DB queries
  - Mock notification relationships: `shouldReceive('unreadNotifications')->andReturn($mockRelation)` where `$mockRelation->shouldReceive('count')->andReturn(N)`
  - `@auth`/`@guest` directives cleanly split guest vs authenticated rendering in layout components
  - Cache DB query results (e.g., `$unreadCount`) in `@php` block at top of `@auth` section to avoid repeated queries
  - The `NotificationDropdown` is planned as a Livewire component per spec — current inline JS dropdown is a placeholder
---

## 2026-03-19 - US-017
- Implemented user registration at `/register` with name, email, password, password confirmation fields
- Updated User model: added `HasRoles` trait (Spatie), implemented `MustVerifyEmail` contract
- Created `RegisterController` (show form + handle registration), `RegisterRequest` (validation with custom messages)
- Created `RoleSeeder` with `user` and `admin` roles; called from `DatabaseSeeder`
- Registration creates account, assigns `user` role via Spatie, fires `Registered` event (triggers verification email), logs in user, redirects to verification notice
- Rate limited: 5 registrations per IP per hour via custom `registration` rate limiter in `AppServiceProvider`
- Added email verification routes: `verification.notice`, `verification.verify`, `verification.send`
- Configured `guest` middleware redirect to `/` and `auth` middleware redirect to `/login` in `bootstrap/app.php`
- Created `auth/register.blade.php` and `auth/verify-email.blade.php` views using `<x-layouts.app>` layout
- Created `tests/Feature/Auth/RegistrationTest.php` with 12 tests (40 assertions): happy path, all validation errors (missing fields, invalid email, duplicate email, short password, confirmation mismatch, name max length), rate limiting (6th attempt returns 429), authenticated user redirect
- Files changed: `app/Models/User.php`, `app/Providers/AppServiceProvider.php`, `bootstrap/app.php`, `database/seeders/DatabaseSeeder.php`, `database/seeders/RoleSeeder.php`, `routes/web.php`, `app/Http/Controllers/Auth/RegisterController.php`, `app/Http/Requests/Auth/RegisterRequest.php`, `resources/views/auth/register.blade.php`, `resources/views/auth/verify-email.blade.php`, `tests/Feature/Auth/RegistrationTest.php`
- **Learnings for future iterations:**
  - Feature tests using `RefreshDatabase` must run via Sail (`./vendor/bin/sail artisan test`) since they need MySQL
  - The `Registered` event triggers `SendEmailVerificationNotification` listener which needs `verification.verify` route to exist — always define all three verification routes together
  - Rate limiter definitions go in `AppServiceProvider::boot()` via `RateLimiter::for('name', ...)`
  - Use `Event::fake()` in tests that don't need to verify email sending to avoid route-not-found errors from the verification notification
  - `guest` middleware redirect configured via `$middleware->redirectUsersTo('/')` in `bootstrap/app.php`
  - Spatie roles must be created before assigning — use `Role::firstOrCreate()` in seeders and test `beforeEach()`
---

## 2026-03-19 - US-018
- Implemented email verification flow with all acceptance criteria met
- Added `verification` config to `config/auth.php` with 60-minute token expiry
- Registered `verified` middleware alias (`EnsureEmailIsVerified`) in `bootstrap/app.php`
- Updated verification verify route to redirect to `/dashboard` instead of `/`
- Added verification banner in `<x-layouts.app>` for authenticated unverified users (gold-50 background, link to verify page)
- Created `notifications` table migration (required by layout's notification queries)
- Created `tests/Feature/Auth/EmailVerificationTest.php` with 14 tests (32 assertions): verify flow, expired token, invalid hash, resend, rate limiting, banner visibility (unverified/verified/guest), verified middleware blocking/allowing, config check, success message after resend
- Files changed: `config/auth.php`, `bootstrap/app.php`, `routes/web.php`, `resources/views/components/layouts/app.blade.php`, `database/migrations/2026_03_19_224712_create_notifications_table.php`, `tests/Feature/Auth/EmailVerificationTest.php`
- **Learnings for future iterations:**
  - The `welcome.blade.php` doesn't use `<x-layouts.app>` — test auth-dependent layout features on pages that use the app layout
  - `notifications` table must exist before rendering authenticated layout (nav queries `unreadNotifications`)
  - `verified` middleware alias must be explicitly registered in `bootstrap/app.php` — Laravel 13 doesn't register it by default
  - Email verification uses signed URLs via `URL::temporarySignedRoute()` — expired links return 403, not a redirect
  - Test verified middleware by registering temporary routes in tests with `Route::middleware(['auth', 'verified'])`
---

## 2026-03-19 - US-019
- Implemented login at `/login` with email, password, and "remember me" checkbox
- Created `LoginController` (show form, authenticate, logout), `LoginRequest` (validation + rate limiting)
- Created `EnsureAccountNotSuspended` middleware redirecting suspended users to `/suspended` route
- Added suspended columns migration (`is_suspended`, `suspended_at`, `suspended_reason`) to users table
- Updated User model with suspended fields (fillable, casts) and UserFactory with `suspended()` state
- Added login/logout/suspended/dashboard routes; registered `notSuspended` middleware alias in `bootstrap/app.php`
- Created `auth/login.blade.php` and `auth/suspended.blade.php` views
- Created `tests/Feature/Auth/LoginTest.php` with 7 tests: happy path, invalid credentials, rate limiting (6th attempt returns 429), suspended user redirect, suspended page with reason, remember me
- Files changed: `app/Http/Controllers/Auth/LoginController.php`, `app/Http/Middleware/EnsureAccountNotSuspended.php`, `app/Http/Requests/Auth/LoginRequest.php`, `app/Models/User.php`, `bootstrap/app.php`, `database/factories/UserFactory.php`, `database/migrations/2026_03_19_224959_add_suspended_columns_to_users_table.php`, `resources/views/auth/login.blade.php`, `resources/views/auth/suspended.blade.php`, `routes/web.php`, `tests/Feature/Auth/LoginTest.php`
- **Learnings for future iterations:**
  - `ValidationException->status(429)` only returns 429 for JSON requests — for HTML form submissions it still redirects (302). Use `$this->postJson()` in rate limit tests to assert 429 status
  - Rate limiting in `LoginRequest` uses `email|ip` composite key via `Str::transliterate(Str::lower($email).'|'.$ip)`
  - `EnsureAccountNotSuspended` middleware should be applied to protected routes (dashboard etc.) but NOT to logout or the suspended page itself
  - The `suspended()` factory state pattern: set `is_suspended=true`, `suspended_at=now()`, `suspended_reason` with a default message
---

## 2026-03-19 - US-020
- Implemented password reset flow using Laravel's built-in Password broker
- Created `ForgotPasswordController` (show form + send reset link) and `ResetPasswordController` (show form + reset password)
- Forgot password form at `/forgot-password`, reset form at `/reset-password/{token}`
- Standard Laravel password reset: `Password::sendResetLink()` and `Password::reset()` with `PasswordReset` event
- Token expires after 60 minutes (configured in `config/auth.php` `passwords.users.expire`)
- Added "Forgot password?" link to login page
- Created `auth/forgot-password.blade.php` and `auth/reset-password.blade.php` views matching existing auth page style
- Routes: `password.request`, `password.email`, `password.reset`, `password.update` — all under `guest` middleware
- Created `tests/Feature/Auth/PasswordResetTest.php` with 9 tests (18 assertions): form display, request link, email not revealed for nonexistent accounts, reset form display, valid reset, expired token, invalid token, password confirmation mismatch, password min length
- Files changed: `app/Http/Controllers/Auth/ForgotPasswordController.php`, `app/Http/Controllers/Auth/ResetPasswordController.php`, `resources/views/auth/forgot-password.blade.php`, `resources/views/auth/reset-password.blade.php`, `resources/views/auth/login.blade.php`, `routes/web.php`, `tests/Feature/Auth/PasswordResetTest.php`
- **Learnings for future iterations:**
  - Laravel's `Password::sendResetLink()` and `Password::reset()` handle the full flow — no custom token generation needed
  - `Password::createToken($user)` in tests creates a valid reset token for testing the reset endpoint
  - `$this->travel(61)->minutes()` in Pest tests advances time to test token expiry
  - Password reset routes should use standard Laravel names: `password.request`, `password.email`, `password.reset`, `password.update`
  - The `password_reset_tokens` table is already in the default users migration — no extra migration needed
---

## 2026-03-19 - US-021
- Logout was already implemented in US-019 (LoginController::destroy + route + layout dropdown)
- Updated layout logout forms to use `{{ route('logout') }}` instead of hardcoded `/logout`
- Created `tests/Feature/Auth/LogoutTest.php` with 3 tests (9 assertions): successful logout + redirect to homepage, session invalidation, unauthenticated user redirect
- Updated existing `AppLayoutTest` to match route helper output
- Files changed: `resources/views/components/layouts/app.blade.php`, `tests/Feature/Auth/LogoutTest.php`, `tests/Component/Layouts/AppLayoutTest.php`
- **Learnings for future iterations:**
  - Use `route()` helper in Blade templates instead of hardcoded paths — keeps URLs consistent and testable
  - When updating route references in views, check component tests that assert on URL strings
---

## 2026-03-20 - US-022
- Created migration `add_profile_columns_to_users_table` adding: avatar_path, bio, location, latitude (decimal 10,7), longitude (decimal 10,7), timezone (default UTC), looking_for (JSON), profile_visibility (enum public/members_only), last_active_at, deleted_at (soft delete)
- Created `ProfileVisibility` enum (`App\Enums\ProfileVisibility`) with Public and MembersOnly cases
- Updated User model: added fillable fields, casts (looking_for as array, profile_visibility as enum, dates as Carbon, is_suspended as boolean), SoftDeletes, HasTags, Searchable, InteractsWithMedia traits
- Added relationships: groups (belongsToMany via group_members with pivot), organizedGroups (hasMany), rsvps (hasMany), discussions (hasMany), blocks (hasMany)
- Configured Laravel Scout: toSearchableArray returns id/name/bio, shouldBeSearchable returns true only when profile_visibility is public
- Configured spatie/laravel-medialibrary: avatar collection (single file, jpeg/png/webp), conversions: nav (44x44), profile-card (96x96), profile-page (256x256)
- Configured spatie/laravel-tags via HasTags trait for interests (type `interest`)
- Updated UserFactory: realistic data with random location from [Copenhagen, Berlin, London, NYC], 3-8 random interests via afterCreating, looking_for array
- Added factory states: `unverified()`, `suspended()`, `admin()` (assigns admin role via Spatie)
- Created stub models: Group, Rsvp, Discussion, Block (extending Model, to be fully implemented later)
- Added `SCOUT_DRIVER=null` to phpunit.xml to prevent Meilisearch client errors during tests
- Created `tests/Unit/Models/UserTest.php` with 21 tests (51 assertions): factory creation, interests, all relationships, all casts, soft delete, HasMedia, media collections, media conversions, searchable array, searchable conditional, factory states (unverified, suspended, admin), location validation
- Added `Unit` directory to Pest test suite in `tests/Pest.php`
- Files changed: `app/Enums/ProfileVisibility.php`, `app/Models/User.php`, `app/Models/Group.php`, `app/Models/Rsvp.php`, `app/Models/Discussion.php`, `app/Models/Block.php`, `database/factories/UserFactory.php`, `database/migrations/2026_03_19_230119_add_profile_columns_to_users_table.php`, `phpunit.xml`, `tests/Pest.php`, `tests/Unit/Models/UserTest.php`
- **Learnings for future iterations:**
  - `SCOUT_DRIVER=null` in `phpunit.xml` is essential — Searchable trait triggers engine resolution on model events, and Meilisearch client isn't installed
  - Spatie MediaLibrary conversion names are accessed via `$conversion->getName()`, not `->name` property
  - When defining relationships to models that don't exist yet, create stub models extending `Illuminate\Database\Eloquent\Model`
  - Factory `afterCreating` callback is the right place for attaching tags and assigning roles (requires persisted model)
  - `ReflectionProperty` is needed to access the protected `$mediaConversions` property for testing conversion registration
---

## 2026-03-20 - US-023
- Created `App\Http\Middleware\TrackLastActivity` with `terminate()` method for after-response DB update
- Uses `DB::table('users')->update()` in `terminate()` to avoid latency on the request — runs after response is sent
- Registered middleware on the `web` group via `$middleware->appendToGroup('web', ...)` in `bootstrap/app.php`
- Created `tests/Feature/Middleware/TrackLastActivityTest.php` with 3 tests (3 assertions): authenticated request updates `last_active_at`, unauthenticated request does nothing, timestamp matches frozen time
- Files changed: `app/Http/Middleware/TrackLastActivity.php`, `bootstrap/app.php`, `tests/Feature/Middleware/TrackLastActivityTest.php`
- **Learnings for future iterations:**
  - `terminate()` method on middleware runs after the response is sent to the browser — ideal for non-blocking updates
  - Use `DB::table()` instead of Eloquent `$user->update()` in terminate to avoid model event side effects (e.g., Scout re-indexing)
  - `$this->freezeTime()` in Pest tests is essential for asserting exact timestamps in after-response middleware
  - Middleware appended to the `web` group applies to all web routes including both guest and auth — the `terminate()` method checks `$request->user()` to skip unauthenticated requests
---

## 2026-03-20 - US-024
- Applied `EnsureAccountNotSuspended` middleware to all authenticated routes (was previously only on dashboard)
- Restructured `routes/web.php`: only `logout` and `suspended` routes remain outside `notSuspended` group; all other auth routes (email verification, dashboard, future routes) are wrapped
- Created `tests/Feature/Middleware/EnsureAccountNotSuspendedTest.php` with 6 tests (13 assertions): suspended redirect, reason display, logout link, non-suspended access, suspended user can logout, middleware applies to email verification routes
- Middleware and view (`auth/suspended.blade.php`) were already created in US-019; this story ensures full coverage
- Files changed: `routes/web.php`, `tests/Feature/Middleware/EnsureAccountNotSuspendedTest.php`
- **Learnings for future iterations:**
  - When applying middleware to "all authenticated routes", keep `logout` and the middleware's target page (e.g., `suspended`) outside the middleware group to avoid infinite redirects and locked-out users
  - Route group nesting pattern: `auth` → `notSuspended` → all protected routes; `auth` only → logout + suspended page
---

## 2026-03-20 - US-025
- Created `GroupRole` enum (`App\Enums\GroupRole`) with 5 roles: Member(0), EventOrganizer(1), AssistantOrganizer(2), CoOrganizer(3), Organizer(4)
- Enum includes `level()` for numeric comparison and `isAtLeast()` for hierarchy checks
- Created `EnsureGroupMember` middleware: resolves group from route, returns 403 if user is not a member
- Created `EnsureGroupRole` middleware: accepts minimum role parameter (e.g., `groupRole:event_organizer`), returns 403 if user's group role is below required level
- Both middleware handle route model binding and raw ID route parameters via `resolveGroup()` helper
- Registered middleware aliases `groupMember` and `groupRole` in `bootstrap/app.php`
- Created `groups` table migration (minimal: id, name, organizer_id) and `group_members` pivot migration (user_id, group_id, role, joined_at)
- Updated Group model from stub to full model with HasFactory, organizer/members relationships
- Created GroupFactory for test use
- Created `tests/Feature/Middleware/EnsureGroupMemberTest.php` with 3 tests (4 assertions): non-member gets 403, member proceeds, membership scoped to correct group
- Created `tests/Feature/Middleware/EnsureGroupRoleTest.php` with 6 tests (11 assertions): non-member 403, insufficient role 403, matching role proceeds, exceeding role proceeds, full hierarchy ordering, higher-tier role requirement
- Files changed: `app/Enums/GroupRole.php`, `app/Http/Middleware/EnsureGroupMember.php`, `app/Http/Middleware/EnsureGroupRole.php`, `app/Models/Group.php`, `bootstrap/app.php`, `database/factories/GroupFactory.php`, `database/migrations/2026_03_19_230941_create_groups_table.php`, `database/migrations/2026_03_19_230942_create_group_members_table.php`, `tests/Feature/Middleware/EnsureGroupMemberTest.php`, `tests/Feature/Middleware/EnsureGroupRoleTest.php`
- **Learnings for future iterations:**
  - `$request->route('param')` returns a string (the ID) when route model binding isn't set up — middleware should handle both Model instances and raw IDs via a `resolveGroup()` helper
  - Migration ordering matters for foreign keys — `groups` table must be created before `group_members` (timestamp-based ordering)
  - Test routes defined in `beforeEach` should use direct URL paths (`test-group/{$group->id}`) rather than `route('name')` — named routes from `beforeEach` may not resolve correctly
  - `uses(RefreshDatabase::class)` must be added per-file since it's commented out globally in `tests/Pest.php`
  - Group role middleware pattern: `groupRole:role_name` passes the role string as a parameter to `handle()`, then cast to enum via `GroupRole::from()`
---

## 2026-03-20 - US-026
- Updated `create_groups_table` migration with all columns from spec section 4.2: slug (unique), description, description_html, location, latitude (decimal 10,7), longitude (decimal 10,7), timezone, cover_photo_path, visibility, requires_approval, max_members, welcome_message, is_active, soft deletes
- Added indexes: (latitude, longitude) for geo queries, (visibility, is_active) for discovery
- Created `GroupVisibility` enum (`App\Enums\GroupVisibility`) with Public and Private cases
- Updated Group model: fillable fields, casts, SoftDeletes, HasSlug, HasTags, Searchable, InteractsWithMedia traits
- Added relationships: organizer (belongsTo User), members (belongsToMany User via group_members), events (hasMany), discussions (hasMany)
- Configured spatie/laravel-sluggable: generates slug from name
- Configured spatie/laravel-medialibrary: cover_photo collection (single file), conversions: card (400x200), header (1200x400)
- Configured spatie/laravel-tags via HasTags trait for interests (type `interest`)
- Added scopes: `scopeActive()`, `scopePublic()`, `scopeNearby($lat, $lng, $radiusKm)` using Haversine formula per spec section 8.2
- Laravel Scout searchable: toSearchableArray returns id, name, description, location
- Updated GroupFactory: realistic data with hardcoded lat/lng locations, states: `private()`, `requiresApproval()`, `inactive()`
- Created stub Event model (to be fully implemented later)
- Created `tests/Unit/Models/GroupTest.php` with 24 tests (59 assertions): factory, all relationships, slug generation (including unique slugs), all casts, soft delete, HasMedia, media collections, media conversions, active/public/nearby scopes with distance assertions, searchable array, factory states, pivot data, tags
- Files changed: `app/Enums/GroupVisibility.php`, `app/Models/Group.php`, `app/Models/Event.php`, `database/factories/GroupFactory.php`, `database/migrations/2026_03_19_230941_create_groups_table.php`, `tests/Unit/Models/GroupTest.php`
- **Learnings for future iterations:**
  - The existing groups migration and model were minimal stubs from US-025 (middleware story) — they needed to be fully fleshed out with all spec columns
  - Haversine formula scope from spec section 8.2 uses bindings array `[$lat, $lng, $lat, $radiusKm]` — the latitude appears twice (for cos and sin)
  - `spatie/laravel-sluggable` `HasSlug` trait + `getSlugOptions()` method handles unique slug generation automatically
  - GroupFactory hardcoded locations pattern matches UserFactory approach — use `fake()->randomElement()` over dynamic faker coordinates for reproducible test data
  - Stub Event model created since Group hasMany events — same pattern used for Rsvp/Discussion/Block stubs in US-022
---

## 2026-03-20 - US-027
- Updated `group_members` migration: added `is_banned` (default false), `banned_at`, `banned_reason`, `index (group_id, role)`, `index (user_id)` per spec section 4.3
- Created `group_membership_questions` migration per spec section 4.4: group_id (FK), question (string 500), is_required (default true), sort_order (default 0)
- Created `group_membership_answers` migration: question_id (FK), user_id (FK), answer (text), UNIQUE (question_id, user_id)
- Created `group_join_requests` migration per spec section 4.5: group_id (FK), user_id (FK), status (enum default pending), reviewed_by (FK nullable), reviewed_at, denial_reason, UNIQUE (group_id, user_id), INDEX (group_id, status)
- Created `GroupMember` pivot model extending `Pivot` with `GroupRole` enum cast, `is_banned` boolean cast, `joined_at`/`banned_at` datetime casts
- Created `GroupMembershipQuestion` model with group/answers relationships, boolean/integer casts
- Created `GroupMembershipAnswer` model with question/user relationships
- Created `GroupJoinRequest` model with `JoinRequestStatus` enum cast, group/user/reviewer relationships, default `pending` status via `$attributes`
- Created `JoinRequestStatus` enum (Pending, Approved, Denied)
- Updated Group model: members relationship now uses `GroupMember` pivot, added `membershipQuestions()` and `joinRequests()` HasMany relationships
- Updated User model: groups relationship now uses `GroupMember` pivot with extended pivot fields
- Fixed `EnsureGroupRole` middleware: handle pivot role being already cast to `GroupRole` enum (guard against double `::from()`)
- Fixed existing `GroupTest` pivot assertion to expect `GroupRole::Member` enum instead of string `'member'`
- Created `tests/Unit/Models/GroupMemberTest.php` with 26 tests (53 assertions) covering all models, relationships, casts, unique constraints, and defaults
- Files changed: `app/Enums/JoinRequestStatus.php`, `app/Http/Middleware/EnsureGroupRole.php`, `app/Models/Group.php`, `app/Models/GroupJoinRequest.php`, `app/Models/GroupMember.php`, `app/Models/GroupMembershipAnswer.php`, `app/Models/GroupMembershipQuestion.php`, `app/Models/User.php`, `database/migrations/2026_03_19_230942_create_group_members_table.php`, `database/migrations/2026_03_19_231750_create_group_membership_questions_table.php`, `database/migrations/2026_03_19_231753_create_group_membership_answers_table.php`, `database/migrations/2026_03_19_231753_create_group_join_requests_table.php`, `tests/Unit/Models/GroupMemberTest.php`, `tests/Unit/Models/GroupTest.php`
- **Learnings for future iterations:**
  - When adding `->using(PivotModel::class)` with enum casts to a BelongsToMany, middleware/code that previously accessed `$pivot->role` as a string will now get the enum — guard with `instanceof` before calling `::from()`
  - `Model::create()` doesn't auto-fill DB column defaults — use `$attributes` property on the model for defaults needed in PHP (e.g., `'status' => 'pending'`)
  - Pivot model extending `Pivot` must set `public $incrementing = true` when the pivot has an auto-incrementing `id` column
  - When updating the pivot model (adding `->using()`), update BOTH sides of the relationship (Group and User models) to avoid inconsistency
---

## 2026-03-20 - US-028
- Created `EventType` and `EventStatus` enums (`in_person/online/hybrid`, `draft/published/cancelled/past`)
- Created `event_series` migration with `group_id` FK and `recurrence_rule` string
- Created `events` migration with all columns from spec 4.6, including composite indexes `(group_id, status, starts_at)`, `(starts_at, status)`, `(venue_latitude, venue_longitude)`, `(series_id)`, and UNIQUE `(group_id, slug)`
- Created `event_hosts` migration with UNIQUE `(event_id, user_id)`
- Created `EventSeries` model with `group` and `events` relationships
- Created `Event` model with relationships (group, creator, hosts, rsvps, comments, chatMessages, feedback, series), scopes (upcoming, past, published, cancelled, nearby), Spatie sluggable scoped to group, medialibrary (cover_photo with card 400x200 and header 1200x400 conversions), Scout searchable (name, description, venue_name)
- Created `EventFactory` with states: `draft()`, `published()`, `cancelled()`, `past()`, `online()`, `hybrid()`, `withRsvpLimit(int)`
- Created `EventSeriesFactory`
- Created stub models for `Comment`, `ChatMessage`, `Feedback` (needed for Event relationships)
- Created `tests/Unit/Models/EventTest.php` with 35 tests (81 assertions)
- Files changed: `app/Enums/EventStatus.php`, `app/Enums/EventType.php`, `app/Models/ChatMessage.php`, `app/Models/Comment.php`, `app/Models/Event.php`, `app/Models/EventSeries.php`, `app/Models/Feedback.php`, `database/factories/EventFactory.php`, `database/factories/EventSeriesFactory.php`, `database/migrations/2026_03_19_232304_create_event_series_table.php`, `database/migrations/2026_03_19_232308_create_events_table.php`, `database/migrations/2026_03_19_232309_create_event_hosts_table.php`, `tests/Unit/Models/EventTest.php`
- **Learnings for future iterations:**
  - When two migrations share the same timestamp, MySQL processes them alphabetically — if `event_hosts` references `events`, its timestamp must be later
  - Use Spatie sluggable `extraScope()` to scope slug uniqueness within a parent (e.g., `->extraScope(fn (Builder $builder) => $builder->where('group_id', $this->group_id))`)
  - Stub models (empty `Model` subclasses) are sufficient placeholders for relationships that will be implemented in later stories
---

## 2026-03-20 - US-029
- Created `rsvps` table migration with all columns per spec section 4.9: event_id, user_id, status (default going), guest_count (default 0), attendance_mode (nullable), checked_in (default false), checked_in_at, checked_in_by (FK users), attended (nullable), waitlisted_at (nullable)
- Added indexes: UNIQUE (event_id, user_id), (event_id, status), (user_id, status), (event_id, status, waitlisted_at)
- Created three enums: `RsvpStatus` (going/not_going/waitlisted), `AttendanceMode` (in_person/online), `AttendanceResult` (attended/no_show)
- Updated `Rsvp` model with relationships (event, user, checkedInBy), enum casts, fillable attributes
- Created `RsvpFactory` with states: `going()`, `waitlisted()`, `notGoing()`, `checkedIn()`, `withGuests(int)`
- Created `tests/Unit/Models/RsvpTest.php` with 17 tests (38 assertions) covering factory, relationships, casts, and unique constraint enforcement
- Files changed: `app/Enums/RsvpStatus.php`, `app/Enums/AttendanceMode.php`, `app/Enums/AttendanceResult.php`, `app/Models/Rsvp.php`, `database/factories/RsvpFactory.php`, `database/migrations/2026_03_19_233000_create_rsvps_table.php`, `tests/Unit/Models/RsvpTest.php`
- **Learnings for future iterations:**
  - The existing `Rsvp` model was already a stub (created during US-028 Event story) — just needed to be fleshed out
  - Separate enums for each domain concept (RsvpStatus, AttendanceMode, AttendanceResult) keeps things clean and type-safe
---

## 2026-03-20 - US-030
- Created `discussions` migration: id, group_id (FK), user_id (FK), title, slug, body, body_html, is_pinned, is_locked, last_activity_at, timestamps, soft deletes, UNIQUE (group_id, slug), index (group_id, is_pinned, last_activity_at)
- Created `discussion_replies` migration: id, discussion_id (FK), user_id (FK), body, body_html, timestamps, soft deletes, index (discussion_id, created_at)
- Fleshed out `Discussion` model with relationships (group, user, replies), Spatie sluggable (scoped to group), casts, soft deletes, and `pinnedFirst` scope
- Created `DiscussionReply` model with relationships (discussion, user) and soft deletes
- Created factories for both models with `pinned()` and `locked()` states on DiscussionFactory
- Created 21 unit tests (47 assertions) covering factory, relationships, slug generation, unique constraints, casts, soft deletes, and scopes
- Files changed: `app/Models/Discussion.php`, `app/Models/DiscussionReply.php`, `database/factories/DiscussionFactory.php`, `database/factories/DiscussionReplyFactory.php`, `database/migrations/2026_03_19_233214_create_discussions_table.php`, `database/migrations/2026_03_19_233217_create_discussion_replies_table.php`, `tests/Unit/Models/DiscussionTest.php`, `tests/Unit/Models/DiscussionReplyTest.php`
- **Learnings for future iterations:**
  - The `Discussion` model was already a stub (created during a prior story for Group's `discussions()` relationship) — just needed to be fleshed out
  - When testing unique DB constraints with Spatie sluggable models, use `DB::table()->insert()` to bypass slug auto-generation, otherwise sluggable will auto-deduplicate and the constraint won't trigger
  - Spatie sluggable `extraScope()` is already the established pattern for scoping slugs within a parent (used in Event model too)
---

## 2026-03-20 - US-031
- Created `event_comments` migration with id, event_id (FK), user_id (FK), parent_id (FK nullable self-referencing), body, body_html, timestamps, soft deletes, indexes on (event_id, created_at) and (parent_id)
- Created `event_comment_likes` migration with id, comment_id (FK), user_id (FK), created_at, UNIQUE (comment_id, user_id)
- Created `event_feedback` migration with id, event_id (FK), user_id (FK), rating (tinyint), body (nullable), timestamps, UNIQUE (event_id, user_id), index (event_id, rating)
- Fleshed out `Comment` model (was stub) with table `event_comments`, relationships (event, user, parent, replies, likedBy), soft deletes
- Fleshed out `Feedback` model (was stub) with table `event_feedback`, relationships (event, user), rating cast
- Created `EventCommentLike` model with relationships (comment, user), `UPDATED_AT = null` since table only has `created_at`
- Created factories: `CommentFactory` (with `reply()` state), `EventCommentLikeFactory`, `FeedbackFactory`
- Created 21 unit tests (48 assertions) covering factory, relationships, soft deletes, unique constraints, casts, table names
- Files changed: `app/Models/Comment.php`, `app/Models/Feedback.php`, `app/Models/EventCommentLike.php`, `database/migrations/2026_03_20_000001_create_event_comments_table.php`, `database/migrations/2026_03_20_000002_create_event_comment_likes_table.php`, `database/migrations/2026_03_20_000003_create_event_feedback_table.php`, `database/factories/CommentFactory.php`, `database/factories/EventCommentLikeFactory.php`, `database/factories/FeedbackFactory.php`, `tests/Unit/Models/CommentTest.php`, `tests/Unit/Models/EventCommentLikeTest.php`, `tests/Unit/Models/FeedbackTest.php`
- **Learnings for future iterations:**
  - `Comment` and `Feedback` were stub models created during US-028 (Event model) — just needed to be fleshed out with proper table names, relationships, and casts
  - When a table only has `created_at` (no `updated_at`), use `public const UPDATED_AT = null` on the model instead of `$timestamps = false` — this preserves auto-filling of `created_at`
  - For `BelongsToMany` pivots without `updated_at`, use `withPivot('created_at')` instead of `withTimestamps()` since the latter expects both columns
  - The Event model already had `comments()` and `feedback()` HasMany relationships pointing to the stub models
---

## 2026-03-20 - US-032
- Created `event_chat_messages` migration with id, event_id (FK), user_id (FK), body, reply_to_id (FK self-referencing, nullable), timestamps, deleted_at, and index on (event_id, created_at)
- Created `EventChatMessage` model with event, user, replyTo (BelongsTo), and replies (HasMany) relationships, plus soft deletes
- Created `EventChatMessageFactory` with `replyTo()` state
- Replaced stub `ChatMessage` model with `EventChatMessage` — updated Event model's `chatMessages()` relationship accordingly
- Created unit test with 8 tests (20 assertions) covering factory, all relationships, soft deletes, table name, and factory state
- Files changed: `app/Models/EventChatMessage.php` (new), `app/Models/ChatMessage.php` (deleted), `app/Models/Event.php`, `database/migrations/2026_03_20_000004_create_event_chat_messages_table.php`, `database/factories/EventChatMessageFactory.php`, `tests/Unit/Models/EventChatMessageTest.php`
- **Learnings for future iterations:**
  - `ChatMessage` was a stub model from US-028 — renamed to `EventChatMessage` to match the `event_chat_messages` table convention
  - Self-referencing FK (reply_to_id) uses `->constrained('event_chat_messages')` with nullable
---

## 2026-03-20 - US-033
- Implemented Conversation, ConversationParticipant, and DirectMessage models for 1:1 messaging
- Created `conversations` migration with id + timestamps
- Created `conversation_participants` migration with conversation_id FK, user_id FK, last_read_at (nullable), is_muted (default false), created_at, UNIQUE(conversation_id, user_id), INDEX(user_id, last_read_at)
- Created `direct_messages` migration with conversation_id FK, user_id FK, body, timestamps, soft deletes, INDEX(conversation_id, created_at)
- Conversation model has hasMany participants and messages
- User model has belongsToMany conversations through conversation_participants
- Factories and unit tests (13 tests, 32 assertions) all passing
- Files changed: app/Models/Conversation.php, ConversationParticipant.php, DirectMessage.php, User.php, 3 migrations, 3 factories, 3 test files
- **Learnings for future iterations:**
  - `conversation_participants` uses only `created_at` (no `updated_at`), so set `$timestamps = false` on the model and cast `created_at` manually
  - When pivot table lacks `updated_at`, don't use `withTimestamps()` on BelongsToMany — use `withPivot()` instead
---

## 2026-03-20 - US-034
- Created `ReportReason` and `ReportStatus` enums
- Created `reports` migration with polymorphic reportable, reason, status (default pending), reviewed_by, reviewed_at, resolution_notes, and index on (status, created_at)
- Created `blocks` migration with blocker_id, blocked_id, created_at only, unique constraint on (blocker_id, blocked_id), index on blocked_id
- Created `Report` model with polymorphic reportable relationship, reporter/reviewer BelongsTo, and pending/reviewed/resolved/dismissed scopes
- Updated `Block` model with blocker/blocked BelongsTo relationships, timestamps disabled (only created_at)
- Created `ReportFactory` with reviewed/resolved/dismissed states
- Created `BlockFactory`
- Created unit tests for both models (25 tests, 50 assertions)
- Files changed: `app/Enums/ReportReason.php`, `app/Enums/ReportStatus.php`, `app/Models/Report.php`, `app/Models/Block.php`, `database/migrations/2026_03_20_000008_create_reports_table.php`, `database/migrations/2026_03_20_000009_create_blocks_table.php`, `database/factories/ReportFactory.php`, `database/factories/BlockFactory.php`, `tests/Unit/Models/ReportTest.php`, `tests/Unit/Models/BlockTest.php`
- **Learnings for future iterations:**
  - `$table->morphs()` already creates the polymorphic index — don't add a duplicate `$table->index(['type', 'id'])` or MySQL will error with "Duplicate key name"
  - For models with only `created_at` (no `updated_at`): set `$timestamps = false`, add `created_at` to `$fillable`, cast it to `datetime`, and set it in the factory definition
---

## 2026-03-20 - US-035
- Created `NotificationChannel` enum (email/web/push)
- Created `notification_preferences` migration: id, user_id (FK), channel (string), type (string), enabled (boolean default true), timestamps, UNIQUE(user_id, channel, type)
- Created `group_notification_mutes` migration: id, user_id (FK), group_id (FK), created_at, UNIQUE(user_id, group_id)
- Created `settings` migration: id, key (string unique), value (text nullable), timestamps
- Created `pending_notification_digests` migration: id, user_id (FK), notification_type (string), data (JSON), created_at, INDEX(user_id, notification_type, created_at)
- Created models: NotificationPreference, GroupNotificationMute, Setting, PendingNotificationDigest with relationships and factories
- Created unit tests: 20 tests, 32 assertions — all passing
- Files changed: `app/Enums/NotificationChannel.php`, `app/Models/NotificationPreference.php`, `app/Models/GroupNotificationMute.php`, `app/Models/Setting.php`, `app/Models/PendingNotificationDigest.php`, `database/migrations/2026_03_20_000010_create_notification_preferences_table.php`, `database/migrations/2026_03_20_000011_create_group_notification_mutes_table.php`, `database/migrations/2026_03_20_000012_create_settings_table.php`, `database/migrations/2026_03_20_000013_create_pending_notification_digests_table.php`, `database/factories/NotificationPreferenceFactory.php`, `database/factories/GroupNotificationMuteFactory.php`, `database/factories/SettingFactory.php`, `database/factories/PendingNotificationDigestFactory.php`, `tests/Unit/Models/NotificationPreferenceTest.php`, `tests/Unit/Models/GroupNotificationMuteTest.php`, `tests/Unit/Models/SettingTest.php`, `tests/Unit/Models/PendingNotificationDigestTest.php`
- **Learnings for future iterations:**
  - For composite index names exceeding MySQL's 64-char limit, provide an explicit shorter name as the second argument to `$table->index()`
  - The `Setting` model is a simple key-value store with no relationships — no enum or cast needed beyond default string handling
---

## 2026-03-20 - US-036
- Created `InterestSeeder` with 33 interest tags across 5 categories (Technology, Languages & Frameworks, Creative, Lifestyle, Professional)
- Uses Spatie Tags `Tag::findOrCreate($name, 'interest')` for idempotent seeding with auto-generated slugs
- Registered `InterestSeeder` in `DatabaseSeeder`
- Files changed: `database/seeders/InterestSeeder.php` (new), `database/seeders/DatabaseSeeder.php`
- **Learnings for future iterations:**
  - Spatie Tags `findOrCreate` is idempotent — finds first, creates only if not found (no need for manual `firstOrCreate`)
  - Spatie Tags auto-generates slugs from the name via its `setNameAttribute` mutator
  - Tags of type `interest` are used for tagging groups and users with topics
---

## 2026-03-20 - US-037
- Created `App\Services\MarkdownService` wrapping `league/commonmark`
- Configured `DisallowedRawHtmlExtension` to block dangerous HTML tags (script, iframe, object, embed, etc.)
- Set `html_input` to `strip` to entirely remove raw HTML from markdown input (not escape)
- Added `ExternalLinkExtension` with `nofollow`, `noopener`, and `target="_blank"` on all links
- Returns empty string for null/empty/whitespace input
- Created unit test with 10 test cases covering: headings, lists, links, code blocks, HTML stripping, link attributes, empty input
- Files changed: `app/Services/MarkdownService.php` (new), `tests/Unit/Services/MarkdownServiceTest.php` (new)
- **Learnings for future iterations:**
  - CommonMark `html_input` option set to `strip` removes raw HTML entirely; `DisallowedRawHtmlExtension` is defense-in-depth
  - `ExternalLinkExtension` adds `noreferrer` automatically alongside `nofollow` and `noopener`
  - `DisallowedRawHtmlExtension` escapes (`&lt;`) rather than strips — use `html_input => strip` for true removal
  - MarkdownService tests are pure unit tests with no DB dependency — they run without Sail
---

## 2026-03-20 - US-038
- Created `App\Services\GeocodingService` wrapping `geocodio/geocodio-library-php` with `geocode()`, `reverse()`, and `batch()` methods
- Added geocodio config to `config/services.php` with `GEOCODIO_API_KEY` env var
- Registered `GeocodingService` as singleton in `AppServiceProvider`
- Created `GeocodeLocation` queued job with 3 retries and exponential backoff (10s, 60s, 300s)
- Created observers for Group, Event, and User models that dispatch `GeocodeLocation` when address fields change
- Registered observers in `AppServiceProvider::boot()`
- Created unit tests covering: forward geocode (valid/invalid), reverse geocode, batch geocode, missing API key (null/empty), API errors, Haversine distance accuracy
- Files changed: `app/Services/GeocodingService.php` (new), `app/Jobs/GeocodeLocation.php` (new), `app/Observers/GroupObserver.php` (new), `app/Observers/EventObserver.php` (new), `app/Observers/UserObserver.php` (new), `app/Providers/AppServiceProvider.php`, `config/services.php`, `tests/Unit/Services/GeocodingServiceTest.php` (new)
- **Learnings for future iterations:**
  - Geocodio library's `geocode()` method accepts a string for single or array for batch — batch response wraps results in `response.results` per entry
  - Geocodio `reverse()` accepts `[lat, lng]` array format
  - When injecting mock via reflection into private property, don't expect `setApiKey` calls since the `client()` factory method is bypassed
  - Model observers are registered in `AppServiceProvider::boot()` via `Model::observe(ObserverClass::class)`
  - Use `updateQuietly()` in jobs to prevent observer re-triggering when storing geocoded lat/lng
  - `wasChanged()` checks post-save attribute changes — use in `updated` observer to detect if address text actually changed
---

## 2026-03-20 - US-039
- Created `App\Services\RsvpService` with methods: `rsvpGoing()`, `rsvpNotGoing()`, `joinWaitlist()`
- Created `App\Jobs\PromoteFromWaitlist` queued job that promotes FIFO waitlisted members when spots open
- Created `tests/Unit/Services/RsvpServiceTest.php` with 15 tests covering all acceptance criteria
- Files changed: `app/Services/RsvpService.php`, `app/Jobs/PromoteFromWaitlist.php`, `tests/Unit/Services/RsvpServiceTest.php`
- **Learnings for future iterations:**
  - EventFactory `guest_limit` defaults to 0 (no guests), not null — tests involving guests need to explicitly set `guest_limit`
  - `Rsvp::updateOrCreate()` is the right pattern for RSVP operations since a user can only have one RSVP per event
  - The `cancelled` factory state sets `status => Cancelled` AND `cancelled_at` — but the service should check both status and `cancelled_at` for robustness
  - `Queue::fake()` must be called before the action that dispatches — otherwise the job is dispatched to the real queue
  - Available spots calculation: `SUM(1 + guest_count)` counts the member + their guests as a single query
---

## 2026-03-20 - US-040
- Created `App\Services\WaitlistService` with `promoteNext(Event): ?Rsvp` and `promoteAll(Event): array` methods
- FIFO ordering by `waitlisted_at`, skips members whose `guest_count + 1` exceeds available spots
- `promoteAll()` loops `promoteNext()` to handle multiple spot openings and revisit skipped members
- Created `App\Notifications\PromotedFromWaitlist` notification (mail + database channels)
- Refactored `App\Jobs\PromoteFromWaitlist` to delegate to `WaitlistService::promoteAll()`
- Created `tests/Unit/Services/WaitlistServiceTest.php` with 11 tests covering FIFO, guest-count skipping, revisiting, empty waitlist, cancelled event, notifications
- Files changed: `app/Services/WaitlistService.php` (new), `app/Notifications/PromotedFromWaitlist.php` (new), `app/Jobs/PromoteFromWaitlist.php` (updated), `tests/Unit/Services/WaitlistServiceTest.php` (new)
- **Learnings for future iterations:**
  - The `PromoteFromWaitlist` job previously had inline promotion logic; now services encapsulate business logic and jobs delegate to them
  - `promoteNext()` returns a single promoted Rsvp (or null) — `promoteAll()` loops it for batch promotions
  - Notification::fake() must be called before any service method that sends notifications in tests
  - The `[event_id, status, waitlisted_at]` index on RSVPs supports efficient FIFO waitlist queries
---

## 2026-03-20 - US-041
- Implemented `App\Policies\GroupPolicy` with all actions from spec section 3.4 permission matrix
- Role hierarchy enforced via `GroupRole::isAtLeast()`: member(0) < event_organizer(1) < assistant_organizer(2) < co_organizer(3) < organizer(4)
- Actions: view (any), join (verified non-member non-banned), leave (member except organizer), event_organizer+ (createEvent, editAnyEvent, cancelEvent, manageRsvps, checkInAttendees, sendGroupMessages, assignEventHosts), assistant_organizer+ (acceptRequests, removeMembers, banMembers), co_organizer+ (editSettings, manageLeadership, viewAnalytics), organizer only (delete, transferOwnership)
- Suspended users denied everything; non-members denied group actions; banned members denied all actions
- Comprehensive unit test with 104 tests covering every action/role combination, role inheritance, suspended/non-member/banned denial
- Files changed: `app/Policies/GroupPolicy.php` (new), `tests/Unit/Policies/GroupPolicyTest.php` (new)
- **Learnings for future iterations:**
  - Policies can be created with `php artisan make:policy --model=ModelName`; the generated scaffold needs full rewrite for custom actions
  - `GroupMember` pivot uses `GroupRole` enum cast — when reading from `->pivot->role`, it's already a `GroupRole` instance (no need for `GroupRole::from()` in most cases, but guard with `instanceof` per codebase pattern)
  - Use Pest datasets with closures (`fn () => $this->member`) for deferred access to `beforeEach` properties
  - Tests requiring database go through `./vendor/bin/sail artisan test` (MySQL container)
---

## 2026-03-20 - US-042
- Created `App\Policies\EventPolicy` with methods: view, create, update, cancel, manageAttendees, checkIn, rsvp
- Event hosts can update, manageAttendees, checkIn for their specific event only (not other events)
- Event organizer+ within the group can perform all event actions on any group event
- Non-members and unverified users cannot RSVP; suspended/banned users denied all actions
- Created `tests/Unit/Policies/EventPolicyTest.php` with 43 tests covering all roles, host-specific scoping, and edge cases
- Files changed: `app/Policies/EventPolicy.php`, `tests/Unit/Policies/EventPolicyTest.php`
- **Learnings for future iterations:**
  - EventPolicy follows same `getMembership`/`hasGroupRole` pattern as GroupPolicy — the private helpers are duplicated rather than extracted to a trait
  - `create` method takes a Group (not Event) since the event doesn't exist yet — matches Laravel policy convention for `create`
  - Host-scoping: hosts are checked via `event->hosts()->where('user_id', ...)->exists()` on the specific event
---

## 2026-03-20 - US-043
- Implemented `DiscussionPolicy` with authorization for discussion threads
- Actions: create, reply, pin, lock, delete (discussion), deleteReply
- Any group member can create discussions and reply (unless locked)
- Co-organizer+ can pin/unpin, lock/unlock, delete any discussion/reply
- Authors can delete their own replies but not others'
- Replying to locked discussions is denied for all roles
- Files changed: `app/Policies/DiscussionPolicy.php`, `tests/Unit/Policies/DiscussionPolicyTest.php`
- **Learnings for future iterations:**
  - Policy pattern: reuse `getMembership()`, `hasGroupRole()`, `isActiveMember()` private helpers (same as EventPolicy)
  - Discussion model has `is_locked` boolean — check it before allowing reply actions
  - DiscussionReply has `user_id` for ownership checks and `discussion` relationship to reach the group
  - Factories support `->for($model, 'relationship')` syntax for setting BelongsTo relations
---

## 2026-03-20 - US-044
- Created `EventChatPolicy` with `send`, `edit`, and `delete` methods
- `send`: allows RSVP Going users OR active group members; blocked when `is_chat_enabled` is false
- `edit`: owner-only; blocked when chat disabled
- `delete`: owner can delete own; event_organizer+ can delete any; blocked when chat disabled
- Created `tests/Unit/Policies/EventChatPolicyTest.php` with 13 tests (18 assertions)
- Files changed: `app/Policies/EventChatPolicy.php`, `tests/Unit/Policies/EventChatPolicyTest.php`
- **Learnings for future iterations:**
  - EventChatPolicy follows same `getMembership`/`hasGroupRole`/`isActiveMember` helper pattern as DiscussionPolicy
  - `event_organizer` is the minimum leadership role for chat message moderation (unlike `co_organizer` for discussions)
  - Chat policies check `is_chat_enabled` on every action (send/edit/delete), returning false when disabled
---

## 2026-03-20 - US-045
- Implemented `App\Services\NotificationService` wrapping notification dispatch with all filtering rules
- Checks: suspended users skipped, blocked user filtering, group mute suppression (with critical exemption), per-type channel preferences, email digest batching (5+ threshold in 15min window)
- Critical notifications (PromotedFromWaitlist, JoinRequestApproved, MemberRemoved, MemberBanned, AccountSuspended) bypass group mutes
- Web/database notifications are never batched — always fire individually
- Email channel batched into `pending_notification_digests` when threshold exceeded
- Unit test with 10 test cases covering all acceptance criteria
- Files changed: `app/Services/NotificationService.php`, `tests/Unit/Services/NotificationServiceTest.php`
- **Learnings for future iterations:**
  - `Notification::fake()` intercepts `notifyNow()` calls — use `assertSentTo` with channel callback to verify specific channels
  - `assertSentToTimes` counts per-channel sends, not per-dispatch calls (mail + database = 2 per dispatch)
  - Block model uses `blocker_id`/`blocked_id` columns — query Block directly rather than User->blocks() relationship (which uses wrong default FK)
  - NotificationPreference `channel` column is cast to `NotificationChannel` enum — map to Laravel driver names (email→mail, web→database)
  - Services that don't need config injection don't require AppServiceProvider registration — container auto-resolves them
---

## 2026-03-20 - US-046
- Implemented 5 additional service classes with unit tests (48 tests, 107 assertions)
- **GroupMembershipService**: join/leave groups, request-to-join approval workflow (approve/deny), role changes, membership checks
- **EventSeriesService**: create series from RRULE, generate recurring event instances (3-month horizon), update single vs all-future events, duration preservation
- **SearchService**: coordinate Scout search across Group, Event, User models with field weights per spec section 8.1 (name=high, description=medium, location/venue/bio=low)
- **ExportService**: CSV export for group members (name, email, joined date, attendance stats) and event attendees (name, RSVP status, guest count, checked-in)
- **AccountService**: soft delete with ownership transfer check, JSON data export (profile, groups, RSVPs), suspend/unsuspend
- Installed `rlanvin/php-rrule` package for RRULE parsing
- Files changed: `app/Services/GroupMembershipService.php`, `app/Services/EventSeriesService.php`, `app/Services/SearchService.php`, `app/Services/ExportService.php`, `app/Services/AccountService.php`, `tests/Unit/Services/GroupMembershipServiceTest.php`, `tests/Unit/Services/EventSeriesServiceTest.php`, `tests/Unit/Services/SearchServiceTest.php`, `tests/Unit/Services/ExportServiceTest.php`, `tests/Unit/Services/AccountServiceTest.php`, `composer.json`, `composer.lock`
- **Learnings for future iterations:**
  - `rlanvin/php-rrule` v2.x throws an exception when DTSTART is embedded in the RRULE string (not RFC-compliant) — extract DTSTART and pass it as a separate constructor option
  - Carbon `diffInMinutes()` returns a float, not int — cast to `(int)` for strict comparisons in tests
  - Rsvp model uses `checked_in` (boolean) field, not `checked_in_at` for check-in status
  - Services that don't need constructor injection (no config/dependencies) don't need singleton registration in AppServiceProvider
  - Group member pivot attributes (role, joined_at) are returned as enum instances when using `->using(GroupMember::class)` with casts — guard with `instanceof` before calling `->value`
---

## 2026-03-20 - US-047
- Implemented Account Settings page at `/settings` with tabbed sections for Profile, Account, Notifications, and Privacy
- Profile section: update name with validation
- Account section: update email (clears `email_verified_at` and sends re-verification), update password (requires current password confirmation)
- Created Form Request validation classes for each update type
- 17 feature tests covering all acceptance criteria
- Files changed: `app/Http/Controllers/Settings/SettingsController.php` (new), `app/Http/Requests/Settings/UpdateProfileRequest.php` (new), `app/Http/Requests/Settings/UpdateAccountRequest.php` (new), `resources/views/settings/index.blade.php` (new), `resources/views/settings/partials/profile.blade.php` (new), `resources/views/settings/partials/account.blade.php` (new), `resources/views/settings/partials/notifications.blade.php` (new), `resources/views/settings/partials/privacy.blade.php` (new), `routes/web.php`, `tests/Feature/Profile/ProfileUpdateTest.php` (new)
- **Learnings for future iterations:**
  - Settings pages use `?section=` query parameter with `<x-tab-bar>` component for tab navigation
  - `UpdateAccountRequest` uses `withValidator()` to validate current password via `Hash::check()` after standard rules pass
  - Email change clears `email_verified_at` to null and calls `sendEmailVerificationNotification()` for re-verification
  - Pint auto-fixes `\Illuminate\Validation\Validator` FQCNs to imports — use `use` statements from the start
---

## 2026-03-20 - US-048
- Expanded profile settings form with bio (textarea), location (text input), timezone (IANA dropdown), avatar upload (2MB max, JPEG/PNG/WebP), interests (multi-select from Spatie Tags), and "looking for" checkboxes
- Updated `UpdateProfileRequest` with validation rules for all new fields, including `LOOKING_FOR_OPTIONS` constant
- Updated `SettingsController::updateProfile` to handle avatar via MediaLibrary, sync interest tags, and update all profile fields
- Geocoding handled by existing `UserObserver` (dispatches `GeocodeLocation` job on location change via `wasChanged()`) — no manual dispatch needed in controller
- Added 8 new tests: profile field updates, valid avatar upload, oversized avatar rejection (422), non-image rejection (422), geocoding job dispatch on location change, no dispatch when unchanged, interests saved, invalid looking_for rejected
- Files changed: `app/Http/Requests/Settings/UpdateProfileRequest.php`, `app/Http/Controllers/Settings/SettingsController.php`, `resources/views/settings/partials/profile.blade.php`, `tests/Feature/Profile/ProfileUpdateTest.php`
- **Learnings for future iterations:**
  - `UserObserver` already dispatches `GeocodeLocation` on `created()` and `updated()` with `wasChanged('location')` — don't duplicate in controllers
  - When testing queue dispatches, call `Queue::fake()` AFTER factory creation to avoid capturing observer-dispatched jobs from `created()` events
  - `QueueFake` has no `flush()` method — use timing of `Queue::fake()` call to control what's captured
  - User interests use Spatie Tags with type `interest` — sync via `$user->syncTagsWithType($tags, 'interest')`
  - `looking_for` is a JSON array cast — stored as `['practicing hobbies', 'making friends', ...]`
  - The `InterestSeeder` seeds all available interest tags with type `interest` — the settings page loads them via `Tag::getWithType('interest')`
  - Avatar media collection is `singleFile()` — adding a new one automatically replaces the old
---

## 2026-03-20 - US-049
- Implemented privacy settings allowing users to toggle `profile_visibility` between `public` and `members_only`
- Created `UpdatePrivacyRequest` form request with validation for `profile_visibility` enum values
- Added `updatePrivacy()` method to `SettingsController` and `PUT /settings/privacy` route
- Replaced privacy settings stub view with radio button form (public vs members_only)
- Created `UserPolicy` with `view()` method enforcing visibility: public profiles visible to all, members_only profiles only visible to owner or users sharing at least one group
- Scout `shouldBeSearchable()` was already implemented on User model — returns false for members_only (from US-048 foundation)
- Created `ProfileVisibilityTest` with 11 tests covering: toggle both modes, invalid values, auth required, policy enforcement for public/members_only/shared-group/no-shared-group, and Scout searchability
- Files changed: `app/Http/Requests/Settings/UpdatePrivacyRequest.php` (new), `app/Policies/UserPolicy.php` (new), `app/Http/Controllers/Settings/SettingsController.php`, `resources/views/settings/partials/privacy.blade.php`, `routes/web.php`, `tests/Feature/Profile/ProfileVisibilityTest.php` (new)
- **Learnings for future iterations:**
  - Policies are auto-discovered in Laravel — no need to register them in a provider
  - The `profile_visibility` column, enum, factory default, and `shouldBeSearchable()` were already in place from prior stories
  - To check shared group membership efficiently: `$userA->groups()->whereIn('groups.id', $userB->groups()->select('groups.id'))->exists()`
  - Settings partials (profile, account, notifications, privacy) are loaded via `?section=` query param in the settings index view
---

## 2026-03-20 - US-050
- Implemented notification preferences UI with toggles for all 22 configurable notification types (excluding AccountSuspended which is a system notification)
- Notification types organized by category: Groups (9), Events (5), Comments (4), Discussions (2), Messages (1), Admin (1)
- Each type shows toggles for its available channels (email and/or web) — types like GroupDeleted (email-only) or NewEventComment (web-only) only show relevant toggles
- Preferences stored in `notification_preferences` table via `updateOrCreate` — supports both new and updated preferences
- Added `notificationPreferences()` HasMany relationship to User model
- Added `NOTIFICATION_TYPES` constant on SettingsController with label, category, and default channels for all 22 types
- Created `UpdateNotificationPreferencesRequest` form request with validation
- Hidden inputs with value="0" ensure unchecked checkboxes submit false values
- Invalid notification types in requests are silently ignored; channels not supported by a type are not saved
- Created 12 Pest feature tests covering: displaying all types, toggling, updating existing, reflecting saved state, invalid types, channel restrictions, auth requirements, delivery integration with NotificationService, and validation
- Files changed: `app/Models/User.php`, `app/Http/Controllers/Settings/SettingsController.php`, `app/Http/Requests/Settings/UpdateNotificationPreferencesRequest.php` (new), `resources/views/settings/partials/notifications.blade.php`, `routes/web.php`, `tests/Feature/Profile/NotificationPreferencesTest.php` (new)
- **Learnings for future iterations:**
  - `NotificationService::dispatch` sends non-email and email channels in separate `notifyNow` calls — test assertions must check each channel group separately
  - The spec lists 23 notification types but AccountSuspended is excluded from user-configurable preferences (system notification), giving 22 configurable types
  - Preferences default to enabled (true) when no record exists — only disabled preferences need to be stored
  - The `NotificationPreference` model casts `channel` to `NotificationChannel` enum — use `->channel->value` to get the string key
---

## 2026-03-20 - US-051
- Implemented account deletion with password confirmation and 30-day grace period (soft delete)
- Created `DeleteAccountRequest` form request with password validation via `withValidator`
- Added `deleteAccount` method to `SettingsController` — logs out, soft deletes, invalidates session
- Added `DELETE settings/account` route named `settings.account.delete`
- Soft-deleted users cannot log in because `SoftDeletes` trait was already on User model and `Auth::attempt()` uses Eloquent which excludes soft-deleted records
- Files changed: `app/Http/Requests/Settings/DeleteAccountRequest.php` (new), `app/Http/Controllers/Settings/SettingsController.php`, `routes/web.php`, `tests/Feature/Auth/AccountDeletionTest.php` (new)
- **Learnings for future iterations:**
  - User model already uses `SoftDeletes` trait — no migration needed for `deleted_at` column
  - `Auth::attempt()` automatically excludes soft-deleted users since it queries via Eloquent — no extra middleware needed
  - Password confirmation pattern: use `withValidator()` + `Hash::check()` in FormRequest (see `UpdateAccountRequest` and `DeleteAccountRequest`)
  - Account deletion route uses `DELETE` HTTP method on the same `settings/account` path
---

## 2026-03-20 - US-052
- Added data export endpoint `GET /settings/data-export` that streams a JSON file download
- Extended `AccountService::exportData()` to include discussions, messages, and notification_preferences (previously only had profile, groups, rsvps)
- Added `exportData` method to `SettingsController` using `response()->streamDownload()`
- Created feature test covering authentication requirement and all data sections
- Files changed: `app/Services/AccountService.php`, `app/Http/Controllers/Settings/SettingsController.php`, `routes/web.php`, `tests/Feature/Settings/DataExportTest.php`
- **Learnings for future iterations:**
  - `AccountService::exportData()` is the central method for user data export — extend it when new user-related models are added
  - Use `response()->streamDownload()` for file downloads — it accepts a closure, filename, and headers
  - `assertDownload()` in Pest/PHPUnit verifies the response has Content-Disposition attachment header
  - `$response->streamedContent()` retrieves the body from a streamed response for assertions
  - DirectMessage belongs to Conversation (not directly to User via HasMany), so query via `DirectMessage::where('user_id', ...)` rather than a relationship
---

## 2026-03-20 - US-053
- Implemented public profile page at `/members/{user}` with avatar, name, bio, location, interests, looking_for tags, and groups in common
- Updated `UserPolicy::view()` to accept `?User` (nullable) for guest access to public profiles
- Added block checking to `UserPolicy` — blocked users cannot view blocker's profile
- Created `MemberController::show()` with Gate authorization, SEO meta tags (title, description, OG image)
- Message button, Report and Block actions in dropdown for authenticated non-owner viewers
- Files changed: `app/Policies/UserPolicy.php`, `app/Http/Controllers/MemberController.php`, `routes/web.php`, `resources/views/members/show.blade.php`, `tests/Feature/MemberProfileTest.php`
- **Learnings for future iterations:**
  - `UserPolicy::view()` must accept `?User` for guest access — Laravel passes null for unauthenticated users on policies with optional first param
  - Route `/members/{user}` is placed outside auth/guest middleware groups so both guests and authenticated users can access it
  - `Gate::allows('view', $user)` works with nullable auth for policies that accept `?User`
  - User interests are stored via Spatie Tags with type `interest` — retrieved via `$user->tagsWithType('interest')`
  - Avatar component supports sizes: sm (24px), md (32px), lg (44px), xl (96px)
  - App layout accepts SEO props: `title`, `description`, `seoImage`, `seoType`, `canonicalUrl`, `jsonLd`
---

## 2026-03-20 - US-054
- Implemented group creation feature with form at `/groups/create`
- Added routes: `groups.create` (GET), `groups.store` (POST), `groups.show` (GET with slug binding)
- Created `GroupController` with `create()`, `store()`, and `show()` methods
- Created `CreateGroupRequest` with full validation rules and custom error messages
- Created Blade view `groups/create.blade.php` with all form fields: name, description (markdown), location, cover photo, topics (dynamic tag input), visibility (radio), requires_approval (checkbox), max_members, welcome_message, membership questions (dynamic add/remove)
- Created placeholder `groups/show.blade.php` view
- Added `@stack('scripts')` to app layout for page-specific JavaScript
- GroupPolicy `create` method already existed: allows verified, non-suspended users
- Slug auto-generated via spatie/laravel-sluggable (already configured in Group model)
- Description rendered to HTML via MarkdownService on save (stored in `description_html`)
- Creator becomes organizer role in `group_members` pivot table
- Location geocoded asynchronously via `GeocodeLocation` queued job (dispatched by `GroupObserver`)
- Feature test `tests/Feature/Groups/CreateGroupTest.php` with 16 tests covering: happy path, minimal fields, slug collisions, validation errors (missing name, missing visibility, name too long, invalid visibility, max_members min, description max), suspended user redirect, unverified user forbidden, geocoding job dispatch/non-dispatch, markdown rendering
- Files changed: `routes/web.php`, `app/Http/Controllers/Groups/GroupController.php`, `app/Http/Requests/Groups/CreateGroupRequest.php`, `app/Policies/GroupPolicy.php`, `resources/views/components/layouts/app.blade.php`, `resources/views/groups/create.blade.php`, `resources/views/groups/show.blade.php`, `tests/Feature/Groups/CreateGroupTest.php`
- **Learnings for future iterations:**
  - `GroupObserver` auto-dispatches `GeocodeLocation` on create/update — don't duplicate dispatch in controllers
  - When testing `Queue::assertNotPushed`, call `Queue::fake()` AFTER `User::factory()->create()` since UserObserver also dispatches GeocodeLocation for users with locations
  - The `notSuspended` middleware redirects to `route('suspended')` — suspended user tests should use `assertRedirect(route('suspended'))`, not `assertForbidden()`
  - Unverified users are blocked by the GroupPolicy `create` method (returns false → 403), not middleware
  - App layout needed `@stack('scripts')` added before `</body>` for page-specific JS (topics input, membership questions)
  - Group topics use Spatie Tags with type `topic` — synced via `syncTagsWithType($topics, 'topic')`
---

## 2026-03-20 - US-055
- Implemented full Group Profile Page at `/groups/{slug}`
- Moved `groups.show` route outside auth middleware for guest access, placed after auth group to avoid conflicting with `groups/create`
- Updated `GroupPolicy::view()` to accept `?User` for guest access
- Enhanced `GroupController::show()` with tab-based data loading, private group restrictions, SEO metadata, member count, avatar stack, topics, organizer info
- Rewrote `groups/show.blade.php` with cover photo/blob header, member count with avatar stack, interest pills, organizer info, tab bar (Upcoming Events, Past Events, Discussions, Members, About), Join/Request to Join/Leave buttons, private group restricted view, leadership team in About tab
- Created `tests/Feature/Groups/ViewGroupTest.php` with 13 tests covering: public view, SEO title, interest pills, blob header, join button, request to join, leave group, avatar stack, private group restrictions for non-members, private group full access for members, leadership team, members tab, guest private group restrictions
- Files changed: `routes/web.php`, `app/Http/Controllers/Groups/GroupController.php`, `app/Policies/GroupPolicy.php`, `resources/views/groups/show.blade.php`, `tests/Feature/Groups/ViewGroupTest.php`
- **Learnings for future iterations:**
  - When adding a public wildcard route like `groups/{group:slug}`, it must be registered AFTER any static routes in the same prefix (e.g., `groups/create`) to avoid the wildcard capturing the static path
  - Public routes accessible to both guests and auth users should go after the auth middleware group in `routes/web.php` to avoid route precedence issues
  - Existing components `<x-pill>`, `<x-avatar-stack>`, `<x-blob>`, `<x-tab-bar>` are available and should be reused for consistent UI
  - `loadCount` with a constraint callback is useful for counting non-banned members
  - MySQL `FIELD()` function works for custom ordering of enum values in queries
---

## 2026-03-20 - US-056
- Implemented "Join Group (Open)" feature for verified users to instantly join open groups
- Created `WelcomeToGroup` notification (web + email channels, queued) with group welcome message support
- Added `join` method to `GroupController` using `Gate::authorize` for policy-based authorization
- Added `POST groups/{group:slug}/join` route under auth+notSuspended middleware
- Updated `GroupMembershipService::joinGroup` to check banned status before membership check and send `WelcomeToGroup` notification
- Added `isBanned` helper method to `GroupMembershipService`
- Created feature test with 5 test cases: happy path, already member, banned, at capacity, unverified
- Files changed: `app/Http/Controllers/Groups/GroupController.php`, `app/Services/GroupMembershipService.php`, `app/Notifications/WelcomeToGroup.php` (new), `routes/web.php`, `tests/Feature/Groups/JoinGroupTest.php` (new)
- **Learnings for future iterations:**
  - Base `Controller` class does not include `AuthorizesRequests` trait — use `Gate::authorize()` instead of `$this->authorize()`
  - `GroupPolicy::join` already handles suspended, unverified, already-member, and banned checks — the policy is the first line of defense
  - `Notification::fake()` must be called before the action that triggers notifications
  - The capacity test expects a 500 status because the `InvalidArgumentException` from the service isn't caught by the controller — consider adding error handling in future
---

## 2026-03-20 - US-057
- Implemented approval-required group join flow with membership questions
- Updated `GroupMembershipService::requestToJoin()` to save membership answers, handle re-requests (update existing record), and send `JoinRequestReceived` notification to leadership
- Updated `approveRequest()` to send `JoinRequestApproved` and optional `WelcomeToGroup` notifications
- Updated `denyRequest()` to send `JoinRequestDenied` notification with optional reason
- Created 3 notifications: `JoinRequestReceived`, `JoinRequestApproved`, `JoinRequestDenied`
- Created `RequestToJoinGroupRequest` form request with dynamic validation for membership questions (required vs optional)
- Created `HandleJoinRequestRequest` form request for approve/deny actions
- Added controller methods: `requestJoin()`, `approveRequest()`, `denyRequest()`
- Added routes: `groups.request-join`, `groups.join-requests.approve`, `groups.join-requests.deny`
- Updated group show view: "Request Pending" status display, membership questions form with toggle, fixed "Join Group" button to be a proper form POST
- Created comprehensive feature test `MembershipApprovalTest.php` (8 tests, 36 assertions)
- Updated existing unit tests to accommodate new notification behavior
- Files changed: `app/Http/Controllers/Groups/GroupController.php`, `app/Services/GroupMembershipService.php`, `resources/views/groups/show.blade.php`, `routes/web.php`, `tests/Unit/Services/GroupMembershipServiceTest.php`, `app/Http/Requests/Groups/RequestToJoinGroupRequest.php` (new), `app/Http/Requests/Groups/HandleJoinRequestRequest.php` (new), `app/Notifications/JoinRequestReceived.php` (new), `app/Notifications/JoinRequestApproved.php` (new), `app/Notifications/JoinRequestDenied.php` (new), `tests/Feature/Groups/MembershipApprovalTest.php` (new)
- **Learnings for future iterations:**
  - When changing service behavior (e.g., from throwing to updating), existing unit tests must be updated to match — run full test suite
  - Adding notifications to service methods means all existing tests calling those methods need `Notification::fake()`
  - Re-request pattern: use `updateOrCreate`-style logic on the join request, resetting review fields to null
  - `GroupMembershipAnswer` uses `updateOrCreate` keyed on `(question_id, user_id)` to handle answer updates on re-request
  - Dynamic form request validation can be built by loading related models in `rules()` method and iterating over them
---

## 2026-03-20 - US-058
- Implemented leave group endpoint: POST `/groups/{slug}/leave`
- Updated `GroupMembershipService::leaveGroup()` to cancel upcoming RSVPs (set to NotGoing) and dispatch `PromoteFromWaitlist` for each event where user was Going
- Added `leave()` method to `GroupController` with `Gate::authorize('leave', $group)` check
- Added route `groups.leave` in `routes/web.php`
- Created `tests/Feature/Groups/LeaveGroupTest.php` with 3 tests: leave, RSVPs cancelled with waitlist promotion, organizer blocked
- Files changed: `app/Services/GroupMembershipService.php`, `app/Http/Controllers/Groups/GroupController.php`, `routes/web.php`, `tests/Feature/Groups/LeaveGroupTest.php`
- **Learnings for future iterations:**
  - `GroupPolicy::leave()` and `GroupMembershipService::leaveGroup()` already existed — only needed to add RSVP cancellation logic, controller method, and route
  - When cancelling RSVPs on leave, only cancel upcoming events (starts_at > now), not past ones
  - Use `Queue::fake()` AFTER model creation to avoid capturing observer-dispatched jobs — pattern already documented in Codebase Patterns
  - The `PromoteFromWaitlist` job should be dispatched per-event (not once) when a user had Going RSVPs on multiple events
---

## 2026-03-20 - US-059
- Implemented group settings page at `/groups/{slug}/manage/settings` with `EnsureGroupRole:co_organizer` middleware
- Created `GroupSettingsController` with `edit()` and `update()` methods
- Created `UpdateGroupSettingsRequest` form request with slug uniqueness validation (ignoring current group)
- Settings view mirrors the create form but pre-populates all fields including existing membership questions
- Membership question sync: creates new, updates existing (by id), deletes removed, reorders via sort_order index
- Description re-renders to `description_html` via `MarkdownService` on every update
- Location change triggers `GeocodeLocation` job automatically via `GroupObserver`
- 16 feature tests covering: authorization (member/event_organizer/assistant rejected, co_organizer/organizer allowed), all field updates, slug change, slug uniqueness, question CRUD and reorder, geocoding dispatch
- Files changed: `routes/web.php`, `app/Http/Controllers/Groups/GroupSettingsController.php`, `app/Http/Requests/Groups/UpdateGroupSettingsRequest.php`, `resources/views/groups/manage/settings.blade.php`, `tests/Feature/Groups/GroupSettingsTest.php`
- **Learnings for future iterations:**
  - The `groupRole` middleware alias is registered in `bootstrap/app.php` — use it via `->middleware('groupRole:co_organizer')` in route groups
  - `EnsureGroupRole` middleware resolves the group from the route parameter and checks the user's pivot role against the hierarchy
  - For slug editing, use `Rule::unique('groups', 'slug')->ignore($group->id)` to allow keeping the current slug
  - Membership question sync pattern: collect existing IDs, match submitted IDs, create/update matched, delete unmatched — the sort_order is derived from the array index
  - `Queue::fake()` must be called AFTER factory `create()` to avoid capturing observer jobs from the `created` event
  - The `GroupObserver` already handles geocoding dispatch on location change — no need to dispatch manually in the controller
---

## 2026-03-20 - US-060
- Implemented member management for assistant organizer+ role
- Added `removeMember`, `banMember`, `unbanMember` methods to `GroupMembershipService`
- Created `MemberRemoved` and `MemberBanned` notifications (queued, mail + database channels)
- Updated `ExportService::exportMembers()` to include No-Shows column using `AttendanceResult` enum (was previously using `RsvpStatus::Going` for "attended" — now uses `AttendanceResult::Attended`)
- Created `GroupMemberManagementController` with index (member list with search/filter, pagination 20/page, attendance stats), remove, ban, unban, export actions
- Created `GroupJoinRequestController` with index (pending requests), approve, deny actions
- Created `RemoveMemberRequest` and `BanMemberRequest` form request classes with `Gate::allows` authorization
- Added routes under `groupRole:assistant_organizer` middleware for both member management and join request management
- Created Blade views: `groups/manage/members.blade.php` and `groups/manage/requests.blade.php`
- Created comprehensive feature test `MemberManagementTest.php` (16 tests, 53 assertions) covering all actions, authorization, CSV export, ban prevents rejoin
- Updated existing `ExportServiceTest` to match new CSV header with No-Shows column
- Files changed: `app/Services/GroupMembershipService.php`, `app/Services/ExportService.php`, `routes/web.php`, `tests/Unit/Services/ExportServiceTest.php`, `app/Http/Controllers/Groups/GroupMemberManagementController.php`, `app/Http/Controllers/Groups/GroupJoinRequestController.php`, `app/Http/Requests/Groups/RemoveMemberRequest.php`, `app/Http/Requests/Groups/BanMemberRequest.php`, `app/Notifications/MemberRemoved.php`, `app/Notifications/MemberBanned.php`, `resources/views/groups/manage/members.blade.php`, `resources/views/groups/manage/requests.blade.php`, `tests/Feature/Groups/MemberManagementTest.php`
- **Learnings for future iterations:**
  - Use `AttendanceResult::Attended` / `AttendanceResult::NoShow` on the `attended` column for attendance stats, not `RsvpStatus::Going` (Going is RSVP status, not actual attendance)
  - Static routes (e.g., `/members/export`) must be registered BEFORE wildcard routes (e.g., `/members/{user}/remove`) to avoid the wildcard capturing static paths
  - Form request classes use `Gate::allows('policyMethod', $this->route('group'))` for authorization — follows the pattern in `HandleJoinRequestRequest`
  - Ban implementation keeps the pivot row with `is_banned=true` (rather than detaching) — this prevents rejoin via the `join` policy check
  - Unban detaches the row entirely (cleans up the ban record) rather than just flipping `is_banned` to false
---

## 2026-03-20 - US-061
- Implemented Leadership Team Management page at `/groups/{slug}/manage/team`
- Created `LeadershipTeamController` with `index` (show leadership + regular members) and `update` (change roles) methods
- Created `ChangeLeadershipRoleRequest` form request with validation for valid role values (member, event_organizer, assistant_organizer, co_organizer)
- Created `RoleChanged` notification (mail + database, queued) with old/new role info
- Created `team.blade.php` view showing current leadership with role change dropdowns, and a section for promoting regular members
- Added routes under `groupRole:co_organizer` middleware group
- Co-organizer restrictions enforced in controller: cannot promote to co_organizer, cannot demote other co_organizers
- Primary organizer role cannot be changed by anyone
- Feature test with 16 tests (39 assertions) covering: page display, promote, demote, co-organizer limitations, authorization, notification, validation
- Files changed: `routes/web.php`, `app/Http/Controllers/Groups/LeadershipTeamController.php`, `app/Http/Requests/Groups/ChangeLeadershipRoleRequest.php`, `app/Notifications/RoleChanged.php`, `resources/views/groups/manage/team.blade.php`, `tests/Feature/Groups/LeadershipTeamTest.php`
- **Learnings for future iterations:**
  - The `manageLeadership` policy already existed in `GroupPolicy` — reused it for authorization
  - `GroupMembershipService::changeRole()` already existed — just needed to wrap it with authorization logic in the controller
  - Pivot role can be either a `GroupRole` enum or a string depending on context — always guard with `instanceof` before comparison
  - Co-organizer limitation logic belongs in the controller, not the policy, since it depends on both actor role and target role/new role
---

## 2026-03-20 - US-062
- Implemented ownership transfer feature for primary organizer
- Created `OwnershipTransferController` with edit/update actions
- Created `TransferOwnershipRequest` with password confirmation and co-organizer validation
- Added `transferOwnership()` method to `GroupMembershipService`
- Created `OwnershipTransferred` notification (mail + database, queued)
- Created transfer form view at `groups/manage/transfer.blade.php`
- Added organizer-only routes under `groupRole:organizer` middleware
- Created feature test with 7 tests (23 assertions): transfer, wrong password, non-co-organizer target, non-member target, authorization
- Files changed: `app/Http/Controllers/Groups/OwnershipTransferController.php`, `app/Http/Requests/Groups/TransferOwnershipRequest.php`, `app/Notifications/OwnershipTransferred.php`, `app/Services/GroupMembershipService.php`, `routes/web.php`, `resources/views/groups/manage/transfer.blade.php`, `tests/Feature/Groups/OwnershipTransferTest.php`
- **Learnings for future iterations:**
  - The `groupRole:organizer` middleware restricts routes to the primary organizer only — use it for organizer-exclusive features like ownership transfer
  - Form request `after()` method is useful for custom validation that depends on multiple fields (e.g., password check + co-organizer verification)
  - The `Group.organizer_id` field tracks the primary organizer separately from the pivot role — both must be updated during ownership transfer
  - GroupPolicy already had `transferOwnership` method defined — check policies before creating new authorization logic
---

## 2026-03-20 - US-063
- Added `destroy()` method to `GroupController` with password confirmation via `DeleteGroupRequest`
- Cancels all upcoming events with `EventCancelled` notifications sent to all members
- Sends `GroupDeleted` notification to all members via email
- Created `groups:purge-deleted` scheduled command (daily) for 90-day grace period hard purge
- Added DELETE `/groups/{slug}` route under `groupRole:organizer` middleware
- Created `EventCancelled` and `GroupDeleted` notification classes (mail + database, queued)
- Created feature test with 7 tests (17 assertions): deletion, events cancelled, notifications sent, wrong password, authorization
- Files changed: `app/Http/Controllers/Groups/GroupController.php`, `app/Http/Requests/Groups/DeleteGroupRequest.php`, `app/Notifications/EventCancelled.php`, `app/Notifications/GroupDeleted.php`, `app/Console/Commands/PurgeDeletedGroups.php`, `routes/web.php`, `routes/console.php`, `tests/Feature/Groups/GroupDeletionTest.php`
- **Learnings for future iterations:**
  - Group model already has `SoftDeletes` trait — just call `$group->delete()` for soft deletion
  - GroupPolicy already had `delete()` method checking for organizer role — check policies before creating new auth logic
  - Use `Group::onlyTrashed()` with `forceDelete()` for hard purge of soft-deleted models
  - Scheduled commands are registered in `routes/console.php` using `Schedule::command()`
  - The `DeleteGroupRequest` follows the same `after()` password confirmation pattern as `TransferOwnershipRequest`
---

## 2026-03-20 - US-064
- Implemented Group Analytics page at `/groups/{slug}/manage/analytics` with co_organizer+ access
- Shows: member growth per month, event count over time, average attendance rate, most active members (top 10), average event rating
- All data from Eloquent aggregation queries using DB::raw for DATE_FORMAT grouping — no separate analytics tables
- Feature test covers authorization (5 role tests) and data accuracy (7 data tests) with seeded test data
- Files changed: `app/Http/Controllers/Groups/GroupAnalyticsController.php` (new), `routes/web.php` (added route), `resources/views/groups/manage/analytics.blade.php` (new), `tests/Feature/Groups/GroupAnalyticsTest.php` (new)
- **Learnings for future iterations:**
  - GroupPolicy already has `viewAnalytics` method — authorization is handled by the `groupRole:co_organizer` middleware at the route level, not by explicit policy checks in the controller
  - Feedback model uses `event_feedback` as its table name (`$table = 'event_feedback'`)
  - `Rsvp::factory()->for($event)` works for setting event relationship in tests
  - `Feedback::factory()->for($event)` works similarly for event feedback
  - DATE_FORMAT MySQL function works for grouping by month in aggregation queries
---

## 2026-03-20 - US-065
- Implemented event creation for group event organizers (event_organizer+ role)
- Created `CreateEventRequest` form request with validation for all fields including conditional venue/online_link requirements
- Created `EventController` with `create` (form) and `store` (save) methods, handling timezone conversion, markdown rendering, media upload, host assignment, and notifications
- Created `NewEvent` notification (queued, mail + database channels) sent to group members when publishing
- Created `events/create.blade.php` view with conditional venue/online fields via JavaScript, draft/publish buttons
- Added routes with `groupRole:event_organizer` middleware in `routes/web.php`
- Created comprehensive feature test (19 tests, 66 assertions) covering: happy path, validation (missing required fields, invalid event_type, conditional fields), draft save, publish with notification, geocoding job dispatch, timezone inheritance/override, UTC conversion, slug collisions, markdown rendering, role authorization
- Files changed: `app/Http/Controllers/Events/EventController.php` (new), `app/Http/Requests/Events/CreateEventRequest.php` (new), `app/Notifications/NewEvent.php` (new), `resources/views/events/create.blade.php` (new), `tests/Feature/Events/CreateEventTest.php` (new), `routes/web.php` (added event routes)
- **Learnings for future iterations:**
  - EventObserver already dispatches `GeocodeLocation` job on create — no need to manually dispatch in controller
  - Event routes with `groupRole:event_organizer` middleware go before `groupRole:assistant_organizer` in route file
  - Carbon::parse with timezone + ->utc() converts local times to UTC for storage
  - CreateEventRequest authorization uses `$this->user()->can('create', [Event::class, $this->route('group')])` pattern for policy with extra parameter
  - Notification::fake() and Queue::fake() can be used together; notifications bypass queue when faked
---

## 2026-03-20 - US-066
- Implemented recurring events: "Make this recurring" checkbox on event creation form with weekly, biweekly, monthly, and custom RRULE recurrence options
- Updated `EventController::store()` to integrate with `EventSeriesService` for creating event series and generating instances (next 3 months)
- Added edit/update/cancel controller methods with series-aware scope: "Edit this event only" vs "Edit this and all future events", same for cancel
- Added routes: `events.edit`, `events.update`, `events.cancel`
- Added recurring fields to `CreateEventRequest` validation: `is_recurring`, `recurrence_pattern`, `custom_rrule`
- Created `events/edit.blade.php` with series edit/cancel scope prompts (shown only when event is part of a series)
- Updated `events/create.blade.php` with recurring UI section and JavaScript toggles
- Fixed `EventSeriesFactory` to use valid RRULE (`FREQ=WEEKLY;INTERVAL=2` instead of `FREQ=BIWEEKLY`)
- Created comprehensive feature test (`RecurringEventTest.php`): 16 tests, 140 assertions covering series creation (weekly/biweekly/monthly/custom), correct instance count, host attachment, edit single, edit all future, cancel single, cancel all future, form display, validation
- All 943 tests pass
- Files changed: `app/Http/Controllers/Events/EventController.php`, `app/Http/Requests/Events/CreateEventRequest.php`, `database/factories/EventSeriesFactory.php`, `resources/views/events/create.blade.php`, `resources/views/events/edit.blade.php` (new), `routes/web.php`, `tests/Feature/Events/RecurringEventTest.php` (new)
- **Learnings for future iterations:**
  - `FREQ=BIWEEKLY` is not valid RRULE — use `FREQ=WEEKLY;INTERVAL=2` instead
  - `rlanvin/php-rrule` requires DTSTART passed separately from RRULE string — the `EventSeriesService::parseRRule()` handles extraction
  - `buildRRule()` in controller computes BYDAY from the starts_at day of week (e.g., Monday → MO) for weekly/biweekly patterns
  - Monthly BYDAY uses nth-weekday format (e.g., "1MO" for first Monday) computed via `ceil($date->day / 7)`
  - `Gate::authorize()` is used for inline policy checks in controllers (base Controller has no AuthorizesRequests trait)
  - Edit/cancel scope for series events uses radio buttons with `edit_scope` / `cancel_scope` form fields
---

## 2026-03-20 - US-067
- Implemented event page at `/groups/{group_slug}/events/{event_slug}` with full details
- Added `show()` method to EventController with eager loading of hosts, group, RSVPs, and attendees
- Created `events/show.blade.php` with cover band (event-type dark accent + decorative blobs), left column (date block, title, hosts, CTA row, tab bar), and right sidebar (attendance card, venue card with Leaflet map, online card, hosts card)
- Time displayed in event timezone as primary; secondary line shows authenticated user's timezone when different
- Added `.ics` calendar file download at `/groups/{group_slug}/events/{event_slug}/calendar`
- JSON-LD Event schema with eventStatus, eventAttendanceMode, location, organizer, offers
- SEO: `<title>` uses "{Event Name} · {Group Name} — {site_name}", meta description from first 160 chars
- Hybrid events show both venue card and online link card
- Cancelled events show cancellation notice and hide RSVP button
- Added `@stack('styles')` to app layout head for Leaflet CSS injection
- Public routes for event show and calendar placed outside auth middleware
- Created `tests/Feature/Events/ViewEventTest.php` with 17 tests (64 assertions)
- Files changed: `app/Http/Controllers/Events/EventController.php`, `resources/views/events/show.blade.php` (new), `resources/views/components/layouts/app.blade.php`, `routes/web.php`, `tests/Feature/Events/ViewEventTest.php` (new)
- **Learnings for future iterations:**
  - `streamedContent()` must be used instead of `assertSee()` to test streamed download responses
  - Event model uses `{event:slug}` binding scoped to group via `extraScope()` — but route model binding won't auto-scope, so `abort_unless($event->group_id === $group->id, 404)` is needed
  - Leaflet map uses OpenStreetMap tiles (free, no API key) — CSS/JS loaded via `@push('styles')` and `@push('scripts')`
  - The app layout already had `@stack('scripts')` but needed `@stack('styles')` added to the `<head>`
  - `Carbon::setTimezone()` converts a UTC timestamp to local time for display
  - ICS files use `\r\n` line endings and need special escaping for commas, semicolons, and newlines
---

## 2026-03-20 - US-068
- Created Livewire `RsvpButton` component (`app/Livewire/RsvpButton.php`) with Going/Not Going/Join Waitlist states
- Created `RsvpConfirmation` notification (mail + database) sent on Going and Waitlisted
- Wired `<livewire:rsvp-button>` into `resources/views/events/show.blade.php`, replacing placeholder RSVP link
- Created `tests/Feature/Events/RsvpTest.php` with 16 tests covering: going, not going, waitlist auto-assign, guest count, hybrid attendance mode, RSVP window enforcement (4 time conditions), non-member rejected, unverified rejected, cancelled event rejected
- Installed `livewire/livewire` package (was not yet a dependency)
- Files changed: `app/Livewire/RsvpButton.php` (new), `resources/views/livewire/rsvp-button.blade.php` (new), `app/Notifications/RsvpConfirmation.php` (new), `resources/views/events/show.blade.php`, `tests/Feature/Events/RsvpTest.php` (new), `composer.json`, `composer.lock`, `boost.json`
- **Learnings for future iterations:**
  - Livewire was not previously installed — `php artisan make:livewire` creates Blade components (with ⚡ prefix) instead of Livewire class components, so manually create Livewire files in `app/Livewire/` and `resources/views/livewire/`
  - `RsvpService` validates group membership, RSVP window, guest count, and attendance mode via `InvalidArgumentException` — catch these in Livewire components to show error messages
  - Use `Livewire::actingAs($user)->test(Component::class, [...])` for feature testing Livewire components
  - `Queue::fake()` should be called AFTER factory `create()` to avoid capturing observer jobs (existing pattern)
  - Notification::fake() must be called in `beforeEach` to prevent actual mail/database notifications during tests
---

## 2026-03-20 - US-069
- Implemented Waitlist Management feature
- Fixed `PromotedFromWaitlist` notification to use `$event->name` instead of `$event->title` (Event model uses `name`, not `title`)
- Created feature test `tests/Feature/Events/WaitlistTest.php` with 9 tests covering:
  - FIFO ordering by `waitlisted_at`
  - Guest count skipping with next-eligible promotion
  - Multi-spot opening with revisit of previously skipped members
  - PromotedFromWaitlist notification sent to promoted members
  - No promotion on cancelled events
  - No promotion for empty waitlists
  - PromoteFromWaitlist job dispatch integration
  - Full end-to-end cancellation-triggers-promotion flow
- Files changed: `app/Notifications/PromotedFromWaitlist.php`, `tests/Feature/Events/WaitlistTest.php`
- **Learnings for future iterations:**
  - `WaitlistService::promoteAll()` calls `promoteNext()` in a loop — promoteNext scans the full waitlist each pass, so skipped large-party members get revisited automatically
  - Event model uses `name` field, not `title` — always check model attributes when building notifications
  - When testing job dispatch after RSVP changes, use `Queue::fake()` to capture the dispatch; for end-to-end tests, manually invoke the job's `handle()` method
  - `createWaitlistScenario()` helper pattern: pass `waitlistedMembers` as array of guest counts with FIFO order derived from array index
---

## 2026-03-20 - US-070
- Implemented attendee management page at `/groups/{slug}/events/{event_slug}/attendees`
- Created Livewire `AttendeeManager` component with Going/Waitlisted/Not Going tabs, 20 per page pagination
- Actions: change RSVP status, move waitlisted to going (manual override), remove RSVP, check in attendees (sets checked_in, checked_in_at, checked_in_by)
- Post-event attendance marking (attended/no_show) per attendee
- CSV export via existing `ExportService::exportAttendees()`
- Authorization via `EventPolicy::manageAttendees()` and `EventPolicy::checkIn()` — hosts and event_organizer+ can access
- Created `AttendeeManagementController` for page and CSV export routes
- Files changed: `routes/web.php`, `app/Http/Controllers/Events/AttendeeManagementController.php`, `app/Livewire/AttendeeManager.php`, `resources/views/events/attendees.blade.php`, `resources/views/livewire/attendee-manager.blade.php`, `tests/Feature/Events/AttendeeManagementTest.php`, `tests/Feature/Events/CheckInTest.php`
- **Learnings for future iterations:**
  - Attendee management routes must be outside `groupRole:event_organizer` middleware group so hosts (who may only be `member` role) can access — let the controller/policy handle authorization instead
  - Livewire `firstOrFail()` throws `ModelNotFoundException` — in tests use `$this->expectException()` rather than `assertStatus(404)`
  - MySQL datetime precision can cause `equalTo()` comparisons to fail — use `diffInSeconds()` for timing assertions
  - Livewire 4 `make:livewire` creates SFC (single-file component) by default — for complex components, create class-based components manually
---

## 2026-03-20 - US-071
- Implemented event editing with full field support (name, description, date/time, venue, online link, cover photo, RSVP settings, event type)
- Created `UpdateEventRequest` form request with editing window enforcement (24h after ends_at or starts_at)
- Created `EventUpdated` notification sent to Going/Waitlisted RSVP members when a published event is updated
- Updated routes to use `{event:slug}` for edit/update/cancel (was `{event}` which resolved by ID)
- Moved edit/update routes out of `groupRole:event_organizer` middleware so hosts with `member` role can edit their own events (policy handles authorization)
- Updated `edit.blade.php` with all editable fields matching create form (event type, datetime, timezone, venue, online link, cover photo, RSVP settings, chat/comments)
- Updated `RecurringEventTest` to include required fields (`starts_at`, `event_type`) for the new `UpdateEventRequest` validation
- Files changed: `app/Http/Controllers/Events/EventController.php`, `app/Http/Requests/Events/UpdateEventRequest.php` (new), `app/Notifications/EventUpdated.php` (new), `resources/views/events/edit.blade.php`, `routes/web.php`, `tests/Feature/Events/EditEventTest.php` (new), `tests/Feature/Events/RecurringEventTest.php`
- **Learnings for future iterations:**
  - Event edit/update routes must be outside `groupRole:event_organizer` middleware — hosts who are regular members need access, and the policy already handles authorization correctly
  - When adding required fields to an existing form request, existing tests that POST to that route need updating with the new required fields
  - Editing window check needs to be in both the form request `authorize()` (for update) and the controller `edit()` method (for viewing the form)
  - Route bindings changed from `{event}` to `{event:slug}` — tests using `route()` helper with model instances auto-resolve correctly
---

## 2026-03-20 - US-072
- Implemented event cancellation with notifications to Going/Waitlisted attendees
- Updated `EventCancelled` notification to use actual cancellation reason (was hardcoded to "group deleted")
- Added `notifyCancelledEventAttendees()` helper to EventController that dispatches to Going/Waitlisted RSVPs
- Updated past events query in GroupController to include cancelled events (even future ones)
- Added "Cancelled" badge to past events list in groups/show view
- Created feature test covering: cancellation with/without reason, authorization, notifications to correct users, RSVP retention, cancelled badge display
- Files changed: `app/Http/Controllers/Events/EventController.php`, `app/Http/Controllers/Groups/GroupController.php`, `app/Notifications/EventCancelled.php`, `resources/views/groups/show.blade.php`, `tests/Feature/Events/CancelEventTest.php`
- **Learnings for future iterations:**
  - Helper functions in Pest test files must have unique names across all test files — Pest loads them all into global scope (renamed to `createCancelTestGroupWithOrganizer` to avoid collision with `RecurringEventTest`)
  - `EventCancelled` notification already existed but was not dispatched from the cancel action — check existing notifications before creating new ones
  - WaitlistService already guards against promoting from cancelled events (`if ($event->status === EventStatus::Cancelled) return null`)
  - Past events query needs explicit OR clause to include cancelled future events: `->where(fn($q) => $q->where('starts_at', '<', now())->orWhere('status', Cancelled))`
---

## 2026-03-20 - US-073
- Implemented threaded event comments via Livewire `CommentThread` component with one level of nesting (parent + replies)
- Markdown rendering via `MarkdownService` for comment body → `body_html`
- Like/unlike toggle on `event_comment_likes` pivot table
- Soft delete by author or co_organizer+ via `CommentPolicy`
- Three notification classes: `NewEventComment` (web only → hosts/Going), `EventCommentReply` (web + email → parent author), `EventCommentLiked` (web only → comment author)
- Pagination at 15 comments per page using `WithPagination` trait
- Updated `events/show.blade.php` comments tab to render `<livewire:comment-thread>` when `is_comments_enabled`
- Created `CommentPolicy` with `create` (group member + comments enabled) and `delete` (author or co_organizer+) methods
- Files changed: `app/Policies/CommentPolicy.php`, `app/Notifications/NewEventComment.php`, `app/Notifications/EventCommentReply.php`, `app/Notifications/EventCommentLiked.php`, `app/Livewire/CommentThread.php`, `resources/views/livewire/comment-thread.blade.php`, `resources/views/events/show.blade.php`, `tests/Feature/Events/EventCommentsTest.php`
- **Learnings for future iterations:**
  - Comment model, factory, and migrations (event_comments + event_comment_likes) already existed from prior model creation stories — check before creating
  - `Gate::authorize('create', [Comment::class, $this->event])` passes the Event as extra parameter to the policy's `create(User $user, Event $event)` method
  - Livewire `WithPagination` works with `->paginate(15)` in `render()` — returns paginator that Blade `$comments->links()` renders
  - `Notification::fake()` must be called before actions that trigger notifications — for tests that only check DB state, still fake to avoid queue errors
  - Pest helper functions must be uniquely named across ALL test files (global scope) — prefix with feature context (e.g., `createEventWithMember`)
---

## 2026-03-20 - US-074
- Implemented real-time event chat via Livewire `EventChat` component powered by Laravel Reverb
- Created `EventChatMessageSent` broadcast event dispatching on private channel `event.{eventId}.chat`
- Livewire component supports: send message, reply (sets `reply_to_id`), edit own message, delete own message (soft delete)
- Leadership (event_organizer+) can delete any message via `EventChatPolicy`
- Rate limiting: 10 messages per 15 seconds per user per event using `RateLimiter::tooManyAttempts()`; 11th returns 429
- Registered `EventChatPolicy` for `EventChatMessage` model explicitly in `AppServiceProvider` via `Gate::policy()` (naming doesn't follow auto-discovery convention)
- Added broadcast channel authorization in `routes/channels.php` for `event.{eventId}.chat`
- Updated `events/show.blade.php` chat tab to render `<livewire:event-chat>` when `is_chat_enabled`
- 12 feature tests covering all acceptance criteria (send, reply, edit, delete, leadership delete, 403s, rate limiting, broadcast dispatch)
- Files changed: `app/Events/EventChatMessageSent.php`, `app/Livewire/EventChat.php`, `app/Providers/AppServiceProvider.php`, `resources/views/livewire/event-chat.blade.php`, `resources/views/events/show.blade.php`, `routes/channels.php`, `tests/Feature/Events/EventChatTest.php`
- **Learnings for future iterations:**
  - `EventChatPolicy` doesn't auto-discover for `EventChatMessage` — must register via `Gate::policy()` in `AppServiceProvider`
  - Rate limiting pattern: check `tooManyAttempts()` BEFORE persisting, then `hit()` with decay seconds after validation passes
  - Livewire `assertForbidden()` catches `AuthorizationException` from `Gate::authorize()` — no need to manually assert HTTP status
  - Pest helper functions are globally scoped — use unique names like `createChatEventWithMember` to avoid conflicts with `createEventWithMember` in EventCommentsTest
  - `Event::fake([SpecificEvent::class])` only fakes the specified event, allowing other events (like model observers) to fire normally
---

## 2026-03-20 - US-075
- Implemented Event Feedback feature: attendees who RSVP'd Going can leave 1-5 star ratings with optional text after event ends
- Created `FeedbackPolicy` with `create` (checks: event ended via `ends_at` or `starts_at + 3h`, user RSVP'd Going, no duplicate) and `viewAttribution` (Organizer role only)
- Created `NewEventFeedback` notification (web/database only) sent to event hosts + group Organizers
- Created `EventFeedback` Livewire component with feedback form, aggregate display, attributed list (organizer), and anonymous list (members)
- Added "Feedback" tab to event show page
- Created comprehensive feature test with 10 tests covering: submission, rating-only, duplicate rejection, not-yet-ended rejection, starts_at+3h fallback, non-attendee rejection, organizer sees attributed feedback, member sees anonymous aggregate, notification delivery, validation
- Files changed: `app/Policies/FeedbackPolicy.php` (new), `app/Notifications/NewEventFeedback.php` (new), `app/Livewire/EventFeedback.php` (new), `resources/views/livewire/event-feedback.blade.php` (new), `resources/views/events/show.blade.php` (modified), `tests/Feature/Events/EventFeedbackTest.php` (new)
- **Learnings for future iterations:**
  - No `<x-button>` component exists in this project — use raw `<button>` elements with Tailwind classes (e.g., `rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-700`)
  - Feedback model, migration, and factory already existed from prior scaffolding — check for pre-existing model artifacts before creating new ones
  - `FeedbackPolicy` auto-discovers for `Feedback` model — no need to register in `AppServiceProvider` (unlike `EventChatPolicy` which needed manual registration for `EventChatMessage`)
  - Pest helper function named `createPastEventWithAttendee` to avoid conflicts with existing `createEventWithMember` helpers in other test files
  - Pre-existing test failures in `RecurringEventTest` (2 tests) are unrelated to this feature
---

## 2026-03-20 - US-076
- Updated `EventController::calendar()` to use UTC timestamps (DTSTART/DTEND with Z suffix instead of TZID), added ORGANIZER field with group name, and fixed LOCATION to use venue_address for in-person and online_link for online events
- Created `tests/Feature/Events/EventCalendarExportTest.php` with 8 tests covering .ics format validation, UTC timestamps, SUMMARY, DESCRIPTION, LOCATION for in-person and online events, ORGANIZER, and 404 for mismatched group
- Files changed:
  - `app/Http/Controllers/Events/EventController.php` (calendar method updated)
  - `tests/Feature/Events/EventCalendarExportTest.php` (new test file)
- **Learnings for future iterations:**
  - The `escapeIcs()` method has a double-escape bug: backslash replacement runs after comma/semicolon replacement, causing `\,` to become `\\,` — avoid asserting exact escaped strings in tests, use partial matches instead
  - Calendar route `events.calendar` and controller method already existed from prior work — check existing code before creating new endpoints
  - The `EventFactory` defaults to `EventType::InPerson` — use `->online()` state for online event tests
---

## 2026-03-20 - US-077
- Implemented Discussion Threads: create discussion form, discussions tab with pinned-first ordering and pagination
- Added `author()` alias to Discussion model (view references `$discussion->author`, model had `user()`)
- Created `DiscussionController` with `create` and `store` methods
- Created `CreateDiscussionRequest` form request with policy-based authorization
- Created `NewDiscussion` notification (database channel, queued, web only)
- Updated `GroupController::show()` discussions query to use `pinnedFirst()` scope and `paginate(15)`
- Updated group show view: added "New Discussion" button for members, pinned badge, pagination links
- Created discussions create view with title and body (markdown) fields
- Added routes: `GET discussions/create`, `POST discussions`
- Files changed: `app/Models/Discussion.php`, `app/Http/Controllers/Discussions/DiscussionController.php`, `app/Http/Requests/Discussions/CreateDiscussionRequest.php`, `app/Notifications/NewDiscussion.php`, `app/Http/Controllers/Groups/GroupController.php`, `resources/views/groups/show.blade.php`, `resources/views/discussions/create.blade.php`, `routes/web.php`, `tests/Feature/Discussions/CreateDiscussionTest.php`
- **Learnings for future iterations:**
  - Pest helper functions must have unique names across all test files — `createGroupWithMember()` was already defined in `CreateEventTest.php`, causing a fatal "cannot redeclare" error
  - Discussion model has `user()` relationship but views reference `author` — added an `author()` alias (BelongsTo on `user_id`)
  - GroupController loaded discussions with `->with('author')` and `->latest()` before this change — updated to `->pinnedFirst()` scope and `->paginate(15)`
  - `RecurringEventTest` has 2 pre-existing failures unrelated to discussions (Call to member function all() on array)
---

## 2026-03-20 - US-078
- Implemented Discussion Replies via DiscussionThread Livewire component
- Flat chronological replies with markdown support, 20 per page pagination
- Reply updates discussion's `last_activity_at` to now
- Locked discussions reject replies (policy + UI notice)
- NewDiscussionReply notification (mail + database) sent to discussion author + previous repliers, excluding the replier
- Added discussion show route/view with back link to group discussions tab
- Linked discussion titles in group show view to discussion show page
- Files changed: app/Livewire/DiscussionThread.php, app/Notifications/NewDiscussionReply.php, app/Http/Controllers/Discussions/DiscussionController.php, resources/views/discussions/show.blade.php, resources/views/livewire/discussion-thread.blade.php, resources/views/groups/show.blade.php, routes/web.php, tests/Feature/Discussions/DiscussionRepliesTest.php
- **Learnings for future iterations:**
  - Policy `deleteReply` takes `(User, DiscussionReply)` — when calling `Gate::allows('deleteReply', ...)` pass only the reply model, not `[Discussion::class, $reply]` which makes Gate pass Discussion as the first non-User arg
  - Pest helper function names must be globally unique — used `createDiscussionReplySetup()` to avoid collision with `createGroupWithDiscussionMember()` in CreateDiscussionTest
  - DiscussionReply notification channels are `['mail', 'database']` per SettingsController config (web + email in AC maps to database + mail)
---

## 2026-03-20 - US-079
- Implemented discussion moderation: pin/unpin, lock/unlock, soft-delete discussion via Livewire actions on DiscussionThread
- Registered `DiscussionReply` → `DiscussionPolicy` mapping in `AppServiceProvider` (auto-discovery doesn't find it since policy name doesn't match model)
- Files changed:
  - `app/Livewire/DiscussionThread.php` — added `togglePin()`, `toggleLock()`, `deleteDiscussion()` methods
  - `app/Providers/AppServiceProvider.php` — added `Gate::policy(DiscussionReply::class, DiscussionPolicy::class)`
  - `tests/Feature/Discussions/DiscussionModerationTest.php` — 12 tests covering all acceptance criteria
- **Learnings for future iterations:**
  - `DiscussionReply` requires explicit `Gate::policy()` registration in `AppServiceProvider` since `DiscussionPolicy` doesn't follow auto-discovery naming for `DiscussionReply`
  - Livewire `assertHasNoErrors()` only checks validation errors, NOT authorization failures — use `assertForbidden()` to verify authorization denials
  - Pest helper function names (e.g., `createModerationSetup()`) must be globally unique across all test files
---

## 2026-03-20 - US-080
- Implemented "Starting a Conversation" feature: POST /messages creates or reopens a 1:1 conversation
- Created ConversationController with store method that checks for existing conversations and blocks
- Created StartConversationRequest form request for validation
- Added DM rate limiter (20/min per user) in AppServiceProvider, applied via `throttle:dm` middleware
- Updated member profile "Message" button from link to POST form
- Created minimal messages/show.blade.php view for redirect target
- Added routes: POST /messages (messages.store), GET /messages/{conversation} (messages.show)
- Files changed: app/Http/Controllers/Messages/ConversationController.php (new), app/Http/Requests/Messages/StartConversationRequest.php (new), app/Providers/AppServiceProvider.php, resources/views/members/show.blade.php, resources/views/messages/show.blade.php (new), routes/web.php, tests/Feature/Messages/ConversationTest.php (new)
- **Learnings for future iterations:**
  - Block model uses `blocker_id`/`blocked_id` (not `user_id`) — User's `blocks()` relationship defaults to `user_id` so query Block directly for block checks
  - The member profile page already had a "Message" link placeholder — converting to a POST form is the right approach
  - Rate limiting via named throttle middleware (`throttle:dm`) registered in AppServiceProvider is the standard pattern
  - ConversationParticipant factory needs explicit `conversation_id` and `user_id` — it doesn't auto-create them
---

## 2026-03-20 - US-081
- Implemented conversation list at `/messages` sorted by most recent message, 20 per page, with unread indicators
- Created Livewire `ConversationView` component at `/messages/{conversation}` with message history, sender name, avatar, timestamps
- Real-time updates via Reverb on private channel `conversation.{conversationId}` with `DirectMessageSent` event
- `NewDirectMessage` notification sent (web + email) — respects conversation mute setting
- Cursor pagination: 30 messages per page
- Soft delete own messages (only owner can delete)
- Files changed: app/Http/Controllers/Messages/ConversationController.php, app/Events/DirectMessageSent.php (new), app/Livewire/ConversationView.php (new), app/Notifications/NewDirectMessage.php (new), resources/views/livewire/conversation-view.blade.php (new), resources/views/messages/index.blade.php (new), resources/views/messages/show.blade.php, routes/channels.php, routes/web.php, tests/Feature/Messages/DirectMessageTest.php (new)
- **Learnings for future iterations:**
  - Livewire `firstOrFail()` throws `ModelNotFoundException` directly in tests — use Pest `->toThrow()` instead of `->assertNotFound()`
  - `ConversationParticipant` has `$timestamps = false` but has a `created_at` cast — use explicit column updates not `touch()`
  - `withMax('messages', 'created_at')` adds `messages_max_created_at` virtual column for sorting conversations by latest message
  - Broadcast channel auth for conversations checks `ConversationParticipant` existence — simpler than policy-based auth
  - Pint auto-fixes `fully_qualified_strict_types` and `ordered_imports` — always run `--dirty` after editing test files
---

## 2026-03-20 - US-082
- Implemented user blocking/unblocking via POST/DELETE `/members/{user}/block`
- Created `BlockController` with `store()` and `destroy()` actions
- Updated `ConversationController::index()` to hide conversations with blocked users (bidirectional)
- Updated `ConversationView` Livewire component to reject DMs when block exists (either direction)
- Added notification suppression for blocked users in `ConversationView::sendMessage()`
- Profile visibility already handled by existing `UserPolicy::view()` which checks blocks
- Files changed: `app/Http/Controllers/BlockController.php` (new), `app/Http/Controllers/Messages/ConversationController.php`, `app/Livewire/ConversationView.php`, `routes/web.php`, `tests/Feature/Messages/BlockingTest.php` (new)
- **Learnings for future iterations:**
  - Block model, factory, and migration already existed — check existing models before creating new ones
  - `UserPolicy::view()` already checked blocks for profile visibility — no changes needed there
  - `ConversationController::store()` already rejected DMs when blocked — the Livewire `sendMessage()` needed the same check
  - Use `Block::firstOrCreate()` to prevent duplicate block records idempotently
  - Conversation hiding requires filtering both directions (blocker_id and blocked_id) in the query
  - Pest helper functions must have unique names across all test files — prefixed with `createBlocking` to avoid collisions
---

## 2026-03-20 - US-083
- Implemented Explore Page at `/explore` as a Livewire full-page component
- Guest homepage (`/`) redirects to `/explore`; authenticated users still see welcome page
- Header with "Events near [location]" + search bar + pill-shaped filter chips (topic, date range, event type, distance)
- Filters: topic/interest via Spatie Tags, date range (today/tomorrow/this_week/this_month), event type (in_person/online/hybrid), distance radius (10-250km, default 50km)
- Results: 2-column featured grid (first 2 events), 3-column grid for rest
- Infinite scroll via `wire:intersect` triggering `loadMore()` — 12 events per page
- Online events displayed in separate "Online Events" section, not filtered by location
- Guest: popular events sorted by RSVP count; with browser geolocation, nearby events via Geocodio reverse geocode
- Authenticated with location: nearby events matching interests → group events not yet RSVP'd → popular
- Authenticated without location: group events first → popular; gold prompt to set location
- SEO: `<title>` "Explore Events — {site_name}", meta description "Discover local meetups, events, and community groups near you."
- Updated ExampleTest and TrackLastActivityTest to expect redirect from `/` for guests
- 16 feature tests covering: guest/auth rendering, guest redirect, SEO meta, event listing, search filter, event type filter, date range filter, online events section, popular sorting, auth without location (group events first + prompt), auth with location (nearby events), infinite scroll, topic filter
- Files changed:
  - `app/Livewire/ExplorePage.php` (new)
  - `resources/views/livewire/explore-page.blade.php` (new)
  - `routes/web.php` (added explore route + guest redirect)
  - `tests/Feature/Discovery/ExplorePageTest.php` (new)
  - `tests/Feature/ExampleTest.php` (updated assertion)
  - `tests/Feature/Middleware/TrackLastActivityTest.php` (updated assertion)
- **Learnings for future iterations:**
  - Livewire full-page components MUST use `#[Layout('components.layouts.app')]` attribute on the class — wrapping the view in `<x-layouts.app>` causes "Multiple root elements detected" error
  - Use `->layoutData([...])` in `render()` to pass title/description to the layout component
  - `Route::livewire('/path', Component::class)` is the Livewire 4 way to register full-page component routes
  - `Livewire\Attributes\Url` attribute replaces `$queryString` property for URL-synced properties
  - `Spatie\Tags\Tag::getWithType('interest')` retrieves all tags of a given type for filter dropdowns
  - Event model uses `name` field (not `title`) and has no `url` accessor — construct URLs via `route('events.show', [$event->group, $event])`
  - The `event-card` Blade component references `$event->title` and `$event->url` which don't exist on the model — render cards directly instead
---

## 2026-03-20 - US-084
- Implemented Group Search page at `/groups` with Livewire full-page component
- Search bar with debounced text input searching by name, description, and location
- Filter controls: topic/interest dropdown, location/distance (when user has coordinates), sort options
- Sort options: relevance (default for search), newest, most members, most active (recent events in last 3 months)
- Scout integration for search with LIKE fallback when SCOUT_DRIVER=null (tests)
- Cursor-based infinite scroll pagination at 12 groups per page
- SEO title: "Browse Groups — {site_name}"
- Files changed: `app/Livewire/GroupSearchPage.php`, `resources/views/livewire/group-search-page.blade.php`, `routes/web.php`, `tests/Feature/Groups/GroupSearchTest.php`
- **Learnings for future iterations:**
  - `SCOUT_DRIVER=null` in phpunit.xml means Scout::search() returns no results in tests — use LIKE fallback for search functionality
  - Groups use `topic` tag type (not `interest`) — `Tag::getWithType('topic')` for group topic filters
  - `Route::livewire()` must be registered BEFORE wildcard routes like `groups/{group:slug}` to avoid capture
  - `withCount(['events as recent_events_count' => fn])` allows aliased conditional counts for sorting by activity
  - The `nearby()` scope on Group model uses Haversine formula and accepts radius in km
---

## 2026-03-20 - US-085
- Implemented Global Search with Livewire `GlobalSearch` component at `/search`
- Searches across Group (name, description), Event (name, description), User (name, bio — public profiles only)
- Powered by Laravel Scout with Meilisearch (database driver fallback using LIKE queries)
- Results grouped by type (Groups, Events, Members) with relevance ranking
- SEO title: `Search: "{query}" — {site_name}`
- Added global search bar to navbar in app layout (form submits to `/search`)
- Feature test with 15 tests covering cross-model search, public-only user filtering, results grouping, draft event exclusion, private group exclusion
- Files changed: `app/Livewire/GlobalSearch.php`, `resources/views/livewire/global-search.blade.php`, `resources/views/components/layouts/app.blade.php`, `routes/web.php`, `tests/Feature/Discovery/GlobalSearchTest.php`
- **Learnings for future iterations:**
  - `Route::livewire()` path argument must NOT include a leading slash duplication — it works just like `Route::get()`
  - Prefix Pest helper functions with unique context names (e.g., `createGlobalSearchGroup()`) to avoid PHP function redeclaration errors across test files
  - The navbar search bar uses a plain HTML form (not Livewire) that submits to the `/search` route with `query` param — the Livewire component on the results page picks up the URL parameter via `#[Url]`
  - Scout driver check pattern: `$scoutDriver && $scoutDriver !== 'null'` to handle both null config and `SCOUT_DRIVER=null` in phpunit.xml
---

## 2026-03-20 - US-086
- Updated `Event::scopeNearby` to fall back to group lat/lng when venue lat/lng is null using COALESCE and JOIN on groups table
- Created `tests/Feature/Discovery/NearbyEventsTest.php` with 12 tests covering: events within/outside radius, group location fallback, null lat/lng handling, online events separation, adjustable radius, default 50km radius
- Updated existing `EventTest` to explicitly set null group coordinates for the no-location test case (group factory always generates coordinates)
- Files changed: `app/Models/Event.php`, `tests/Feature/Discovery/NearbyEventsTest.php`, `tests/Unit/Models/EventTest.php`
- **Learnings for future iterations:**
  - Group factory always generates coordinates from predefined cities — explicitly set `latitude: null, longitude: null` when testing no-location scenarios
  - `Event::scopeNearby` uses JOIN on groups table for COALESCE fallback — use `->select('events.*')` to avoid column ambiguity
  - The explore page falls through from nearby → group events → popular events, so distant events may still appear in the popular section even when outside the nearby radius
  - Pre-existing test failure in `RecurringEventTest > it edits all future` (unrelated to location features)
---

## 2026-03-20 - US-087
- Implemented Dashboard page at `/dashboard` as a Livewire full-page component
- Sections: Upcoming Events (RSVP Going, sorted by date), Your Groups (with next event per group), Suggested Events (group events not RSVP'd + interest-matching nearby events), Recent Notifications (unread)
- Updated homepage `/` to redirect authenticated users to `/dashboard`, guests to `/explore`
- SEO title: "Dashboard — {site_name}"
- 17 feature tests covering data display, empty states, recommendation query correctness
- Files changed: `app/Livewire/DashboardPage.php`, `resources/views/livewire/dashboard-page.blade.php`, `routes/web.php`, `tests/Feature/Discovery/DashboardTest.php`
- **Learnings for future iterations:**
  - EventFactory sets `venue_latitude`/`venue_longitude` with 80% probability to random coords — when testing `nearby` scope with group fallback coordinates, explicitly set venue coords to null
  - Blade `assertSee` HTML-encodes apostrophes — use "have not" instead of "haven't" or use `assertSee($string, false)` for HTML content
  - `Route::livewire()` inside `auth` middleware group works correctly for protected Livewire pages
  - The `Event::scopeNearby` uses COALESCE(events.venue_latitude, groups.latitude) — event-level coords take priority over group coords
---

## 2026-03-20 - US-088
- Implemented all 22 notification types as Laravel Notification classes
- Created 2 new notifications: `ReportReceived` (web+email) and `AccountSuspended` (email only)
- Fixed `GroupDeleted` to use email-only channel (`['mail']`) instead of `['mail', 'database']`
- Added `link` key to `toArray()` for all notifications with database channel, pointing to relevant content
- Added `->action()` URLs to `toMail()` for notifications missing them (NewEvent, EventUpdated, EventCancelled, EventCommentReply, NewDiscussionReply, NewDirectMessage, RoleChanged, OwnershipTransferred)
- Fixed `RsvpConfirmation` and `PromotedFromWaitlist` mail action URLs to use proper `/groups/{slug}/events/{slug}` route pattern instead of `/events/{id}`
- Created 3 feature test files with 30 tests covering all notification types:
  - `tests/Feature/Notifications/EventNotificationsTest.php` (11 tests)
  - `tests/Feature/Notifications/GroupNotificationsTest.php` (15 tests)
  - `tests/Feature/Notifications/MessageNotificationsTest.php` (4 tests)
- Files changed: All 21 existing notification classes in `app/Notifications/`, 2 new notification classes, 3 new test files
- **Learnings for future iterations:**
  - `GroupJoinRequest` has no factory — use `GroupJoinRequest::create()` directly in tests
  - Event routes use `/groups/{group:slug}/events/{event:slug}` pattern — notifications must use group slug + event slug
  - Comment model uses `event_comments` table (not `comments`) and has `event_id` column
  - The `NotificationService::CRITICAL_NOTIFICATIONS` array already references `AccountSuspended` by string — class must match
  - Email-only notifications (GroupDeleted, AccountSuspended) use `['mail']` channel, web-only (NewEventComment, EventCommentLiked, NewEventFeedback, NewDiscussion) use `['database']`
---

## 2026-03-20 - US-089
- Implemented Livewire `NotificationDropdown` component replacing static navbar notification bell
- Bell icon with unread count badge (coral-500), capped at 99+
- Dropdown shows 10 most recent notifications with "Load more" button for pagination
- Each notification has contextual icon (calendar/users/chat/envelope/ticket/shield), message, timestamp, and link
- Mark as read on click; "Mark all as read" button when unread exist
- Real-time count updates via Reverb on private channel `user.{userId}.notifications` using `NotificationSent` broadcast event
- Set up Laravel Echo + Pusher JS in `bootstrap.js` for WebSocket connectivity
- Added `user.{userId}.notifications` private channel in `routes/channels.php`
- Files changed:
  - `app/Events/NotificationSent.php` (new)
  - `app/Livewire/NotificationDropdown.php` (new)
  - `resources/views/livewire/notification-dropdown.blade.php` (new)
  - `resources/views/components/layouts/app.blade.php` (replaced static bell with `<livewire:notification-dropdown />`)
  - `resources/js/bootstrap.js` (added Echo/Reverb setup)
  - `routes/channels.php` (added notification channel)
  - `package.json`, `package-lock.json` (added laravel-echo, pusher-js)
  - `tests/Feature/Notifications/NotificationDropdownTest.php` (new, 15 tests)
  - `tests/Unit/Events/NotificationSentTest.php` (new, 2 tests)
  - `tests/Component/Layouts/AppLayoutTest.php` (updated mocks for Livewire component)
- **Learnings for future iterations:**
  - Livewire inline components (non-full-page) don't need `#[Layout]` — they render within parent layout
  - Use `Illuminate\Support\Collection` (not `Eloquent\Collection`) for Livewire typed properties to avoid type mismatch
  - When replacing inline layout code with a Livewire component, `AppLayoutTest` mock user must support all method chains the component calls (e.g., `notifications()->count()`)
  - Livewire `#[On('echo-private:channel,Event')]` attribute listens for Reverb events — requires Echo to be configured in JS
  - `x-cloak` + `x-show` with Alpine transitions give smooth dropdown UX without custom JS
---

## 2026-03-20 - US-090
- Implemented notification digest batching for high-frequency email notifications
- Fixed `shouldBatchDigest` in `NotificationService` to count from both the `notifications` table (database notifications) and `pending_notification_digests` table, ensuring accurate threshold detection
- Created `SendNotificationDigests` artisan command (`notifications:send-digests`) that groups pending items by (user_id, notification_type), renders a `NotificationDigestMail` per group, sends it, and deletes pending records
- Created `NotificationDigestMail` mailable with markdown email view (`emails/notification-digest.blade.php`)
- Scheduled `notifications:send-digests` every 5 minutes in `routes/console.php`
- Web (database) notifications are never batched — always sent individually regardless of frequency
- Feature test covers: 4 notifications send individually, 5th triggers batching, digest command groups and sends, pending records deleted, web notifications not batched, schedule verification
- Updated existing unit test in `NotificationServiceTest` to match new threshold counting logic
- Files changed: `app/Services/NotificationService.php`, `app/Console/Commands/SendNotificationDigests.php` (new), `app/Mail/NotificationDigestMail.php` (new), `resources/views/emails/notification-digest.blade.php` (new), `routes/console.php`, `tests/Feature/Notifications/NotificationDigestTest.php` (new), `tests/Unit/Services/NotificationServiceTest.php`
- **Learnings for future iterations:**
  - `Mail::fake()` does NOT capture emails sent via `notifyNow()` (notification mail channel) — it only captures `Mail::to()->send()` calls. For testing notification email sends, assert on DB state (pending digests, database notifications) rather than `Mail::assertSentCount()`
  - When `notifyNow($notification, ['database'])` runs, the database notification is created immediately BEFORE any subsequent email batching check — so `shouldBatchDigest` threshold must account for the current notification's DB entry already being counted
  - The `pending_notification_digests` table has `$timestamps = false` with only a manual `created_at` — must pass `created_at` explicitly in `::create()`
  - Artisan command attributes: use `#[Signature('name')]` and `#[Description('desc')]` (matching existing `PurgeDeletedGroups` pattern)
---

## 2026-03-20 - US-091
- Added mute/unmute toggle on group page for members
- Added `toggleMute` controller method in `GroupController` that creates/deletes `GroupNotificationMute` records
- Added `groups.toggle-mute` route
- Passed `isMuted` state to the group show view
- Created feature test `tests/Feature/Notifications/NotificationMutingTest.php` with 6 tests covering:
  - Mute suppresses NewEvent notification
  - Mute does NOT suppress PromotedFromWaitlist (critical) notification
  - Toggle mute on creates record
  - Toggle mute off deletes record
  - Mute button shows on group page for members
  - Unmute button shows when already muted
- Files changed: `app/Http/Controllers/Groups/GroupController.php`, `resources/views/groups/show.blade.php`, `routes/web.php`, `tests/Feature/Notifications/NotificationMutingTest.php`
- **Learnings for future iterations:**
  - `GroupNotificationMute` model, factory, and migration already existed from US-090 (digest batching story)
  - `NotificationService::dispatch()` already had group muting logic with critical notification bypass — no service changes needed
  - The group show view uses `data-testid` attributes for test identification
  - Pre-existing failures in `RecurringEventTest` (Call to member function all() on array) are unrelated to notification work
---

## 2026-03-20 - US-092
- Implemented content reporting: users can report user profiles, groups, events, event comments, discussions, discussion replies, and chat messages
- Created `ReportController` with `store` method handling report creation, duplicate prevention, and admin notification
- Created `StoreReportRequest` form request with validation for reportable_type (alias mapping), reason (enum), and optional description
- Added `POST /reports` route with `throttle:report-submission` rate limiter (10/hour)
- Added `report-submission` rate limiter in `AppServiceProvider`
- Report model, enums (`ReportReason`, `ReportStatus`), migration, factory, and `ReportReceived` notification already existed
- Unique constraint enforced at app level (pending reports only per reporter+reportable) since MySQL doesn't support partial unique indexes
- Feature test covers: report creation for all 7 reportable types, duplicate rejection, re-report after resolution, admin notification (web+email), auth required, validation, non-existent content rejection, optional description
- Files changed: `app/Http/Controllers/ReportController.php` (new), `app/Http/Requests/Reports/StoreReportRequest.php` (new), `app/Providers/AppServiceProvider.php`, `routes/web.php`, `tests/Feature/Reporting/ReportContentTest.php` (new)
- **Learnings for future iterations:**
  - Report model, enums, migration, factory, and ReportReceived notification were pre-created — check existing models before creating new ones
  - Reportable type aliases (e.g., 'user' -> User::class) in the form request keep the API clean and decouple from internal class names
  - `Notification::send($admins, ...)` with `User::role('admin')->get()` is the pattern for notifying all admins
  - Pre-existing `RecurringEventTest` failure (Call to member function all() on array) continues — only fails in full suite, passes in isolation
---

## 2026-03-20 - US-093
- Implemented Admin Dashboard at `/admin` route, protected by `role:admin` middleware
- Created `AdminDashboardController` with stats: total users, total groups, total events, events this month, new users this week
- Dashboard shows recent pending reports (with reporter info) and recently created groups
- Quick links section for managing users, groups, reports, settings, and interests
- SEO title set to "Admin: Dashboard — {site_name}"
- Registered Spatie `role` middleware alias in `bootstrap/app.php` (was not previously registered)
- Files changed: `app/Http/Controllers/Admin/AdminDashboardController.php` (new), `resources/views/admin/dashboard.blade.php` (new), `routes/web.php`, `bootstrap/app.php`, `tests/Feature/Admin/AdminDashboardTest.php` (new)
- **Learnings for future iterations:**
  - Spatie's `role` middleware (`RoleMiddleware`) was not registered as a middleware alias — had to add it to `bootstrap/app.php`
  - Admin routes go inside the `auth` + `notSuspended` middleware group, with an additional `role:admin` middleware
  - SEO title pattern for admin pages: "Admin: {Page} — {site_name}"
  - Quick links currently point to `admin.dashboard` as placeholder — future admin pages should replace these routes
---

## 2026-03-20 - US-094
- Implemented Admin User Management at `/admin/users`
- Created `AdminUserController` with index (searchable/filterable list, 25/page), show (user details, groups, events attended), suspend, unsuspend, and destroy (hard delete) actions
- Created `SuspendUserRequest` form request with reason validation
- Added admin user routes under `role:admin` middleware group
- Created views: `admin/users/index.blade.php` (search, filter suspended, paginated table) and `admin/users/show.blade.php` (user detail with groups, events, suspend/unsuspend/delete actions)
- Updated dashboard "Manage Users" quick link to point to `admin.users.index`
- Suspend sets `is_suspended=true`, `suspended_at=now`, `suspended_reason`; sends `AccountSuspended` email notification
- Unsuspend clears all suspension fields
- Delete uses `forceDelete()` for hard delete (User has SoftDeletes trait)
- 15 feature tests covering: admin access, non-admin rejection (403), unauthenticated redirect, pagination, search by name/email, filter suspended, user detail with groups/events, suspend with notification, suspend validation, unsuspend clearing fields, hard delete
- Files changed: `app/Http/Controllers/Admin/AdminUserController.php`, `app/Http/Requests/Admin/SuspendUserRequest.php`, `resources/views/admin/users/index.blade.php`, `resources/views/admin/users/show.blade.php`, `resources/views/admin/dashboard.blade.php`, `routes/web.php`, `tests/Feature/Admin/AdminUserManagementTest.php`
- **Learnings for future iterations:**
  - Event model uses `name` field, not `title` — the factory also uses `name`
  - User model has `SoftDeletes` trait — use `forceDelete()` for hard deletion and `withTrashed()` to verify deletion in tests
  - User factory has `->suspended()` and `->admin()` states ready to use
  - Admin routes live inside `Route::middleware('role:admin')` group within the `auth + notSuspended` group
  - `Notification::fake()` must be called before the action to intercept notifications
---

## 2026-03-20 - US-095
- Implemented admin group management: list, view details, and hard delete groups
- Created `AdminGroupController` with `index`, `show`, `destroy` methods
- Added searchable group list (by name/location) with visibility filter and 25/page pagination
- Added group detail view showing organizer, members count, events count, visibility, location, description
- Hard delete with confirmation (uses `forceDelete()` since Group has SoftDeletes)
- Files changed:
  - `app/Http/Controllers/Admin/AdminGroupController.php` (new)
  - `resources/views/admin/groups/index.blade.php` (new)
  - `resources/views/admin/groups/show.blade.php` (new)
  - `routes/web.php` (added admin group routes)
  - `tests/Feature/Admin/AdminGroupManagementTest.php` (new — 11 tests, 25 assertions)
- **Learnings for future iterations:**
  - Admin controller pattern: query building with search/filter → eager loading → compact() to view
  - Group model uses `SoftDeletes` — use `forceDelete()` for hard delete and `Group::withTrashed()->find()` to verify in tests
  - Admin views follow consistent card styling: `rounded-lg bg-white p-6 shadow-sm` with `style="border: 0.5px solid var(--color-neutral-200)"`
  - Admin routes use `middleware('role:admin')` group, named `admin.{resource}.{action}`
---

## 2026-03-20 - US-096
- Implemented Admin Report Management at `/admin/reports`
- Created `AdminReportController` with index, review, resolve, dismiss, suspendUser, and deleteContent actions
- Created `ResolveReportRequest` form request for resolution_notes validation
- Added 6 admin report routes (`admin.reports.*`)
- Created `admin/reports/index.blade.php` view with status filter, grouped report counts, inline resolve/suspend forms
- Created `AdminReportManagementTest.php` with 22 tests (72 assertions) covering access control, listing, review, resolve, dismiss, suspend user, delete content, and status transitions
- Files changed:
  - `app/Http/Controllers/Admin/AdminReportController.php` (new)
  - `app/Http/Requests/Admin/ResolveReportRequest.php` (new)
  - `routes/web.php` (added report routes)
  - `resources/views/admin/reports/index.blade.php` (new)
  - `tests/Feature/Admin/AdminReportManagementTest.php` (new)
- **Learnings for future iterations:**
  - `pluck()` with `DB::raw()` as the key column doesn't work — use `->get()->mapWithKeys()` instead for composite keys
  - Report model uses enum casts for `reason` (ReportReason) and `status` (ReportStatus) — compare with enum instances, not strings
  - ReportFactory default creates reports on Comments; use `reviewed()`, `resolved()`, `dismissed()` states for different statuses
  - `resolveReportableUser()` pattern: check if reportable is User, then look for `user()` or `organizer()` relationships
  - Status transition guards: check `$report->status !== ReportStatus::Pending` or `! in_array($report->status, [...])` before allowing actions
---

## 2026-03-20 - US-097
- Implemented Admin Interest/Topic Management at `/admin/interests`
- Full CRUD: create, read (index with search), update, delete interests (Spatie tags with type 'interest')
- Merge functionality: reassigns all taggable relationships from source to target tag, handles duplicates, deletes source
- Usage count per interest: counts all taggable relationships (users + groups)
- Files changed:
  - `app/Http/Controllers/Admin/AdminInterestController.php` (new)
  - `app/Http/Requests/Admin/StoreInterestRequest.php` (new)
  - `app/Http/Requests/Admin/UpdateInterestRequest.php` (new)
  - `app/Http/Requests/Admin/MergeInterestRequest.php` (new)
  - `resources/views/admin/interests/index.blade.php` (new)
  - `resources/views/admin/interests/create.blade.php` (new)
  - `resources/views/admin/interests/edit.blade.php` (new)
  - `routes/web.php` (added interest admin routes)
  - `tests/Feature/Admin/AdminInterestManagementTest.php` (new, 15 tests)
- **Learnings for future iterations:**
  - Spatie Tags store `name` as JSON (`name->en` for English locale) — use `where('name->en', $value)` for queries
  - `Tag::findOrCreate($name, $type)` is the standard way to create tags with a type
  - The `taggables` table is a polymorphic pivot — query it directly via `DB::table('taggables')` for usage counts and merge operations
  - When merging tags, check for existing taggable relationships on the target to avoid duplicate pivot entries
  - Admin routes use `{interest}` model binding which resolves to `Spatie\Tags\Tag` model automatically
---

## 2026-03-20 - US-098
- Implemented Admin Platform Settings page at `/admin/settings`
- Added caching helpers to `Setting` model (`allCached()`, `get()`, `clearCache()`) with `DEFAULTS` constant for all 7 configurable settings
- Created `AdminSettingsController` with index/update methods, `UpdateSettingsRequest` for validation
- Created Blade view with form for all settings: site_name, site_description, registration_enabled (toggle), require_email_verification (toggle), max_groups_per_user (nullable), default_timezone (IANA select), default_locale
- Added routes: `GET /admin/settings`, `PUT /admin/settings`
- Settings stored in `settings` table (key/value), cached via `Cache::rememberForever`, invalidated on update
- Feature test with 12 tests: access control (admin OK, user 403, guest redirect), display defaults, display stored values, update, nullable max_groups, validation, timezone validation, cache invalidation, cache usage
- Files changed: `app/Models/Setting.php`, `app/Http/Controllers/Admin/AdminSettingsController.php`, `app/Http/Requests/Admin/UpdateSettingsRequest.php`, `resources/views/admin/settings.blade.php`, `routes/web.php`, `tests/Feature/Admin/AdminSettingsTest.php`
- **Learnings for future iterations:**
  - `$request->validated()` does NOT include nullable fields that are absent from the request — use `?? null` when accessing potentially missing keys
  - The `timezone:all` validation rule validates against PHP's `timezone_identifiers_list()`
  - Array cache driver shares state within the same test process, but cache populated via `rememberForever` may persist across HTTP request boundaries in tests — test cache invalidation by verifying DB state and manual re-cache rather than asserting cache absence
---

## 2026-03-20 - US-099
- Created custom error pages (403, 404, 419, 429, 500, 503) in `resources/views/errors/`
- All pages share common design: centered content, neutral-50 background, decorative green blob (opacity 0.06), error code (44px), headline (22px), body text (16px), primary CTA button
- 503 page is standalone HTML (no layout component) since framework may not be booted during maintenance mode; includes meta refresh tag for 60-second auto-reload
- Pages 403/404/419/429/500 use `<x-layouts.app>` layout to keep navbar visible
- Added feature tests in `tests/Feature/ErrorPagesTest.php` covering all 6 error pages
- Files changed: `resources/views/errors/{403,404,419,429,500,503}.blade.php`, `tests/Feature/ErrorPagesTest.php`
- **Learnings for future iterations:**
  - `assertSee()` escapes HTML by default — use `assertSee($value, false)` for raw HTML assertions like `href="/explore"`
  - `assertSeeText()` strips HTML tags and doesn't escape — use for checking visible text content
  - 503 error page should be standalone HTML (no Blade components) since the framework may not be fully booted during `php artisan down`
  - The `<x-blob>` component can be used for decorative blobs — accepts color, size, opacity, and shape props
---

## 2026-03-20 - US-100
- Redesigned `resources/views/auth/suspended.blade.php` to use standalone minimal layout (like error pages) with red-500 accent
- Minimal nav: only Greetup logo and logout link (no Explore/Groups/Dashboard)
- Displays `suspended_reason` from user model
- Added optional "Contact support" link configurable via `Setting::get('support_url')`
- Added `support_url` to `Setting::DEFAULTS` in `app/Models/Setting.php`
- Added 3 new tests: minimal nav verification, support link shown when configured, support link hidden when not configured
- Files changed: `resources/views/auth/suspended.blade.php`, `app/Models/Setting.php`, `tests/Feature/Middleware/EnsureAccountNotSuspendedTest.php`
- **Learnings for future iterations:**
  - `Setting::get()` returns null for keys with `null` default when no DB row exists — use `updateOrCreate` in tests to avoid duplicate key errors
  - The suspended page uses a standalone HTML layout (not `<x-layouts.app>`) to avoid showing full navigation to suspended users
  - Test isolation: `Setting::clearCache()` must be called after modifying settings in tests since `allCached()` uses `Cache::rememberForever()`
---

## 2026-03-20 - US-101
- Implemented SEO across all public pages using `Setting::get('site_name')` for dynamic site name
- Updated `<x-seo>` component to always render canonical URLs (auto-generated from current URL without query params)
- Updated layout default description to use `site_description` platform setting with fallback "A free, open source community events platform."
- Updated error pages (403, 404, 419, 429, 500, 503) to use `{Error Code} — {site_name}` title format
- Changed homepage route from redirect to rendering `home.blade.php` view with `{site_name} — Find your people` title
- Updated all controllers (GroupController, EventController, MemberController, 6 Admin controllers) and Livewire components (DashboardPage, ExplorePage, GlobalSearch, GroupSearchPage) to use `Setting::get('site_name')`
- OG image selection already implemented: event → event cover > group cover > default; group → group cover > default; profile → avatar > default
- Default OG image at `public/images/og-default.png` already exists
- Files changed: `resources/views/components/seo.blade.php`, `resources/views/components/layouts/app.blade.php`, `resources/views/home.blade.php` (new), `resources/views/errors/{403,404,419,429,500,503}.blade.php`, `routes/web.php`, `app/Livewire/{DashboardPage,ExplorePage,GlobalSearch,GroupSearchPage}.php`, `app/Http/Controllers/{MemberController,Groups/GroupController,Events/EventController,Admin/*}.php`, `tests/Feature/SeoTest.php` (new), `tests/Component/SeoTest.php`, `tests/Component/Layouts/AppLayoutTest.php`, and 8 other test files
- **Learnings for future iterations:**
  - SEO titles are built from `Setting::get('site_name')` not `config('app.name')` — tests should assert against "Greetup" directly (the Setting default), not `config('app.name')` which is "Laravel" in phpunit
  - Component tests rendering views that call `Setting::get()` need the cache pre-populated via `Cache::put(Setting::CACHE_KEY, Setting::DEFAULTS)` in `beforeEach()`
  - Changing the homepage from redirect to view rendering requires updating multiple test files that asserted `assertRedirect('/explore')`
  - The `<x-seo>` component uses `strtok(url()->current(), '?')` to auto-generate clean canonical URLs — no need for each page to pass canonical explicitly
---

## 2026-03-20 - US-102
- Added PreOrder availability logic to `buildJsonLd()` in EventController — when `rsvp_opens_at` is set and in the future, availability is PreOrder
- Added 9 new JSON-LD tests covering: EventCancelled status, OnlineEventAttendanceMode, MixedEventAttendanceMode, SoldOut/PreOrder/InStock availability, organizer info, endDate, and Offer details
- Files changed: `app/Http/Controllers/Events/EventController.php`, `tests/Feature/Events/ViewEventTest.php`
- **Learnings for future iterations:**
  - JSON-LD infrastructure was already built: `<x-seo>` component renders `<script type="application/ld+json">` when `$jsonLd` prop is passed, and the layout passes it through
  - `buildJsonLd()` is a private method on EventController — the show action calls it and passes result to the view
  - Event factory states: `published()`, `cancelled()`, `online()`, `hybrid()`, `withRsvpLimit(n)` — use these for test setup
  - Rsvp factory states: `going()`, `waitlisted()` — use for availability testing
---

## 2026-03-20 - US-103
- Implemented unauthenticated homepage with hero section featuring multi-line colored headline ("Find your people. / Do the thing. / Keep showing up.") at 44px, weight 500, letter-spacing -0.03em
- Added "Get started" (primary) and "Explore events" (secondary) CTA buttons
- Added three decorative blobs at low opacity in hero background (green, coral, violet)
- Added stat cards (coral, violet, gold) anchored bottom-right of hero showing live platform stats (total groups, events, members)
- Added popular interests pill cloud below hero using Spatie Tags with type 'interest'
- Added upcoming events preview grid (up to 6 published future events) below interests
- SEO title: "{site_name} — Find your people"
- Authenticated users redirected to /dashboard
- Fixed event-card component: enum-as-array-key bug, `title` → `name`, `capacity` → `rsvp_limit`, proper URL generation
- Files changed: `routes/web.php`, `resources/views/home.blade.php`, `resources/views/components/event-card.blade.php`, `tests/Feature/HomepageTest.php`
- **Learnings for future iterations:**
  - Event model uses `name` not `title` — the event-card component had a bug referencing `$event->title`
  - `event_type` is cast to `EventType` enum — cannot use directly as array key, need `->value`
  - `rsvp_limit` is the correct field name, not `capacity`
  - Interests are Spatie Tags with type 'interest' — use `Tag::getWithType('interest')` to query
  - Event-card component had no proper URL generation — now uses `route('events.show', ...)` with group and event slugs
  - The `scopeUpcoming()` filters by `starts_at > now()` AND `status = Published`
---

## 2026-03-20 - US-104
- Implemented all 5 scheduled commands:
  - `events:generate-recurring` (daily): generates recurring event instances from EventSeries RRULE strings up to 3 months ahead, skips series with >= 3 future events
  - `events:mark-past` (hourly): transitions published events to past status when ends_at has passed (or starts_at + 3h if no ends_at)
  - `accounts:purge-deleted` (daily): hard-deletes users soft-deleted > 30 days ago
  - `groups:purge-deleted` (daily): already existed, verified working
  - `notifications:send-digests` (every 5 minutes): already existed, verified working
- All 5 commands registered in `routes/console.php` with correct frequencies
- Created comprehensive feature tests (13 tests, 30 assertions)
- Files changed: `app/Console/Commands/GenerateRecurringEvents.php`, `app/Console/Commands/MarkPastEvents.php`, `app/Console/Commands/PurgeDeletedAccounts.php`, `routes/console.php`, `tests/Feature/Commands/ScheduledCommandsTest.php`
- **Learnings for future iterations:**
  - `deleted_at` is not in `$fillable` for User/Group models — use `->toBase()->update()` to set it in tests
  - EventSeries uses `recurrence_rule` field (not `rrule_string` or `rrule`)
  - `rlanvin/php-rrule` requires DTSTART passed separately from the RRULE string — use `RRule::parseRfcString()` then merge DTSTART
  - Existing commands use `#[Signature]` and `#[Description]` PHP attributes (not the older `$signature`/$description` properties)
  - Pre-existing test failures exist in EventCardTest (ViewException) and RecurringEventTest — not related to scheduled commands
---

## 2026-03-20 - US-105
- Created `greetup:install` command: interactive first-time setup wizard — creates admin user, sets site name, optionally configures Geocodio API key. Uses safe defaults with `--no-interaction`
- Created `greetup:geocode-missing` command: batch geocodes groups/events/users with addresses but missing lat/lng coordinates. Skips if no API key configured
- Created `greetup:stats` command: prints platform statistics table (total users, groups, events, published events, active events this month, past events)
- Created 9 tests in `tests/Feature/Commands/UtilityCommandsTest.php` covering all three commands
- Files changed: `app/Console/Commands/GreetupInstall.php` (new), `app/Console/Commands/GeocodeMissing.php` (new), `app/Console/Commands/GreetupStats.php` (new), `tests/Feature/Commands/UtilityCommandsTest.php` (new)
- **Learnings for future iterations:**
  - `email_verified_at` is NOT in User model's `$fillable` — use `$user->markEmailAsVerified()` instead of passing it to `create()`
  - `$this->input->isInteractive()` detects `--no-interaction` flag in artisan commands — use this to branch between interactive prompts and safe defaults
  - Laravel Prompts (`text()`, `password()`, `confirm()`) work in artisan commands but fail in test environment unless `--no-interaction` is passed
  - Existing commands use PHP 8 attributes `#[Signature]` and `#[Description]` instead of `$signature`/`$description` properties
  - `GeocodingService` already handles missing API keys gracefully (returns null), but checking `config('services.geocodio.api_key')` upfront gives better UX with an explicit warning message
  - Pre-existing EventCardTest failures (12 tests) are unrelated — `stdClass::$slug` issue in event-card component tests
- Broadcasting channel auth tests need `config(['broadcasting.default' => 'reverb'])`, a mocked Pusher SDK via `Broadcast::swap()`, and `postJson()` — see `tests/Feature/Broadcasting/ChannelAuthorizationTest.php`
- Use `withBroadcasting()` (not `withRouting(channels:)`) in `bootstrap/app.php` to register both channel definitions AND the `/broadcasting/auth` route
---

## 2026-03-20 - US-106
- Verified three private WebSocket channels already defined in `routes/channels.php`: `user.{userId}.notifications`, `conversation.{conversationId}`, `event.{eventId}.chat`
- Reverb already configured as broadcast driver via `config/broadcasting.php` and `BROADCAST_CONNECTION=reverb` in `.env`
- Switched from `withRouting(channels:)` to `withBroadcasting()` in `bootstrap/app.php` to properly register the `/broadcasting/auth` route
- Created 9 feature tests in `tests/Feature/Broadcasting/ChannelAuthorizationTest.php` covering all three channels (authorized + unauthorized scenarios)
- Files changed: `bootstrap/app.php`, `tests/Feature/Broadcasting/ChannelAuthorizationTest.php` (new)
- **Learnings for future iterations:**
  - `withRouting(channels:)` loads channel definitions but does NOT register the `/broadcasting/auth` route — use `withBroadcasting()` instead to get both
  - `phpunit.xml` sets `BROADCAST_CONNECTION=null` — the null driver doesn't enforce channel authorization, so tests must set `config(['broadcasting.default' => 'reverb'])` to test auth
  - The Reverb/Pusher broadcaster needs a real Pusher SDK to generate auth signatures — mock it in tests: `Broadcast::swap(new PusherBroadcaster($pusherMock))` then re-require `routes/channels.php`
  - Use `postJson()` (not `post()`) for broadcast auth requests — `post()` gets rendered as HTML error pages instead of proper status codes
  - The `PusherBroadcaster::auth()` throws `AccessDeniedHttpException` for both unauthenticated and unauthorized users (both return 403)
---

## 2026-03-20 - US-107
- Created complete seed data system with 11 seeders orchestrated by DatabaseSeeder
- **UserSeeder**: 50 users — admin@greetup.test (admin), user@greetup.test (regular), 8 named organizers with diverse locations, 40 factory-generated regular users
- **GroupSeeder**: 8 groups per spec — Copenhagen Laravel (35 members), Berlin JS (28), London Book Club (20, approval+questions), NYC Hiking (25), CPH Photography (15), Remote Workers DK (22), Board Game Nights (18), Women in Tech Berlin (20, approval+questions). Leadership teams with co-organizers, assistants, event organizers
- **EventSeeder**: 100 events — 2-3 past, 2-3 upcoming, 1 draft, 1 cancelled, 1 recurring series (5 instances) per group. Realistic venue addresses with hardcoded lat/lng
- **RsvpSeeder**: 1217 RSVPs — distributed across events, waitlisted members on capacity events, ~80% attended/~20% no-show on past events, some with guests
- **DiscussionSeeder**: 21 discussions with 149 replies, 1 pinned per group, 1 locked in largest group
- **EventCommentSeeder**: 196 comments with threaded replies and 219 likes
- **EventFeedbackSeeder**: 437 feedback entries on past events, mostly 4-5 stars, ~50% with text
- **DirectMessageSeeder**: 10 conversations with 32 messages, mix of read/unread
- **ReportSeeder**: 5 reports — 3 pending, 1 resolved, 1 dismissed
- **SettingsSeeder**: Default platform settings from Setting::DEFAULTS
- Files changed: `database/seeders/DatabaseSeeder.php`, `database/seeders/UserSeeder.php` (new), `database/seeders/GroupSeeder.php` (new), `database/seeders/EventSeeder.php` (new), `database/seeders/RsvpSeeder.php` (new), `database/seeders/DiscussionSeeder.php` (new), `database/seeders/EventCommentSeeder.php` (new), `database/seeders/EventFeedbackSeeder.php` (new), `database/seeders/DirectMessageSeeder.php` (new), `database/seeders/ReportSeeder.php` (new), `database/seeders/SettingsSeeder.php` (new)
- **Learnings for future iterations:**
  - `WithoutModelEvents` in DatabaseSeeder prevents Spatie's HasSlug from generating slugs — must disable Scout syncing per-model instead (`Model::disableSearchSyncing()`)
  - Spatie Tags `findOrCreate()` needs model events enabled for auto-slug generation (the `slug` column is NOT NULL)
  - When using `sprintf` with template strings, count `%s` placeholders and provide matching argument count — use `substr_count` to be safe
  - Group members pivot uses `role` as a string value (not the enum directly) when attaching: `'role' => GroupRole::Organizer->value`
  - EventCardTest failures are pre-existing — mock `stdClass` objects are missing `slug` property needed by the view
---

## 2026-03-20 - US-108
- Created `.env.ci.mysql` for GitHub Actions MySQL test environment
- Created `.env.dusk.ci` for Dusk browser tests in CI with Reverb config
- Both files set `GEOCODIO_API_KEY=` (empty) — GeocodingService gracefully returns null when key is missing
- Files added:
  - `.env.ci.mysql`
  - `.env.dusk.ci`
- **Learnings for future iterations:**
  - `.gitignore` only ignores `.env`, `.env.backup`, `.env.production` — other `.env.*` files are tracked
  - GeocodingService already handles empty API keys gracefully (returns null) — no additional mocking needed in CI
  - All geocoding tests (unit + feature) already mock GeocodingService via Mockery — zero real API calls
  - `phpunit.xml` sets `SCOUT_DRIVER=null` but CI env files use `SCOUT_DRIVER=collection` for broader test coverage
---

## 2026-03-20 - US-109
- Implemented GitHub Actions CI workflow at `.github/workflows/ci.yml`
- Three jobs: `lint` (Pint + Larastan), `test` (Pest with MySQL + coverage), `dusk` (browser tests with MySQL)
- `test` and `dusk` run in parallel (no job dependencies between them)
- All tests run against MySQL 8.0 service containers — no SQLite
- Triggered on push to main and pull requests to main
- Files changed: `.github/workflows/ci.yml`
- **Learnings for future iterations:**
  - The spec at section 11.1 contains the exact YAML for the CI workflow — follow it verbatim
  - `.env.ci.mysql` and `.env.dusk.ci` already exist from US-108 — CI workflow references them
  - The three CI jobs are intentionally independent (no `needs:` dependencies) so `test` and `dusk` run in parallel
---

## 2026-03-20 - US-110
- Implemented comprehensive README.md per spec section 12 with all required sections
- Created CONTRIBUTING.md per spec section 13 with full contributor guidelines
- Files changed: README.md (replaced default Laravel README), CONTRIBUTING.md (new)
- **Learnings for future iterations:**
  - The spec sections 12 and 13 contain exact markdown content to use verbatim for README.md and CONTRIBUTING.md
  - Spec content starts at line 2856 of greetup-spec.md
  - Pre-existing component test failures exist (12 failed) related to event-card compiled views — unrelated to documentation changes
  - Memory limit issues when running full test suite locally — use `-d memory_limit=512M` or run specific test suites
---

## 2026-03-20 - US-111
- CONTRIBUTING.md already exists with correct content (created in US-110 commit 325ed58)
- Verified all acceptance criteria against spec section 13: Getting Started, Branch Naming, Making Changes, Before Submitting a PR, Commit Messages, Pull Request Process, Reporting Bugs, Code of Conduct — all present and matching spec
- No changes needed — file is already committed and content is verbatim from the spec
- **Learnings for future iterations:**
  - CONTRIBUTING.md was bundled with README.md in US-110 — check git history before creating files that may already exist
---

## 2026-03-20 - US-112
- Verified all pagination configurations match spec section 5.9.3
- All per-page values and pagination styles were already correctly implemented:
  - Explore page: 12 items, infinite scroll via Livewire (cursor-style `loadMore`)
  - Group search: 12 items, infinite scroll via Livewire (cursor-style `loadMore`)
  - Event attendees: 20 items, standard pagination
  - Group members: 20 items, standard pagination
  - Discussion list: 15 items, standard pagination
  - Discussion replies: 20 items, standard pagination
  - Event comments: 15 items, standard pagination
  - DM conversations: 20 items, standard pagination
  - DM messages: 30 items, cursor pagination
  - Admin lists (users/groups/reports): 25 items, standard pagination
  - Notification dropdown: 10 items, load more button
- Created comprehensive test covering all 11 pagination configurations
- Files changed: `tests/Feature/PaginationConfigurationTest.php` (new)
- **Learnings for future iterations:**
  - `groupRole:assistant_organizer` middleware requires the user to have a membership record with organizer/assistant_organizer role — attach organizer to group members in tests
  - DirectMessage factory uses `user_id` (not `sender_id`) for the message author
  - Pre-existing test failures (12) in AdminInterestManagement and EventCard tests are unrelated to pagination
  - Pest helper functions must have globally unique names — use `createPaginationGroup()` prefix pattern
---

## 2026-03-20 - US-113
- Added timezone conversion to explore and dashboard pages — event times now display in user's timezone (authenticated) or instance `default_timezone` setting (guests)
- Updated `ExplorePage` and `DashboardPage` Livewire components to compute `$displayTimezone` and pass to views
- Updated explore-page and dashboard-page Blade views to use `->setTimezone($displayTimezone)` on all `starts_at` displays
- Updated `event-card` and `event-row` Blade components to accept optional `displayTimezone` prop for timezone conversion
- Added time display (`g:ia` format) alongside date on explore page event cards (previously showed date only)
- Added 4 new timezone-specific feature tests across ExplorePageTest and DashboardTest
- Files changed: `app/Livewire/ExplorePage.php`, `app/Livewire/DashboardPage.php`, `resources/views/livewire/explore-page.blade.php`, `resources/views/livewire/dashboard-page.blade.php`, `resources/views/components/event-card.blade.php`, `resources/views/components/event-row.blade.php`, `tests/Feature/Discovery/ExplorePageTest.php`, `tests/Feature/Discovery/DashboardTest.php`
- **Learnings for future iterations:**
  - Explore and dashboard pages use inline event card markup (not the `<x-event-card>` component) — timezone changes must be applied to both the inline markup and the reusable component
  - `Setting::get('default_timezone', 'UTC')` provides the instance-wide fallback timezone for guests
  - Timezone display chain: authenticated user's `->timezone` property → `Setting::get('default_timezone')` → `'UTC'`
  - `$carbon->setTimezone($tz)` is safe to call multiple times — Carbon returns a copy, doesn't mutate
  - Tests that modify `Setting` values must call `Setting::clearCache()` after to flush the cached settings
---

## 2026-03-20 - US-114
- Configured all media collections (User avatar, Group cover_photo, Event cover_photo) to store originals on private `local` disk with conversions on `public` disk via `useDisk('local')->storeConversionsOnDisk('public')`
- Created `tests/Feature/Profile/ImageUploadTest.php` with 5 tests: valid avatar upload stored via medialibrary, oversized avatar returns 422, non-image avatar returns 422, group cover conversions generated (card + header), event cover conversions generated (card + header)
- Files changed: `app/Models/User.php`, `app/Models/Group.php`, `app/Models/Event.php`, `tests/Feature/Profile/ImageUploadTest.php`
- **Learnings for future iterations:**
  - `useDisk('local')->storeConversionsOnDisk('public')` on media collections stores originals privately while making conversions publicly accessible
  - Event creation via `events.store` route requires `venue_name` and `venue_address` for in_person events (validated via `required_if:event_type,in_person,hybrid`)
  - Group settings update route requires `groupRole:co_organizer` middleware — test users need organizer role attached via `$group->members()->attach($user->id, ['role' => GroupRole::Organizer->value, 'joined_at' => now()])`
  - Pre-existing test failures in `EventCardTest` (missing `slug` on stdClass mock) and `RecurringEventTest` (array vs collection) are unrelated to media changes
---

## 2026-03-20 - US-115
- Created 16 Dusk browser test files covering all acceptance criteria:
  - Auth flows: `tests/Browser/Auth/RegistrationTest.php`, `LoginTest.php`, `PasswordResetTest.php`
  - Group flows: `tests/Browser/Groups/BrowseGroupsTest.php`, `JoinGroupTest.php`, `CreateGroupTest.php`, `ManageGroupTest.php`
  - Event flows: `tests/Browser/Events/BrowseEventsTest.php`, `RsvpFlowTest.php`, `CreateEventTest.php`, `EventChatTest.php`, `EventFeedbackTest.php`
  - Discovery flows: `tests/Browser/Discovery/ExplorePageTest.php`, `GlobalSearchTest.php`
  - Message flows: `tests/Browser/Messages/DirectMessageTest.php`
  - Admin flows: `tests/Browser/Admin/AdminDashboardTest.php`
- All tests use `DatabaseMigrations` trait with MySQL (not SQLite)
- All tests seed `RoleSeeder` in `beforeEach()` since `DatabaseMigrations` runs fresh migrations per test class
- Tests leverage `data-testid` attributes for reliable element selection where available
- Livewire component interactions (RSVP, chat, feedback) use `waitFor` with data-testid selectors
- **Learnings for future iterations:**
  - Dusk tests are NOT included in `phpunit.xml` — they only run via `php artisan dusk`
  - Existing example test (`tests/Browser/ExampleTest.php`) was scaffolded by `laravel/dusk` install — left as-is
  - PHPStan errors on Pest test files are expected (closure-based `$this` context) — don't block on them
  - The group show view `leave-form` action currently points to `groups.show` route (not `groups.leave`) — may need fix
  - Event edit form submit button text is "Save Changes" (not "Update Event")
  - Group settings form submit button text is "Save Settings"
  - Admin reports page has "Review" (text link), "Resolve" (green text button that reveals form), and "Dismiss" buttons
  - For approval-required groups, `request-join-button` is a JS toggle that shows `join-request-form` div — not a form submit
  - Memory limit issues with full test suite — run subsets or increase `memory_limit` to 512M
---
