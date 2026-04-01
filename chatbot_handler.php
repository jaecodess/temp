<?php
// handles chatbot messages from the frontend
// receives POST request with 'message' field, returns JSON reply

require_once 'inc/db.inc.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['reply' => 'Invalid request.']);
    exit;
}

// get and sanitise the message
$msg = isset($_POST['message']) ? trim($_POST['message']) : '';
$msg = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
$lower = strtolower($msg);

if ($lower === '') {
    echo json_encode(['reply' => 'Please type something!']);
    exit;
}

$conn = getDbConnection();

// get all events with genre and cheapest price
function getAllEvents($conn) {
    $sql = "SELECT p.id, p.name, p.venue, p.event_date, p.event_time,
                   g.name AS genre,
                   MIN(tc.price) AS min_price,
                   SUM(tc.available_seats) AS total_available
            FROM performances p
            LEFT JOIN genres g ON p.genre_id = g.id
            LEFT JOIN ticket_categories tc ON tc.performance_id = p.id
            GROUP BY p.id
            ORDER BY p.event_date ASC";
    $result = $conn->query($sql);
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    return $events;
}

// get ticket categories for a specific performance
function getTicketCategories($conn, $pid) {
    $stmt = $conn->prepare("SELECT name, price, available_seats, total_seats FROM ticket_categories WHERE performance_id = ? ORDER BY name");
    $stmt->bind_param('i', $pid);
    $stmt->execute();
    $result = $stmt->get_result();
    $cats = [];
    while ($row = $result->fetch_assoc()) {
        $cats[] = $row;
    }
    $stmt->close();
    return $cats;
}

// try to find an event name mentioned in the user message
function findEvent($conn, $lower) {
    $result = $conn->query("SELECT id, name FROM performances");
    while ($row = $result->fetch_assoc()) {
        if (strpos($lower, strtolower($row['name'])) !== false) {
            return (int)$row['id'];
        }
        // check if at least 2 words from the event name appear in message
        $words = explode(' ', strtolower($row['name']));
        $count = 0;
        foreach ($words as $w) {
            if (strlen($w) >= 4 && strpos($lower, $w) !== false) {
                $count++;
            }
        }
        if ($count >= 2) {
            return (int)$row['id'];
        }
    }
    return null;
}

