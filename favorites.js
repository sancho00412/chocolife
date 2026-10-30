// Saved offers page: remove an offer without reloading the page

const favoritesCount = document.querySelector('#favorites-count');
const favoritesEmpty = document.querySelector('#favorites-empty');

document.querySelectorAll('.favorite-remove').forEach(button => {
    button.addEventListener('click', async () => {
        if (!confirm(`Remove "${button.dataset.name}" from saved offers?`)) {
            return;
        }

        button.disabled = true;

        try {
            // The same API as the "Save" button: a second press removes the offer
            const result = await requestJson('api/favorites.php', 'POST', {
                productId: Number(button.dataset.productId)
            });

            if (!result.success) {
                alert(result.error);
                return;
            }

            button.closest('.favorite-card').remove();

            const left = document.querySelectorAll('.favorite-card').length;
            favoritesCount.textContent = `${left} saved`;
            updateFavoritesBadge(left);
            favoritesEmpty.hidden = left > 0;
        } catch {
            alert('Could not connect to the server.');
        } finally {
            button.disabled = false;
        }
    });
});
