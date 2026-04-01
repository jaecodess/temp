# PHPAuth Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Wire PHPAuth into the existing auth flow to gain server-side session tokens, brute-force rate-limiting, and proper session lifecycle management — while keeping every other page unchanged.

**Architecture:** PHPAuth runs alongside the existing `members` table via a dedicated PDO connection. Only three handler files (`process_login.php`, `process_register.php`, `logout.php`) are rewritten; all session keys and FK relationships remain identical. A `phpauth_uid` column links `members` rows to `phpauth_users` rows.

**Tech Stack:** PHP 8+, MySQLi (existing), PDO (PHPAuth only), PHPAuth `^1.0` via Composer, MySQL/MariaDB

**Spec:** `docs/superpowers/specs/2026-03-21-phpauth-integration-design.md`

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `composer.json` | CREATE | Declare PHPAuth dependency |
| `.gitignore` | EDIT | Exclude `vendor/` |
| `vendor/` | GENERATED | PHPAuth library + autoloader |
| `inc/phpauth_db.inc.php` | CREATE | PDO connection for PHPAuth (mirrors credential pattern in `inc/db.inc.php`) |
| `setup_phpauth.sql` | CREATE | PHPAuth tables + `members` migration (run once after `setup.sql`) |
| `process_login.php` | REWRITE | Accept username or email → PHPAuth login → set `$_SESSION` from `members` |
| `process_register.php` | REWRITE | Validate → `members` INSERT first → PHPAuth register → rollback on failure |
| `logout.php` | REWRITE | PHPAuth logout by session hash → `session_destroy()` |
| `login.php` | EDIT | Add CSRF hidden field; update label to "Username or Email" |
| `register.php` | EDIT | Add CSRF hidden field |

**Unchanged:** `inc/auth.inc.php`, `inc/db.inc.php`, all cart/checkout/order/admin/view pages.

---

## Task 1: Composer + PHPAuth Install

**Files:**
- Create: `composer.json`
- Edit: `.gitignore`

> **Note:** This project has no automated test suite. Verification steps are manual database/browser checks.

- [ ] **Step 1.1: Create `composer.json`**

```json
{
    "require": {
        "phpauth/phpauth": "^1.0"
    }
}
```

Save to project root as `composer.json`.

- [ ] **Step 1.2: Add `vendor/` to `.gitignore`**

Open `.gitignore` and confirm it already contains the `vendor/` entry added during brainstorming. If not, add:

```
### Composer dependencies ###
vendor/
```

- [ ] **Step 1.3: Run `composer install`**

```bash
composer install
```

Expected output: Composer resolves and installs `phpauth/phpauth` and its dependencies into `vendor/`.

- [ ] **Step 1.4: Verify install**

```bash
ls vendor/phpauth/phpauth/
```

Expected: directory exists containing `Auth.php`, `Config.php`, `Sql/mysql.sql`.

- [ ] **Step 1.5: Commit**

```bash
git add composer.json composer.lock .gitignore
git commit -m "chore: add Composer and PHPAuth dependency"
```

---

## Task 2: PDO Connection for PHPAuth

**Files:**
- Create: `inc/phpauth_db.inc.php`

- [ ] **Step 2.1: Create `inc/phpauth_db.inc.php`**

```php
<?php
/*
 * PDO connection for PHPAuth.
 * Reads credentials from /var/www/private/db-config.ini on the server.
 * Falls back to .env at the project root for local development.
 * Used exclusively by PHPAuth — all other queries use inc/db.inc.php (MySQLi).
 */
function getPHPAuthDbConnection(): PDO
{
    $config = @parse_ini_file('/var/www/private/db-config.ini');

    if ($config === false) {
        $config = parse_ini_file(__DIR__ . '/../.env');
        if ($config === false) {
            error_log("PHPAuth DB: failed to load credentials from .env");
            die("A database error occurred. Please try again later.");
        }
    }

    $host   = $config['servername'];
    $user   = $config['username'];
    $pass   = $config['password'];
    $dbname = $config['dbname'];
    $port   = isset($config['port']) ? intval($config['port']) : 3306;

    try {
        return new PDO(
            "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $e) {
        error_log("PHPAuth DB connection failed: " . $e->getMessage());
        die("A database error occurred. Please try again later.");
    }
}
```

- [ ] **Step 2.2: Verify connection works**

Create a temporary `test_pdo.php` in the project root:

```php
<?php
require_once 'inc/phpauth_db.inc.php';
$pdo = getPHPAuthDbConnection();
echo "PDO connection OK\n";
```

