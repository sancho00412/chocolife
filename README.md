# Chocolife

A coupon service in the style of chocolife.me. Customers browse discount offers (restaurants, spa, entertainment, travel, fitness), add them to a cart, place orders and leave reviews. Administrators manage offers, orders and users and see sales reports.

**Stack:** PHP 8.2 (PDO), MySQL / MariaDB, HTML, CSS, JavaScript (Fetch API). No frameworks.

## Setup

1. Install [XAMPP](https://www.apachefriends.org) and start **Apache** and **MySQL**.
2. Put the project into `C:\xampp\htdocs\chocolife`.
3. Create the database: open `http://localhost/phpmyadmin` → **SQL** → paste `database/schema.sql` → **Go**.
4. Fill it with test data:
   ```
   C:\xampp\php\php.exe database\seed.php
   ```
5. Open `http://localhost/chocolife/`.

Database settings are in `config/db.php` (XAMPP defaults: user `root`, empty password).

To reset the data at any time, run `database\seed.php` again.

## Test accounts

| Role     | Email                | Password   |
|----------|----------------------|------------|
| Admin    | admin@chocolife.kz   | admin123   |
| Customer | aruzhan@mail.kz      | user123    |
| Customer | daniyar@mail.kz      | user123    |

Six more customers (`madina@`, `timur@`, `aigerim@`, `nursultan@`, `dana@mail.kz`) use the password `user123`.

## Pages

| Page | Who | What it does |
|------|-----|--------------|
| `welcome.html` | everyone | start page with login and register buttons |
| `register.html`, `login.html` | guests | registration and login |
| `user-home.php` | logged in | catalog: search, category filter, sorting, pagination (AJAX) |
| `offer.php?id=` | logged in | offer details, add to cart, save to favorites, reviews |
| `favorites.php` | customers | saved offers (heart in the header), remove without reload |
| `cart.php` | customers | cart stored in the database, change quantity, remove |
| `checkout.php` | customers | order form with validation, places the order in a transaction |
| `order-success.php?id=` | customers | confirmation, only for the owner of the order |
| `faq.php`, `feedback.php`, `business.php` | logged in | "Help" menu in the header: FAQ, feedback form, partner request form |
| `about.php` | logged in | about the service: live numbers from the database, how it works, categories, contacts |
| `profile.php` | logged in | edit name and phone, change password, order history, saved offers |
| `admin.php` | admins | dashboard: revenue, orders, customers, offers that need attention |
| `admin-orders.php` | admins | all orders: search, status and date filters, sorting, status change |
| `admin-products.php` | admins | offers: search, filters, sorting, pagination, delete |
| `admin-product-form.php` | admins | create and edit offers with image upload |
| `admin-users.php` | admins | users with their order statistics, delete customers |
| `admin-messages.php` | admins | feedback and partner requests, mark as answered |
| `admin-reports.php` | admins | 10 SQL reports for a chosen period |

`index.php` sends visitors to the right start page for their role.

## Project structure

```
api/          PHP endpoints that return JSON (used with fetch)
config/       database connection
database/     schema.sql and seed.php (test data)
includes/     sessions, access checks, shared header/footer for user and admin pages
images/       logo; images/products/ holds uploaded offer images
tests/        automatic API tests
*.php         pages
*.js, *.css   page scripts and styles
```

`config/`, `database/`, `includes/` and `tests/` are closed for the browser with `.htaccess`.

## Database

10 related tables: `users`, `categories`, `partners`, `products`, `cart_items`, `orders`, `order_items`, `reviews`, `favorites`, `messages`.

- Primary keys, foreign keys with `ON DELETE CASCADE / SET NULL / RESTRICT`.
- `CHECK` constraints: price ≥ 0, old price ≥ price, stock ≥ 0, quantity 1–99, rating 1–5.
- `UNIQUE`: user email, offer number, one cart row and one review per user and offer.
- `order_items` keeps the offer name and price at the moment of purchase, so old orders stay correct after an offer is changed or deleted.

### Non-trivial queries used in the application

| # | Query | Where | Features |
|---|-------|-------|----------|
| 1 | Catalog with rating and sales | `api/products.php` | JOIN, LEFT JOIN, 2 subqueries with AVG/COUNT/SUM + GROUP BY, filters, ORDER BY, LIMIT/OFFSET |
| 2 | Categories with offer count | `api/categories.php` | LEFT JOIN, COUNT, GROUP BY |
| 3 | Users with orders and money spent | `api/admin-users.php` | LEFT JOIN, COUNT, SUM with CASE, MAX, GROUP BY |
| 4 | Orders with filters | `api/admin-orders.php` | JOIN, SUM, GROUP BY, several conditions, date range |
| 5 | Dashboard numbers | `admin.php` | subqueries with SUM, COUNT, AVG; this month vs last month |
| 6 | Revenue by month | `admin-reports.php` | date-based statistics, DATE_FORMAT, GROUP BY |
| 7 | Revenue by category | `admin-reports.php` | 4 tables, SUM, COUNT(DISTINCT), GROUP BY |
| 8 | Top 5 offers | `admin-reports.php` | 4 tables, SUM, GROUP BY, ORDER BY, LIMIT |
| 9 | Loyal customers | `admin-reports.php` | JOIN, GROUP BY, **HAVING** COUNT ≥ 2 |
| 10 | Partner ratings | `admin-reports.php` | LEFT JOIN ×2, AVG, COUNT(DISTINCT) |
| 11 | Never bought offers | `admin-reports.php` | NOT EXISTS subquery |
| 12 | Offers above category average | `admin-reports.php` | correlated subquery with AVG |
| 13 | Offers ending in 60 days | `admin-reports.php` | date filter with several conditions, DATEDIFF |
| 14 | Order statuses in period | `admin-reports.php` | COUNT, SUM, GROUP BY |

The reports page shows the SQL text under every report ("Show SQL").

### Business rules

- **Stock:** an offer cannot be bought when no coupons are left; the catalog shows "Sold out".
- **Limit per customer** (`products.max_per_customer`, set by the admin, 1–10): 2 for things people do together (cinema, dinner, bowling), 1 for personal ones (salon, gym, hotel). Coupons from earlier orders count too, so the limit cannot be bypassed with several orders. Checked in the cart (`api/cart.php`) and again inside the checkout transaction (`api/orders.php`); shared logic is in `includes/shop.php`.
- **Reviews:** only customers who bought the offer, one review per offer.

### Transactions

- **Checkout** (`api/orders.php`): the order, its items, the stock decrease and clearing the cart are saved together. Products are locked with `SELECT … FOR UPDATE`, so two customers cannot buy the last coupon at the same time. If something fails, `ROLLBACK` leaves the database unchanged.
- **Order status** (`api/admin-orders.php`): declining an order returns its coupons to the stock; opening a declined order again takes them back, also in one transaction.

## Security

- All SQL uses prepared statements (`?` placeholders). Sorting uses a whitelist, because `ORDER BY` cannot use placeholders.
- Passwords are stored with `password_hash()` and checked with `password_verify()`.
- Sessions: `httponly` and `SameSite=Strict` cookie, new session id after login, full logout. The user is read from the database on every request, so a deleted user is logged out at once.
- Access is checked on the server: `requireLogin()`, `requireCustomer()`, `requireAdmin()` in the API and `requirePageLogin()`, `requirePageUser()`, `requirePageAdmin()` on pages.
- Users can only see and change their own cart, orders, reviews and profile (the user id always comes from the session, never from the request).
- XSS: PHP output goes through `htmlspecialchars()` (`e()`), JavaScript uses `textContent`.
- Every form is validated in the browser and again on the server.
- Image upload: the real file type is checked with `finfo`, max 2 MB, random file name.

## JavaScript and AJAX

- Catalog search (with 300 ms debounce), filter, sorting and pagination without page reload.
- Cart: add, change quantity, remove, header counter updates without reload.
- Reviews: add, edit, delete without reload.
- Favorites toggle, profile and password forms, admin order status change.
- Loading indicators, "nothing found" messages, error messages, `confirm()` dialogs before deleting, logging out and placing an order.
- Light and dark theme in the admin panel.

## Tests

See [TESTING.md](TESTING.md). Automatic API tests (57 checks):

```
C:\xampp\php\php.exe database\seed.php
C:\xampp\php\php.exe tests\api-tests.php
C:\xampp\php\php.exe database\seed.php
```

## Photos

The 50 offer photos in `images/offers/` are free-licensed images from [Wikimedia Commons](https://commons.wikimedia.org) (CC0, Public Domain, CC BY, CC BY-SA). The author and license of each photo are stored in `database/photo-credits.php`, saved in `products.image_credit` / `products.image_source` and shown under the photo on the offer page, as the licenses require. Photos uploaded by the admin go to `images/products/` and have no credit line.

## Deployment

Any hosting with PHP 8.1+ and MySQL/MariaDB works:

1. Upload all files.
2. Create a database and import `database/schema.sql` (remove the first three lines `DROP DATABASE / CREATE DATABASE / USE` if the hosting gives you a ready database).
3. Put the hosting database name, user and password into `config/db.php`.
4. Run `php database/seed.php` over SSH, or import the data another way.
5. Make sure `images/products/` is writable for PHP.
