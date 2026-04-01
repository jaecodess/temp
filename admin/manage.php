<?php
require_once '../inc/auth.inc.php';
require_once '../inc/db.inc.php';

require_admin();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

$conn = getDbConnection();

// ── Active tab & global messages ─────────────────────────────────────────────
$activeTab  = $_GET['tab'] ?? 'events';
if (!in_array($activeTab, ['events', 'members', 'genres'], true)) $activeTab = 'events';
$successMsg = htmlspecialchars($_GET['success'] ?? '');

// ── Per-tab state ────────────────────────────────────────────────────────────
// Events
$ev_err = ''; $ev_showForm = false; $ev_editMode = false; $ev_editId = 0;
$ev_item = null; $ev_ticketCats = []; $ev_fd = [];

// Members
$mb_err = ''; $mb_showForm = false; $mb_editMode = false; $mb_editId = 0;
$mb_member = null; $mb_fd = [];

// Genres
$gn_err = ''; $gn_showForm = false; $gn_editMode = false; $gn_editId = 0;
$gn_cat = null; $gn_fd = [];

// ── Genres list (needed for events form) ─────────────────────────────────────
$genres = [];
$gr = $conn->query("SELECT * FROM genres ORDER BY name");
while ($row = $gr->fetch_assoc()) $genres[] = $row;

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        // Route error to correct tab
        if (str_starts_with($postAction, 'add_event') || str_starts_with($postAction, 'edit_event')) {
            $activeTab = 'events'; $ev_err = "Invalid request."; $ev_showForm = true;
        } elseif (str_starts_with($postAction, 'add_member') || str_starts_with($postAction, 'edit_member')) {
            $activeTab = 'members'; $mb_err = "Invalid request."; $mb_showForm = true;
        } else {
            $activeTab = 'genres'; $gn_err = "Invalid request."; $gn_showForm = true;
        }
    } else {

        // ── Events ──────────────────────────────────────────────────────────
        if ($postAction === 'add_event' || $postAction === 'edit_event') {
            $activeTab   = 'events';
            $name        = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $venue       = trim($_POST['venue'] ?? '');
            $eventDate   = trim($_POST['event_date'] ?? '');
            $eventTime   = trim($_POST['event_time'] ?? '');
            $genreId     = intval($_POST['genre_id'] ?? 0);
            $cat1Price   = floatval($_POST['cat1_price'] ?? 0);
            $cat1Seats   = intval($_POST['cat1_seats'] ?? 0);
            $cat2Price   = floatval($_POST['cat2_price'] ?? 0);
            $cat2Seats   = intval($_POST['cat2_seats'] ?? 0);
            $cat3Price   = floatval($_POST['cat3_price'] ?? 0);
            $cat3Seats   = intval($_POST['cat3_seats'] ?? 0);
            $ev_fd = compact('name','description','venue','eventDate','eventTime','genreId',
                             'cat1Price','cat1Seats','cat2Price','cat2Seats','cat3Price','cat3Seats');

            if (strlen($name) < 5 || strlen($name) > 100)    $ev_err = "Name must be 5–100 characters.";
            elseif (strlen($description) < 5)                 $ev_err = "Description must be at least 5 characters.";
            elseif (strlen($venue) < 3)                       $ev_err = "Venue must be at least 3 characters.";
            elseif (empty($eventDate) || empty($eventTime))   $ev_err = "Event date and time are required.";
            elseif ($cat1Price <= 0 || $cat2Price <= 0 || $cat3Price <= 0) $ev_err = "All ticket prices must be > 0.";
            elseif ($cat1Seats <= 0 || $cat2Seats <= 0 || $cat3Seats <= 0) $ev_err = "All seat counts must be > 0.";
            else {
                $mimeOk = true;
                if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] === UPLOAD_ERR_OK) {
                    if (!in_array(mime_content_type($_FILES['itemImage']['tmp_name']), ['image/jpeg','image/png'], true)) {
                        $ev_err = "Only JPEG and PNG images are allowed."; $mimeOk = false;
                    }
                }
                if ($mimeOk) {
                    if ($postAction === 'add_event') {
                        $imgName = '';
                        $s = $conn->prepare("INSERT INTO performances (name,description,venue,event_date,event_time,img_name,genre_id) VALUES (?,?,?,?,?,?,?)");
                        $s->bind_param("ssssssi", $name, $description, $venue, $eventDate, $eventTime, $imgName, $genreId);
                        if ($s->execute()) {
                            $newId = $s->insert_id; $s->close();
                            if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] === UPLOAD_ERR_OK) {
                                $imgName   = basename($_FILES['itemImage']['name']);
                                $dir = "../uploads/performances/{$newId}/";
                                if (!is_dir($dir)) mkdir($dir, 0775, true);
                                move_uploaded_file($_FILES['itemImage']['tmp_name'], $dir . $imgName);
                                $u = $conn->prepare("UPDATE performances SET img_name=? WHERE id=?");
                                $u->bind_param("si", $imgName, $newId); $u->execute(); $u->close();
                            }
                            $s = $conn->prepare("INSERT INTO ticket_categories (performance_id,name,price,total_seats,available_seats) VALUES (?,?,?,?,?)");
                            foreach ([['Cat 1',$cat1Price,$cat1Seats],['Cat 2',$cat2Price,$cat2Seats],['Cat 3',$cat3Price,$cat3Seats]] as $c) {
                                $s->bind_param("isdii", $newId, $c[0], $c[1], $c[2], $c[2]); $s->execute();
                            }
                            $s->close(); $conn->close();
                            header("Location: /admin/manage.php?tab=events&success=" . urlencode("Performance added successfully.")); exit;
                        } else { $ev_err = "Failed to add performance."; $s->close(); }
                    } else { // edit_event
                        $ev_editId = intval($_POST['id'] ?? 0);
                        if ($ev_editId <= 0) { $ev_err = "Invalid performance ID."; }
                        else {
                            $origImg = $_POST['original_image'] ?? '';
                            $imgName = $origImg;
                            if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] === UPLOAD_ERR_OK) {
                                $imgName = basename($_FILES['itemImage']['name']);
                                $dir = "../uploads/performances/{$ev_editId}/";
                                if (!is_dir($dir)) mkdir($dir, 0775, true);
                                move_uploaded_file($_FILES['itemImage']['tmp_name'], $dir . $imgName);
                            }
                            $s = $conn->prepare("UPDATE performances SET name=?,description=?,venue=?,event_date=?,event_time=?,img_name=?,genre_id=? WHERE id=?");
                            $s->bind_param("ssssssii", $name, $description, $venue, $eventDate, $eventTime, $imgName, $genreId, $ev_editId);
                            if ($s->execute()) {
                                $s->close();
                                foreach ([['Cat 1',$cat1Price,$cat1Seats],['Cat 2',$cat2Price,$cat2Seats],['Cat 3',$cat3Price,$cat3Seats]] as $c) {
                                    $u = $conn->prepare("UPDATE ticket_categories SET price=?,total_seats=? WHERE performance_id=? AND name=?");
                                    $u->bind_param("diis", $c[1], $c[2], $ev_editId, $c[0]); $u->execute(); $u->close();
                                }
                                $conn->close();
                                header("Location: /admin/manage.php?tab=events&success=" . urlencode("Performance updated successfully.")); exit;
                            } else { $ev_err = "Failed to update performance."; $s->close(); }
                        }
                        $ev_editMode = true;
                    }
                }
            }
            $ev_showForm = true;
            if ($postAction === 'edit_event') { $ev_editMode = true; $ev_editId = intval($_POST['id'] ?? 0); }
        }

        // ── Members ─────────────────────────────────────────────────────────
        elseif ($postAction === 'add_member' || $postAction === 'edit_member') {
            $activeTab = 'members';
            $name      = trim($_POST['name'] ?? '');
            $username  = trim($_POST['username'] ?? '');
            $email     = trim($_POST['email'] ?? '');
            $password  = $_POST['password'] ?? '';
            $mb_fd = compact('name','username','email');

            if (strlen($name) < 5 || strlen($name) > 50)     $mb_err = "Name must be 5–50 characters.";
            elseif (strlen($username) < 5 || strlen($username) > 20) $mb_err = "Username must be 5–20 characters.";
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $mb_err = "Invalid email format.";
            elseif ($postAction === 'add_member' && strlen($password) < 5) $mb_err = "Password must be at least 5 characters.";
            else {
                if ($postAction === 'add_member') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $s = $conn->prepare("INSERT INTO members (name,username,email,password,role) VALUES (?,?,?,?,'user')");
                    $s->bind_param("ssss", $name, $username, $email, $hash);
                    if ($s->execute()) {
                        $s->close(); $conn->close();
                        header("Location: /admin/manage.php?tab=members&success=" . urlencode("Member added successfully.")); exit;
                    } else {
                        $mb_err = ($conn->errno === 1062) ? "Username or email already exists." : "Failed to add member.";
                        $s->close();
                    }
                } else { // edit_member
                    $mb_editId = intval($_POST['id'] ?? 0);
                    if ($mb_editId <= 0) { $mb_err = "Invalid member ID."; }
                    else {
                        if (!empty($password) && strlen($password) < 5) {
                            $mb_err = "New password must be at least 5 characters.";
                        } else {
                            if (!empty($password)) {
                                $hash = password_hash($password, PASSWORD_DEFAULT);
                                $s = $conn->prepare("UPDATE members SET name=?,username=?,email=?,password=? WHERE id=?");
                                $s->bind_param("ssssi", $name, $username, $email, $hash, $mb_editId);
                            } else {
                                $s = $conn->prepare("UPDATE members SET name=?,username=?,email=? WHERE id=?");
                                $s->bind_param("sssi", $name, $username, $email, $mb_editId);
                            }
                            if ($s->execute()) {
                                $s->close(); $conn->close();
                                header("Location: /admin/manage.php?tab=members&success=" . urlencode("Member updated successfully.")); exit;
                            } else {
                                $mb_err = ($conn->errno === 1062) ? "Username or email already exists." : "Failed to update member.";
                                $s->close();
                            }
                        }
                    }
                    $mb_editMode = true;
                    $mb_editId = intval($_POST['id'] ?? 0);
                }
            }
            $mb_showForm = true;
            if ($postAction === 'edit_member') { $mb_editMode = true; $mb_editId = intval($_POST['id'] ?? 0); }
        }

        // ── Genres ──────────────────────────────────────────────────────────
        elseif ($postAction === 'add_genre' || $postAction === 'edit_genre') {
            $activeTab = 'genres';
            $name = trim($_POST['name'] ?? '');
            $gn_fd = ['name' => $name];

            if (strlen($name) < 3 || strlen($name) > 50) {
                $gn_err = "Genre name must be 3–50 characters.";
            } else {
                if ($postAction === 'add_genre') {
                    $s = $conn->prepare("INSERT INTO genres (name) VALUES (?)");
                    $s->bind_param("s", $name);
                    if ($s->execute()) {
                        $s->close(); $conn->close();
                        header("Location: /admin/manage.php?tab=genres&success=" . urlencode("Genre added successfully.")); exit;
                    } else {
                        $gn_err = ($conn->errno === 1062) ? "A genre with that name already exists." : "Failed to add genre.";
                        $s->close();
                    }
                } else { // edit_genre
                    $gn_editId = intval($_POST['id'] ?? 0);
                    if ($gn_editId <= 0) { $gn_err = "Invalid genre ID."; }
                    else {
                        $s = $conn->prepare("UPDATE genres SET name=? WHERE id=?");
                        $s->bind_param("si", $name, $gn_editId);
                        if ($s->execute()) {
                            $s->close(); $conn->close();
                            header("Location: /admin/manage.php?tab=genres&success=" . urlencode("Genre updated successfully.")); exit;
                        } else {
                            $gn_err = ($conn->errno === 1062) ? "A genre with that name already exists." : "Failed to update genre.";
                            $s->close();
                        }
                    }
                    $gn_editMode = true;
                    $gn_editId = intval($_POST['id'] ?? 0);
                }
            }
            $gn_showForm = true;
            if ($postAction === 'edit_genre') { $gn_editMode = true; $gn_editId = intval($_POST['id'] ?? 0); }
        }
    }
}

