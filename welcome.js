// A logged-in user goes straight to the catalog or the admin panel
fetch('api/me.php')
    .then(response => response.json())
    .then(result => {
        if (result.user) {
            window.location.href = result.user.role === 'admin' ? 'admin.php' : 'user-home.php';
        }
    })
    .catch(() => {
        // Server is not available, the page still shows the login buttons
    });
