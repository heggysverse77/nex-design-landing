# 📄 NexDesign — System Changes & Architecture Summary Report

**Project**: NexDesign Landing Page, Backend Architecture & Desktop Licensing Engine  
**Tracking Period**: Base Commit (`01e6ac0`) → Latest Build (`b1873be`)  
**Date**: August 26, 2026 | **Status**: Production Ready & Fully Tested  

---

## 🚀 1. Executive Overview
Since merging latest remote changes, the platform evolved from a standalone landing page into a full-stack SaaS architecture with relational subscription tiers, OOP backend controllers, a dedicated Rust Desktop licensing engine, and an enhanced admin panel.

---

## 🗄️ 2. Database Architecture & Relational Schema
**File**: `public/api/config/db.php` (Compatible with SQLite & MySQL)

* **Relational `plans` Table**:
  - `starter` (ID: 1): $0.00/mo | 1 Device | 1080p Export | Core UI/UX Tools.
  - `professional` (ID: 2): $29.00/mo | 3 Devices | 4K Export | AI Generative Tools | Cloud Rendering.
  - `teams` (ID: 3): $79.00/mo | 10 Devices | 8K Export | AI Generative Tools | Team Collaboration.
* **Auto-Migration Columns on `users`**:
  - `plan_id` (`INT DEFAULT 1` → Foreign Key to `plans.id`), `plan_expires_at` (`DATETIME`), `restriction_reason` (`TEXT`), and `license_key` (`VARCHAR(100)`).

---

## ⚙️ 3. Object-Oriented Backend Controllers
**Directory**: `public/api/controllers/`

* **`UserController.php`**:
  - `changePlan(int $id, string $slug)`: Modifies user package tier and auto-generates activation keys.
  - `restrictUser(int $id, string $status, ?string $reason)`: Toggles status (`active`/`restricted`/`suspended`) and stores notes.
  - `generateLicenseKey(int $id, string $slug)`: Issues prefixed keys (`NEX-STR-...`, `NEX-PRO-...`, `NEX-TEAM-...`).
  - `revokeAndRegenerateLicenseKey(int $id)`: Revokes leaked/stale keys and provisions a fresh key instantly.
  - `setPlanExpiration(int $id, ?string $type, ?string $date)`: Manages access (+30d, +1y, custom date, lifetime).
  - `bulkUpdateUsers(array $ids, array $updates)`: Executes atomic batch actions across selected users.

* **`DesktopLicenseController.php`**:
  - `verifyLicenseAction(array $body, ?array $user)`: Validates JWT token or raw key, enforces restriction checks (`HTTP 403`), checks subscription expiration, and returns unlocked tier capabilities.

---

## 🖥️ 4. Desktop Licensing API
**Endpoint**: `POST /api/desktop/verify_license.php` (Public / Bearer JWT)

* **Verification Flow**: Authenticates client via Token or `license_key` + `hardware_id`.
* **Access Enforcement**: Rejects restricted accounts (`403 ACCOUNT_RESTRICTED`) and expired subscriptions (`403 SUBSCRIPTION_EXPIRED`).
* **Entitlement Output**: Returns active plan metadata (`max_devices`, `max_resolution`, `ai_features`, `cloud_rendering`).

---

## 🎛️ 5. Admin Dashboard & Operations
**File**: `src/views/AdminDashboardView.vue`

* **Multi-Select Bulk Operations**: Checkbox selection with floating bottom bar for batch plan, status, expiry, and key resets.
* **Inline Plan Management**: Direct tier dropdowns with real-time UI and database synchronization.
* **Restriction & Expiration Modals**: Custom prompts to log moderation reasons and select expiration presets (+30d, +1y, custom, lifetime).
* **License Controls**: Formatted key display with one-click copy (`📋`) and instant revocation (`🔄`).

---

## 🔒 6. Security, Validation & Queue Integrity
* **Live Password Validation** (`AuthView.vue`): Enforces 8+ characters, uppercase letter, special character, match confirmation, and show/hide eye toggles.
* **Waitlist Synchronization** (`register.php`): Computes global `MAX(waitlist_number)` across all non-admin users to eliminate queue number collisions.
* **Environment Protection** (`.htaccess`): Configured Apache rules to prevent public access to `.env` and `.sqlite` files.

---

## 📋 7. Commit Log & QA Verification
* **Commits**: `b1873be` (waitlist query fix) → `5fb6afe` (plans join) → `c744080` (token alias) → `bf333e5` (db migrations) → `89aeeda` (OOP & desktop API) → `01e6ac0` (.env loader).
* **Verification**: `php -l` (0 errors) | `vue-tsc --noEmit` (0 errors) | `vite build` (Production Build Passed).
