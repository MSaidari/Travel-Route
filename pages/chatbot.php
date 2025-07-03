<style>
#chatbot-icon {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #007bff;
    color: white;
    padding: 15px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 24px;
    z-index: 1000;
}

#chatbot-container {
    display: none;
    position: fixed;
    bottom: 80px;
    right: 20px;
    width: 300px;
    height: 400px;
    background: white;
    border: 1px solid #ccc;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
    z-index: 1000;
    flex-direction: column;
}

#chatbot-header {
    background: #007bff;
    color: white;
    padding: 10px;
    text-align: center;
    font-weight: bold;
    position: relative;
}

#chatbot-close {
    position: absolute;
    right: 10px;
    top: 5px;
    cursor: pointer;
    font-size: 20px;
}

#chatbot-messages {
    flex: 1;
    padding: 10px;
    overflow-y: auto;
    font-size: 14px;
}

#chatbot-input-area {
    display: flex;
    padding: 10px;
    border-top: 1px solid #ccc;
}

#chatbot-input {
    flex: 1;
    padding: 5px;
    font-size: 14px;
}

#chatbot-input-area button {
    padding: 5px 10px;
    background: #007bff;
    color: white;
    border: none;
    cursor: pointer;
    margin-left: 5px;
    font-size: 14px;
}
</style>

<!-- Chatbot Başlangıcı -->
<div id="chatbot-icon" onclick="toggleChatbot()">💬</div>

<div id="chatbot-container">
    <div id="chatbot-header">
        Chatbot
        <span id="chatbot-close" onclick="toggleChatbot()">×</span>
    </div>
    <div id="chatbot-messages"></div>
    <div id="chatbot-input-area">
        <input type="text" id="chatbot-input" placeholder="Bir şey yazın...">
        <button id="chatbot-send">Gönder</button>
        </div>
</div>
<!-- Chatbot Bitişi -->
<script>
(function(){
  const handlerUrl = 'chat_handler_flow.php';  // Gerekirse '/pages/chat_handler.php' gibi tam yol verin

  const icon      = document.getElementById('chatbot-icon');
  const container = document.getElementById('chatbot-container');
  const closeBtn  = document.getElementById('chatbot-close');
  const messages  = document.getElementById('chatbot-messages');
  const input     = document.getElementById('chatbot-input');
  const sendBtn   = document.getElementById('chatbot-send');

  icon.addEventListener('click', toggleChat);
  closeBtn.addEventListener('click', toggleChat);
  sendBtn.addEventListener('click', sendMessage);
  input.addEventListener('keypress', e => { if (e.key==='Enter') sendMessage(); });

  function toggleChat(){
    const isVisible = container.style.display === 'flex';
    container.style.display = isVisible ? 'none' : 'flex';
    if (!isVisible) input.focus();
  }

  function addMessage(sender, text){
    const el = document.createElement('div');
    el.innerHTML = `<strong>${sender}:</strong> ${text}`;
    messages.appendChild(el);
    messages.scrollTop = messages.scrollHeight;
  }

  async function sendMessage(){
    const text = input.value.trim();
    if (!text) return;
    addMessage('Kullanıcı', text);
    input.value = '';

    // "Bot yazıyor..." göstergesi
    const loading = document.createElement('div');
    loading.id = 'bot-loading';
    loading.innerHTML = '<em>Bot yazıyor...</em>';
    messages.appendChild(loading);
    messages.scrollTop = messages.scrollHeight;

    try {
        const res  = await fetch(handlerUrl, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ message: text })
        });

        const rawText = await res.text();  // önce düz metin olarak al
        let data;
        try {
            data = JSON.parse(rawText);  // sonra JSON'a çevir
        } catch (e) {
            messages.removeChild(loading);
            addMessage('Bot', 'Ne dediğinizi anlayamadım. Lütfen tekrar deneyin.');
            return;
        }

        messages.removeChild(loading);

        if (data.reply) {
            addMessage('Bot', data.reply);
        } else if (data.error) {
            addMessage('Bot', `⚠️ Hata: ${data.error}`);
        } else {
            addMessage('Bot', 'Ne dediğinizi anlayamadım. Lütfen tekrar deneyin.');
        }
    } catch (err) {
        messages.removeChild(loading);
        addMessage('Bot', `Sunucu hatası: ${err.message}`);
    }
}

})();
// Sayfa açılır açılmaz başlangıç mesajını tetikle
window.addEventListener('DOMContentLoaded', () => {
    fetch('chat_handler_flow.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ message: '' })
    })
    .then(res => res.json())
    .then(data => {
        if (data.reply) {
            addMessage('Bot', data.reply);
        }
    });
});
function sendMessageText(text) {
    document.getElementById('chatbot-input').value = text;
    sendMessage();
}
</script>

<!-- 
    Kaydırmalı olması için 
  
<script>
dragElement(document.getElementById("chatbot-container"));

function dragElement(elmnt) {
  var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
  if (document.getElementById("chatbot-header")) {
    /* eğer bir header varsa, onu sürükle */
    document.getElementById("chatbot-header").onmousedown = dragMouseDown;
  } else {
    /* yoksa tüm kutuyu sürükle */
    elmnt.onmousedown = dragMouseDown;
  }

  function dragMouseDown(e) {
    e = e || window.event;
    e.preventDefault();
    // Mouse başlangıç pozisyonları
    pos3 = e.clientX;
    pos4 = e.clientY;
    document.onmouseup = closeDragElement;
    // mouse hareket ederken çağır
    document.onmousemove = elementDrag;
  }

  function elementDrag(e) {
    e = e || window.event;
    e.preventDefault();
    // Ne kadar hareket etti
    pos1 = pos3 - e.clientX;
    pos2 = pos4 - e.clientY;
    pos3 = e.clientX;
    pos4 = e.clientY;
    // yeni pozisyonu ayarla
    elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
    elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
  }

  function closeDragElement() {
    /* hareketi bırak */
    document.onmouseup = null;
    document.onmousemove = null;
  }
}
</script>
-->