// ── Handle GET edit params ───────────────────────────────────────────────────
if (!$ev_showForm && isset($_GET['edit_event']) && intval($_GET['edit_event']) > 0) {
    $activeTab = 'events'; $ev_editId = intval($_GET['edit_event']); $ev_showForm = true; $ev_editMode = true;
}
if (!$mb_showForm && isset($_GET['edit_member']) && intval($_GET['edit_member']) > 0) {
    $activeTab = 'members'; $mb_editId = intval($_GET['edit_member']); $mb_showForm = true; $mb_editMode = true;
}
if (!$gn_showForm && isset($_GET['edit_genre']) && intval($_GET['edit_genre']) > 0) {
    $activeTab = 'genres'; $gn_editId = intval($_GET['edit_genre']); $gn_showForm = true; $gn_editMode = true;
}

// ── Fetch edit data ──────────────────────────────────────────────────────────
if ($ev_editMode && $ev_editId > 0 && empty($ev_fd)) {
    $s = $conn->prepare("SELECT * FROM performances WHERE id=?"); $s->bind_param("i", $ev_editId); $s->execute();
    $r = $s->get_result();
    if ($r->num_rows > 0) { $ev_item = $r->fetch_assoc(); } else { $ev_editMode = false; $ev_showForm = false; }
    $s->close();
    if ($ev_item) {
        $s = $conn->prepare("SELECT * FROM ticket_categories WHERE performance_id=? ORDER BY name");
        $s->bind_param("i", $ev_editId); $s->execute(); $tcr = $s->get_result();
        while ($row = $tcr->fetch_assoc()) $ev_ticketCats[$row['name']] = $row;
        $s->close();
    }
}
if ($mb_editMode && $mb_editId > 0 && empty($mb_fd)) {
    $s = $conn->prepare("SELECT * FROM members WHERE id=?"); $s->bind_param("i", $mb_editId); $s->execute();
    $r = $s->get_result();
    if ($r->num_rows > 0) { $mb_member = $r->fetch_assoc(); } else { $mb_editMode = false; $mb_showForm = false; }
    $s->close();
}
if ($gn_editMode && $gn_editId > 0 && empty($gn_fd)) {
    $s = $conn->prepare("SELECT * FROM genres WHERE id=?"); $s->bind_param("i", $gn_editId); $s->execute();
    $r = $s->get_result();
    if ($r->num_rows > 0) { $gn_cat = $r->fetch_assoc(); } else { $gn_editMode = false; $gn_showForm = false; }
    $s->close();
}

