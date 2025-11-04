<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

require_login();
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

$page_title = "Chat";
require_once 'header.php'; 
?>

<style>
/* Chat-specific Styles */
.chat-container { display: flex; height: calc(100vh - 180px); background: var(--light-color); border-radius: var(--border-radius); box-shadow: var(--shadow); overflow: hidden; }
.contacts-panel { width: 30%; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; }
.contacts-header { padding: 1rem; font-weight: 600; border-bottom: 1px solid var(--border-color); }
.contacts-list { overflow-y: auto; flex-grow: 1; }
.contact-item { display: flex; align-items: center; padding: 1rem; cursor: pointer; border-bottom: 1px solid var(--border-color); transition: background-color 0.2s ease; }
.contact-item:hover, .contact-item.active { background-color: var(--background-color); }
.contact-item .username { font-weight: 500; }
.messages-panel { width: 70%; display: flex; flex-direction: column; }
.messages-header { padding: 1rem; border-bottom: 1px solid var(--border-color); font-weight: 600; }
.messages-body { flex-grow: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; }
.messages-welcome { margin: auto; text-align: center; color: var(--text-color-light); }
.message-bubble { max-width: 70%; padding: 0.75rem 1rem; border-radius: 1.2rem; margin-bottom: 0.5rem; line-height: 1.4; word-wrap: break-word; }
.message-bubble.sent { background-color: var(--secondary-color); color: var(--light-color); align-self: flex-end; border-bottom-right-radius: 0.2rem; }
.message-bubble.received { background-color: #eef2f7; color: var(--text-color); align-self: flex-start; border-bottom-left-radius: 0.2rem; }
.messages-footer { padding: 1rem; border-top: 1px solid var(--border-color); }
.messages-footer form { display: flex; gap: 1rem; }
.messages-footer input { flex-grow: 1; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 2rem; }
.messages-footer button { border-radius: 50%; width: 45px; height: 45px; border: none; background-color: var(--secondary-color); color: var(--light-color); cursor: pointer; }
</style>

<div class="chat-container">
    <div class="contacts-panel">
        <div class="contacts-header">Contacts</div>
        <div id="contacts-list" class="contacts-list"></div>
    </div>
    <div class="messages-panel">
        <div id="messages-header" class="messages-header">Select a conversation</div>
        <div id="messages-body" class="messages-body">
            <div class="messages-welcome">
                <i class="fas fa-comments" style="font-size: 4rem;"></i>
                <p>Select a contact to start chatting.</p>
            </div>
        </div>
        <div class="messages-footer">
            <form id="message-form" style="display: none;">
                <input type="hidden" id="receiver-id-input" name="receiver_id">
                <input type="text" id="message-input" name="message" placeholder="Type a message..." autocomplete="off" required>
                <button type="submit"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactsList = document.getElementById('contacts-list');
    const messagesHeader = document.getElementById('messages-header');
    const messagesBody = document.getElementById('messages-body');
    const messageForm = document.getElementById('message-form');
    const receiverIdInput = document.getElementById('receiver-id-input');
    const messageInput = document.getElementById('message-input');
    
    let activeReceiverId = null;
    let messagePollingInterval = null;

    async function fetchContacts() {
        try {
            const response = await fetch('api_get_conversations.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contacts = await response.json();
            
            contactsList.innerHTML = '';
            if (contacts.length === 0) {
                contactsList.innerHTML = '<p style="padding: 1rem; color: var(--text-color-light);">No contacts available.</p>';
                return;
            }

            contacts.forEach(contact => {
                const contactDiv = document.createElement('div');
                contactDiv.className = 'contact-item';
                contactDiv.dataset.userId = contact.id;
                contactDiv.innerHTML = `<span class="username">${contact.username}</span>`;
                contactDiv.addEventListener('click', () => loadConversation(contact.id, contact.username));
                contactsList.appendChild(contactDiv);
            });
        } catch(e) {
            console.error("Error fetching contacts:", e);
            contactsList.innerHTML = '<p style="padding: 1rem; color: var(--danger-color);">Could not load contacts.</p>';
        }
    }

    async function loadConversation(receiverId, username) {
        activeReceiverId = receiverId;
        
        document.querySelectorAll('.contact-item').forEach(c => c.classList.remove('active'));
        document.querySelector(`.contact-item[data-user-id='${receiverId}']`).classList.add('active');

        messagesHeader.textContent = `Chat with ${username}`;
        receiverIdInput.value = receiverId;
        messageForm.style.display = 'flex';
        
        await fetchMessages();

        if (messagePollingInterval) clearInterval(messagePollingInterval);
        messagePollingInterval = setInterval(fetchMessages, 5000);
    }

    async function fetchMessages() {
        if (!activeReceiverId) return;
        
        const response = await fetch(`api_get_messages.php?receiver_id=${activeReceiverId}`);
        const messages = await response.json();
        
        messagesBody.innerHTML = '';
        messages.forEach(msg => {
            const bubble = document.createElement('div');
            bubble.className = 'message-bubble';
            bubble.classList.add(msg.sender_id == <?= $current_user_id ?> ? 'sent' : 'received');
            bubble.textContent = msg.body;
            messagesBody.appendChild(bubble);
        });

        messagesBody.scrollTop = messagesBody.scrollHeight;
    }

    messageForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (!messageInput.value.trim()) return;

        const formData = new FormData(this);
        
        const response = await fetch('api_send_message.php', {
            method: 'POST',
            body: formData
        });

        // Check if the send was successful before clearing and fetching
        if (response.ok) {
            messageInput.value = '';
            await fetchMessages();
        } else {
            console.error("Failed to send message.");
        }
    });

    // Initial load
    fetchContacts();
});
</script>

<?php require_once 'footer.php'; ?>