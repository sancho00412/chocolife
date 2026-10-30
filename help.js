// Feedback and partner request forms: validation and sending without reloading the page

const messageForm = document.querySelector('#message-form');
const formStatus = document.querySelector('#form-status');
const sendButton = document.querySelector('#send-button');

function value(id) {
    const input = document.querySelector(`#${id}`);
    return input ? input.value.trim() : '';
}

function setFieldError(field, text) {
    const element = document.querySelector(`#${field}-error`);

    if (element) {
        element.textContent = text;
    }
}

messageForm.addEventListener('submit', async event => {
    event.preventDefault();

    const type = messageForm.dataset.type;
    const data = {
        type,
        name: value('name'),
        email: value('email'),
        phone: value('phone'),
        company: value('company'),
        subject: value('subject'),
        message: value('message')
    };

    document.querySelectorAll('.field-error').forEach(element => {
        element.textContent = '';
    });
    formStatus.textContent = '';
    formStatus.className = 'form-status';

    // Client-side validation, the server checks the same rules again
    const errors = {};

    if (data.name.length < 2) {
        errors.name = 'Enter your name.';
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
        errors.email = 'Enter a valid email address.';
    }
    if (data.message.length < 10) {
        errors.message = 'The message must be at least 10 characters.';
    }
    if (type === 'feedback' && !data.subject) {
        errors.subject = 'Choose a subject.';
    }
    if (type === 'business' && data.company.length < 2) {
        errors.company = 'Enter the company name.';
    }
    if (type === 'business' && !/^\+?[\d\s()-]{10,20}$/.test(data.phone)) {
        errors.phone = 'Enter a valid phone number.';
    }

    if (Object.keys(errors).length > 0) {
        Object.entries(errors).forEach(([field, text]) => setFieldError(field, text));
        return;
    }

    sendButton.disabled = true;
    formStatus.textContent = 'Sending...';

    try {
        const result = await requestJson('api/messages.php', 'POST', data);

        if (!result.success) {
            Object.entries(result.fields || {}).forEach(([field, text]) => setFieldError(field, text));
            formStatus.textContent = result.error;
            formStatus.classList.add('error');
            return;
        }

        document.querySelector('#message').value = '';
        formStatus.textContent = type === 'business'
            ? 'Thank you! We will call you within two working days.'
            : 'Thank you! Your message has been sent. We will answer by email.';
        formStatus.classList.add('success');
    } catch {
        formStatus.textContent = 'Could not connect to the server.';
        formStatus.classList.add('error');
    } finally {
        sendButton.disabled = false;
    }
});
