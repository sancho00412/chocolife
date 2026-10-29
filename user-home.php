<?php
require __DIR__ . '/includes/page.php';

$user = requirePageLogin();

$pageTitle   = 'Home';
$activePage  = 'home';
$pageCss     = ['user-home.css'];
$pageScripts = ['user-home.js'];

require __DIR__ . '/includes/header.php';
?>


    <main>

        <section class="user-hero">
            <div class="hero-content">
                <p class="eyebrow">YOUR CITY. YOUR EXPERIENCES.</p>

                <h1>
                    Discover something
                    <span>amazing today.</span>
                </h1>

                <p>
                    Restaurants, wellness, entertainment and travel
                    experiences — all in one place.
                </p>

                <a href="#offers" class="user-primary-button">
                    Explore offers
                    <span class="material-symbols-outlined">arrow_forward</span>
                </a>
            </div>

            <div class="hero-decoration">
                <span class="material-symbols-outlined">favorite</span>
            </div>
        </section>

        <section class="user-section" id="categories">
            
            <div class="section-heading">
                <div>
                    <p class="eyebrow">BROWSE BY INTEREST</p>
                    <h2>Categories</h2>
                </div>
            </div>

            <div class="user-product-filters">
                <div class="user-search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input
                        type="search"
                        id="user-product-search"
                        placeholder="Search offers..."
                        aria-label="Search offers"
                        autocomplete="off"
                    >
                </div>

                <select id="user-category-filter" aria-label="Filter by category">
                    <option value="">All Categories</option>
                </select>

                <select id="user-sort" aria-label="Sort offers">
                    <option value="newest">Newest first</option>
                    <option value="price_asc">Price: low to high</option>
                    <option value="price_desc">Price: high to low</option>
                    <option value="discount">Biggest discount</option>
                    <option value="rating">Best rated</option>
                    <option value="popular">Most popular</option>
                </select>
            </div>


            <div class="category-grid">

                <article class="category-card">
                    <span class="material-symbols-outlined">restaurant</span>
                    <h3>Restaurants</h3>
                    <p>Discover new places to eat.</p>
                </article>

                <article class="category-card">
                    <span class="material-symbols-outlined">spa</span>
                    <h3>Beauty & Spa</h3>
                    <p>Take time for yourself.</p>
                </article>

                <article class="category-card">
                    <span class="material-symbols-outlined">movie</span>
                    <h3>Entertainment</h3>
                    <p>Make your free time special.</p>
                </article>

                <article class="category-card">
                    <span class="material-symbols-outlined">flight</span>
                    <h3>Travel</h3>
                    <p>Find your next adventure.</p>
                </article>

            </div>
        </section>

        <section class="user-section" id="offers">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">HANDPICKED FOR YOU</p>
                    <h2>Popular Offers</h2>
                </div>
            </div>

            <p id="catalog-status" class="catalog-status" role="status" hidden></p>

            <p id="user-no-results" class="user-no-results" hidden>
                No offers found. Try another search or category.
            </p>

            <div id="user-products" class="user-product-grid"></div>

            <nav id="user-pagination" class="user-pagination" aria-label="Offers pages"></nav>
        </section>

    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>


    