// ── Stats ────────────────────────────────────────────────────────────────────
$statEvents = 0; $r = $conn->query("SELECT COUNT(*) c FROM performances"); if ($r) $statEvents = $r->fetch_assoc()['c'];
$statUpcoming = 0; $r = $conn->query("SELECT COUNT(*) c FROM performances WHERE event_date >= CURDATE()"); if ($r) $statUpcoming = $r->fetch_assoc()['c'];
$statMembers = 0; $r = $conn->query("SELECT COUNT(*) c FROM members WHERE role='user'"); if ($r) $statMembers = $r->fetch_assoc()['c'];
$statGenres = 0; $r = $conn->query("SELECT COUNT(*) c FROM genres"); if ($r) $statGenres = $r->fetch_assoc()['c'];

// ── Fetch all table data ─────────────────────────────────────────────────────
$allEvents = [];
$s = $conn->prepare("SELECT p.*, g.name genre_name FROM performances p LEFT JOIN genres g ON p.genre_id=g.id ORDER BY p.event_date DESC");
$s->execute(); $res = $s->get_result(); while ($row = $res->fetch_assoc()) $allEvents[] = $row; $s->close();

$allMembers = [];
$s = $conn->prepare("SELECT * FROM members ORDER BY id");
$s->execute(); $res = $s->get_result(); while ($row = $res->fetch_assoc()) $allMembers[] = $row; $s->close();

