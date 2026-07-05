# Budgetra UI/UX Redesign — Design Spec

**Date:** 2026-07-02  
**Scope:** Full front-end redesign covering all traveler-facing and admin-adjacent UI surfaces. Backend logic (controllers, models, observers) is complete (P1a–P5). This spec is purely about views, Livewire components, CSS, and wiring the existing backend to a polished UI.

---

## Design System

**Colors:**
- Primary / brand: `#5C2D0E` (dark brown)
- Accent gold: `#C9A84C`
- Accent blue: `#1A4C8B`
- Background: `#FAF5F0` (warm off-white)
- Card surface: `#FFFFFF`
- Border / divider: `#E8DDD5`
- Text primary: `#1A1A1A`
- Text secondary: `#6B6B6B`
- Success: `#2ECC71`
- Danger: `#E74C3C`

**Typography:**
- Headings: `Playfair Display` (Google Fonts)
- Body / UI: `Inter` (Google Fonts)

**Stylesheet:** All new styles go in `public/css/budgetra.css`. Existing `public/css/style.css` remains untouched (admin views use it). Traveler views load `budgetra.css` instead via the `layouts.app` layout update.

**Icons:** Heroicons SVG inline (no npm — copy SVG paths directly into Blade where needed).

---

## Global Layout — `layouts/app.blade.php`

Replace the existing layout with:

```
┌─────────────────────────────────────────────────┐
│  Sidebar (240px fixed)   │  Main content area   │
│  ─────────────────────── │  ─────────────────── │
│  Logo + brand name       │  Top bar (40px)      │
│  ─────────────────────── │  ─────────────────── │
│  Nav links               │  @yield('content')   │
│  (with active state)     │                      │
│  ─────────────────────── │                      │
│  Notification badge      │                      │
│  User avatar + name      │                      │
│  Logout                  │                      │
└─────────────────────────────────────────────────┘
```

Sidebar nav items (traveler): Dashboard, My Trips, Expenses, Savings Goals, Itinerary, Attractions, Alerts (+ badge), Multi-Trip Hub.

The sidebar `NotificationBadge` Livewire component (`wire:poll.30s`) shows unread alert count as a red dot next to "Alerts".

`layouts.admin` is unchanged.

---

## P6 — Landing Page + Auth

### Landing Page (`/`)

`GET /` → if authenticated, redirect to `/dashboard`. Otherwise render `resources/views/welcome.blade.php` (replaced with new design — pure Blade, no Livewire).

**Sections:**

1. **Hero** — full-width, warm gradient (`#5C2D0E` → `#8B4513`), centered:
   - Tagline: "Plan Smart. Travel More."
   - Subtext: "Track budgets, manage itineraries, and hit your savings goals — all in one place."
   - Two CTA buttons: "Start Planning" (→ `/register`) and "Sign In" (→ `/login`)

2. **How It Works** — 3-column card grid on `#FAF5F0`:
   - Step 1: Plan Your Trip (calendar icon)
   - Step 2: Track Expenses (receipt icon)
   - Step 3: Hit Your Goals (savings icon)
   Each card: icon circle (brown), step number, title, 1-sentence description.

3. **Footer** — minimal: brand name + tagline, copyright.

### Login (`/login`) — `resources/views/auth/login.blade.php`

Split-screen layout (50/50):
- **Left panel:** brand color background (`#5C2D0E`), logo, tagline, travel illustration (CSS geometric shapes — no external images required).
- **Right panel:** white, vertically centered form:
  - "Welcome back" heading (Playfair Display)
  - Email field, Password field (show/hide toggle)
  - "Forgot password?" link (right-aligned)
  - "Sign In" primary button (full width, brown)
  - "Don't have an account? Register" link

### Register (`/register`) — `resources/views/auth/register.blade.php`

Same split-screen layout, right panel form:
- Full Name, Email, Phone (optional), Country (select), Currency Code + Symbol (two side-by-side), Password, Confirm Password
- "Create Account" primary button
- "Already have an account? Sign In" link
- Validation errors shown inline below each field with `@error`.

---

## P7 — Trip Planning Wizard

**Route:** `GET /trips/plan` → Livewire `TripPlannerWizard` component  
**Component file:** `app/Livewire/TripPlannerWizard.php`  
**View file:** `resources/views/livewire/trip-planner-wizard.blade.php`

### Step flow (5 steps):

**Step 1 — Trip Type**
Two large cards side by side: "Local" / "International". Selecting one sets `$tripScope` and advances to Step 2.

