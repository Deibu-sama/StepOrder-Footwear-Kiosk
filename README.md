# StepOrder — Footwear Self-Service Kiosk

Laravel 10 + Cloud Firestore prototype modeled after a fast-food self-service kiosk, adapted for footwear.

## Kiosk workflow
Browse footwear → filter by category/gender/price → select color/size → add to cart → review order → generate order number → proceed to cashier.

**Payment is not collected at the kiosk.** Cashier/admin changes the order from pending to paid/completed.

## Database and images
- Cloud Firestore is the only application database.
- Product images are stored as public image URLs in Firestore, with optional per-color image URLs.
- Firebase Storage is not required.
- Firestore is accessed through the REST API, so PHP gRPC is not required.

## Setup
1. `composer install`
2. `copy .env.example .env`
3. `php artisan key:generate`
4. Set `FIREBASE_PROJECT_ID` in `.env`.
5. Put the Firebase service-account JSON at `storage/app/firebase/service-account.json`.
6. Run `php artisan steporder:seed`.
7. Run `php artisan steporder:seed --force` to load the full demo footwear catalog.
8. Run `php artisan serve`.

Kiosk: `/`  
Admin/Cashier: `/admin/login`

Default prototype credentials come from `ADMIN_EMAIL` and `ADMIN_PASSWORD`.

## Firestore collections
`categories`, `products`, `orders`.

This is a classroom prototype; stock deduction is intentionally simple and does not use Firestore transactions for multi-kiosk concurrency.


## Kiosk features
- Tap-to-start screen with footwear slideshow
- Sidebar filters for categories, gender, price range, Top Picks, and sale items
- Sale prices with automatic discount badges
- Top Pick badges
- Per-color product images
- Per-size/per-color stock visibility
- Automatic disabling of out-of-stock variants
- 10-second post-order countdown back to the Tap to Start screen
