# PHPAuth Integration Design

**Date:** 2026-03-21
**Project:** TicketSG (INF1005_Statik)
**Status:** Approved

---

## Goal

Integrate [PHPAuth](https://github.com/PHPAuth/PHPAuth) into the existing hand-rolled auth system to gain:
- Server-side session token storage (`phpauth_sessions` table)
- Brute-force / rate-limiting via `phpauth_attempts`
- Proper session lifecycle management (invalidation on logout)
- CSRF protection wired to login and register forms

Email verification is **disabled** — accounts activate immediately on registration.
Multiple concurrent sessions per user are permitted (PHPAuth default behaviour).

---

## Constraints

| Constraint | Decision |
|---|---|
| No Composer in project yet | Add `composer.json`; run `composer install` locally and on cloud server |
| PHPAuth requires PDO; site uses MySQLi | Create a second PDO-only connection (`inc/phpauth_db.inc.php`) used exclusively by PHPAuth; keep MySQLi for everything else |
| PHPAuth authenticates by email; login supports username or email | Detect input type: contains `@` → treat as email; otherwise → MySQLi lookup of email by username first |
| `cart_items` and `order_items` FK to `members.id` | Never touch `members` PK. Add nullable `phpauth_uid VARCHAR(255)` to link rows; FKs untouched |
| Session vars `user_id / username / name / role` used across ~20 pages | After PHPAuth authenticates, set those same `$_SESSION` keys from the `members` row — zero changes to any other page |
| Existing bcrypt hashes (`$2a$12$…`) in members | PHPAuth uses `password_verify()` internally — existing hashes are compatible; no password resets needed |
| CSRF functions in `auth.inc.php` are unwired | Wire `generate_csrf_token()` / `verify_csrf_token()` onto login and register forms |
| `members.password` is NOT NULL | New registrations insert sentinel value `'!'` (a known-invalid hash); PHPAuth owns the authoritative password in `phpauth_users`. The `members.password` column is a tombstone for PHPAuth-managed accounts and must never be passed to `password_verify()`. |

---

## Architecture

PHPAuth sits as an auth layer in front of `members`, not a replacement.

```
Browser
  │
  ├─ process_login.php      ← REWRITTEN
  ├─ process_register.php   ← REWRITTEN
  └─ logout.php             ← REWRITTEN
        │
        ├─ PHPAuth (via PDO)  ─── phpauth_* tables  (rate-limit, sessions, attempts)
        │
        └─ MySQLi             ─── members table      (app data, FKs, session vars)
                                        │
                              cart_items, order_items (unchanged — FK to members.id)

All other pages (~20) ──── inc/auth.inc.php ──── $_SESSION['user_id/username/name/role']
                                                    (populated from members, unchanged)
```

**Key invariants:**
- `members.id` (integer PK) never changes — all FKs stay intact
- `$_SESSION` keys stay identical — zero changes to any other page
- MySQLi used for all app queries; PDO used exclusively by PHPAuth
- `members.phpauth_uid` links the two tables; used for administrative queries and future reference only (not used by any runtime app logic)
- `members.password` holds `'!'` sentinel for accounts registered via PHPAuth; never passed to `password_verify()`

---

## Database Changes (`setup_phpauth.sql`)

Run once, after `setup.sql`.

**1. Extend `members`:**
```sql
ALTER TABLE members ADD COLUMN phpauth_uid VARCHAR(255) NULL AFTER role;
```

**2. Create PHPAuth tables** — verbatim from `vendor/phpauth/phpauth/Sql/mysql.sql`:
`phpauth_config`, `phpauth_users`, `phpauth_attempts`, `phpauth_sessions`,
`phpauth_requests`, `phpauth_emails_banned`

**3. Disable email verification in PHPAuth config:**
```sql
UPDATE phpauth_config SET value = '0' WHERE setting = 'verify_email';
```
This ensures new accounts activate immediately (`isactive = 1`) without a confirmation email. If the row does not exist after the SQL import, insert it:
```sql
INSERT IGNORE INTO phpauth_config (setting, value) VALUES ('verify_email', '0');
```

**4. Add `username` to `phpauth_users`:**
```sql
ALTER TABLE phpauth_users ADD COLUMN username VARCHAR(20) NULL AFTER email;
```

**5. Migrate existing members and back-fill the link:**
```sql
INSERT INTO phpauth_users (email, password, isactive, dt, username)
SELECT email, password, 1, created_at, username FROM members;

UPDATE members m
JOIN phpauth_users pu ON m.email = pu.email
SET m.phpauth_uid = pu.uid;

-- Invalidate members.password for all migrated accounts.
-- PHPAuth now owns the authoritative password in phpauth_users.
-- The '!' sentinel is an invalid bcrypt hash that can never be verified.
UPDATE members SET password = '!' WHERE phpauth_uid IS NOT NULL;
```

---

## New Files

### `composer.json`
```json
{
    "require": {
        "phpauth/phpauth": "^1.0"
    }
}
```
`vendor/` added to `.gitignore`.

### `inc/phpauth_db.inc.php`
PDO connection using the same credential-loading pattern as `inc/db.inc.php`:
1. Try `/var/www/private/db-config.ini` (cloud server)
2. Fall back to `.env` at project root (local dev)
3. After each `parse_ini_file()` call, check the return value — if it is `false`, `error_log()` the failure and `die("A database error occurred. Please try again later.")` before attempting array access. This is a safety improvement over `inc/db.inc.php` which does not check the fallback parse result.
4. Return `PDO` with `charset=utf8mb4`, `ERRMODE_EXCEPTION`
5. On PDO connection exception: `error_log()` the exception message then `die("A database error occurred. Please try again later.")`

---

## Rewritten Auth Handlers

### `process_login.php`
1. Verify CSRF token — redirect to `/login.php?error=Invalid+request` on failure
2. Validate that username/email and password fields are present
3. Detect input type: contains `@` → treat as email directly; otherwise → MySQLi `SELECT email FROM members WHERE username = ?`. **Note:** usernames containing `@` are prohibited at registration (see `process_register.php` step 2) to prevent ambiguous detection.
4. If no member row found → redirect with "Invalid credentials"
5. Instantiate PHPAuth with PDO connection
6. `$result = $auth->login($email, $password)`
7. If `$result['error']` → redirect with `$result['message']` (includes PHPAuth rate-limit messages)
8. On success:
   - `session_regenerate_id(true)`
   - Store PHPAuth session hash: `$_SESSION['phpauth_session_hash'] = $result['hash']`
   - MySQLi `SELECT * FROM members WHERE email = ?`
   - Set `$_SESSION['user_id']`, `['username']`, `['name']`, `['role']` only — **do not read or use `$row['password']`**; it may be `'!'` for PHPAuth-managed accounts
   - Redirect to `/`

### `process_register.php`
1. Verify CSRF token
2. Validate name, username, email, password (same rules as current). Add one new rule: **username must not contain `@`** — reject with error if it does. This preserves the login input-type detection invariant.
3. MySQLi: check username uniqueness in `members` — redirect with error if taken (PHPAuth only checks email uniqueness, not username)
4. MySQLi: `INSERT INTO members (name, username, email, password, role) VALUES (?, ?, ?, '!', 'user')` — insert into `members` **first**
5. If MySQLi INSERT fails (e.g. duplicate email) → redirect with error; no PHPAuth call made
6. Instantiate PHPAuth
7. `$result = $auth->register($email, $password, $password)` — PHPAuth does not accept extra columns via `register()`; username is populated separately
8. If `$result['error']`:
   - **Best-effort PHPAuth cleanup:** PDO `DELETE FROM phpauth_users WHERE email = ?` — PHPAuth may have partially written a row before returning the error. Log if this DELETE fails but do not die; a missing row is acceptable.
   - **members rollback:** MySQLi `DELETE FROM members WHERE email = ?`. Check that affected_rows = 1 — if not, `error_log()` the failure and `die()` with a generic server error rather than silently leaving an orphaned row.
   - Redirect with the PHPAuth error message.
9. On success:
   - `UPDATE phpauth_users SET username = ? WHERE email = ?` (via PDO) — populate the `username` column added to `phpauth_users`
   - `UPDATE members SET phpauth_uid = ? WHERE email = ?` (via MySQLi) — back-fill the link
   - Redirect to `/login.php`

**Registration order rationale:** `members` is inserted first so that a MySQLi failure (e.g. duplicate username/email) is caught before any PHPAuth state is created. If PHPAuth subsequently fails, the `members` row is deleted — a clean rollback. This avoids orphaned `phpauth_users` rows with no corresponding `members` entry.

### `logout.php`
1. `require` `inc/auth.inc.php`, `vendor/autoload.php`, `inc/phpauth_db.inc.php`
2. Instantiate PHPAuth
3. `$hash = $_SESSION['phpauth_session_hash'] ?? null`
4. If `$hash` is set: `$auth->logout($hash)` — removes the `phpauth_sessions` row using the hash stored at login
5. `session_unset(); session_destroy()`
6. Redirect to `/login.php?logout=1`

**Note:** `session_regenerate_id()` is not called after `session_destroy()` — doing so after destroy is a no-op and may emit a warning. The session is simply unset and destroyed.

**Migration transition window:** Users who logged in before PHPAuth was deployed will have no `phpauth_session_hash` in their session. The `?? null` null-coalescing in step 3 handles this — `$auth->logout($hash)` is only called when `$hash !== null` (step 4). Such users are logged out cleanly via `session_destroy()`; no PHPAuth session record exists for them to remove.

---

## CSRF Wiring

`generate_csrf_token()` and `verify_csrf_token()` already exist in `inc/auth.inc.php`.

**`login.php`** and **`register.php`** — add inside `<form>`:
```html
<input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
```

**`login.php`** — update the username field label:
```html
<label for="username" class="form-label">Username or Email</label>
```

**`process_login.php`** and **`process_register.php`** — first check:
```php
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header("Location: /login.php?error=Invalid+request");
    exit;
}
```

**CSRF token rotation:** The existing `generate_csrf_token()` in `auth.inc.php` does not rotate the token after verification — the same token is valid for the full session lifetime. This is a known limitation. It is accepted for now given the low sensitivity of the login and register forms (no state-changing operations that can be replayed without credentials). A future improvement would be to unset `$_SESSION['csrf_token']` inside `verify_csrf_token()` after a successful check.

---

## File Change Summary

| File | Action |
|---|---|
| `composer.json` | CREATE |
| `.gitignore` | EDIT — add `vendor/` |
| `inc/phpauth_db.inc.php` | CREATE |
| `setup_phpauth.sql` | CREATE |
| `process_login.php` | REWRITE |
| `process_register.php` | REWRITE |
| `logout.php` | REWRITE |
| `login.php` | EDIT — add CSRF hidden field; update label to "Username or Email" |
| `register.php` | EDIT — add CSRF hidden field |

**Unchanged:** `inc/auth.inc.php`, `inc/db.inc.php`, all cart/checkout/order pages, all admin pages, all view pages.

---

## Verification Checklist

1. `composer install` — confirm `vendor/phpauth/phpauth/` exists
2. Run `setup_phpauth.sql` — confirm PHPAuth tables created; `members.phpauth_uid` populated for all existing rows; `members.password = '!'` for all existing rows; confirm `SELECT value FROM phpauth_config WHERE setting = 'verify_email'` returns `'0'`
3. **Existing user login by username** — log in with `billy` / `Admin@1234` — session vars set (`role='user'`), land on `/`
4. **Existing user login by email** — log in with `billy@ticketsg.com` / `Admin@1234` — same result
5. **New registration** — row appears in both `phpauth_users` and `members`; `members.phpauth_uid` not null; `members.password = '!'`; `phpauth_users.username` populated; `phpauth_users.isactive = 1`
6. **Bad credentials** — wrong password → `phpauth_attempts` row inserted
7. **Brute-force lockout** — 5 rapid failed logins → PHPAuth rate-limit error message returned
8. **Logout** — `phpauth_sessions` row removed (check via DB); PHP session destroyed; redirected to `/login.php?logout=1`
9. **CSRF** — submit login form without token (e.g. via curl) → rejected with redirect to `/login.php?error=Invalid+request`
10. **Admin login** — log in as `admin` / `Admin@1234` → `require_admin()` passes; admin bar renders; `role='admin'` in session
11. **Cart + checkout** — add to cart, proceed to PayPal — all `member_id` FKs intact; no FK errors
12. **Duplicate username on register** — attempt to register with an existing username → rejected before PHPAuth is called; no orphaned `phpauth_users` row
