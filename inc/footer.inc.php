<!-- footer -->
<div class="footer-area">
	<div class="container">
		<div class="row">
			<div class="col-lg-4 col-md-3">
				<div class="footer-box about-widget">
					<h2 class="widget-title">About us</h2>
					<p>Statik makes it easy to discover live performances and secure your tickets in minutes. No queues, no fuss — just your favourite events, right at your fingertips.</p>
				</div>
			</div>
			<div class="col-lg-4 col-md-3">
				<div class="footer-box get-in-touch">
					<h2 class="widget-title">Get in Touch</h2>
					<ul>
						<li>1 Punggol Coast Road, Singapore 828608</li>
						<li>inf1005.statik@gmail.com</li>
						<li>+65 6510 3000</li>
					</ul>
				</div>
			</div>
			<div class="col-lg-4 col-md-3">
				<div class="footer-box pages">
					<h2 class="widget-title">Shortcuts</h2>
					<ul>
						<li><a href="/">Home</a></li>
						<li><a href="/about.php">About</a></li>
						<li><a href="/contact.php">Contact</a></li>
						<li><a href="/shop.php">Shop</a></li>
						<li><a href="/account.php">Account</a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- end footer -->

<!-- copyright -->
<div class="copyright">
	<div class="container">
		<div class="row">
			<div class="col-lg-6 col-md-12">
				<p>
					Copyrights &copy; 2026 - <a href="https://www.singaporetech.edu.sg/">Statik</a>
					Singapore Institute of Technology. All Rights Reserved.
				</p>
			</div>
		</div>
	</div>
</div>
<!-- end copyright -->

<!-- chatbot popup widget -->
<div id="chat-popup" class="chat-popup" role="dialog" aria-label="Chat Assistant" aria-hidden="true">
    <div class="chat-popup-header">
        <div class="chat-popup-header-info">
            <div class="chat-header-icon"><i class="fas fa-robot"></i></div>
            <div>
                <div class="chat-popup-title">Statik Assistant</div>
                <div class="chat-popup-subtitle">Ask me about events &amp; tickets</div>
            </div>
        </div>
        <button class="chat-popup-close" id="chat-close-btn" aria-label="Close chat">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="chat-chips">
        <button class="chip" type="button">🎉 All events</button>
        <button class="chip" type="button">⏰ Coming soon</button>
        <button class="chip" type="button">🎸 Concerts</button>
        <button class="chip" type="button">🎭 Musicals</button>
        <button class="chip" type="button">😂 Comedy</button>
        <button class="chip" type="button">❓ Help</button>
    </div>
    <div class="chat-messages" id="chat-messages">
        <div class="msg-row bot">
            <div class="msg-avatar bot-avatar"><i class="fas fa-robot"></i></div>
            <div class="msg-bubble bot-msg">
                👋 Hi! I'm Statik's assistant.<br><br>
                Ask me about <strong>events, ticket prices, seat availability</strong>, and more!
            </div>
        </div>
    </div>
    <div class="chat-input-row">
        <input type="text" id="chat-input" placeholder="Ask about events, prices, venues..." maxlength="300" autocomplete="off" aria-label="Chat message">
        <button class="send-btn" id="send-btn" type="button" aria-label="Send">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<button class="chat-float-btn" id="chat-float-btn" aria-label="Open chat assistant" aria-expanded="false">
    <i class="fas fa-comment-dots"></i>
</button>

<script>
(function () {
    var popup  = document.getElementById('chat-popup');
    var floatBtn = document.getElementById('chat-float-btn');
    var closeBtn = document.getElementById('chat-close-btn');
    var messages = document.getElementById('chat-messages');
    var input    = document.getElementById('chat-input');
    var sendBtn  = document.getElementById('send-btn');
    var isOpen   = false;

    function togglePopup() {
        isOpen = !isOpen;
        popup.classList.toggle('chat-popup--open', isOpen);
        floatBtn.setAttribute('aria-expanded', isOpen);
        popup.setAttribute('aria-hidden', !isOpen);
        floatBtn.innerHTML = isOpen
            ? '<i class="fas fa-times"></i>'
            : '<i class="fas fa-comment-dots"></i>';
        if (isOpen) { input.focus(); }
    }

    floatBtn.addEventListener('click', togglePopup);
    closeBtn.addEventListener('click', togglePopup);

    function scrollBottom() {
        messages.scrollTop = messages.scrollHeight;
    }

    function addMessage(text, isUser) {
        var row    = document.createElement('div');
        row.className = 'msg-row ' + (isUser ? 'user' : 'bot');
        var avatar = document.createElement('div');
        avatar.className = 'msg-avatar ' + (isUser ? 'user-avatar' : 'bot-avatar');
        avatar.innerHTML = isUser ? '<i class="fas fa-user"></i>' : '<i class="fas fa-robot"></i>';
        var bubble = document.createElement('div');
        bubble.className = 'msg-bubble ' + (isUser ? 'user-msg' : 'bot-msg');
        if (isUser) {
            bubble.textContent = text;
        } else {
            bubble.innerHTML = text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/\n/g, '<br>');
        }
        row.appendChild(avatar);
        row.appendChild(bubble);
        messages.appendChild(row);
        scrollBottom();
    }

    function showTyping() {
        var row = document.createElement('div');
        row.id = 'chat-typing';
        row.className = 'msg-row bot';
        row.innerHTML = '<div class="msg-avatar bot-avatar"><i class="fas fa-robot"></i></div>'
            + '<div class="msg-bubble bot-msg"><div class="typing"><span></span><span></span><span></span></div></div>';
        messages.appendChild(row);
        scrollBottom();
    }

    function hideTyping() {
        var el = document.getElementById('chat-typing');
        if (el) el.remove();
    }

    function sendMessage(text) {
        if (!text.trim()) return;
        addMessage(text, true);
        input.value = '';
        sendBtn.disabled = true;
        showTyping();
        var fd = new FormData();
        fd.append('message', text);
        fetch('/chatbot_handler.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                hideTyping();
                addMessage(d.reply || 'Sorry, something went wrong.', false);
                sendBtn.disabled = false;
                input.focus();
            })
            .catch(function () {
                hideTyping();
                addMessage('⚠️ Could not reach the server. Please try again.', false);
                sendBtn.disabled = false;
            });
    }

    sendBtn.addEventListener('click', function () { sendMessage(input.value.trim()); });
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); sendMessage(input.value.trim()); }
    });

    document.querySelectorAll('.chat-chips .chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            var text = chip.textContent.replace(/[^\x20-\x7E]/g, '').trim();
            sendMessage(text);
        });
    });
}());
</script>

<!-- jquery -->
<script src="/js/jquery-1.11.3.min.js"></script>
<!-- bootstrap -->
<script src="/bootstrap/js/bootstrap.min.js"></script>
<!-- count down -->
<script src="/js/jquery.countdown.js"></script>
<!-- isotope -->
<script src="/js/jquery.isotope-3.0.6.min.js"></script>
<!-- waypoints -->
<script src="/js/waypoints.js"></script>
<!-- owl carousel -->
<script src="/js/owl.carousel.min.js"></script>
<!-- magnific popup -->
<script src="/js/jquery.magnific-popup.min.js"></script>
<!-- mean menu -->
<script src="/js/jquery.meanmenu.min.js"></script>
<!-- sticker js -->
<script src="/js/sticker.js"></script>
<!-- main js -->
<script src="/js/main.js"></script>
