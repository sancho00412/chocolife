// Sends JSON to an API file and returns the parsed answer
async function postJson(url, data) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    return response.json();
}

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

            const user = result.user;

            // Temporary: other pages still read these keys.
            // They will be removed when those pages move to the API.
            localStorage.setItem('chocolifeUserRole', user.role);
            localStorage.setItem('chocolifeCurrentUser', JSON.stringify({
                firstName: user.firstName,
                lastName: user.lastName,
                email: user.email
            }));

            window.location.href =
                user.role === 'admin' ? 'index.html' : 'user-home.html';
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

        if (password !== confirmPassword) {
            message.textContent = 'Passwords do not match.';
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
