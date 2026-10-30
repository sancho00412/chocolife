// Offer page: add to cart, favorites and reviews

const offer = document.querySelector('#offer');

// Offer was not found, nothing to do
if (offer) {
    const productId = Number(offer.dataset.productId);

    const addToCartButton = document.querySelector('#add-to-cart');
    const quantityInput = document.querySelector('#offer-quantity');
    const favoriteButton = document.querySelector('#favorite-button');
    const cartMessage = document.querySelector('#cart-message');

    const reviewForm = document.querySelector('#review-form');
    const reviewFormTitle = document.querySelector('#review-form-title');
    const ratingSelect = document.querySelector('#review-rating');
    const commentInput = document.querySelector('#review-comment');
    const reviewMessage = document.querySelector('#review-message');
    const reviewHint = document.querySelector('#review-hint');
    const reviewsStatus = document.querySelector('#reviews-status');
    const reviewsList = document.querySelector('#reviews-list');
    const reviewsSummary = document.querySelector('#reviews-summary');
    const reviewsMore = document.querySelector('#reviews-more');

    // Reviews are shown 10 at a time
    const REVIEWS_PAGE = 10;
    let allReviews = [];
    let shownReviews = 0;

    function showMessage(element, text, isError) {
        element.textContent = text;
        element.classList.toggle('error', isError);
    }

    addToCartButton?.addEventListener('click', async () => {
        const quantity = Number(quantityInput.value);
        const max = Number(quantityInput.max);

        if (!Number.isInteger(quantity) || quantity < 1 || quantity > max) {
            showMessage(cartMessage, `Quantity must be from 1 to ${max}.`, true);
            return;
        }

        addToCartButton.disabled = true;

        try {
            const result = await requestJson('api/cart.php', 'POST', { productId, quantity });

            if (!result.success) {
                showMessage(cartMessage, result.error, true);
                return;
            }

            updateCartBadge(result.count);
            showMessage(cartMessage, 'Added to cart! ', false);

            const link = document.createElement('a');
            link.href = 'cart.php';
            link.textContent = 'Go to cart';
            cartMessage.appendChild(link);
        } catch {
            showMessage(cartMessage, 'Could not connect to the server.', true);
        } finally {
            addToCartButton.disabled = false;
        }
    });

    favoriteButton?.addEventListener('click', async () => {
        try {
            const result = await requestJson('api/favorites.php', 'POST', { productId });

            if (!result.success) {
                showMessage(cartMessage, result.error, true);
                return;
            }

            favoriteButton.classList.toggle('active', result.isFavorite);
            favoriteButton.setAttribute('aria-pressed', String(result.isFavorite));
            favoriteButton.querySelector('.favorite-label').textContent =
                result.isFavorite ? 'Saved' : 'Save';
            updateFavoritesBadge(result.count);
        } catch {
            showMessage(cartMessage, 'Could not connect to the server.', true);
        }
    });

    function stars(rating) {
        return '★'.repeat(rating) + '☆'.repeat(5 - rating);
    }

    async function loadReviews() {
        try {
            const result = await requestJson(`api/reviews.php?productId=${productId}`);

            if (!result.success) {
                throw new Error(result.error);
            }

            renderReviews(result);
        } catch {
            reviewsStatus.hidden = false;
            showMessage(reviewsStatus, 'Could not load reviews. Please try again later.', true);
        }
    }

    function renderReviews(result) {
        reviewsSummary.textContent = result.count > 0
            ? `★ ${result.average} · ${result.count}`
            : '';

        // The numbers under the "Add to Cart" button
        document.querySelector('#stat-rating').textContent = result.count > 0 ? result.average : '—';
        document.querySelector('#stat-reviews').textContent = result.count;

        reviewForm.hidden = !result.canReview;
        reviewHint.hidden = result.canReview || result.isAdmin;

        if (result.myReview) {
            reviewFormTitle.textContent = 'Edit your review';
            ratingSelect.value = result.myReview.rating;
            commentInput.value = result.myReview.comment || '';
        } else {
            reviewFormTitle.textContent = 'Leave a review';
            ratingSelect.value = '';
            commentInput.value = '';
        }

        reviewsList.innerHTML = '';
        reviewsStatus.hidden = result.count > 0;
        showMessage(reviewsStatus, 'No reviews yet.', false);

        allReviews = result.reviews;
        shownReviews = 0;
        showMoreReviews();
    }

    function showMoreReviews() {
        const next = allReviews.slice(shownReviews, shownReviews + REVIEWS_PAGE);
        shownReviews += next.length;

        const left = allReviews.length - shownReviews;
        reviewsMore.hidden = left === 0;
        reviewsMore.textContent = `Show more reviews (${left})`;

        next.forEach(review => {
            const item = document.createElement('article');
            item.className = 'review-item';

            const header = document.createElement('div');
            header.className = 'review-header';

            const author = document.createElement('strong');
            author.textContent = review.isMine ? `${review.author} (you)` : review.author;

            const rating = document.createElement('span');
            rating.className = 'review-stars';
            rating.textContent = stars(review.rating);
            rating.setAttribute('aria-label', `${review.rating} out of 5`);

            const date = document.createElement('span');
            date.className = 'review-date';
            date.textContent = review.date;

            header.append(author, rating, date);
            item.appendChild(header);

            if (review.comment) {
                const comment = document.createElement('p');
                comment.textContent = review.comment;
                item.appendChild(comment);
            }

            if (review.canDelete) {
                const deleteButton = document.createElement('button');
                deleteButton.type = 'button';
                deleteButton.className = 'review-delete';
                deleteButton.textContent = 'Delete';
                deleteButton.addEventListener('click', () => deleteReview(review.id));
                item.appendChild(deleteButton);
            }

            reviewsList.appendChild(item);
        });
    }

    reviewsMore.addEventListener('click', showMoreReviews);

    reviewForm.addEventListener('submit', async event => {
        event.preventDefault();

        const rating = Number(ratingSelect.value);
        const comment = commentInput.value.trim();

        // Client-side validation, the server checks the same rules again
        if (rating < 1 || rating > 5) {
            showMessage(reviewMessage, 'Please choose a rating.', true);
            return;
        }

        if (comment.length > 1000) {
            showMessage(reviewMessage, 'Comment must be at most 1000 characters.', true);
            return;
        }

        try {
            const result = await requestJson('api/reviews.php', 'POST', { productId, rating, comment });

            if (!result.success) {
                showMessage(reviewMessage, result.error, true);
                return;
            }

            showMessage(reviewMessage, 'Thank you! Your review has been saved.', false);
            loadReviews();
        } catch {
            showMessage(reviewMessage, 'Could not connect to the server.', true);
        }
    });

    async function deleteReview(reviewId) {
        if (!confirm('Delete this review?')) {
            return;
        }

        try {
            const result = await requestJson('api/reviews.php', 'DELETE', { reviewId });

            if (!result.success) {
                alert(result.error);
                return;
            }

            showMessage(reviewMessage, '', false);
            loadReviews();
        } catch {
            alert('Could not connect to the server.');
        }
    }

    loadReviews();

    // 2GIS map with a marker at the partner (only when the page has a map block)
    const mapElement = document.querySelector('#partner-map');

    if (mapElement) {
        if (window.mapgl) {
            const coordinates = [Number(mapElement.dataset.lng), Number(mapElement.dataset.lat)];

            const map = new mapgl.Map('partner-map', {
                key: mapElement.dataset.key,
                center: coordinates,
                zoom: 15
            });

            new mapgl.Marker(map, { coordinates });
        } else {
            mapElement.textContent = 'The map could not be loaded. Use the "Open in 2GIS" link above.';
        }
    }
}
