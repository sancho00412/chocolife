<?php
require __DIR__ . '/includes/page.php';

$user = requirePageLogin();

// Category cards: only available offers are counted
$categories = getDb()->query("
    SELECT c.id, c.name, COUNT(p.id) AS offers
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id AND p.status = 'Available'
    GROUP BY c.id, c.name
    ORDER BY c.name
")->fetchAll();

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
<?php foreach ($categories as $category): ?>
                <button type="button" class="category-card" data-category-id="<?= $category['id'] ?>">
                    <span class="material-symbols-outlined"><?= categoryIcon($category['name']) ?></span>
                    <h3><?= e($category['name']) ?></h3>
                    <p><?= (int) $category['offers'] ?> offer(s)</p>
                </button>
<?php endforeach; ?>
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


    