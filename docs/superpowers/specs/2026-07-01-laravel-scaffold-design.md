# Budgetra — Laravel Scaffold Design

**Date:** 2026-07-01
**Project:** Budgetra — Web-Based Smart Vacation Budget Planning and Expense Management System
**Migration:** Flat PHP → Laravel 11 + Blade + Livewire 3

---

## 1. Overview

Budgetra is a capstone web application for travel budget planning and expense management. The system supports two actor roles: **Traveler** and **Admin**. This document describes the full Laravel 11 scaffold — directory structure, models, migrations, routes, and view stubs — before any feature implementation begins.

**This scaffold pass does not implement authentication or any feature logic.** It establishes the skeleton so that features can be built incrementally on a solid foundation.

---

## 2. Stack

| Layer | Choice | Reason |
|---|---|---|
| Framework | Laravel 11 | Current release, slim default structure |
| Frontend | Blade + Livewire 3 | Server-rendered with reactive components; no separate JS build pipeline |
| CSS | Existing `style.css` ported as-is | Preserves design tokens (colors, fonts, card styles) from current build |
| Database | MySQL | Same as current project |
| Auth | None yet | Added in a future implementation phase |

---

## 3. Architecture

Two fully separated role zones:

| Zone | URL Prefix | Middleware (stub) | Actors |
|---|---|---|---|
| Traveler | `/` | `auth` | Travelers |
| Admin | `/admin` | `auth`, `admin` | Admins |

**Controllers are thin** — load data and return views only. Business logic lives in Service classes. Livewire components handle all interactive UI (tables, modals, filters, forms).

---

## 4. Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── UserController.php
│   │   │   ├── DestinationController.php
│   │   │   ├── AttractionController.php
│   │   │   ├── ReviewController.php
│   │   │   ├── IntegrationController.php
│   │   │   └── ReportController.php
│   │   └── Traveler/
│   │       ├── DashboardController.php
│   │       ├── TripController.php
│   │       ├── ExpenseController.php
│   │       ├── SavingsController.php
│   │       ├── ItineraryController.php
│   │       ├── AttractionController.php
│   │       ├── AlertController.php
│   │       └── ReportController.php
│   └── Middleware/
│       ├── AdminMiddleware.php
│       └── AuthMiddleware.php
├── Livewire/
│   ├── Admin/
│   │   ├── UserTable.php
│   │   ├── DestinationTable.php
│   │   ├── AttractionTable.php
│   │   ├── ReviewTable.php
│   │   ├── OcrMonitor.php
│   │   └── KlookConfig.php
│   └── Traveler/
│       ├── TripPlanner.php
│       ├── ExpenseTracker.php
│       ├── SavingsGoal.php
│       ├── ItineraryManager.php
│       ├── AttractionBrowser.php
│       └── BudgetComparison.php
├── Models/
│   ├── User.php
│   ├── Trip.php
│   ├── TripBudget.php
│   ├── Expense.php
│   ├── SavingsGoal.php
│   ├── Notification.php
│   ├── Itinerary.php
│   ├── DestinationCost.php
│   ├── Attraction.php
│   ├── Review.php
│   ├── OcrLog.php
│   └── AppConfig.php
└── Services/
    ├── BudgetService.php
    ├── OcrService.php
    ├── KlookService.php
    └── ReportService.php

resources/views/
├── layouts/
│   ├── app.blade.php           ← traveler shell
│   ├── admin.blade.php         ← admin shell
│   └── guest.blade.php         ← future auth pages
├── components/
│   ├── sidebar.blade.php
│   ├── admin-sidebar.blade.php
│   ├── stat-card.blade.php
│   └── modal.blade.php
├── admin/
│   ├── dashboard.blade.php
│   ├── users/index.blade.php
│   ├── destinations/index.blade.php
│   ├── attractions/index.blade.php
│   ├── reviews/index.blade.php
│   ├── integrations/index.blade.php
│   └── reports/index.blade.php
└── traveler/
    ├── dashboard/index.blade.php
    ├── trips/
    │   ├── index.blade.php
    │   ├── type.blade.php
    │   ├── create.blade.php
    │   └── estimate.blade.php
    ├── expenses/
    │   ├── index.blade.php
    │   └── create.blade.php
    ├── savings/
    │   ├── index.blade.php
    │   └── create.blade.php
    ├── itinerary/index.blade.php
    ├── attractions/index.blade.php
    ├── alerts/index.blade.php
    └── reports/index.blade.php

routes/
├── web.php       ← traveler routes
└── admin.php     ← admin routes (registered in bootstrap/app.php)

database/
├── migrations/   ← one file per table (see Section 5)
├── seeders/
│   ├── DatabaseSeeder.php
│   ├── AdminUserSeeder.php
│   └── DestinationSeeder.php
└── factories/
    ├── UserFactory.php
    └── TripFactory.php

public/
└── css/style.css     ← ported from existing project

