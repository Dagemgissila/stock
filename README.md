# Stock Management System

Full-featured multi-warehouse inventory system built with Laravel 9.

## Features
- Multi-warehouse inventory with stock transfers
- POS with barcode scanner and thermal receipt printing
- Sales and Purchase orders with PDF invoices
- Sales and Purchase Returns with stock reversal
- Quotations with one-click convert-to-sale
- Expense tracking by category
- Multi-currency and multi-language support
- Role-based access control with granular permissions
- Payment gateways: Stripe, PayPal, Razorpay, Mollie, Paystack
- Online store customer API
- SaaS multi-tenancy with subscription plans
- Two-factor authentication with Redis OTP
- Rate limiting on all endpoint groups

## Requirements
- PHP >= 8.0, MySQL 8.0, Redis, Composer 2.x, Node.js 16+

## Quick Start
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

## License
MIT
