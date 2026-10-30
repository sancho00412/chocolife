# Testing

## Automatic tests

`tests/api-tests.php` sends real HTTP requests to the API as a guest, two customers and an admin, and checks the answers. It covers valid input, invalid input, missing data, boundary values, business rules and permissions.

```
C:\xampp\php\php.exe database\seed.php       # fresh test data
C:\xampp\php\php.exe tests\api-tests.php     # 57 checks
C:\xampp\php\php.exe database\seed.php       # remove the data the tests created
```

## Test cases

| # | Area | Steps | Input | Expected result |
|---|------|-------|-------|-----------------|
| 1 | Registration, valid | Register a new account | names, new email, password `12345678` | Account created, redirect to login |
| 2 | Registration, boundary | Register with a short password | password `1234567` (7 chars) | "Password must be at least 8 characters" |
| 3 | Registration, duplicate | Register an existing email in capitals | `ARUZHAN@MAIL.KZ` | "This email is already registered" (409) |
| 4 | Login, invalid | Log in with a wrong password | `aruzhan@mail.kz` / `wrong` | "Incorrect email or password" (401), same text as for an unknown email |
| 5 | Access control | Open `user-home.php` without logging in | — | Redirect to `login.html` |
| 6 | Access control | Log in as a customer, open `admin.php` | — | Redirect to the catalog; admin API answers 403 |
| 7 | Catalog | Search `bowling`, then `zzz` | — | 1 offer; then "No offers found" |
| 8 | Catalog | Sort by "Price: low to high", go to page 5 | — | Cheapest first (Coffee and Dessert, 2,500 ₸); page 5 shows the last 2 of 50 offers |
| 9 | Cart, missing stock | Add more Quest Room coupons than in stock | quantity 2 (stock 1) | "Only 1 coupon(s) of \"Quest Room\" left." |
| 9a | Limit per customer | Add 3 cinema coupons, then 2; place the order; try to add 1 more | cinema (limit 2) | 3 rejected; 2 added; after the order "You have already bought the maximum of 2 coupon(s)…" |
| 9b | Limit per customer | Add 2 Hotel Weekend coupons | hotel (limit 1) | "…is limited to 1 coupon(s) per customer" |
| 10 | Cart, unavailable | Open Ski Day Pass | — | No "Add to Cart" button; API answers 409 |
| 11 | Checkout, invalid | Place an order with a wrong phone and a short address | phone `abc`, address `A` | Errors under both fields, nothing saved |
| 12 | Checkout, transaction | Place a valid order | 2 × Cinema Tickets | Order created, stock 120 → 118, cart empty |
| 13 | Checkout, empty cart | Send the order request again | — | "Your cart is empty" (409) |
| 14 | Reviews, business rule | Review an offer you did not buy | Yoga Course | "Only customers who bought this offer can leave a review" (403) |
| 15 | Reviews, boundary | Send rating 6 to the API | rating `6` | "Please choose a rating from 1 to 5" (400) |
| 16 | Other users' data | Open `order-success.php?id=2` as Aruzhan (order of Daniyar) | — | "Order not found" (404) |
| 17 | Other users' data | Daniyar deletes Aruzhan's review through the API | review id | "You can delete only your own reviews" (403) |
| 18 | XSS | Register with first name `<script>alert(1)</script>` | — | The name is shown as text, no alert |
| 19 | SQL injection | Search `' OR 1=1 --`; log in with it as email | — | 0 offers; login rejected |
| 20 | Admin, order status | Decline an order | — | Coupons go back to stock |
| 21 | Admin, offer form | Save an offer with empty name, negative price, wrong category | — | Error under each field |
| 22 | Admin, image upload | Upload a `.php` file renamed to `.png` | — | "Only JPG, PNG or WEBP images are allowed" |
| 23 | Admin, delete | Delete your own admin account through the API | id 1 | "Admin accounts cannot be deleted" (403) |
| 24 | Database error | Stop MySQL and open the catalog | — | "Could not load offers. Please try again later." |
| 25 | Logout | Log out, then call `api/orders.php` | — | 401 "Please log in first" |

## Edge cases and how they are handled

1. **Two customers buy the last coupon at the same time.**
   Checkout locks the product rows with `SELECT … FOR UPDATE` inside a transaction. The second customer waits until the first `COMMIT`, then sees the new stock and gets "Only 0 coupon(s) left". The stock can never become negative; the `CHECK (stock >= 0)` constraint is a second safety net.

2. **An offer is deleted after it was ordered.**
   `order_items.product_id` becomes `NULL` (`ON DELETE SET NULL`), but the name and price were copied into `order_items` at the moment of purchase. Old orders, the customer's history and the reports still show the correct data.

3. **Database is not available or a query fails.**
   `includes/api.php` catches every unexpected exception, writes the details to the PHP error log and answers with JSON `{"success": false, "error": "Server error. Please try again later."}` and status 500. The JavaScript shows this message instead of breaking the page. Expected problems (empty cart, not enough stock) roll back the transaction and return a clear message with status 409.

4. **Invalid page or sort values in the URL.**
   `page=999` returns the last page, `page=abc` returns page 1, an unknown `sort` returns 400. Values that are arrays instead of strings (`search[]=x`) are ignored.

5. **A user is deleted while logged in.**
   The session stores only the user id; the user is read from the database on every request. A deleted user is treated as a guest at once.