storage/app/public/
├── receipts/
└── attraction-images/
```

---

## 5. Models & Migrations

| Migration file | Table | Notes |
|---|---|---|
| `create_users_table` | `users` | + `role` enum (`traveler`, `admin`), + `profile_photo` |
| `create_trips_table` | `trips` | Matches current schema |
| `create_trip_budgets_table` | `trip_budgets` | Matches current schema |
| `create_expenses_table` | `expenses` | `receipt_path` replaces `receipt_image` |
| `create_savings_goals_table` | `savings_goals` | Matches current schema |
| `create_notifications_table` | `notifications` | Matches current schema |
| `create_itinerary_table` | `itinerary` | Matches current schema |
| `create_destination_costs_table` | `destination_costs` | cost_level, multiplier, category, image_url |
| `create_attractions_table` | `attractions` | name, destination, category (CSV), image, rating, description |
| `create_reviews_table` | `reviews` | user_id, destination, rating, body, status |
| `create_ocr_logs_table` | `ocr_logs` | user_id, filename, status, confidence, error_message |
| `create_app_config_table` | `app_config` | config_key (unique), config_value |
| `create_password_reset_tokens_table` | `password_reset_tokens` | Laravel built-in pattern |

**Each model includes:** `$fillable`, `$casts`, and all relationships.

**Key relationships:**
- `User` hasMany `Trip`, `Expense`, `SavingsGoal`, `Notification`, `Review`, `OcrLog`
- `Trip` hasMany `TripBudget`, `Expense`, `Itinerary`, `SavingsGoal`
- `Trip` belongsTo `User`
- `Expense` belongsTo `Trip`, `User`

---

## 6. Routes

### `routes/web.php` — Traveler zone

```
GET    /                          → redirect to /dashboard
GET    /dashboard                 → Traveler\DashboardController
GET    /trips                     → Traveler\TripController@index
GET    /trips/type                → Traveler\TripController@type
GET    /trips/create              → Traveler\TripController@create
POST   /trips                     → Traveler\TripController@store
GET    /trips/{trip}/estimate     → Traveler\TripController@estimate
GET    /expenses                  → Traveler\ExpenseController@index
GET    /expenses/create           → Traveler\ExpenseController@create
POST   /expenses                  → Traveler\ExpenseController@store
DELETE /expenses/{expense}        → Traveler\ExpenseController@destroy
GET    /savings                   → Traveler\SavingsController@index
POST   /savings                   → Traveler\SavingsController@store
POST   /savings/{goal}/add        → Traveler\SavingsController@addAmount
GET    /itinerary                 → Traveler\ItineraryController@index
POST   /itinerary                 → Traveler\ItineraryController@store
DELETE /itinerary/{item}          → Traveler\ItineraryController@destroy
GET    /attractions               → Traveler\AttractionController@index
GET    /alerts                    → Traveler\AlertController@index
GET    /reports                   → Traveler\ReportController@index
GET    /reports/download          → Traveler\ReportController@download
```

### `routes/admin.php` — Admin zone (prefix `/admin`)

```
GET    /admin                         → Admin\DashboardController
GET    /admin/users                   → Admin\UserController@index
DELETE /admin/users/{user}            → Admin\UserController@destroy
GET    /admin/destinations            → Admin\DestinationController@index
POST   /admin/destinations            → Admin\DestinationController@store
PUT    /admin/destinations/{dest}     → Admin\DestinationController@update
DELETE /admin/destinations/{dest}     → Admin\DestinationController@destroy
GET    /admin/attractions             → Admin\AttractionController@index
POST   /admin/attractions             → Admin\AttractionController@store
PUT    /admin/attractions/{attr}      → Admin\AttractionController@update
DELETE /admin/attractions/{attr}      → Admin\AttractionController@destroy
GET    /admin/reviews                 → Admin\ReviewController@index
PATCH  /admin/reviews/{review}        → Admin\ReviewController@updateStatus
DELETE /admin/reviews/{review}        → Admin\ReviewController@destroy
GET    /admin/integrations            → Admin\IntegrationController@index
POST   /admin/integrations/klook      → Admin\IntegrationController@saveKlook
POST   /admin/integrations/test       → Admin\IntegrationController@testKlook
GET    /admin/reports                 → Admin\ReportController@index
GET    /admin/reports/download        → Admin\ReportController@download
```

---

## 7. Layouts & View Stubs

**Three layouts:**
- `layouts/app.blade.php` — Traveler shell. Includes `<x-sidebar>`, loads `style.css` + Font Awesome + `@livewireStyles` / `@livewireScripts`.
- `layouts/admin.blade.php` — Admin shell. Includes `<x-admin-sidebar>`. Same asset loading.
- `layouts/guest.blade.php` — Bare centered layout for future auth pages.

**Four Blade components:**
- `<x-sidebar>` — Traveler sidebar, `$active` prop for highlight
- `<x-admin-sidebar>` — Admin sidebar, `$active` prop for highlight
- `<x-stat-card>` — Icon + number + label card used in dashboards
- `<x-modal>` — Reusable modal backdrop wired to Livewire

**All page views are stubs** — `@extends` + `@section('content')` with a placeholder `<h1>` only. No real UI until implementation phase.

**All Livewire components are stubs** — empty `render()` returning their paired blade view.

---

## 8. Seeders

- `AdminUserSeeder` — creates one admin user (`admin@budgetra.com` / `password`)
- `DestinationSeeder` — seeds the Philippine destination list from the existing `destinations.php` config

---

## 9. Out of Scope for This Scaffold

The following are **not** part of this scaffold and will be implemented in subsequent phases:

- Authentication (login, register, password reset)
- Any controller logic beyond returning stub views
- Any Livewire component logic
- PDF report generation
- OCR integration
- Klook API calls
- Real-time budget calculations
- File upload handling
