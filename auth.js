const loginForm = document.querySelector('#login-form');

if (loginForm) {
    loginForm.addEventListener('submit', (event) => {
        event.preventDefault();

        const email = document.querySelector('#login-email')
            .value.trim().toLowerCase();

        const password = document.querySelector('#login-password').value;
        const message = document.querySelector('#login-message');

        message.style.color = '#e74c3c';
        message.textContent = '';

        if (
            email === 'admin@chocolife.kz' &&
            password === 'Admin123!'
        ) {
            localStorage.setItem('chocolifeUserRole', 'admin');
            window.location.href = 'index.html';
            return;
        }

        const users =
            JSON.parse(localStorage.getItem('chocolifeUsers')) || [];

        const user = users.find(user =>
            String(user.email || '').trim().toLowerCase() === email &&
            user.password === password
        );

        if (!user) {
            message.textContent = 'Incorrect email or password.';
            return;
        }

        localStorage.setItem('chocolifeUserRole', 'user');

        const currentUser = {
            firstName: user.firstName,
            lastName: user.lastName,
            email: user.email
        };

        localStorage.setItem(
            'chocolifeCurrentUser',
            JSON.stringify(currentUser)
        );

        window.location.href = 'user-home.html';
    });
}
const registerForm = document.querySelector('#register-form');

if (registerForm) {
    registerForm.addEventListener('submit', (event) => {
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

        let users = [];

        try {
            const savedUsers = JSON.parse(
                localStorage.getItem('chocolifeUsers') || '[]'
            );
            users = Array.isArray(savedUsers) ? savedUsers : [];
        } catch (error) {
            message.textContent = 'Could not read saved users.';
            return;
        }

        const existingUser = users.some(
            user => String(user.email || '').trim().toLowerCase() === email
        );

        if (existingUser) {
            message.textContent = 'This email is already registered.';
            return;
        }

        const newUser = {
            firstName,
            lastName,
            email,
            password
        };

        users.push(newUser);

        localStorage.setItem('chocolifeUsers', JSON.stringify(users));

        message.style.color = '#16a34a';
        message.textContent = 'Account created successfully! Redirecting...';

        setTimeout(() => {
            window.location.href = 'login.html';
        }, 1000);
    });
}
