<?php
require_once 'inc/auth.inc.php';
$pageTitle = 'Chat Assistant';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'inc/head.inc.php'; ?>
    <style>
        .chatbot-section {
            padding: 80px 0 100px;
            background: var(--bg-body);
            min-height: 75vh;
        }

        .chat-wrapper {
            max-width: 750px;
            margin: 0 auto;
            background: var(--surface-card);
            border-radius: 12px;
            box-shadow: var(--shadow-card);
            display: flex;
            flex-direction: column;
            height: 600px;
            overflow: hidden;
        }

        .chat-header {
            background: var(--color-accent);
            color: white;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chat-header-icon {
            width: 42px;
            height: 42px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .chat-header h2 {
            margin: 0;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
        }

        .chat-header p {
            margin: 0;
            font-size: 12px;
            opacity: 0.8;
        }

        /* quick suggestion buttons */
        .chat-chips {
            padding: 8px 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            border-bottom: 1px solid #eee;
            background: #fff;
        }

        .chip {
            background: rgba(14,159,173,0.08);
            color: var(--color-accent);
            border: 1px solid rgba(14,159,173,0.25);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .chip:hover {
            background: var(--color-accent);
            color: white;
        }

        /* messages area */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .chat-messages::-webkit-scrollbar { width: 4px; }
        .chat-messages::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }

        .msg-row {
            display: flex;
            gap: 8px;
            align-items: flex-end;
            max-width: 85%;
        }

        .msg-row.user { 
            align-self: flex-end; 
            flex-direction: row-reverse; 
        }

        .msg-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }

        .bot-avatar { background: rgba(14,159,173,0.12); color: var(--color-accent); }
        .user-avatar { background: var(--color-accent); color: white; }

        .msg-bubble {
            padding: 10px 14px;
            border-radius: 14px;
            font-size: 14px;
            line-height: 1.6;
            font-family: 'Open Sans', sans-serif;
        }

        .bot-msg {
            background: var(--bg-warm-gray);
            color: #333;
            border-bottom-left-radius: 3px;
        }

        .user-msg {
            background: var(--color-accent);
            color: white;
            border-bottom-right-radius: 3px;
        }

        .bot-msg a {
            color: var(--color-accent);
            font-weight: 600;
        }

        /* typing dots */
        .typing {
            display: flex;
            gap: 4px;
            padding: 10px 14px;
            align-items: center;
        }

        .typing span {
            width: 6px;
            height: 6px;
            background: #bbb;
            border-radius: 50%;
            animation: bounce 1.2s infinite;
        }

        .typing span:nth-child(2) { animation-delay: 0.2s; }
        .typing span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes bounce {
            0%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-6px); }
        }

        /* input */
        .chat-input {
            padding: 12px 14px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .chat-input input {
            flex: 1;
            border: 1.5px solid #ddd;
            border-radius: 20px;
            padding: 9px 16px;
            font-size: 14px;
            outline: none;
            font-family: 'Open Sans', sans-serif;
        }

        .chat-input input:focus {
            border-color: var(--color-accent);
        }

        .send-btn {
            width: 40px;
            height: 40px;
            background: var(--color-accent);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .send-btn:hover { background: var(--color-accent-hover); }
        .send-btn:disabled { background: #ccc; cursor: not-allowed; }

        @media (max-width: 576px) {
            .chat-wrapper { height: 88vh; border-radius: 0; }
            .chatbot-section { padding: 0; }
        }
    </style>
</head>

<body>
    <?php include 'inc/header.inc.php'; ?>
    <?php include 'inc/search.inc.php'; ?>

    <!-- breadcrumb -->
    <div class="breadcrumb-section breadcrumb-bg">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2 text-center">
                    <div class="breadcrumb-text">
                        <p class="breadcrumb-label">Need Help?</p>
                        <h1>Chat Assistant</h1>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="chatbot-section" id="main-content">
        <div class="container">
            <div class="chat-wrapper">

                <div class="chat-header">
                    <div class="chat-header-icon">🤖</div>
                    <div>
                        <h2>Statik Assistant</h2>
                        <p>Ask me anything about events and tickets</p>
                    </div>
                </div>

                <!-- quick chips -->
                <div class="chat-chips">
                    <button class="chip" onclick="useChip(this)" type="button">🎉 All events</button>
                    <button class="chip" onclick="useChip(this)" type="button">⏰ Coming soon</button>
                    <button class="chip" onclick="useChip(this)" type="button">🎸 Concerts</button>
                    <button class="chip" onclick="useChip(this)" type="button">🎭 Musicals</button>
                    <button class="chip" onclick="useChip(this)" type="button">😂 Comedy</button>
                    <button class="chip" onclick="useChip(this)" type="button">❓ Help</button>
                </div>

                <!-- chat messages -->
                <div class="chat-messages" id="chat-messages">
                    <div class="msg-row bot">
                        <div class="msg-avatar bot-avatar"><i class="fas fa-robot"></i></div>
                        <div class="msg-bubble bot-msg">
                            👋 Hi! I'm Statik's assistant.<br><br>
                            Ask me about <strong>events, ticket prices, seat availability</strong>, and more!<br><br>
                            Try: <em>"Show all events"</em> or <em>"Prices for Hamilton"</em> 🎟️
                        </div>
                    </div>
                </div>

                <!-- input -->
                <div class="chat-input">
                    <input type="text" id="chat-input" placeholder="Ask about events, prices, venues..." maxlength="300" autocomplete="off">
                    <button class="send-btn" id="send-btn" type="button">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>

            </div>
        </div>
    </section>

    <?php include 'inc/footer.inc.php'; ?>

    <script>
        var messagesDiv = document.getElementById('chat-messages');
        var inputEl = document.getElementById('chat-input');
        var sendBtn = document.getElementById('send-btn');

        function addMessage(text, isUser) {
            var row = document.createElement('div');
            row.className = 'msg-row ' + (isUser ? 'user' : 'bot');

            var avatar = document.createElement('div');
            avatar.className = 'msg-avatar ' + (isUser ? 'user-avatar' : 'bot-avatar');
            avatar.innerHTML = isUser ? '<i class="fas fa-user"></i>' : '<i class="fas fa-robot"></i>';

            var bubble = document.createElement('div');
            bubble.className = 'msg-bubble ' + (isUser ? 'user-msg' : 'bot-msg');

            if (isUser) {
                bubble.textContent = text;
            } else {
                bubble.innerHTML = formatText(text);
            }

            row.appendChild(avatar);
            row.appendChild(bubble);
            messagesDiv.appendChild(row);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        // formats **bold** and newlines in bot replies
        function formatText(text) {
            return text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/\n/g, '<br>');
        }

        function showTyping() {
            var row = document.createElement('div');
            row.className = 'msg-row bot';
            row.id = 'typing';

            var avatar = document.createElement('div');
            avatar.className = 'msg-avatar bot-avatar';
            avatar.innerHTML = '<i class="fas fa-robot"></i>';

            var bubble = document.createElement('div');
            bubble.className = 'msg-bubble bot-msg';
            bubble.innerHTML = '<div class="typing"><span></span><span></span><span></span></div>';

            row.appendChild(avatar);
            row.appendChild(bubble);
            messagesDiv.appendChild(row);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function hideTyping() {
            var el = document.getElementById('typing');
            if (el) el.remove();
        }

        function sendMessage(text) {
            if (!text.trim()) return;

            addMessage(text, true);
            inputEl.value = '';
            sendBtn.disabled = true;
            showTyping();

            var formData = new FormData();
            formData.append('message', text);

            fetch('/chatbot_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                hideTyping();
                addMessage(data.reply || "Sorry, something went wrong.", false);
                sendBtn.disabled = false;
                inputEl.focus();
            })
            .catch(function() {
                hideTyping();
                addMessage("⚠️ Could not reach the server. Please try again.", false);
                sendBtn.disabled = false;
            });
        }

        sendBtn.addEventListener('click', function() {
            sendMessage(inputEl.value.trim());
        });

        inputEl.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendMessage(inputEl.value.trim());
            }
        });

        // chip button click
        function useChip(btn) {
            var text = btn.textContent.replace(/[^\x20-\x7E]/g, '').trim();
            sendMessage(text);
        }
    </script>
</body>
</html>