# 📝 Changelog — AutoVault Car Showroom

All changes are recorded below in reverse chronological order.

---

## [1.1.0] — 2026-05-12

### Changed — Role-Based Access Control for `user` role

**User permissions now restricted to:**
- ✅ View the full car inventory (read-only listing)
- ✅ Update a car's **status** (`available` / `sold` / `reserved`)
- ✅ Edit a car's **description / notes** field

**User permissions explicitly removed:**
- ❌ Add new cars
- ❌ Edit any other car fields (brand, model, plate, color, year, mileage, price, fuel type)
- ❌ Delete cars

#### `add_car.php`
- `requireLogin()` → `requireAdmin()` — redirects users to dashboard

#### `layout.php`
- "Add Car" nav link moved inside the `isAdmin()` block — invisible to regular users

#### `edit_car.php` *(full rewrite)*
- **Admin path:** unchanged full form (all 10 fields)
- **User path:** new restricted layout:
  - Read-only info panel showing brand, model, plate, color, year, price
  - Radio-button status selector (Available / Sold / Reserved) with visual highlight
  - Description textarea
  - POST handler updates **only** `status` and `description` columns — server-enforced whitelist

#### `cars.php`
- Admin rows: `✏️ Edit` + `🗑️` delete buttons (unchanged)
- User rows: `🏷️ Status` button only (links to `edit_car.php` restricted form)
- "＋ Add Car" page-header button hidden from users
- Empty-state "Add one →" link hidden from users

#### `index.php`
- "＋ Add Car" dashboard button wrapped in `isAdmin()` guard
- Empty-inventory "Add one now →" link wrapped in `isAdmin()` guard

---

## [1.0.0] — 2026-05-12

### Added — Database

- **`ALTER TABLE cars`** — Extended with 8 new columns:
  - `brand VARCHAR(80)` — manufacturer name
  - `year YEAR` — model year
  - `mileage INT UNSIGNED` — odometer reading in km
  - `price DECIMAL(12,2)` — listing price in EUR
  - `fuel_type ENUM('petrol','diesel','electric','hybrid','lpg','other')`
  - `status ENUM('available','sold','reserved')` DEFAULT `available`
  - `description TEXT` — free-text notes
  - `updated_at TIMESTAMP` — auto-updated on every row change
- **`CREATE TABLE users`** — Authentication table:
  - `id`, `username UNIQUE`, `password_hash`, `role ENUM('admin','user')`, `created_at`
- **`CREATE TABLE backup_log`** — Audit trail for backup events:
  - `id`, `filename`, `created_by`, `created_at`
- **Seeded** default admin account: `admin` / `Admin@1234` (bcrypt hashed)

---

### Added — PHP Files

#### `config.php` *(replaces `app_header.php`)*
- PDO singleton with `ERRMODE_EXCEPTION` and `FETCH_ASSOC` defaults
- `APP_NAME`, `APP_VERSION`, `BACKUP_DIR` constants
- `isLoggedIn()`, `isAdmin()`, `requireLogin()`, `requireAdmin()` auth guards
- `csrfToken()`, `verifyCsrf()` CSRF protection utilities
- `flash()`, `renderFlash()` flash message system
- `h()` output escaping helper, `postStr()` input trimming helper
- Secure session cookie params: `httponly=true`, `samesite=Strict`

#### `layout.php`
- `renderHeader(string $pageTitle)` — Outputs full HTML `<head>`, sticky glassmorphism header, responsive nav, user badge with role tag
- `renderFooter()` — Site footer with version and JS script tag
- `buildNav()` — Dynamic nav links: role-aware (Backup/Users shown to admins only)

#### `login.php`
- Bcrypt password verification via `password_verify()`
- CSRF token on form
- Session fixation prevention: `session_regenerate_id(true)` on successful login
- Generic error message (prevents username enumeration)
- Password visibility toggle (JS)

#### `logout.php`
- Clears `$_SESSION`, expires session cookie, calls `session_destroy()`

#### `index.php` *(Dashboard)*
- Live stats: total, available, sold, reserved cars; total & average value
- Recent 5 additions table with color swatches and status badges
- Fuel-type breakdown sidebar
- Admin shortcut to backup page

#### `cars.php` *(Inventory)*
- Full car listing with server-side WHERE clause (search by brand/model/plate, filter by status & fuel)
- Sortable columns: brand, model, plate, year, price, status, created_at
- Color swatch cells
- Admin-only delete buttons behind confirm modal
- Client-side live search via `#carSearch` input

