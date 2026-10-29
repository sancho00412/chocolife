// Shared code for pages with the user header

const logoutButton = document.querySelector('#logout-button');

if (logoutButton) {
    logoutButton.addEventListener('click', async () => {
        if (!confirm('Do you want to log out?')) {
            return;
        }

        try {
            await fetch('api/logout.php', { method: 'POST' });
        } finally {
            // Temporary keys from auth.js
            localStorage.removeItem('chocolifeCurrentUser');
            localStorage.removeItem('chocolifeUserRole');

            window.location.href = 'welcome.html';
        }
    });
}
