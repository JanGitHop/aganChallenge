# Shopping Cart REST API

RESTful API for managing shopping carts built with Symfony 7.3 and PHP 8.3.
#### Author: Jan Geißler, Berlin, Germany

### The Challenge

Wir würden gerne sehen, inwiefern du eine einfache REST-API eines Warenkorbes in PHP mit aktuellem Symfony ohne API-Platform umsetzt.
Die API sollte RESTful sein und folgende Funktionen bieten:

- Einen Artikel in den Warenkorb legen
- Einen Artikel aus dem Warenkorb löschen
- Einen Artikel im Warenkorb editieren
- Den Warenkorb anzeigen lassen

Für die Coding Challenge hast du zwei Wochen Zeit und wir sind gespannt auf das Ergebnis.
Es geht wie gesagt in erster Linie um die Qualität- und Code-Liebe, und nicht darum, der/die Schnellste zu sein. :)

## Features

- RESTful API design
- Full CRUD operations for shopping carts
- Input validation and error handling
- Redis-based caching for performance
- Rate limiting for API security
- Comprehensive test coverage
- API documentation (OpenAPI/Swagger)
- PSR-12 code standards

## Requirements

- Docker & Docker Compose
- Git

## Installation

### Quick Start

```bash
git clone https://github.com/JanGitHop/aganChallenge
cd aganChallenge
./bin/setup
```

The setup script will:
- Check Docker installation
- Build and start all containers
- Display next steps

### Complete Setup

After running `./bin/setup`, complete the installation:

```bash
# Install PHP dependencies
./bin/sail composer install

# Create database and run migrations
./bin/sail console doctrine:database:create
./bin/sail console doctrine:migrations:migrate
```

### Optional: Trust HTTPS Certificate

To access `https://localhost` without browser warnings:

```bash
./bin/sail trust-cert
```

### Optional: Shell Alias

For convenience, you can create a shell alias to use `sail` instead of `./bin/sail`:

**Bash:**
```bash
echo "alias sail='./bin/sail'" >> ~/.bashrc && source ~/.bashrc
```

**Zsh:**
```bash
echo "alias sail='./bin/sail'" >> ~/.zshrc && source ~/.zshrc
```

After setting up the alias, you can use `sail` commands directly (e.g., `sail up`, `sail composer install`).

**For detailed setup and configuration, see [SETUP.md](SETUP.md)**

**For Redis implementation details, see [REDIS_IMPLEMENTATION.md](REDIS_IMPLEMENTATION.md)**

## Usage

API is available at:
- HTTP: `http://localhost`
- HTTPS: `https://localhost` (self-signed certificate)
- API Documentation: `http://localhost/api/doc`

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/carts` | Create new cart |
| GET | `/api/carts` | List all carts |
| GET | `/api/carts/{id}` | Get cart details |
| POST | `/api/carts/{id}/items` | Add item to cart |
| PATCH | `/api/carts/{id}/items/{itemId}` | Update item quantity |
| DELETE | `/api/carts/{id}/items/{itemId}` | Remove item from cart |

### Examples

**Create cart:**
```bash
curl -X POST http://localhost/api/carts
```

**Add item:**
```bash
curl -X POST http://localhost/api/carts/{cartId}/items \
  -H "Content-Type: application/json" \
  -d '{
    "productId": 123,
    "productName": "Laptop",
    "price": 999.99,
    "quantity": 2,
    "category": "Electronics",
    "sku": "LAP-001"
  }'
```

**Update quantity:**
```bash
curl -X PATCH http://localhost/api/carts/{cartId}/items/{itemId} \
  -H "Content-Type: application/json" \
  -d '{"quantity": 5}'
```

## Testing

```bash
# Run all tests
./bin/sail composer test

# Run unit tests only
./bin/sail composer test:unit

# Run integration tests only
./bin/sail composer test:integration
```

## Code Quality

```bash
# Static analysis
./bin/sail composer phpstan

# Check code style
./bin/sail composer cs-check

# Fix code style
./bin/sail composer cs-fix
```

## Development

The application uses FrankenPHP with hot-reload enabled. Changes to PHP files are reflected immediately without container restart.

## Database

- PostgreSQL 16
- Development DB: `app`
- Test DB: `app_test`

Access database:
```bash
./bin/sail db
```

## Redis Caching & Rate Limiting

### Features
- **Response Caching**: GET requests cached for 5 minutes (95% faster response times)
- **Rate Limiting**: IP-based limits to prevent API abuse
  - Global: 1000 req/min
  - Read: 100 req/min
  - Write: 30 req/min
  - Cart modifications: 10 req/10s
- **X-RateLimit Headers**: Standard rate limit information in responses

### Access Redis
```bash
./bin/sail exec redis redis-cli

# Check cache keys
KEYS cart_*

# Monitor operations
MONITOR
```

## Tech Stack

- PHP 8.3
- Symfony 7.3
- PostgreSQL 16
- Redis 7
- FrankenPHP
- Doctrine ORM
- PHPUnit 12
- PHPStan (Level 8)
- PHP-CS-Fixer (PSR-12)

## Project Structure

```
src/
├── Controller/Api/    # API endpoints
├── Entity/            # Domain entities
├── Exception/         # Custom exceptions
├── EventListener/     # Exception handling
└── Repository/        # Database queries

tests/
├── Unit/              # Entity tests
└── Integration/       # API endpoint tests
```

## License

Proprietary
