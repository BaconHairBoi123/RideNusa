<style>
    /* Chatbot Widget Styles */
    #rns-chat-widget {
        position: fixed;
        bottom: 30px;
        right: 80px;
        /* Geser ke kiri agar tidak menimpa tombol Go Back Top */
        z-index: 9999;
        font-family: 'Inter', 'Outfit', sans-serif;
    }

    /* Floating Button */
    #rns-chat-btn {
        width: 60px;
        height: 60px;
        background-color: #FFB51D;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    #rns-chat-btn:hover {
        transform: scale(1.1);
    }

    #rns-chat-btn i {
        font-size: 28px;
        color: #1a1a1a;
    }

    /* Chat Window */
    #rns-chat-window {
        display: none;
        /* hidden by default */
        position: absolute;
        bottom: 80px;
        right: 0;
        width: 350px;
        height: 500px;
        background: rgba(30, 30, 30, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
        flex-direction: column;
        overflow: hidden;
    }

    /* Header */
    #rns-chat-header {
        background: #1a1a1a;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #FFB51D;
    }

    #rns-chat-header .header-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    #rns-chat-header .header-info img {
        height: 30px;
    }

    #rns-chat-header h4 {
        margin: 0;
        color: #fff;
        font-size: 16px;
        font-weight: 600;
    }

    #rns-chat-header p {
        margin: 0;
        color: #aaa;
        font-size: 12px;
    }

    #rns-chat-close {
        color: #fff;
        cursor: pointer;
        font-size: 20px;
    }

    /* Message Area */
    #rns-chat-body {
        flex: 1;
        padding: 15px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    /* Scrollbar style inside chat */
    #rns-chat-body::-webkit-scrollbar {
        width: 5px;
    }

    #rns-chat-body::-webkit-scrollbar-thumb {
        background: #FFB51D;
        border-radius: 5px;
    }

    .msg-bubble {
        max-width: 80%;
        padding: 10px 15px;
        border-radius: 15px;
        font-size: 14px;
        line-height: 1.4;
        word-wrap: break-word;
    }

    .msg-user {
        background: #FFB51D;
        color: #000;
        align-self: flex-end;
        border-bottom-right-radius: 0;
    }

    .msg-bot {
        background: #333;
        color: #fff;
        align-self: flex-start;
        border-bottom-left-radius: 0;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* Typing Indicator */
    .typing-indicator {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 10px 15px;
        background: transparent;
        color: #aaa;
        align-self: flex-start;
        font-size: 12px;
        font-style: italic;
    }

    /* Input Area */
    #rns-chat-input-area {
        display: flex;
        padding: 15px;
        background: #1a1a1a;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        gap: 10px;
    }

    #rns-chat-input {
        flex: 1;
        background: #333;
        border: 1px solid #444;
        color: #fff;
        padding: 10px 15px;
        border-radius: 20px;
        outline: none;
        font-size: 14px;
    }

    #rns-chat-input:focus {
        border-color: #FFB51D;
    }

    #rns-chat-send {
        background: #FFB51D;
        color: #000;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        transition: 0.2s;
    }

    #rns-chat-send:active {
        transform: scale(0.9);
    }
</style>