#### `add_car.php`
- Full form: brand, model, plate number, color picker, year, mileage, price, fuel type, status, description
- Server-side validation: required fields, year range, allowed ENUMs, hex color regex
- Duplicate plate detection via `PDOException` error code 1062
- Live color preview swatch (JS)
- CSRF protection

#### `edit_car.php`
- Same form as `add_car.php` pre-filled from DB record
- Fetches car by `?id=` param; 404-redirects if not found
- All same validation and duplicate plate detection
- CSRF protection

#### `delete_car.php`
- POST-only (GET redirects away)
- Admin guard via `requireAdmin()`
- CSRF verification
- Fetches car name for descriptive flash message before deleting

#### `backup.php`
- `action=backup` — runs `mysqldump` via `exec()`, credentials passed via `MYSQL_PWD` env var (avoids shell history exposure)
- `action=restore` — runs `mysql` client to import chosen `.sql` file; filename validated via regex `^backup_[\w\-]+\.sql$`
- `action=delete_backup` — removes `.sql` file and clears its DB log entry
- Auto-creates `BACKUP_DIR` with `0750` permissions on first visit
- Auto-writes `backups/.htaccess` with `Deny from all`
- Logs every backup creation to `backup_log` table
- Shows file size and modification time for each backup
- Two-column layout: backup files + audit log

#### `users.php`
- Admin-only user list with avatar initials, role tags
- Self-delete guard (your own row shows 🔒 disabled button)
- Client-side live search via `#userSearch`

#### `add_user.php`
- Username regex validation: `[a-zA-Z0-9_\-\.]{3,64}`
- Password minimum 8 chars + confirmation match
- Bcrypt hash via `password_hash($pw, PASSWORD_BCRYPT)`
- Role selector: `user` / `admin`
- Duplicate username check

#### `edit_user.php`
- Optional password change (blank = keep current)
- Self-demotion guard: logged-in admin cannot change own role away from admin
- Updates `$_SESSION['username']` and `['role']` immediately when editing own account

#### `delete_user.php`
- POST-only, admin guard, CSRF check
- Self-delete prevention

---

### Added — Frontend

#### `assets/css/style.css`
- Design system tokens: `--bg-base`, `--accent`, `--danger`, `--success`, `--warning`, etc.
- Dark glassmorphism theme (`rgba` backgrounds + `backdrop-filter: blur`)
- Radial gradient hero glow on `<body>`
- Components: `.card`, `.btn` (primary/danger/ghost/warning/info/sm), `.alert` (success/danger/warning/info), `.badge` (available/sold/reserved), `.stat-card`, `.backup-item`, `.dialog-overlay`/`.dialog-box`
- Typography: Google Fonts Outfit (300–700)
- Responsive: `@media(max-width:768px)` grid collapses to single column
- Micro-animations: `slideIn` for alerts, `scaleIn` for dialog, `fadeIn` for overlay, hover `translateY(-2px)` on stat cards

#### `assets/js/main.js`
- `openConfirm(msg, formId)` / `closeConfirm()` — modal confirm dialog
- `initTableSearch(inputId, tableId)` — live client-side row filter
- `initAlerts()` — auto-dismiss flash alerts after 4.5 s
- `initColorPreview()` — syncs color picker to swatch preview element
- `togglePassword(btnId, inputId)` — show/hide password toggle
- Escape key and click-outside to close dialog

---

### Modified — Legacy Files

#### `index.html`
- Changed from empty placeholder to meta-refresh redirect → `index.php`

#### `save_car.php`
- Added redirect to `cars.php` at top (legacy compatibility)

#### `app_header.php`
- Added note pointing to `config.php` as its replacement

---

### Added — Security

#### `.htaccess` (project root)
- Disabled directory listing (`Options -Indexes`)
- Blocked direct access to `.php` config/helper files from browser
- `X-Frame-Options`, `X-Content-Type-Options` security headers

#### `backups/.htaccess`
- `Deny from all` — prevents HTTP access to raw SQL dump files

---

## [0.1.0] — 2026-05-05 *(initial base)*

- `app_header.php` — raw PDO connection
- `save_car.php` — single-file insert + show form (no auth, no edit/delete)
- `index.html` — empty placeholder
- `cars` MySQL table with 5 basic columns
