# Products Demo App - Laravel Backend

A robust backend API for managing products, users, and related data. Built with Laravel 11, featuring modern PHP practices, RESTful API design, and comprehensive testing capabilities.

## 🚀 Technologies Used

- **Laravel 11** - Modern PHP web application framework
- **PHP 8.2+** - Latest version of PHP with strong typing support
- **MySQL/MariaDB** - Relational database management
- **Eloquent ORM** - Powerful and intuitive database abstraction layer
- **Laravel Sanctum** - Token-based API authentication
- **Pest PHP** - Modern, elegant testing framework
- **Composer** - PHP package manager
- **Artisan CLI** - Laravel's command-line interface
- **Database Migrations** - Version control for your database schema
- **Laravel Seeders** - Database seeding for demo data

## 📋 Requirements

- PHP 8.2 or higher
- Composer (dependency manager)
- MySQL 8.0+ or MariaDB 10.3+
- Node.js v18+ (for frontend asset compilation)

## 🛠️ Installation

### 1. Clone and Setup
```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 2. Database Configuration

Update your `.env` file with database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=products_demo
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Database Setup
```bash
# Run migrations
php artisan migrate

# Seed demo data
php artisan db:seed
```

## 🏃 Development Commands

Start development server:
```bash
php artisan serve
```

Run tests:
```bash
php artisan test
```

Run specific test file:
```bash
php artisan test tests/Feature/YourTestFile.php
```

Generate cache for better performance:
```bash
php artisan optimize
```

Clear all caches:
```bash
php artisan cache:clear
```

## 📁 Project Structure

```
app/
├── Http/
│   ├── Controllers/    # API controllers
│   └── Requests/       # Form request validation
├── Models/             # Eloquent models
│   ├── User.php
│   ├── Product.php
│   └── ...
└── Providers/          # Service providers

database/
├── migrations/         # Database schema migrations
├── factories/          # Model factories for testing
└── seeders/           # Database seeders

routes/
├── api.php            # API routes
├── web.php            # Web routes
└── console.php        # Console commands

tests/
├── Feature/           # Feature tests
├── Unit/              # Unit tests
└── TestCase.php       # Base test class

config/                # Configuration files
storage/               # Logs, uploads, caches
```

## 🔌 API Endpoints

### Authentication
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/user` - Get authenticated user

### Products
- `GET /api/products` - List all products
- `POST /api/products` - Create new product
- `GET /api/products/{id}` - Get product details
- `PUT /api/products/{id}` - Update product
- `DELETE /api/products/{id}` - Delete product

### Product Categories
- `GET /api/categories` - List categories
- `POST /api/categories` - Create category

## 🧪 Testing

The project uses Pest for testing with comprehensive test coverage:

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test suite
php artisan test tests/Feature
```

## 🔐 Security

- Uses Laravel Sanctum for API authentication
- CORS configured in `config/cors.php`
- Environment variables for sensitive data
- SQL injection prevention with Eloquent ORM
- Validation on all user inputs

## 📚 Key Features

- RESTful API design
- Comprehensive error handling
- Request validation
- Database migrations for version control
- Seeder factories for testing data
- Eloquent relationships
- Authentication & authorization
- Rate limiting (configurable)
- CORS support

## 🔄 Database Schema

The application manages:
- **Users** - User accounts and profiles
- **Products** - Product listings and details
- **Product Categories** - Product categorization
- **Product Images** - Images associated with products
- **Personal Access Tokens** - API authentication tokens

## 🚀 Deployment

### Production Build
```bash
# Install production dependencies
composer install --no-dev --optimize-autoloader

# Optimize application
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📖 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Eloquent ORM](https://laravel.com/docs/eloquent)
- [Laravel API Authentication](https://laravel.com/docs/sanctum)
- [Pest Documentation](https://pestphp.com)

## 🤝 Contributing

Follow Laravel conventions and ensure all tests pass before submitting changes.

## 📄 License

This project is part of the Products Demo Application suite.