<div id="rns-chat-widget">
    <!-- Chat Window -->
    <div id="rns-chat-window">
        <div id="rns-chat-header">
            <div class="header-info">
                {{-- Gunakan fas fa-robot atau logo RideNusa --}}
                <i class="fas fa-robot" style="color: #FFB51D; font-size: 24px;"></i>
                <div>
                    <h4>Ride Nusa AI</h4>
                    <p>Powered by LLaMA 8b</p>
                </div>
            </div>
            <i class="fas fa-times" id="rns-chat-close"></i>
        </div>

        <div id="rns-chat-body">
            <div class="msg-bubble msg-bot">
                Halo! Saya asisten AI Ride Nusa yang berjalan di server lokal Anda. Ada yang bisa saya bantu hari ini
                terkait sewa motor?
            </div>
            <!-- Pesan akan ditambahkan di sini oleh JavaScript -->
        </div>

        <div id="rns-chat-input-area">
            <input type="text" id="rns-chat-input" placeholder="Ketik pesan Anda..." autocomplete="off">
            <button id="rns-chat-send"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <!-- Floating Toggle Button -->
    <div id="rns-chat-btn">
        <i class="fas fa-comment-dots"></i>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chatWidget = document.getElementById('rns-chat-widget');
        const chatBtn = document.getElementById('rns-chat-btn');
        const chatWindow = document.getElementById('rns-chat-window');
        const chatClose = document.getElementById('rns-chat-close');
        const chatBody = document.getElementById('rns-chat-body');
        const chatInput = document.getElementById('rns-chat-input');
        const chatSendBtn = document.getElementById('rns-chat-send');

        // ============================================
        // 1. SETTING WEBHOOK CLOUDFLARE TUNNEL / N8N ANDA
        // ============================================
        // Ganti URL ini dengan URL Webhook n8n Anda yang berjalan di Cloudflare Tunnel
        // Harus menggunakan method POST!
        const WEBHOOK_URL = 'https://squatter-revert-levitate.ngrok-free.dev/webhook/214733d5-35b7-45dc-8b1b-5e7f3fe1e7ad';


        // ============================================

        // Dynamic Session ID generator
        function generateSessionId() {
            return 'session_web_' + "{{ Auth::id() ?? 'guest' }}" + '_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);
        }
        let currentSessionId = generateSessionId();

        // Toggle opened/closed window
        chatBtn.addEventListener('click', () => {
            if (chatWindow.style.display === 'flex') {
                chatWindow.style.display = 'none';
                // Reset chat messages view
                chatBody.innerHTML = `
                    <div class="msg-bubble msg-bot">
                        Halo! Saya asisten AI Ride Nusa yang berjalan di server lokal Anda. Ada yang bisa saya bantu hari ini terkait sewa motor?
                    </div>
                `;
                currentSessionId = generateSessionId();
            } else {
                chatWindow.style.display = 'flex';
                // Focus on input when opened
                setTimeout(() => chatInput.focus(), 100);
            }
        });

        // Close button
        chatClose.addEventListener('click', () => {
            chatWindow.style.display = 'none';
            // Reset chat messages view
            chatBody.innerHTML = `
                <div class="msg-bubble msg-bot">
                    Halo! Saya asisten AI Ride Nusa yang berjalan di server lokal Anda. Ada yang bisa saya bantu hari ini terkait sewa motor?
                </div>
            `;
            currentSessionId = generateSessionId();
        });

        // Helper to append messages
        function appendMessage(text, sender) {
            const div = document.createElement('div');
            div.className = `msg-bubble msg-${sender}`;
            // Mencegah script injection basic
            div.textContent = text;
            chatBody.appendChild(div);
            // Scroll to bottom
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        function appendTypingIndicator() {
            const div = document.createElement('div');
            div.className = 'typing-indicator';
            div.id = 'typing-indicator';
            div.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Sedang berpikir...';
            chatBody.appendChild(div);
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        function removeTypingIndicator() {
            const indicator = document.getElementById('typing-indicator');
            if (indicator) {
                indicator.remove();
            }
        }

        // Send logic
        async function sendMessage() {
            const message = chatInput.value.trim();
            if (!message) return;

            // Bersihkan input
            chatInput.value = '';

            // Tampilkan pesan user
            appendMessage(message, 'user');

            // Tampilkan indikator ngetik
            appendTypingIndicator();

            try {
                // Konfigurasi Fetch URL ke Cloudflare Tunnels n8n Anda
                const response = await fetch(WEBHOOK_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message,
                        user_id: "{{ Auth::id() }}",
                        user_name: "{{ Auth::user()->name ?? 'Guest' }}",
                        session_id: currentSessionId,
                        platform: "web"
                    })
                });

                removeTypingIndicator();

                if (!response.ok) {
                    throw new Error('Jaringan bermasalah atau webhook mati.');
                }

                const data = await response.json();

                // Mendukung format Object {reply:...} maupun Array [{reply:...}]
                const result = Array.isArray(data) ? data[0] : data;
                let botReply = result.reply || result.output || result.message || "Terkoneksi, tapi format respons bot tidak cocok.";

                // Menghapus tanda = di awal jika ada (bug dari settingan n8n expression)
                botReply = botReply.replace(/^=/, '');

                // Menghapus awalan "Tolong ya" jika ada
                botReply = botReply.replace(/^Tolong ya[!,]?\s*/i, '');
                if (botReply.length > 0) {
                    botReply = botReply.charAt(0).toUpperCase() + botReply.slice(1);
                }

                appendMessage(botReply, 'bot');

            } catch (error) {
                removeTypingIndicator();
                appendMessage('Mohon maaf, koneksi ke tunnel Cloudflare n8n saat ini terputus. Pastikan lokal server menyala.', 'bot');
                console.error('Webhook Error:', error);
            }
        }

        // Event listeners untuk tombol kirim
        chatSendBtn.addEventListener('click', sendMessage);
        chatInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    });
</script>