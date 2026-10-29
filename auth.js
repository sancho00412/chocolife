// Sends JSON to an API file and returns the parsed answer
async function postJson(url, data) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    return response.json();
}

function homePageFor(user) {
    return user.role === 'admin' ? 'admin.php' : 'user-home.php';
}

// A logged-in user does not need the login and register pages
fetch('api/me.php')
    .then(response => response.json())
    .then(result => {
        if (result.user) {
            window.location.href = homePageFor(result.user);
        }
    })
    .catch(() => {
        // Server is not available, the form will show the error on submit
    });

const loginForm = document.querySelector('#login-form');

if (loginForm) {
    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const email = document.querySelector('#login-email')
            .value.trim().toLowerCase();

        const password = document.querySelector('#login-password').value;
        const message = document.querySelector('#login-message');

        message.style.color = '#e74c3c';
        message.textContent = '';

        try {
            const result = await postJson('api/login.php', { email, password });

            if (!result.success) {
                message.textContent = result.error;
                return;
            }

            window.location.href = homePageFor(result.user);
        } catch (error) {
            message.textContent = 'Could not connect to the server.';
        }
    });
}

const registerForm = document.querySelector('#register-form');

if (registerForm) {
    registerForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const firstName = document.querySelector('#first-name').value.trim();
        const lastName = document.querySelector('#last-name').value.trim();
        const email = document.querySelector('#email').value.trim().toLowerCase();
        const password = document.querySelector('#password').value;
        const confirmPassword = document.querySelector('#confirm-password').value;
        const message = document.querySelector('#register-message');

        message.style.color = '#e74c3c';
        message.textContent = '';

        // Client-side validation, the server checks the same rules again
        const errors = {
            'first-name': firstName.length < 2 ? 'At least 2 characters.' : '',
            'last-name': lastName.length < 2 ? 'At least 2 characters.' : '',
            'email': /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ? '' : 'Enter a valid email address.',
            'password': password.length < 8 ? 'At least 8 characters.' : '',
            'confirm-password': password !== confirmPassword ? 'Passwords do not match.' : ''
        };

        Object.entries(errors).forEach(([field, text]) => {
            document.querySelector(`#${field}-error`).textContent = text;
        });

        if (Object.values(errors).some(text => text !== '')) {
            message.textContent = 'Please fix the errors above.';
            return;
        }

        try {
            const result = await postJson('api/register.php', {
                firstName,
                lastName,
                email,
                password
            });

            if (!result.success) {
                message.textContent = result.error;
                return;
            }

            message.style.color = '#16a34a';
            message.textContent = 'Account created successfully! Redirecting...';

            setTimeout(() => {
                window.location.href = 'login.html';
            }, 1000);
        } catch (error) {
            message.textContent = 'Could not connect to the server.';
        }
    });
}