**Step 2 — Choose Destination**
Filterable card grid. Cards loaded from `Destination::all()`. Each card: destination image (or gradient placeholder), name, country. Search bar filters by name. Selecting a card sets `$destinationId` and advances.

**Step 3 — Travel Dates**
Two date pickers: Departure and Return. Duration auto-calculated and shown below ("5 days"). Advances on "Next".

**Step 4 — Trip Details**
Two rows of selection cards:
- Row 1 — Trip Type: Solo / Family / Couple / Friends (icons)
- Row 2 — Budget Tier: Shoestring / Mid-range / Luxury

Advances on "Next".

**Step 5 — Trip Cost Estimator**
Calls `KlookService::estimate($destinationId, $travelType, $budgetTier, $numDays)` (existing service). Displays:
- Estimated total in a highlighted box
- Category breakdown table: Accommodation, Food, Transport, Activities, Miscellaneous
- "Adjust Budget" input (pre-filled with estimate, editable)
- "Confirm & Create Trip" button

On confirm: creates `Trip` record + `TripBudget` records per category, redirects to `/trips/{trip}/dashboard`.

**Progress indicator:** horizontal step bar at top showing Steps 1–5 with labels, current step highlighted in brown.

---

## P8 — Per-Trip Dashboard + Expense Scanning

### Per-Trip Dashboard

**Route:** `GET /trips/{trip}/dashboard` → Livewire `TripDashboard` component

**Layout (top → bottom):**

1. **"Track Your Spending" banner** — light warm card spanning full width:
   - Left: receipt icon + "Track Your Spending" + subtitle "Scan a receipt to keep your budget up to date."
   - Right: "Scan Expense" button (→ `/expenses/create?trip_id={trip}`)

2. **3 stat cards** (horizontal row):
   - **Total Budget** (brown top-border accent): `₱{budget_limit}`, subtitle "Allocated for {N} days"
   - **Amount Spent** (gold top-border): `₱{total_spent}`, subtitle "{pct}% of total budget"
   - **Remaining Funds** (blue top-border): `₱{remaining}`, subtitle "Stay on budget!" or "Over budget!" (red if negative)

3. **Charts row** (two columns):
   - Left: Chart.js pie chart — spending by category. `wire:ignore` wrapper. Legend below chart.
   - Right: Chart.js line chart — daily spend trend. `wire:ignore` wrapper.

4. **Budget Breakdown table** — category | budgeted | spent | remaining | % used. Color-coded: green < 80%, orange 80–99%, red ≥ 100%.

5. **Recent Expenses** — last 5 expenses as a simple table: date, description, category, amount. "View All" link → `/expenses?trip_id={trip}`.

### Global Dashboard

**Route:** `GET /dashboard` → controller returns aggregate view across all trips. Shows: active trip cards grid (same cards as Multi-Trip Hub), total stats summary, "Plan New Trip" CTA.

### Expense Scanning Page — `GET /expenses/create`

Pure Blade (no Livewire needed — standard form + JS for drag-and-drop).

**Two-column layout:**

**Left (main):**
- Heading: "Scan Your Receipt"
- Subtext: "Upload a photo of your receipt to automatically extract and track expenses."
- **Drop zone** (dashed brown border, rounded, `#FDF0E8` background):
  - Camera icon
  - "Drag & Drop Receipt"
  - "or click to browse from your computer"
  - "Select File" button
- **Expense Details form** below drop zone:
  - Trip (select, pre-populated from `?trip_id` param)
  - Amount (₱ prefix)
  - Category (select)
  - Date (default today)
  - Description / Merchant (text)
  - "Save Expense" button (full width, brown)

**Right sidebar:**
- "On the go? Use mobile camera" card (blue background) — `<input type="file" accept="image/*" capture="environment">` hidden, triggered by button.
- "Recent Scans" card — last 3 OCR logs for the user, "View All" link.
- Active trip summary card (gold background) — trip destination, savings progress bar.

**OCR flow:** On file select/drop, JS reads the file and POSTs to `POST /expenses/ocr-scan` (existing route). The response JSON (`amount`, `merchant`, `date`) auto-fills the form fields. OCR uses OCR.space API key configured in `.env` as `OCR_API_KEY=K82825648388957` and read via `config('services.ocr.key')`. The existing `OcrService.php` handles the API call.

---

## P9 — Savings Goals UI

**Route:** `GET /savings` → `SavingsGoalController::index` (existing)  
Redesign `resources/views/traveler/savings/index.blade.php`. No Livewire needed for list, but **deposit modal** and **projection modal** use Livewire (`SavingsGoalManager` component).

