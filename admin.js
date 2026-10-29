// Shared code for admin pages: mobile menu

const adminSidebar = document.querySelector('#admin-sidebar');

document.querySelector('#admin-menu')?.addEventListener('click', () => {
    adminSidebar.classList.add('open');
});

document.querySelector('#admin-close')?.addEventListener('click', () => {
    adminSidebar.classList.remove('open');
});

// Status badge for orders and offers
function statusBadge(status) {
    const badge = document.createElement('span');
    badge.className = `badge badge-${status.toLowerCase()}`;
    badge.textContent = status;
    return badge;
}

// Creates a table cell with plain text (textContent protects against XSS)
function textCell(text) {
    const cell = document.createElement('td');
    cell.textContent = text;
    return cell;
}