// get one performance by id
function getEvent($conn, $id) {
    $stmt = $conn->prepare("SELECT p.*, g.name AS genre FROM performances p LEFT JOIN genres g ON p.genre_id = g.id WHERE p.id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

function fmtDate($d) { return date('D, d M Y', strtotime($d)); }
function fmtTime($t) { return date('g:i A', strtotime($t)); }

$reply = '';

// check for greetings
$greetings = ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening', 'yo', 'sup'];
foreach ($greetings as $g) {
    if (strpos($lower, $g) !== false) {
        $reply = "👋 Hi! I'm Statik's assistant. I can help you with:\n\n"
               . "• 🎟️ **Upcoming events** — \"What events are on?\"\n"
               . "• 💰 **Ticket prices** — \"How much are tickets for Hamilton?\"\n"
               . "• 🪑 **Seat availability** — \"Are there seats left for BTS?\"\n"
               . "• 📍 **Venue & date** — \"Where is Swan Lake?\"\n"
               . "• 🎭 **Browse by genre** — \"Show me concerts\"\n\n"
               . "What would you like to know?";
        break;
    }
}

// help command
if (!$reply && (strpos($lower, 'help') !== false || $lower === '?')) {
    $reply = "🤖 **Here's what I can help with:**\n\n"
           . "• **List events** — \"Show all events\"\n"
           . "• **Event details** — \"Tell me about Hamilton\"\n"
           . "• **Prices** — \"How much are tickets for BTS?\"\n"
           . "• **Availability** — \"Are there seats left for Swan Lake?\"\n"
           . "• **Venue** — \"Where is Lion King held?\"\n"
           . "• **Date/Time** — \"When is the IDLE concert?\"\n"
           . "• **Browse genre** — \"Show me concerts\" / \"List musicals\"\n"
           . "• **Coming soon** — \"What's coming up soon?\"\n\n"
           . "Just ask naturally and I'll try my best! 😊";
}

// genre browsing
if (!$reply) {
    $genres = [
        'concert'  => 'Concert',
        'concerts' => 'Concert',
        'musical'  => 'Musical',
        'musicals' => 'Musical',
        'theatre'  => 'Theatre',
        'theater'  => 'Theatre',
        'dance'    => 'Dance',
        'comedy'   => 'Comedy',
        'comedies' => 'Comedy',
        'stand-up' => 'Comedy',
        'standup'  => 'Comedy',
        'k-pop'    => 'Concert',
        'kpop'     => 'Concert',
        'ballet'   => 'Dance',
    ];

    $matched = null;
    foreach ($genres as $kw => $genre) {
        if (strpos($lower, $kw) !== false) {
            $matched = $genre;
            break;
        }
    }

    if ($matched) {
        $stmt = $conn->prepare(
            "SELECT p.id, p.name, p.venue, p.event_date, p.event_time, MIN(tc.price) AS min_price
             FROM performances p
             JOIN genres g ON p.genre_id = g.id
             LEFT JOIN ticket_categories tc ON tc.performance_id = p.id
             WHERE g.name = ?
             GROUP BY p.id
             ORDER BY p.event_date ASC"
        );
        $stmt->bind_param('s', $matched);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($rows)) {
            $reply = "😕 No **$matched** events found right now. Check back soon or browse all events at <a href='/shop.php'>Events</a>.";
        } else {
            $reply = "🎭 **$matched events:**\n\n";
            foreach ($rows as $e) {
                $reply .= "🎟️ **<a href='/item.php?id={$e['id']}'>{$e['name']}</a>**\n"
                        . "   📅 " . fmtDate($e['event_date']) . " at " . fmtTime($e['event_time']) . "\n"
                        . "   📍 {$e['venue']}\n"
                        . "   💰 From $" . number_format($e['min_price'], 2) . "\n\n";
            }
        }
    }
}

// show all events
if (!$reply) {
    $keywords = ['all events', 'show events', "what's on", "what is on", 'upcoming events',
                 'list events', 'show all', 'what events', 'events available', 'events do you have'];
    foreach ($keywords as $kw) {
        if (strpos($lower, $kw) !== false) {
            $events = getAllEvents($conn);
            if (empty($events)) {
                $reply = "No events at the moment, please check back soon!";
            } else {
                $reply = "🎉 **Upcoming events:**\n\n";
                foreach ($events as $e) {
                    $avail = $e['total_available'] > 0 ? "🟢 {$e['total_available']} seats left" : "🔴 Sold out";
                    $reply .= "🎟️ **<a href='/item.php?id={$e['id']}'>{$e['name']}</a>**\n"
                            . "   📅 " . fmtDate($e['event_date']) . "\n"
                            . "   📍 {$e['venue']}\n"
                            . "   💰 From $" . number_format($e['min_price'], 2) . " · $avail\n\n";
                }
                $reply .= "Click any event name to view details and book tickets!";
            }
            break;
        }
    }
}

// coming up soon
if (!$reply && (strpos($lower, 'soon') !== false || strpos($lower, 'coming up') !== false || strpos($lower, 'next event') !== false)) {
    $result = $conn->query(
        "SELECT p.id, p.name, p.venue, p.event_date, p.event_time, MIN(tc.price) AS min_price
         FROM performances p
         LEFT JOIN ticket_categories tc ON tc.performance_id = p.id
         WHERE p.event_date >= CURDATE()
         GROUP BY p.id
         ORDER BY p.event_date ASC
         LIMIT 3"
    );
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    if (empty($rows)) {
        $reply = "No upcoming events right now, check back soon!";
    } else {
        $reply = "⏰ **Coming up soon:**\n\n";
        foreach ($rows as $e) {
            $reply .= "🎟️ **<a href='/item.php?id={$e['id']}'>{$e['name']}</a>**\n"
                    . "   📅 " . fmtDate($e['event_date']) . " at " . fmtTime($e['event_time']) . "\n"
                    . "   📍 {$e['venue']}\n"
                    . "   💰 From $" . number_format($e['min_price'], 2) . "\n\n";
        }
    }
}

// event specific questions - find which event they are asking about
if (!$reply) {
    $eid = findEvent($conn, $lower);

    if ($eid) {
        $event = getEvent($conn, $eid);
        $cats  = getTicketCategories($conn, $eid);

        if (preg_match('/price|cost|how much|cheap|expensive/', $lower)) {
            // ticket prices
            $reply = "💰 **Ticket prices for {$event['name']}:**\n\n";
            foreach ($cats as $c) {
                $status = $c['available_seats'] > 0 ? "✅ {$c['available_seats']} seats left" : "❌ Sold out";
                $reply .= "• **{$c['name']}** — $" . number_format($c['price'], 2) . " ($status)\n";
            }
            $reply .= "\n<a href='/item.php?id={$eid}'>👉 Book now</a>";

        } elseif (preg_match('/seat|availab|left|sold out|space/', $lower)) {
            // seat availability
            $total = array_sum(array_column($cats, 'available_seats'));
            if ($total === 0) {
                $reply = "😔 Sorry, **{$event['name']}** is sold out.";
            } else {
                $reply = "🪑 **Seats available for {$event['name']}:**\n\n";
                foreach ($cats as $c) {
                    $pct = $c['total_seats'] > 0 ? round(($c['available_seats'] / $c['total_seats']) * 100) . "% remaining" : "";
                    $reply .= "• **{$c['name']}** — {$c['available_seats']} / {$c['total_seats']} seats ($pct)\n";
                }
                $reply .= "\n<a href='/item.php?id={$eid}'>👉 Book now</a>";
            }

        } elseif (preg_match('/venue|where|location|held/', $lower)) {
            // venue info
            $reply = "📍 **{$event['name']}** is at **{$event['venue']}**\n\n"
                   . "📅 " . fmtDate($event['event_date']) . " at " . fmtTime($event['event_time']) . "\n\n"
                   . "<a href='/item.php?id={$eid}'>View event</a>";

        } elseif (preg_match('/date|when|time|day|schedule/', $lower)) {
            // date and time
            $reply = "📅 **{$event['name']}** is on **" . fmtDate($event['event_date']) . "** at **" . fmtTime($event['event_time']) . "**\n"
                   . "📍 {$event['venue']}\n\n"
                   . "<a href='/item.php?id={$eid}'>View event</a>";

        } else {
            // general event info
            $minPrice = !empty($cats) ? min(array_column($cats, 'price')) : null;
            $totalSeats = array_sum(array_column($cats, 'available_seats'));
            $availText = $totalSeats > 0 ? "🟢 $totalSeats seats available" : "🔴 Sold out";

            $reply = "🎟️ **{$event['name']}**\n\n"
                   . "{$event['description']}\n\n"
                   . "📅 " . fmtDate($event['event_date']) . " at " . fmtTime($event['event_time']) . "\n"
                   . "📍 {$event['venue']}\n"
                   . "🎭 " . ($event['genre'] ?? 'N/A') . "\n"
                   . ($minPrice ? "💰 From $" . number_format($minPrice, 2) . "\n" : '')
                   . "$availText\n\n"
                   . "<a href='/item.php?id={$eid}'>👉 View & book tickets</a>";
        }
    }
}

// thank you
if (!$reply && preg_match('/thank|thanks|cheers|great|awesome/', $lower)) {
    $reply = "😊 You're welcome! Let me know if you need anything else.\n\nBrowse all events at <a href='/shop.php'>Events</a>!";
}

// goodbye
if (!$reply && preg_match('/bye|goodbye|see you|later/', $lower)) {
    $reply = "👋 Bye! Hope to see you at a show soon 🎶";
}

// fallback if nothing matched
if (!$reply) {
    $events = getAllEvents($conn);
    $names = array_map(fn($e) => "<em>{$e['name']}</em>", $events);
    $reply = "🤔 I didn't quite get that. Try asking:\n\n"
           . "• \"Show all events\"\n"
           . "• \"Prices for [event name]\"\n"
           . "• \"Seats left for [event name]\"\n"
           . "• \"When is [event name]?\"\n"
           . "• \"Show me concerts\"\n\n"
           . "**Events we have:** " . implode(', ', $names) . "\n\n"
           . "Or type **help** for more options.";
}

$conn->close();
echo json_encode(['reply' => $reply]);