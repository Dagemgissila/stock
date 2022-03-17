# Stock Management System

Full-featured inventory & stock management system built with Laravel 9.

## Features
- Multi-warehouse inventory tracking
- Point of Sale (POS)
- Purchase & Sales management
- Sales & Purchase Returns
- Quotations
- Expense tracking
- Multi-currency & multi-language support
- Role-based access control (RBAC)
- Payment gateway integrations (Stripe, PayPal, Razorpay, Mollie, Paystack)

## Requirements
- PHP >= 8.0
- MySQL 8.0
- Composer 2.x
- Node.js 16+

## Quick Start
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
```
