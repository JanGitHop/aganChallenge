# Shopping Cart REST API

RESTful API for managing shopping carts built with Symfony 7.3 and PHP 8.3.
#### Author: Jan Geißler, Berlin, Germany

### The Challenge

Wir würden gerne sehen, inwiefern du eine einfache REST-API eines Warenkorbes in PHP mit aktuellem Symfony ohne API-Platform umsetzt.
Die API sollte RESTful sein und folgende Funktionen bieten:

Einen Artikel in den Warenkorb legen
Einen Artikel aus dem Warenkorb löschen
Einen Artikel im Warenkorb editieren
Den Warenkorb anzeigen lassen

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

```bash
git clone https://github.com/JanGitHop/aganChallenge
cd aganChallenge
docker compose up -d
docker compose exec frankenphp composer install
docker compose exec frankenphp composer require symfony/cache predis/predis symfony/rate-limiter
docker compose exec frankenphp php bin/console doctrine:database:create
docker compose exec frankenphp php bin/console doctrine:migrations:migrate
```

**For detailed Redis setup and configuration, see [REDIS_IMPLEMENTATION.md](REDIS_IMPLEMENTATION.md)**

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
docker compose exec frankenphp composer test

# Run unit tests only
docker compose exec frankenphp composer test:unit

# Run integration tests only
docker compose exec frankenphp composer test:integration
```

## Code Quality

```bash
# Static analysis
docker compose exec frankenphp composer phpstan

# Check code style
docker compose exec frankenphp composer cs-check

# Fix code style
docker compose exec frankenphp composer cs-fix
```

## Development

The application uses FrankenPHP with hot-reload enabled. Changes to PHP files are reflected immediately without container restart.

## Database

- PostgreSQL 16
- Development DB: `app`
- Test DB: `app_test`

Access database:
```bash
docker compose exec database psql -U app -d app
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
docker compose exec redis redis-cli

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