**Page layout:**

- Header row: "Savings Goals" heading + "New Goal" button (→ `/savings/create`)
- Goal cards grid (2-column on wide screens, 1-column mobile):

Each card:
- Goal name (bold, Playfair Display)
- Optional "Linked to: {destination}" chip
- "% Done" badge (top-right corner): green if ≥ 100%, gold otherwise
- Progress bar (full width of card)
- `₱{current} / ₱{target}` text
- Deadline line: "Due {date} · {N} days left" (red if past deadline)
- Two action buttons: "Add Savings" (opens deposit modal), "Projection" (opens projection modal)
- Edit / Delete icon buttons (top-right)
- **Completed goals** (current_savings ≥ target_amount): card background darkened (`#F0EBE5`), opacity 0.75, "COMPLETED" badge overlay

**Deposit modal** (Livewire `SavingsGoalManager`):
- Amount input
- "Add to Goal" button → `PATCH /savings/{goal}/deposit`
- Progress updates reactively after deposit

**Projection modal** (same component, different state):
- Shows: "You need to save ₱{daily_needed}/day to reach your goal by {deadline}"
- Formula: `(target - current) / max(1, days_remaining)`
- Bar showing current % vs. needed

**Create/Edit views** (`savings/create.blade.php`, `savings/edit.blade.php`): same fields as current but styled with `budgetra.css` card layout.

---

## P10 — Calendar-Based Itinerary

**Route:** `GET /itinerary` → Livewire `ItineraryManager` component

**Layout:**

1. **Trip selector** (top): dropdown to choose which trip's itinerary to view.

2. **Horizontal day strip calendar:**
   - Scrollable row of day pills: `Mon 5`, `Tue 6`, etc. (trip date range)
   - Selected day highlighted in brown
   - Days with items have a small brown dot indicator

3. **Day content area** (below strip):
   - Heading: "Tuesday, July 6" + "Add Item" button (slide-up panel)
   - List of itinerary items for selected day:
     - Each item: time badge, title, type chip (Activity / Transport / Accommodation / Meal), notes, delete button
     - Activity items: optional "Linked Attraction" display if `attraction_id` is set