Run it: `php test_pdo.php` (or open via browser if PHP CLI is unavailable).

Expected: `PDO connection OK` with no errors.

Delete `test_pdo.php` after verifying.

- [ ] **Step 2.3: Commit**

```bash
git add inc/phpauth_db.inc.php
git commit -m "feat: add PDO connection helper for PHPAuth"
```

---

## Task 3: Database Migration

**Files:**
- Create: `setup_phpauth.sql`

- [ ] **Step 3.1: Build `setup_phpauth.sql` programmatically**

Run this shell command from the project root to compose the full migration file:

```bash
cat > setup_phpauth.sql << 'HEADER'
USE statik;

-- 1. Extend members with a link column to phpauth_users
ALTER TABLE members ADD COLUMN phpauth_uid VARCHAR(255) NULL AFTER role;

-- 2. PHPAuth's own tables
HEADER

cat vendor/phpauth/phpauth/Sql/mysql.sql >> setup_phpauth.sql

cat >> setup_phpauth.sql << 'FOOTER'

-- 3. Add username column to phpauth_users (PHPAuth supports extra columns)
ALTER TABLE phpauth_users ADD COLUMN username VARCHAR(20) NULL AFTER email;

-- 4. Disable email verification — accounts activate immediately
UPDATE phpauth_config SET value = '0' WHERE setting = 'verify_email';
INSERT IGNORE INTO phpauth_config (setting, value) VALUES ('verify_email', '0');

-- 5. Migrate existing members into phpauth_users
INSERT INTO phpauth_users (email, password, isactive, dt, username)
SELECT email, password, 1, created_at, username FROM members;

-- 6. Back-fill members.phpauth_uid from the newly inserted phpauth_users rows
UPDATE members m
JOIN phpauth_users pu ON m.email = pu.email
SET m.phpauth_uid = pu.uid;

-- 7. Invalidate members.password for all migrated accounts.
-- PHPAuth now owns the authoritative password in phpauth_users.
-- '!' is an invalid bcrypt hash that can never be verified by password_verify().
UPDATE members SET password = '!' WHERE phpauth_uid IS NOT NULL;
FOOTER
```

Verify the file contains all 6 `CREATE TABLE` statements from PHPAuth:

```bash
grep -c "CREATE TABLE" setup_phpauth.sql
# Expected: 6
```

- [ ] **Step 3.3: Run the migration**

```bash
mysql -u inf1005-sqldev -p -h 127.0.0.1 -P 3307 statik < setup_phpauth.sql
```

Expected: no errors.

- [ ] **Step 3.4: Verify migration**

Run these queries in your MySQL client:

```sql
-- PHPAuth tables exist
SHOW TABLES LIKE 'phpauth_%';
-- Expected: 6 tables (phpauth_attempts, phpauth_config, phpauth_emails_banned,
--           phpauth_requests, phpauth_sessions, phpauth_users)

-- members has phpauth_uid column and all rows are linked — zero-assertion check
SELECT COUNT(*) FROM members WHERE password != '!' OR phpauth_uid IS NULL;
-- Expected: 0

-- Email verification disabled
SELECT value FROM phpauth_config WHERE setting = 'verify_email';
-- Expected: '0'

-- phpauth_users has all three seeded members
SELECT email, isactive, username FROM phpauth_users;
-- Expected: 3 rows (billy, alice, admin), all isactive = 1
```

- [ ] **Step 3.5: Commit**

```bash
git add setup_phpauth.sql
git commit -m "feat: add PHPAuth migration SQL"
```

---

## Task 4: Rewrite `process_login.php`

**Files:**
- Modify: `process_login.php`

- [ ] **Step 4.1: Rewrite `process_login.php`**

Replace the entire file with:

