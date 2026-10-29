// Admin users: search, filter, sort and delete

const usersBody = document.querySelector('#users-body');
const usersSummary = document.querySelector('#users-summary');
const usersSearch = document.querySelector('#users-search');
const usersRole = document.querySelector('#users-role');
const usersSort = document.querySelector('#users-sort');

let usersSearchTimer = null;
let usersRequestId = 0;

async function loadUsers() {
    const requestId = ++usersRequestId;
    const params = new URLSearchParams({
        search: usersSearch.value.trim(),
        role: usersRole.value,
        sort: usersSort.value
    });

    usersSummary.textContent = 'Loading users...';
    usersSummary.classList.remove('error');

    try {
        const result = await requestJson(`api/admin-users.php?${params}`);

        if (requestId !== usersRequestId) {
            return;
        }

        if (!result.success) {
            throw new Error(result.error);
        }

        usersSummary.textContent = result.users.length > 0
            ? `${result.users.length} user(s) found`
            : 'No users match the filters.';

        renderUsers(result.users);
    } catch (error) {
        if (requestId !== usersRequestId) {
            return;
        }

        usersSummary.textContent = error.message || 'Could not load users.';
        usersSummary.classList.add('error');
        usersBody.innerHTML = '';
    }
}

function renderUsers(users) {
    usersBody.innerHTML = '';

    users.forEach(user => {
        const row = document.createElement('tr');

        const roleCell = document.createElement('td');
        roleCell.appendChild(statusBadge(user.role === 'admin' ? 'Admin' : 'Customer'));

        const actions = document.createElement('td');

        if (user.canDelete) {
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'link-button danger';
            remove.textContent = 'Delete';
            remove.addEventListener('click', () => deleteUser(user));
            actions.appendChild(remove);
        }

        row.append(
            textCell(user.name),
            textCell(user.email),
            textCell(user.phone || '—'),
            roleCell,
            textCell(user.registered),
            textCell(user.ordersCount),
            textCell(formatPrice(user.spent)),
            textCell(user.lastOrder || '—'),
            actions
        );

        usersBody.appendChild(row);
    });
}

async function deleteUser(user) {
    if (!confirm(`Delete the account of ${user.name} (${user.email})?\n\nTheir orders stay in the reports.`)) {
        return;
    }

    try {
        const result = await requestJson('api/admin-users.php', 'DELETE', { id: user.id });

        if (!result.success) {
            alert(result.error);
            return;
        }

        loadUsers();
    } catch {
        alert('Could not connect to the server.');
    }
}

document.querySelector('#users-filter').addEventListener('submit', event => event.preventDefault());

usersSearch.addEventListener('input', () => {
    clearTimeout(usersSearchTimer);
    usersSearchTimer = setTimeout(loadUsers, 300);
});

usersRole.addEventListener('change', loadUsers);
usersSort.addEventListener('change', loadUsers);

loadUsers();
