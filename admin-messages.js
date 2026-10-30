// Admin messages: feedback and partner requests from the Help pages

const messagesList = document.querySelector('#messages-list');
const messagesSummary = document.querySelector('#messages-summary');
const messagesType = document.querySelector('#messages-type');
const messagesStatus = document.querySelector('#messages-status');

async function loadMessages() {
    const params = new URLSearchParams({ type: messagesType.value, status: messagesStatus.value });

    messagesSummary.textContent = 'Loading messages...';
    messagesSummary.classList.remove('error');

    try {
        const result = await requestJson(`api/messages.php?${params}`);

        if (!result.success) {
            throw new Error(result.error);
        }

        const newCount = result.messages.filter(message => message.status === 'New').length;
        messagesSummary.textContent = result.messages.length > 0
            ? `${result.messages.length} message(s), ${newCount} new`
            : 'No messages match the filters.';

        renderMessages(result.messages);
    } catch (error) {
        messagesSummary.textContent = error.message || 'Could not load messages.';
        messagesSummary.classList.add('error');
        messagesList.innerHTML = '';
    }
}

function renderMessages(messages) {
    messagesList.innerHTML = '';

    messages.forEach(message => {
        const card = document.createElement('article');
        card.className = 'message-card';

        const top = document.createElement('div');
        top.className = 'message-top';

        const title = document.createElement('strong');
        title.textContent = message.type === 'business'
            ? `Partner request: ${message.company}`
            : message.subject;

        const status = statusBadge(message.status);
        const date = document.createElement('span');
        date.className = 'text-muted';
        date.textContent = message.date;

        top.append(title, status, date);

        const from = document.createElement('p');
        from.className = 'text-muted';
        from.textContent = [message.name, message.email, message.phone].filter(Boolean).join(' · ');

        const text = document.createElement('p');
        text.className = 'message-text';
        text.textContent = message.message;

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'button button-secondary';
        toggle.textContent = message.status === 'New' ? 'Mark as answered' : 'Mark as new';
        toggle.addEventListener('click', () => changeStatus(message, toggle));

        card.append(top, from, text, toggle);
        messagesList.appendChild(card);
    });
}

async function changeStatus(message, button) {
    const status = message.status === 'New' ? 'Answered' : 'New';
    button.disabled = true;

    try {
        const result = await requestJson('api/messages.php', 'PATCH', { id: message.id, status });

        if (!result.success) {
            alert(result.error);
            return;
        }

        loadMessages();
    } catch {
        alert('Could not connect to the server.');
    } finally {
        button.disabled = false;
    }
}

document.querySelector('#messages-filter').addEventListener('submit', event => event.preventDefault());
messagesType.addEventListener('change', loadMessages);
messagesStatus.addEventListener('change', loadMessages);

loadMessages();