```php
<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';
require_once 'vendor/autoload.php';
require_once 'inc/phpauth_db.inc.php';

// CSRF check
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header("Location: /login.php?error=Invalid+request");
    exit;
}

$errorMsg = "";
$success  = true;

// Validate fields present
if (empty($_POST['username'])) {
    $errorMsg .= "Username or email is required.<br>";
    $success = false;
}
if (empty($_POST['password'])) {
    $errorMsg .= "Password is required.<br>";
    $success = false;
}

if (!$success) {
    header("Location: /login.php?error=" . urlencode($errorMsg));
    exit;
}

$input    = sanitize_input($_POST['username']);
$password = $_POST['password']; // Do NOT sanitize password

// Detect email vs username — usernames cannot contain '@'
if (strpos($input, '@') !== false) {
    $email = $input;
} else {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT email FROM members WHERE username = ?");
    $stmt->bind_param("s", $input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        header("Location: /login.php?error=" . urlencode("Invalid credentials."));
        exit;
    }

    $email = $result->fetch_assoc()['email'];
    $stmt->close();
    $conn->close();
}

// PHPAuth login — handles rate-limiting and attempt recording automatically
$pdo    = getPHPAuthDbConnection();
$auth   = new PHPAuth\Auth($pdo, new PHPAuth\Config($pdo));
$result = $auth->login($email, $password);

if ($result['error']) {
    header("Location: /login.php?error=" . urlencode($result['message']));
    exit;
}

// Success — regenerate session, store PHPAuth hash, set session vars from members
session_regenerate_id(true);
$_SESSION['phpauth_session_hash'] = $result['hash'];

$conn = getDbConnection();
$stmt = $conn->prepare("SELECT id, username, name, role FROM members WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

$_SESSION['user_id']  = $member['id'];
$_SESSION['username'] = $member['username'];
$_SESSION['name']     = $member['name'];
$_SESSION['role']     = $member['role'];

header("Location: /");
exit;
```

> **Note:** The SELECT only fetches the four columns needed for `$_SESSION` — `members.password` is intentionally excluded.

- [ ] **Step 4.2: Verify login by username**

Open the browser, navigate to `/login.php`, log in with:
- Username: `billy`, Password: `Admin@1234`

Expected: redirect to `/`, session has `user_id`, `username = 'billy'`, `role = 'user'`.

- [ ] **Step 4.3: Verify login by email**

Log out, then log in with:
- Email: `billy@ticketsg.com`, Password: `Admin@1234`

Expected: same result as above.

- [ ] **Step 4.4: Verify bad credentials**

Attempt login with `billy` / `WrongPassword`.

Expected: redirect to `/login.php` with "Invalid credentials" or PHPAuth error message. Check `phpauth_attempts` table — a row should be inserted.

- [ ] **Step 4.5: Verify admin login**

Log in with `admin` / `Admin@1234`. Navigate to `/admin/`.

Expected: admin panel loads; `$_SESSION['role'] = 'admin'`; `require_admin()` passes.

- [ ] **Step 4.6: Commit**

```bash
git add process_login.php
git commit -m "feat: rewrite process_login.php to use PHPAuth"
```

---

## Task 5: Rewrite `process_register.php`

**Files:**
- Modify: `process_register.php`

- [ ] **Step 5.1: Rewrite `process_register.php`**

Replace the entire file with:

```php
<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';
require_once 'vendor/autoload.php';
require_once 'inc/phpauth_db.inc.php';

// CSRF check
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header("Location: /register.php?error=Invalid+request");
    exit;
}

$name = $username = $email = $password = "";
$errorMsg = "";
$success  = true;

// Validate name
if (empty($_POST['name'])) {
    $errorMsg .= "Name is required.<br>";
    $success = false;
} else {
    $name = sanitize_input($_POST['name']);
    if (strlen($name) < 5 || strlen($name) > 50) {
        $errorMsg .= "Name must be 5-50 characters.<br>";
        $success = false;
    }
}

// Validate username
if (empty($_POST['username'])) {
    $errorMsg .= "Username is required.<br>";
    $success = false;
} else {
    $username = sanitize_input($_POST['username']);
    if (strlen($username) < 5 || strlen($username) > 20) {
        $errorMsg .= "Username must be 5-20 characters.<br>";
        $success = false;
    }
    if (strpos($username, '@') !== false) {
        $errorMsg .= "Username cannot contain @.<br>";
        $success = false;
    }
}

// Validate email
if (empty($_POST['email'])) {
    $errorMsg .= "Email is required.<br>";
    $success = false;
} else {
    $email = sanitize_input($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg .= "Invalid email format.<br>";
        $success = false;
    }
}

// Validate password
if (empty($_POST['password'])) {
    $errorMsg .= "Password is required.<br>";
    $success = false;
} else {
    $password = $_POST['password']; // Do NOT sanitize password
    if (strlen($password) < 5) {
        $errorMsg .= "Password must be at least 5 characters.<br>";
        $success = false;
    }
}

if (!$success) {
    header("Location: /register.php?error=" . urlencode($errorMsg));
    exit;
}

$conn = getDbConnection();

// Check username uniqueness (PHPAuth only checks email; we own username)
$stmt = $conn->prepare("SELECT id FROM members WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    $conn->close();
    header("Location: /register.php?error=" . urlencode("Username already taken."));
    exit;
}
$stmt->close();

// Insert into members FIRST — catches duplicate email before PHPAuth is called.
// '!' is the sentinel password (an invalid bcrypt hash). PHPAuth owns the real password.
$stmt = $conn->prepare(
    "INSERT INTO members (name, username, email, password, role) VALUES (?, ?, ?, '!', 'user')"
);
$stmt->bind_param("sss", $name, $username, $email);

if (!$stmt->execute()) {
    $errno = $conn->errno;
    $errmsg = $stmt->error;
    $stmt->close();
    $conn->close();

    if ($errno === 1062) {
        header("Location: /register.php?error=" . urlencode("Email already registered."));
    } else {
        error_log("Registration DB error: " . $errmsg);
        header("Location: /register.php?error=" . urlencode("Registration failed. Please try again."));
    }
    exit;
}
$stmt->close();

// PHPAuth register — members row is the rollback target if this fails
$pdo    = getPHPAuthDbConnection();
$auth   = new PHPAuth\Auth($pdo, new PHPAuth\Config($pdo));
$result = $auth->register($email, $password, $password);

if ($result['error']) {
    // Best-effort: remove any partial phpauth_users row PHPAuth may have written
    try {
        $pdo->prepare("DELETE FROM phpauth_users WHERE email = ?")->execute([$email]);
    } catch (PDOException $e) {
        error_log("PHPAuth cleanup failed for {$email}: " . $e->getMessage());
    }

    // Required rollback: remove the members row inserted above
    $del = $conn->prepare("DELETE FROM members WHERE email = ?");
    $del->bind_param("s", $email);
    $del->execute();
    if ($del->affected_rows !== 1) {
        error_log("Registration rollback failed — orphaned members row for email: {$email}");
        $del->close();
        $conn->close();
        die("A server error occurred. Please contact support.");
    }
    $del->close();
    $conn->close();

    header("Location: /register.php?error=" . urlencode($result['message']));
    exit;
}

// Populate phpauth_users.username (register() does not accept extra columns)
$pdo->prepare("UPDATE phpauth_users SET username = ? WHERE email = ?")
    ->execute([$username, $email]);

// Back-fill members.phpauth_uid
if (!isset($result['uid'])) {
    error_log("PHPAuth register() succeeded but returned no uid for email: {$email}");
    $conn->close();
    die("A server error occurred. Please contact support.");
}
$uid  = $result['uid'];
$stmt = $conn->prepare("UPDATE members SET phpauth_uid = ? WHERE email = ?");
$stmt->bind_param("ss", $uid, $email);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: /login.php");
exit;
```

- [ ] **Step 5.2: Verify successful registration**

Open `/register.php` and register a new account (e.g., name `Test User`, username `testuser`, email `test@example.com`, password `Test@1234`).

Expected: redirect to `/login.php`.

Check the database:
```sql
SELECT id, username, email, password, phpauth_uid FROM members WHERE username = 'testuser';
-- Expected: row present, password = '!', phpauth_uid not null

SELECT email, isactive, username FROM phpauth_users WHERE email = 'test@example.com';
-- Expected: row present, isactive = 1, username = 'testuser'
```

- [ ] **Step 5.3: Verify duplicate username rejection**

Attempt to register again with username `testuser` (different email).

Expected: redirect to `/register.php` with "Username already taken." No new rows in either table.

- [ ] **Step 5.4: Verify duplicate email rejection**

Attempt to register with email `test@example.com` (different username).

Expected: redirect to `/register.php` with "Email already registered." No orphaned rows.

- [ ] **Step 5.5: Verify username @ rejection**

Attempt to register with username `user@corp`.

Expected: redirect to `/register.php` with "Username cannot contain @."

- [ ] **Step 5.6: Log in with newly registered account**

Log in with `testuser` / `Test@1234`.

Expected: session set correctly, redirect to `/`.

- [ ] **Step 5.7: Commit**

```bash
git add process_register.php
git commit -m "feat: rewrite process_register.php to use PHPAuth"
```

---

## Task 6: Rewrite `logout.php`

**Files:**
- Modify: `logout.php`

- [ ] **Step 6.1: Rewrite `logout.php`**

Replace the entire file with:

```php
<?php
require_once 'inc/auth.inc.php';
require_once 'vendor/autoload.php';
require_once 'inc/phpauth_db.inc.php';

// Invalidate the PHPAuth server-side session token.
// $hash was stored at login time. Pre-migration sessions won't have it — that's fine.
$hash = $_SESSION['phpauth_session_hash'] ?? null;

if ($hash !== null) {
    try {
        $pdo  = getPHPAuthDbConnection();
        $auth = new PHPAuth\Auth($pdo, new PHPAuth\Config($pdo));
        $auth->logout($hash);
    } catch (Exception $e) {
        error_log("PHPAuth logout error: " . $e->getMessage());
    }
}

session_unset();
session_destroy();

header("Location: /login.php?logout=1");
exit;
```

- [ ] **Step 6.2: Verify logout clears PHPAuth session**

While logged in as `billy`, note the count of rows in `phpauth_sessions`:
```sql
SELECT COUNT(*) FROM phpauth_sessions;
```

Click logout. Verify you are redirected to `/login.php?logout=1` and the "You have been logged out" alert is shown.

Check again:
```sql
SELECT COUNT(*) FROM phpauth_sessions;
-- Expected: count decreased by 1
```

- [ ] **Step 6.3: Verify PHP session is destroyed**

After logout, check that navigating to a protected page (e.g., `/orders.php`) redirects to `/login.php`.

- [ ] **Step 6.4: Commit**

```bash
git add logout.php
git commit -m "feat: rewrite logout.php to invalidate PHPAuth session token"
```

---

## Task 7: Wire CSRF on Login and Register Forms

**Files:**
- Modify: `login.php`
- Modify: `register.php`

- [ ] **Step 7.1: Edit `login.php` — add CSRF field and update label**

Locate the username label and input in `login.php`:
```html
<label for="username" class="form-label">Username</label>
<input type="text" class="form-control" id="username" name="username" required />
```

Replace with:
```html
<label for="username" class="form-label">Username or Email</label>
<input type="text" class="form-control" id="username" name="username" required />
```

Then add the CSRF hidden field anywhere inside the `<form>` tag (e.g., just before the submit button):
```html
<input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
```

- [ ] **Step 7.2: Edit `register.php` — add CSRF field**

Add the CSRF hidden field inside the `<form>` tag in `register.php` (e.g., just before the submit button):
```html
<input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
```

- [ ] **Step 7.3: Verify CSRF protection on login**

Submit the login form normally — confirm login still works.

Then test rejection by sending a request without the token (use browser DevTools to remove the hidden field value, or use curl):

```bash
curl -X POST http://localhost/process_login.php \
  -d "username=billy&password=Admin%401234"
```

Expected: redirect to `/login.php?error=Invalid+request` (HTTP 302), no session created.

- [ ] **Step 7.4: Verify CSRF protection on register**

Submit registration form normally — confirm registration still works.

- [ ] **Step 7.5: Commit**

```bash
git add login.php register.php
git commit -m "feat: wire CSRF tokens onto login and register forms"
```

---

## Task 8: End-to-End Verification

> No code changes in this task — full system check only.

- [ ] **Step 8.1: Existing user login by username**

Log in as `billy` / `Admin@1234`. Confirm landing on `/`, session vars correct.

- [ ] **Step 8.2: Existing user login by email**

Log in as `billy@ticketsg.com` / `Admin@1234`. Confirm same result.

- [ ] **Step 8.3: Brute-force lockout**

Attempt 5+ rapid failed logins for `billy`. Confirm PHPAuth returns a rate-limit message (not "Invalid credentials").

Check:
```sql
SELECT * FROM phpauth_attempts ORDER BY id DESC LIMIT 10;
-- Expected: rows present for your IP
```

- [ ] **Step 8.4: Admin access**

Log in as `admin` / `Admin@1234`. Navigate to `/admin/analytics.php`. Confirm admin panel loads without 403.

- [ ] **Step 8.5: Cart and checkout FKs**

While logged in, add a ticket to the cart. Confirm no FK errors. Check:
```sql
SELECT ci.id, ci.member_id, m.username FROM cart_items ci
JOIN members m ON ci.member_id = m.id;
-- Expected: rows show correct username, no orphaned member_id values
```

- [ ] **Step 8.6: Logout and session cleanup**

Log out. Confirm `phpauth_sessions` row removed. Confirm protected pages redirect to login.

- [ ] **Step 8.7: New registration full flow**

Register a fresh account → log in → add to cart → log out → log back in. Confirm full flow works end-to-end.

- [ ] **Step 8.8: Final commit tag**

```bash
git tag phpauth-integration
git log --oneline -8
```

Confirm commit history shows all the feature commits from tasks 1–7.