$allGenres = [];
$r = $conn->query("SELECT g.*, COUNT(p.id) event_count FROM genres g LEFT JOIN performances p ON p.genre_id=g.id GROUP BY g.id ORDER BY g.id");
if ($r) while ($row = $r->fetch_assoc()) $allGenres[] = $row;

$conn->close();

// ── Form value helpers ───────────────────────────────────────────────────────
function fv($fd, $item, $key, $default = '') {
    if (!empty($fd)) return htmlspecialchars($fd[$key] ?? $default);
    if ($item)       return htmlspecialchars($item[$key] ?? $default);
    return $default;
}
function tcv($fd, $cats, $n, $field) {
    $fdKey = "cat{$n}_" . ($field === 'price' ? 'price' : 'seats');
    if (!empty($fd)) return htmlspecialchars($fd[$fdKey] ?? '');
    $dbKey = $field === 'price' ? 'price' : 'total_seats';
    return htmlspecialchars($cats["Cat {$n}"][$dbKey] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $pageTitle = 'Admin — Manage'; include "../inc/head.inc.php"; ?>
    <style>
        /* ── Hero (matches analytics.php exactly) ── */
        .analytics-hero {
            background: linear-gradient(135deg, #051922 0%, #0d2e42 60%, #051922 100%);
            padding: 150px 0 40px;
            position: relative;
            overflow: hidden;
        }
        .analytics-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 15% 50%, rgba(14,159,173,0.10) 0%, transparent 50%),
                radial-gradient(circle at 85% 20%, rgba(14,159,173,0.06) 0%, transparent 40%);
            pointer-events: none;
        }
        .analytics-hero h1 { color:#fff; font-family:'Poppins',sans-serif; font-size:2.4rem; font-weight:800; letter-spacing:-0.5px; margin:0; }
        .analytics-hero h1 span { color:#0E9FAD; }
        .analytics-hero p { color:rgba(255,255,255,0.55); margin:8px 0 0; font-size:0.95rem; }

        /* ── Stat cards ── */
        .stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin:-30px 0 0; }
        @media(max-width:991px){ .stat-grid { grid-template-columns:repeat(2,1fr); } }
        @media(max-width:575px){ .stat-grid { grid-template-columns:1fr; } }
        .stat-card { background:#fff; border-radius:12px; padding:28px 24px; box-shadow:0 4px 24px rgba(5,25,34,.10); border-left:4px solid #0E9FAD; display:flex; flex-direction:column; gap:6px; transition:transform .18s,box-shadow .18s; }
        .stat-card:hover { transform:translateY(-3px); box-shadow:0 8px 32px rgba(5,25,34,.14); }
        .stat-card .stat-label { font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:1.2px; color:#7a8f99; }
        .stat-card .stat-value { font-family:'Poppins',sans-serif; font-size:2rem; font-weight:800; color:#051922; line-height:1.1; }
        .stat-card .stat-icon { font-size:1.6rem; color:#0E9FAD; margin-bottom:4px; }

        /* ── Body ── */
        .manage-body { background:var(--bg-body); padding:40px 0 80px; }

        /* ── Tabs ── */
        .manage-tabs { border-bottom:2px solid var(--surface-border); margin-bottom:28px; gap:4px; }
        .manage-tabs .nav-link {
            color:var(--text-muted); font-weight:700; font-size:.88rem; padding:10px 20px;
            border:none; border-bottom:2px solid transparent; border-radius:0;
            margin-bottom:-2px; transition:.2s; background:none;
        }
        .manage-tabs .nav-link:hover { color:var(--color-dark); }
        .manage-tabs .nav-link.active { color:var(--color-accent); border-bottom-color:var(--color-accent); background:none; }
        .manage-tabs .nav-link i { margin-right:5px; }

        /* ── Section title ── */
        .section-title { font-size:1.05rem; font-weight:700; color:var(--color-dark); margin-bottom:14px; display:flex; align-items:center; gap:8px; }
        .section-title::after { content:''; flex:1; height:2px; background:linear-gradient(to right,var(--color-accent),transparent); border-radius:2px; }

        /* ── Form panel ── */
        .form-panel { background:var(--surface-card); border:1px solid var(--surface-border); border-radius:10px; padding:28px; margin-bottom:28px; box-shadow:var(--shadow-soft); }
        .form-panel .form-label { font-size:.82rem; font-weight:600; color:var(--color-dark); }
        .form-panel .form-control, .form-panel .form-select { border-color:var(--surface-border); background:#FDFAF6; }
        .form-panel .form-control:focus, .form-panel .form-select:focus { border-color:var(--color-accent); box-shadow:0 0 0 .2rem rgba(14,159,173,.15); }

        /* ── Ticket categories grid ── */
        .ticket-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
        @media(max-width:767px){ .ticket-grid { grid-template-columns:1fr; } }
        .ticket-cat-card { background:var(--bg-warm-gray); border-radius:8px; padding:14px; }
        .ticket-cat-card .cat-label { font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--color-accent); margin-bottom:10px; }

        /* ── FK badge ── */
        .fk-badge { font-size:.7rem; background:rgba(210,43,43,.10); color:#D22B2B; padding:2px 7px; border-radius:10px; border:1px solid rgba(210,43,43,.20); }
    </style>
</head>
<body>
<?php include "../inc/header.inc.php"; ?>

<!-- Hero -->
<div class="analytics-hero" id="main-content">
    <div class="container">
        <p style="color:rgba(255,255,255,.45);font-size:.78rem;font-weight:600;letter-spacing:3px;text-transform:uppercase;margin-bottom:6px;">Admin Panel</p>
        <h1>Manage <span>Dashboard</span></h1>
        <p>Events, members, and genres — all in one place.</p>
    </div>
</div>

<!-- Stat cards -->
<div class="manage-body">
<div class="container">
    <div class="stat-grid" style="margin-bottom:40px;">
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-calendar-alt"></i></div><div class="stat-label">Total Events</div><div class="stat-value"><?= $statEvents ?></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-label">Upcoming</div><div class="stat-value"><?= $statUpcoming ?></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-label">Members</div><div class="stat-value"><?= $statMembers ?></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-tags"></i></div><div class="stat-label">Genres</div><div class="stat-value"><?= $statGenres ?></div></div>
    </div>

    <?php if (!empty($successMsg)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-2"></i><?= $successMsg ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <!-- Tab nav -->
    <ul class="nav manage-tabs" id="manageTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'events'  ? 'active' : '' ?>" data-toggle="tab" href="#tab-events"  role="tab"><i class="fas fa-calendar-alt"></i> Events</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'members' ? 'active' : '' ?>" data-toggle="tab" href="#tab-members" role="tab"><i class="fas fa-users"></i> Members</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'genres'  ? 'active' : '' ?>" data-toggle="tab" href="#tab-genres"  role="tab"><i class="fas fa-tags"></i> Genres</a>
        </li>
    </ul>

    <div class="tab-content">

        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- EVENTS TAB -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <div class="tab-pane fade <?= $activeTab === 'events' ? 'show active' : '' ?>" id="tab-events" role="tabpanel">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title mb-0" style="flex:1;">All Performances</div>
                <button class="btn btn-primary btn-sm ml-3" onclick="togglePanel('ev-form-panel', this)">
                    <i class="fas fa-plus mr-1"></i> Add Performance
                </button>
            </div>

            <!-- Events form panel -->
            <div id="ev-form-panel" <?= $ev_showForm ? '' : 'style="display:none"' ?>>
                <div class="section-title"><?= $ev_editMode ? 'Edit Performance' : 'Add Performance' ?></div>
                <div class="form-panel">
                    <?php if ($ev_err): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($ev_err) ?></div>
                    <?php endif; ?>
                    <form method="post" action="/admin/manage.php" enctype="multipart/form-data" class="needs-validation" novalidate id="ev-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="action" value="<?= $ev_editMode ? 'edit_event' : 'add_event' ?>">
                        <?php if ($ev_editMode): ?>
                            <input type="hidden" name="id" value="<?= $ev_editId ?>">
                            <input type="hidden" name="original_image" value="<?= htmlspecialchars($ev_item['img_name'] ?? '') ?>">
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Performance Name</label>
                                    <input type="text" class="form-control" name="name" required minlength="5" maxlength="100"
                                        value="<?= fv($ev_fd, $ev_item, 'name') ?>">
                                    <div class="invalid-feedback">5–100 characters required.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Venue</label>
                                    <input type="text" class="form-control" name="venue" required minlength="3" maxlength="100"
                                        value="<?= fv($ev_fd, $ev_item, 'venue') ?>">
                                    <div class="invalid-feedback">At least 3 characters required.</div>
                                </div>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Date</label>
                                        <input type="date" class="form-control" name="event_date" required
                                            value="<?= fv($ev_fd, $ev_item, 'event_date') ?>">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Time</label>
                                        <input type="time" class="form-control" name="event_time" required
                                            value="<?= fv($ev_fd, $ev_item, 'event_time') ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Genre</label>
                                    <select class="form-control" name="genre_id" required>
                                        <option value="">Choose a genre</option>
                                        <?php foreach ($genres as $g):
                                            $sel = !empty($ev_fd) ? ($ev_fd['genreId'] ?? 0) : ($ev_item['genre_id'] ?? 0); ?>
                                        <option value="<?= $g['id'] ?>" <?= $g['id'] == $sel ? 'selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a genre.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" required minlength="5" rows="5"><?= fv($ev_fd, $ev_item, 'description') ?></textarea>
                                    <div class="invalid-feedback">At least 5 characters required.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Image <?= $ev_editMode ? '<small class="text-muted">(leave blank to keep)</small>' : '' ?></label>
                                    <input type="file" class="form-control" name="itemImage" accept="image/png,image/jpeg">
                                    <?php if ($ev_editMode && !empty($ev_item['img_name'])): ?>
                                        <img src="/uploads/performances/<?= $ev_editId ?>/<?= htmlspecialchars($ev_item['img_name']) ?>"
                                            class="mt-2 img-fluid rounded" style="max-height:80px;" alt="Current image">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <hr style="border-color:var(--surface-border);">
                        <p class="mb-2" style="font-size:.82rem;font-weight:700;color:var(--color-dark);text-transform:uppercase;letter-spacing:.8px;">Ticket Categories</p>
                        <div class="ticket-grid mb-4">
                            <?php foreach ([1,2,3] as $n): ?>
                            <div class="ticket-cat-card">
                                <div class="cat-label">Cat <?= $n ?></div>
                                <div class="mb-2">
                                    <label class="form-label">Price ($)</label>
                                    <input type="number" class="form-control" name="cat<?= $n ?>_price" required min="0.01" step="0.01"
                                        value="<?= tcv($ev_fd, $ev_ticketCats, $n, 'price') ?>">
                                </div>
                                <div>
                                    <label class="form-label">Seats</label>
                                    <input type="number" class="form-control" name="cat<?= $n ?>_seats" required min="1"
                                        value="<?= tcv($ev_fd, $ev_ticketCats, $n, 'seats') ?>">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-flex" style="gap:8px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i><?= $ev_editMode ? 'Update' : 'Add Performance' ?>
                            </button>
                            <a href="/admin/manage.php?tab=events" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Events table -->
            <div class="table-responsive">
                <table class="table table-hover analytics-table" style="font-size:.88rem;">
                    <thead><tr><th>Genre</th><th>Performance</th><th>Venue</th><th>Date</th><th style="width:110px;">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($allEvents as $ev): ?>
                        <tr>
                            <td><?= htmlspecialchars($ev['genre_name'] ?? '—') ?></td>
                            <td><a href="/item.php?id=<?= $ev['id'] ?>" target="_blank" style="color:var(--color-accent);"><?= htmlspecialchars($ev['name']) ?></a></td>
                            <td><?= htmlspecialchars($ev['venue']) ?></td>
                            <td><?= $ev['event_date'] ?></td>
                            <td>
                                <a href="/admin/manage.php?tab=events&edit_event=<?= $ev['id'] ?>" class="btn btn-sm btn-outline-primary mr-1" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                                <form method="post" action="/admin/request_confirm.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                    <input type="hidden" name="type" value="delete_item">
                                    <input type="hidden" name="delete" value="<?= $ev['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($allEvents)): ?><tr><td colspan="5" class="text-center text-muted py-4">No performances yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div><!-- /tab-events -->


        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- MEMBERS TAB -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <div class="tab-pane fade <?= $activeTab === 'members' ? 'show active' : '' ?>" id="tab-members" role="tabpanel">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title mb-0" style="flex:1;">All Members</div>
                <button class="btn btn-primary btn-sm ml-3" onclick="togglePanel('mb-form-panel', this)">
                    <i class="fas fa-plus mr-1"></i> Add Member
                </button>
            </div>

            <!-- Members form panel -->
            <div id="mb-form-panel" <?= $mb_showForm ? '' : 'style="display:none"' ?>>
                <div class="section-title"><?= $mb_editMode ? 'Edit Member' : 'Add Member' ?></div>
                <div class="form-panel">
                    <?php if ($mb_err): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($mb_err) ?></div>
                    <?php endif; ?>
                    <form method="post" action="/admin/manage.php" class="needs-validation" novalidate id="mb-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="action" value="<?= $mb_editMode ? 'edit_member' : 'add_member' ?>">
                        <?php if ($mb_editMode): ?><input type="hidden" name="id" value="<?= $mb_editId ?>"><?php endif; ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="name" required minlength="5" maxlength="50"
                                        value="<?= htmlspecialchars(!empty($mb_fd) ? ($mb_fd['name'] ?? '') : ($mb_member['name'] ?? '')) ?>">
                                    <div class="invalid-feedback">5–50 characters required.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" required minlength="5" maxlength="20"
                                        value="<?= htmlspecialchars(!empty($mb_fd) ? ($mb_fd['username'] ?? '') : ($mb_member['username'] ?? '')) ?>">
                                    <div class="invalid-feedback">5–20 characters required.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required maxlength="100"
                                        value="<?= htmlspecialchars(!empty($mb_fd) ? ($mb_fd['email'] ?? '') : ($mb_member['email'] ?? '')) ?>">
                                    <div class="invalid-feedback">Valid email required.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password <?= $mb_editMode ? '<small class="text-muted">(blank = keep current)</small>' : '' ?></label>
                                    <input type="password" class="form-control" name="password"
                                        <?= $mb_editMode ? '' : 'required' ?> minlength="5" maxlength="200" autocomplete="new-password">
                                    <div class="invalid-feedback">At least 5 characters required.</div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex" style="gap:8px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i><?= $mb_editMode ? 'Update Member' : 'Add Member' ?>
                            </button>
                            <a href="/admin/manage.php?tab=members" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Members table -->
            <div class="table-responsive">
                <table class="table table-hover analytics-table" style="font-size:.88rem;">
                    <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th style="width:110px;">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($allMembers as $m): ?>
                        <tr>
                            <td><?= $m['id'] ?></td>
                            <td><?= htmlspecialchars($m['name']) ?></td>
                            <td><a href="/orders.php?member_id=<?= $m['id'] ?>" style="color:var(--color-accent);"><?= htmlspecialchars($m['username']) ?></a></td>
                            <td><?= htmlspecialchars($m['email']) ?></td>
                            <td>
                                <?php if ($m['role'] === 'admin'): ?>
                                    <span class="badge" style="background:var(--color-accent);color:#fff;font-size:.7rem;">admin</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary" style="font-size:.7rem;">user</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/admin/manage.php?tab=members&edit_member=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary mr-1" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                                <?php if ($m['role'] === 'admin'): ?>
                                    <button class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete admin accounts"><i class="fas fa-trash"></i></button>
                                <?php else: ?>
                                    <form method="post" action="/admin/request_confirm.php" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                        <input type="hidden" name="type" value="delete_member">
                                        <input type="hidden" name="delete" value="<?= $m['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($allMembers)): ?><tr><td colspan="6" class="text-center text-muted py-4">No members yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div><!-- /tab-members -->


        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- GENRES TAB -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <div class="tab-pane fade <?= $activeTab === 'genres' ? 'show active' : '' ?>" id="tab-genres" role="tabpanel">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title mb-0" style="flex:1;">All Genres</div>
                <button class="btn btn-primary btn-sm ml-3" onclick="togglePanel('gn-form-panel', this)">
                    <i class="fas fa-plus mr-1"></i> Add Genre
                </button>
            </div>

            <!-- Genres form panel -->
            <div id="gn-form-panel" <?= $gn_showForm ? '' : 'style="display:none"' ?>>
                <div class="section-title"><?= $gn_editMode ? 'Edit Genre' : 'Add Genre' ?></div>
                <div class="form-panel" style="max-width:400px;">
                    <?php if ($gn_err): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($gn_err) ?></div>
                    <?php endif; ?>
                    <form method="post" action="/admin/manage.php" class="needs-validation" novalidate id="gn-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="action" value="<?= $gn_editMode ? 'edit_genre' : 'add_genre' ?>">
                        <?php if ($gn_editMode): ?><input type="hidden" name="id" value="<?= $gn_editId ?>"><?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Genre Name</label>
                            <input type="text" class="form-control" name="name" required minlength="3" maxlength="50"
                                value="<?= htmlspecialchars(!empty($gn_fd) ? ($gn_fd['name'] ?? '') : ($gn_cat['name'] ?? '')) ?>">
                            <div class="invalid-feedback">3–50 characters required.</div>
                        </div>
                        <div class="d-flex" style="gap:8px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i><?= $gn_editMode ? 'Update Genre' : 'Add Genre' ?>
                            </button>
                            <a href="/admin/manage.php?tab=genres" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Genres table -->
            <div class="table-responsive">
                <table class="table table-hover analytics-table" style="font-size:.88rem;">
                    <thead><tr><th>#</th><th>Genre</th><th>Events</th><th style="width:110px;">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($allGenres as $gn): ?>
                        <tr>
                            <td><?= $gn['id'] ?></td>
                            <td><?= htmlspecialchars($gn['name']) ?></td>
                            <td>
                                <span style="font-size:.78rem;color:var(--text-muted);"><?= $gn['event_count'] ?> event<?= $gn['event_count'] != 1 ? 's' : '' ?></span>
                                <?php if ($gn['event_count'] > 0): ?>
                                    <span class="fk-badge ml-1">has events</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/admin/manage.php?tab=genres&edit_genre=<?= $gn['id'] ?>" class="btn btn-sm btn-outline-primary mr-1" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                                <?php if ($gn['event_count'] > 0): ?>
                                    <button class="btn btn-sm btn-outline-secondary" disabled title="Remove all events in this genre before deleting."><i class="fas fa-trash"></i></button>
                                <?php else: ?>
                                    <form method="post" action="/admin/request_confirm.php" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                        <input type="hidden" name="type" value="delete_category">
                                        <input type="hidden" name="delete" value="<?= $gn['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($allGenres)): ?><tr><td colspan="4" class="text-center text-muted py-4">No genres yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div><!-- /tab-genres -->

    </div><!-- /tab-content -->
</div><!-- /container -->
</div><!-- /manage-body -->

<?php include "../inc/footer.inc.php"; ?>

<script>
'use strict';
function togglePanel(id, btn) {
    var panel = document.getElementById(id);
    var isHidden = panel.style.display === 'none' || panel.style.display === '';
    panel.style.display = isHidden ? 'block' : 'none';
    if (isHidden) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
// Bootstrap form validation on each form
document.querySelectorAll('.needs-validation').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
        form.classList.add('was-validated');
    });
});
// If a form panel is open on load (edit mode / error), scroll to it
document.addEventListener('DOMContentLoaded', function() {
    ['ev-form-panel','mb-form-panel','gn-form-panel'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el && el.style.display !== 'none') {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>
</body>
</html>
