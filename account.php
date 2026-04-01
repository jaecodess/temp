<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';
require_once 'inc/phpauth_db.inc.php';

require_login();

$errorMsg   = '';
$successMsg = '';
$conn = getDbConnection();

// ── POST handler ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Update profile ───────────────────────────────────────────────────────
    if ($action === 'update') {
        $name       = trim($_POST['name']             ?? '');
        $email      = trim($_POST['email']            ?? '');
        $currentPwd = $_POST['current_password']      ?? '';
        $newPwd     = $_POST['new_password']          ?? '';

        // Fetch password from phpauth_users (members.password is a sentinel '!')
        $pdo  = getPHPAuthDbConnection();
        $ps   = $pdo->prepare("SELECT password FROM phpauth_users WHERE email = ?");
        $ps->execute([$_SESSION['email']]);
        $row  = $ps->fetch();

        if (strlen($name) < 5 || strlen($name) > 50) {
            $errorMsg = "Name must be 5–50 characters.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = "Please enter a valid email address.";
        } elseif (empty($currentPwd)) {
            $errorMsg = "Your current password is required to save changes.";
        } elseif (!password_verify($currentPwd, $row['password'])) {
            $errorMsg = "Current password is incorrect.";
        } elseif (!empty($newPwd) && strlen($newPwd) < 8) {
            $errorMsg = "New password must be at least 8 characters.";
        } else {
            // members.password is always the sentinel '!' — never update it here
            $stmt = $conn->prepare("UPDATE members SET name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $email, $_SESSION['user_id']);

            if ($stmt->execute()) {
                $oldEmail = $_SESSION['email'];

                // Sync email in phpauth_users if it changed
                if ($email !== $oldEmail) {
                    $pdo->prepare("UPDATE phpauth_users SET email = ? WHERE email = ?")
                        ->execute([$email, $oldEmail]);
                }

                // Update password in phpauth_users if a new one was provided
                if (!empty($newPwd)) {
                    $hash = password_hash($newPwd, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE phpauth_users SET password = ? WHERE email = ?")
                        ->execute([$hash, $email]);
                }

                $_SESSION['name']  = $name;
                $_SESSION['email'] = $email;
                $successMsg = "Account updated successfully.";
            } else {
                $errorMsg = ($conn->errno === 1062)
                    ? "That email address is already registered to another account."
                    : "Update failed. Please try again.";
            }
            $stmt->close();
        }
    }

    // ── Delete account ───────────────────────────────────────────────────────
    elseif ($action === 'delete') {
        $deletePwd = $_POST['delete_password'] ?? '';

        $pdo  = getPHPAuthDbConnection();
        $ps   = $pdo->prepare("SELECT password FROM phpauth_users WHERE email = ?");
        $ps->execute([$_SESSION['email']]);
        $row  = $ps->fetch();

        if (!password_verify($deletePwd, $row['password'])) {
            $errorMsg = "Incorrect password. Your account was not deleted.";
        } else {
            $stmt = $conn->prepare("DELETE FROM members WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            $conn->close();
            session_unset();
            session_destroy();
            header("Location: /register.php?deleted=1");
            exit;
        }
    }
}

// ── Fetch current member ─────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM members WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

$pageTitle = 'My Account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "inc/head.inc.php"; ?>
    <style>
        .account-page {
            padding: 64px 0 100px;
            background-color: var(--bg-body);
            min-height: 60vh;
        }

        /* ── Page layout ── */
        .account-grid {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 32px;
            align-items: start;
        }
        @media (max-width: 767px) {
            .account-grid { grid-template-columns: 1fr; }
        }

        /* ── Sidebar card ── */
        .account-sidebar {
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-radius: 16px;
            padding: 28px 24px;
            box-shadow: var(--shadow-card);
            text-align: center;
        }
        .account-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: var(--color-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
        }
        .account-avatar i {
            font-size: 32px;
            color: var(--color-accent);
        }
        .account-display-name {
            font-family: var(--font-display);
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--color-dark);
            letter-spacing: -0.2px;
            margin-bottom: 2px;
        }
        .account-display-username {
            font-family: var(--font-heading);
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-bottom: 18px;
        }
        .account-meta-row {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 9px 0;
            border-top: 1px solid var(--surface-border);
            text-align: left;
        }
        .account-meta-row i {
            width: 16px;
            text-align: center;
            font-size: 12px;
            color: var(--color-accent);
            flex-shrink: 0;
        }
        .account-meta-row span {
            font-family: var(--font-heading);
            font-size: 0.8rem;
            color: var(--color-dark);
            word-break: break-all;
        }
        .account-quick-links {
            margin-top: 18px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .account-quick-link {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 10px 14px;
            border-radius: 10px;
            text-decoration: none;
            font-family: var(--font-heading);
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--color-dark);
            background: var(--bg-warm-gray);
            transition: background 0.18s, color 0.18s;
        }
        .account-quick-link:hover {
            background: var(--color-accent);
            color: #fff;
        }
        .account-quick-link i { width: 14px; text-align: center; }

        /* ── Main form card ── */
        .account-section-title {
            font-family: var(--font-display);
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--color-dark);
            letter-spacing: -0.2px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .account-section-title::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(to right, var(--color-accent), transparent);
            border-radius: 2px;
        }

        .account-form-card {
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-radius: 16px;
            padding: 32px;
            box-shadow: var(--shadow-card);
            margin-bottom: 24px;
        }

        .form-field {
            margin-bottom: 20px;
        }
        .form-field label {
            display: block;
            font-family: var(--font-heading);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            margin-bottom: 7px;
        }
        .form-field label .required { color: var(--color-accent); margin-left: 2px; }
        .form-field input {
            width: 100%;
            padding: 11px 16px;
            border: 1px solid var(--surface-border);
            border-radius: 10px;
            background: #FDFAF6;
            font-family: var(--font-heading);
            font-size: 0.9rem;
            color: var(--color-dark);
            transition: border-color 0.18s, box-shadow 0.18s;
            outline: none;
        }
        .form-field input:focus {
            border-color: var(--color-accent);
            box-shadow: 0 0 0 3px rgba(14,159,173,0.14);
        }
        .form-field .field-hint {
            margin-top: 5px;
            font-family: var(--font-heading);
            font-size: 0.75rem;
            color: var(--text-subtle);
        }
        .form-field input.is-invalid { border-color: var(--color-red); }

        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 575px) { .form-row-2 { grid-template-columns: 1fr; } }

        /* ── Alert banners ── */
        .alert-success {
            background: rgba(14,159,173,0.08);
            border: 1px solid rgba(14,159,173,0.28);
            border-radius: 10px;
            padding: 12px 18px;
            font-family: var(--font-heading);
            font-size: 0.85rem;
            color: var(--color-accent-hover);
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-error {
            background: rgba(210,43,43,0.06);
            border: 1px solid rgba(210,43,43,0.22);
            border-radius: 10px;
            padding: 12px 18px;
            font-family: var(--font-heading);
            font-size: 0.85rem;
            color: var(--color-red);
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ── Buttons ── */
        .btn-save {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--color-dark);
            color: #fff;
            font-family: var(--font-heading);
            font-size: 0.88rem;
            font-weight: 700;
            padding: 12px 28px;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }
        .btn-save:hover { background: var(--color-accent); transform: translateY(-1px); }
        .btn-save:focus-visible { outline: 2px solid var(--color-accent); outline-offset: 3px; }

        /* ── Danger zone ── */
        .danger-zone-card {
            background: var(--surface-card);
            border: 1px solid rgba(210,43,43,0.22);
            border-radius: 16px;
            padding: 28px 32px;
            box-shadow: var(--shadow-card);
        }
        .danger-zone-title {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--color-red);
            margin-bottom: 6px;
        }
        .danger-zone-desc {
            font-family: var(--font-heading);
            font-size: 0.83rem;
            color: var(--text-muted);
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .danger-toggle {
            background: none;
            border: 1px solid rgba(210,43,43,0.35);
            color: var(--color-red);
            font-family: var(--font-heading);
            font-size: 0.84rem;
            font-weight: 700;
            padding: 9px 20px;
            border-radius: 999px;
            cursor: pointer;
            transition: background 0.18s, color 0.18s;
        }
        .danger-toggle:hover { background: var(--color-red); color: #fff; }
        .danger-toggle:focus-visible { outline: 2px solid var(--color-red); outline-offset: 3px; }

        .delete-form-wrap {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.32s ease;
            margin-top: 0;
        }
        .delete-form-wrap.open { grid-template-rows: 1fr; }
        .delete-form-inner { overflow: hidden; }
        .delete-form-inner .form-field { margin-top: 20px; }
        .btn-delete-confirm {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--color-red);
            color: #fff;
            font-family: var(--font-heading);
            font-size: 0.88rem;
            font-weight: 700;
            padding: 11px 26px;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            transition: opacity 0.18s, transform 0.18s;
        }
        .btn-delete-confirm:hover { opacity: 0.88; transform: translateY(-1px); }
        .btn-delete-confirm:focus-visible { outline: 2px solid var(--color-red); outline-offset: 3px; }
    </style>
</head>
<body>
    <?php include "inc/header.inc.php"; ?>
    <?php include "inc/search.inc.php"; ?>

    <!-- Breadcrumb -->
    <div class="breadcrumb-section breadcrumb-bg">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2 text-center">
                    <div class="breadcrumb-text">
                        <p class="breadcrumb-label">Statik</p>
                        <h1>My Account</h1>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <main id="main-content" class="account-page">
        <div class="container">

            <?php if (!empty($errorMsg)): ?>
            <div class="alert-error" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <?= htmlspecialchars($errorMsg) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($successMsg)): ?>
            <div class="alert-success" role="alert">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <?= htmlspecialchars($successMsg) ?>
            </div>
            <?php endif; ?>

            <div class="account-grid">

                <!-- ── Sidebar ── -->
                <aside class="account-sidebar" aria-label="Account summary">
                    <div class="account-avatar" aria-hidden="true">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="account-display-name"><?= htmlspecialchars($member['name']) ?></div>
                    <div class="account-display-username">@<?= htmlspecialchars($member['username']) ?></div>
                    <div class="account-meta-row">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <span><?= htmlspecialchars($member['email']) ?></span>
                    </div>
                    <div class="account-meta-row">
                        <i class="fas fa-shield-alt" aria-hidden="true"></i>
                        <span><?= $member['role'] === 'admin' ? 'Administrator' : 'Member' ?></span>
                    </div>
                    <div class="account-meta-row">
                        <i class="fas fa-calendar-check" aria-hidden="true"></i>
                        <span>Since <?= date('M Y', strtotime($member['created_at'])) ?></span>
                    </div>
                    <nav class="account-quick-links" aria-label="Account navigation">
                        <a href="/orders.php" class="account-quick-link">
                            <i class="fas fa-receipt" aria-hidden="true"></i> My Orders
                        </a>
                        <a href="/cart.php" class="account-quick-link">
                            <i class="fas fa-shopping-cart" aria-hidden="true"></i> My Cart
                        </a>
                    </nav>
                </aside>

                <!-- ── Main column ── -->
                <div>

                    <!-- Edit Profile -->
                    <div class="account-form-card">
                        <div class="account-section-title">
                            <i class="fas fa-user-edit" aria-hidden="true" style="color:var(--color-accent);font-size:1rem;"></i>
                            Edit Profile
                        </div>

                        <form method="post" action="/account.php" novalidate id="edit-form">
                            <input type="hidden" name="action" value="update">

                            <div class="form-row-2">
                                <div class="form-field">
                                    <label for="name">Full Name <span class="required" aria-hidden="true">*</span></label>
                                    <input type="text" id="name" name="name"
                                           value="<?= htmlspecialchars($member['name']) ?>"
                                           required minlength="5" maxlength="50"
                                           aria-describedby="name-hint">
                                    <p class="field-hint" id="name-hint">5–50 characters</p>
                                </div>
                                <div class="form-field">
                                    <label for="username">Username</label>
                                    <input type="text" id="username" name="username_display"
                                           value="<?= htmlspecialchars($member['username']) ?>"
                                           disabled aria-describedby="username-hint">
                                    <p class="field-hint" id="username-hint">Username cannot be changed.</p>
                                </div>
                            </div>

                            <div class="form-field">
                                <label for="email">Email Address <span class="required" aria-hidden="true">*</span></label>
                                <input type="email" id="email" name="email"
                                       value="<?= htmlspecialchars($member['email']) ?>"
                                       required maxlength="50">
                            </div>

                            <div class="account-section-title" style="font-size:1rem; margin-top:8px;">
                                <i class="fas fa-lock" aria-hidden="true" style="color:var(--color-accent);font-size:0.9rem;"></i>
                                Change Password
                            </div>

                            <div class="form-field">
                                <label for="current_password">Current Password <span class="required" aria-hidden="true">*</span></label>
                                <input type="password" id="current_password" name="current_password"
                                       required maxlength="200"
                                       aria-describedby="current-pwd-hint">
                                <p class="field-hint" id="current-pwd-hint">Required to confirm any changes.</p>
                            </div>

                            <div class="form-field">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password"
                                       minlength="8" maxlength="200"
                                       aria-describedby="new-pwd-hint">
                                <p class="field-hint" id="new-pwd-hint">Leave blank to keep your current password. Min 8 characters.</p>
                            </div>

                            <button type="submit" class="btn-save">
                                <i class="fas fa-check" aria-hidden="true"></i> Save Changes
                            </button>
                        </form>
                    </div>

                    <!-- Danger Zone -->
                    <div class="danger-zone-card">
                        <div class="danger-zone-title"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Danger Zone</div>
                        <p class="danger-zone-desc">
                            Permanently deleting your account removes all your data, including your order history. This cannot be undone.
                        </p>

                        <button class="danger-toggle" type="button"
                                onclick="toggleDeleteZone(this)"
                                aria-expanded="false" aria-controls="delete-zone">
                            Delete My Account
                        </button>

                        <div class="delete-form-wrap" id="delete-zone" aria-hidden="true">
                            <div class="delete-form-inner">
                                <form method="post" action="/account.php" id="delete-form"
                                      onsubmit="return confirm('This is permanent and cannot be undone. Are you sure?')">
                                    <input type="hidden" name="action" value="delete">
                                    <div class="form-field">
                                        <label for="delete_password">Confirm with your password <span class="required" aria-hidden="true">*</span></label>
                                        <input type="password" id="delete_password" name="delete_password"
                                               maxlength="200" required>
                                    </div>
                                    <button type="submit" class="btn-delete-confirm">
                                        <i class="fas fa-trash-alt" aria-hidden="true"></i> Permanently Delete Account
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div><!-- end main column -->
            </div><!-- end account-grid -->
        </div>
    </main>

    <script>
        function toggleDeleteZone(btn) {
            var wrap = document.getElementById('delete-zone');
            var isOpen = wrap.classList.contains('open');
            wrap.classList.toggle('open', !isOpen);
            wrap.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
            btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            btn.textContent = isOpen ? 'Delete My Account' : 'Cancel';
        }

        // Client-side validation
        document.getElementById('edit-form').addEventListener('submit', function(e) {
            var name = document.getElementById('name').value.trim();
            var email = document.getElementById('email').value.trim();
            var currentPwd = document.getElementById('current_password').value;

            if (name.length < 5 || name.length > 50) {
                alert('Name must be 5–50 characters.');
                e.preventDefault(); return;
            }
            if (!email || !email.includes('@')) {
                alert('Please enter a valid email address.');
                e.preventDefault(); return;
            }
            if (!currentPwd) {
                alert('Current password is required to save changes.');
                e.preventDefault(); return;
            }
            var newPwd = document.getElementById('new_password').value;
            if (newPwd && newPwd.length < 8) {
                alert('New password must be at least 8 characters.');
                e.preventDefault(); return;
            }
        });
    </script>

    <?php include "inc/footer.inc.php"; ?>
</body>
</html>