4. **Add Item slide-up panel** (Livewire `$showPanel = true`):
   - Fields: Time, Title, Type (select), Notes
   - If Type = "Activity": "Link to Attraction" dropdown (attractions from user's trip destinations)
   - "Add to Itinerary" button → `POST /itinerary`

**Itinerary items** are stored in the existing `itineraries` table (`trip_id`, `date`, `title`, `type`, `notes`, `attraction_id` nullable).

---

## P11 — Attractions + Per-Attraction Reviews

### Schema change

Add nullable `attraction_id` foreign key to `reviews` table (new migration). Existing reviews keep `attraction_id = null` (destination-level reviews). New reviews created from an attraction detail page set `attraction_id`.

### Attractions browse page — `GET /attractions`

Livewire `AttractionBrowser` component (replaces existing controller view).

- **Filter bar:** destination filter (dropdown of user's trip destinations), search by name
- **Card grid:** each attraction card:
  - Image placeholder (gradient by category)
  - Attraction name
  - Destination chip
  - Star rating (average from reviews where `attraction_id = {id}`)
  - "View Details" button

### Attraction detail page — `GET /attractions/{attraction}`

Pure Blade (new view `resources/views/traveler/attractions/show.blade.php`).

- **Hero section:** attraction name, destination, description, category badge
- **Star rating display:** average + total review count
- **Reviews section:**
  - List of reviews where `attraction_id = {attraction->id}`
  - Each review: user avatar initials, username, star rating (1–5), comment, date
- **Add Review form** (shown to authenticated travelers who haven't reviewed this attraction yet):
  - Star picker (1–5, CSS radio buttons styled as stars)
  - Comment textarea
  - "Submit Review" → `POST /reviews` with `attraction_id` in payload

`ReviewController::store()` updated to accept optional `attraction_id` and set it on the created review.

---

## P12 — Alerts UI + 50% Budget Threshold

### Alerts page — `GET /alerts`

Pure Blade `resources/views/traveler/alerts/index.blade.php` (redesigned).

**Layout:**
- Header: "Alerts & Notifications" + "Mark All Read" button
- **Empty state** (if no alerts): illustration placeholder, "You're all caught up!" message
- **Notification cards** (one per alert):
  - Left: colored icon circle (orange for warning, red for danger, green for success)
  - Body: bold title + description text (e.g. "You've used 50% of your Bangkok trip budget")
  - Right: timestamp + unread indicator dot (blue)
  - On click → mark as read + optionally link to trip dashboard

### 50% threshold trigger

`ExpenseObserver::syncBudgetForExpense()` (existing) already fires on expense save. Add logic:
- After updating budget, calculate `pct = total_spent / budget_limit * 100`
- If `pct >= 50 && pct < 80` and no existing "50%" notification for this trip: create `Notification` with `type = 'budget_warning'`, message "You've used 50% of your {destination} trip budget."
- If `pct >= 80` and no existing "80%" notification: create with `type = 'budget_danger'`, message "Warning: You've used 80% of your {destination} trip budget."
- De-duplicate by checking `Notification::where('trip_id', $tripId)->where('type', $type)->exists()`.

### Sidebar badge

`NotificationBadge` Livewire component:
- `wire:poll.30s` — counts `auth()->user()->notifications()->where('is_read', false)->count()`
- Renders as a small red circle with count, overlaid on "Alerts" nav item
- Zero count = badge hidden

---

## P13 — Multi-Trip Hub

**Route:** `GET /trips` → Livewire `MultiTripHub` component (replaces existing `TripController::index`)

**Page layout:**

1. **Header row:** "Multi-Trip Hub" + "{N} trips total" + "Plan New Adventure" button (→ `/trips/plan`)

2. **3 aggregate stat tiles:**
   - Total Trips | Total Budget (sum) | Total Spent (sum)

3. **Search bar** (right-aligned): live-filters by destination name via `wire:model.debounce.300ms`

4. **"Active Trips" section** (heading + horizontal scroll or grid):
   - Trip cards:
     - Green gradient background (or image if available)
     - Status badge: "UPCOMING" (blue) / "ACTIVE" (green) / "COMPLETED" (grey)
     - Destination name (Playfair Display, white)
     - Date range + duration
     - "Spent ₱X / ₱Y budget" line
     - Progress bar (% of budget used)
     - "Compare" checkbox (top-left corner)
     - Action buttons: "Details" (→ `/trips/{trip}/dashboard`) + "Expenses"
   - "+ Plan New Adventure" card (dashed border, centered +)

5. **"Past Trips" section** (heading + list view):
   - Past trips (end_date < today): list rows, slightly desaturated/blurred (`filter: grayscale(30%) opacity(0.75)`)
   - Columns: destination, dates, budget, spent, status label "PAST", "View" button

6. **Trip Comparison modal:**
   - Shown when exactly 2 trips are checked and "Compare Now" button is clicked
   - Side-by-side layout: destination names as column headers
   - Metric table: Budget | Spent | Budget Used % | Daily Avg | Duration
   - Spending by Category comparison (two bar segments per category)
   - Close (×) button

**Active vs. past logic:**
- upcoming: `start_date > today`
- active: `start_date <= today && end_date >= today`
- past: `end_date < today`

---

## OCR API Configuration

`config/services.php` must include:
```php
'ocr' => [
    'key' => env('OCR_API_KEY'),
    'url' => 'https://api.ocr.space/parse/image',
],
```

`.env` must have: `OCR_API_KEY=K82825648388957`

The existing `OcrService.php` reads `config('services.ocr.key')` (verify and update if it uses a different key name).

---

## Implementation Order

| Plan | Scope | Dependencies |
|------|-------|-------------|
| P6 | Landing + Auth | None — pure Blade, standalone |
| P7 | Trip Wizard | Destinations table, KlookService |
| P8 | Per-Trip Dashboard + OCR Scan page | Expenses, OcrService, budgetra.css |
| P9 | Savings Goals UI | SavingsGoalController complete |
| P10 | Itinerary Calendar | ItineraryController complete |
| P11 | Attractions + Reviews | Migration: `attraction_id` on reviews |
| P12 | Alerts UI + threshold | ExpenseObserver, NotificationBadge |
| P13 | Multi-Trip Hub | All trip/budget data complete |

All plans share `public/css/budgetra.css` and the updated `layouts/app.blade.php`. P6 must be implemented first as it sets up the shared stylesheet and layout.

---

## Out of Scope

- Admin panel UI redesign (admin views keep existing `layouts.admin` + `style.css`)
- Mobile app or PWA
- Real-time WebSocket notifications (polling via `wire:poll` is sufficient)
- Payment processing or real booking integration (KlookService remains a stub estimator)
- Multi-language / i18n
