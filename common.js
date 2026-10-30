// Shared code for pages with the user header

// Sends a request to the API and returns the parsed JSON answer.
// data is sent as JSON for POST, PATCH and DELETE.
async function requestJson(url, method = 'GET', data = null) {
    const options = { method };

    if (data !== null) {
        options.headers = { 'Content-Type': 'application/json' };
        options.body = JSON.stringify(data);
    }

    const response = await fetch(url, options);
    return response.json();
}

function formatPrice(price) {
    return Number(price).toLocaleString('en-US') + ' ₸';
}

// Number of items in the cart, shown in the header
function updateCartBadge(count) {
    const badge = document.querySelector('#cart-badge');

    if (badge) {
        badge.textContent = count;
    }
}

// Number of saved offers, shown next to the heart in the header
function updateFavoritesBadge(count) {
    const badge = document.querySelector('#favorites-badge');

    if (badge) {
        badge.textContent = count;
    }
}

const logoutButton = document.querySelector('#logout-button');

if (logoutButton) {
    logoutButton.addEventListener('click', async () => {
        if (!confirm('Do you want to log out?')) {
            return;
        }

        try {
            await fetch('api/logout.php', { method: 'POST' });
        } finally {
            window.location.href = 'welcome.html';
        }
    });
}

// "Help" menu in the header: opens on click, closes on a click outside or Escape
const helpButton = document.querySelector('#help-button');
const helpMenu = document.querySelector('#help-menu');

function setHelpMenu(open) {
    helpMenu.hidden = !open;
    helpButton.setAttribute('aria-expanded', String(open));
}

if (helpButton && helpMenu) {
    helpButton.addEventListener('click', event => {
        event.stopPropagation();
        setHelpMenu(helpMenu.hidden);
    });

    document.addEventListener('click', event => {
        if (!helpMenu.hidden && !helpMenu.contains(event.target)) {
            setHelpMenu(false);
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !helpMenu.hidden) {
            setHelpMenu(false);
            helpButton.focus();
        }
    });
}
