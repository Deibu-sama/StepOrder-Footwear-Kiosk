# StepOrder — Footwear Self-Service Kiosk

Laravel 10 + Cloud Firestore prototype modeled after a fast-food self-service kiosk, adapted for footwear.

## Kiosk workflow
Browse footwear → select size/color → add to cart → review order → generate order number → proceed to cashier.

**Payment is not collected at the kiosk.** Cashier/admin changes the order from pending to paid/completed.

## Database and images
- Cloud Firestore is the only application database.
- Product images are stored as public image URLs in Firestore.
- Firebase Storage is not required.
- Firestore is accessed through the REST API, so PHP gRPC is not required.

## Setup
1. `composer install`
2. `copy .env.example .env`
3. `php artisan key:generate`
4. Set `FIREBASE_PROJECT_ID` in `.env`.
5. Put the Firebase service-account JSON at `storage/app/firebase/service-account.json`.
6. Run `php artisan steporder:seed`.
7. Run `php artisan serve`.

Kiosk: `/`  
Admin/Cashier: `/admin/login`

Default prototype credentials come from `ADMIN_EMAIL` and `ADMIN_PASSWORD`.

## Firestore collections
`categories`, `products`, `orders`.

This is a classroom prototype; stock deduction is intentionally simple and does not use Firestore transactions for multi-kiosk concurrency.
