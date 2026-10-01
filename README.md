# StepOrder Footwear Kiosk

Laravel 10 + Firestore self-service footwear ordering kiosk.

## Workflow
Customer browses footwear -> selects variants -> reviews cart -> generates order -> proceeds to cashier.

## Storage
- Cloud Firestore for application data
- Product images stored as public image URLs in Firestore
- No Firebase Storage required

## Local setup
1. Copy `.env.example` to `.env`
2. Add the Firebase service-account JSON at `storage/app/firebase/service-account.json`
3. Set `FIREBASE_PROJECT_ID`
4. Run `composer install`
5. Run `php artisan key:generate`
6. Run `php artisan steporder:seed`
7. Run `php artisan serve`

See the project files for the full setup and admin/kiosk routes.
