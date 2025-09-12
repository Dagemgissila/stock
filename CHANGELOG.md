# Changelog

## [2.1.0] - 2025-09-20
### Added
- Two-factor authentication with Redis OTP caching
- SubscriptionPlan model for SaaS billing
- Bulk product delete and bulk order status update
- PDF invoice generation with Unicode/RTL support
- Rate limiting: 300/min auth, 60/min public, 30/min payments
- Composite database indexes for performance

### Fixed
- Double stock deduction bug in OrderObserver
- JWT refresh race condition via Redis distributed lock
- Cross-company data leakage in CompanyScope
- UTF-8 BOM handling in CSV imports from Excel
- Missing withDateRange scope on Expense and Payment models

### Performance
- Dashboard KPIs cached 5 minutes per company
- Eager loading eliminates N+1 queries on all list endpoints

## [1.0.0] - 2022-03-15
### Added
- Initial project scaffold on Laravel 9 (released Feb 2022)
- JWT authentication and RBAC with spatie/laravel-permission
- Product, Sales, Purchase CRUD with basic stock tracking
- Multi-warehouse inventory foundation
