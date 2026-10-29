

document.addEventListener('DOMContentLoaded', () => {
    const adminLoginButton = document.querySelector('#admin-login-button');
    const adminModal = document.querySelector('#admin-modal');


    if (adminLoginButton) {
        adminLoginButton.remove();
    }

    if (adminModal) {
        adminModal.remove();
    }
});
