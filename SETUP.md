# AsGoodasNew Challenge - Docker Setup Guide

This project provides a **Laravel Sail-inspired** Docker development environment for **Symfony 7.3** with **FrankenPHP**, **Caddy**, and full **HTTPS support**.

## 🚀 Quick Start

### Prerequisites
- Docker Desktop (with Compose V2)
- macOS, Linux, or WSL2 on Windows

### Initial Setup

1. **Run the setup script:**
   ```bash
   ./bin/setup
   ```
   This will:
   - Check your Docker installation
   - Build and start all containers
   - Display next steps

<!-- 2. **Add asgoodasnew.test to your hosts file (NOT USED IN CURRENT SETUP):**
   ```bash
   sudo sh -c 'echo "127.0.0.1 asgoodasnew.test" >> /etc/hosts'
   ``` -->

2. **Trust the local CA certificate (for HTTPS - optional):**
   ```bash
   ./bin/sail trust-cert
   ```
   This installs Caddy's self-signed certificate to your system's trust store for accessing https://localhost without warnings.

3. **Access the application:**
   - Application (HTTPS): **https://localhost**
   - Application (HTTP): **http://localhost**
   - API (HTTPS): **https://localhost/api**
   - API (HTTP): **http://localhost/api**
   <!-- - Frontend domain: https://asgoodasnew.test (NOT CONFIGURED) -->
   <!-- - Vite HMR: http://localhost:5173 (NOT USED - NO FRONTEND) -->

---

## 🏗️ Architecture Overview

### Services

| Service                             | Description | Access                                   |
|-------------------------------------|-------------|------------------------------------------|
| **frankenphp** (FrankenPHP + Caddy) | PHP 8.3 runtime + web server with automatic HTTPS | ports 80, 443, 8080                      |
| **database**                        | PostgreSQL 16 | localhost:5432                           |
| **redis**                           | Redis 7 (cache/sessions) | localhost:6379                           |
| **mailer**                          | Mailpit (SMTP testing) | SMTP: localhost:1025, UI: localhost:8025 |
| (**rabbitmq**)                      | RabbitMQ with management UI | not installed                            | -->
| (**vite**)                          | Node 20 dev server for Vue 3 + Vite HMR | not installed                            | -->

### Domain Routing

The Caddy configuration serves the application on **localhost** with both HTTP and HTTPS:

1. **localhost** - Full Application Access
   - Full Symfony application available
   - API routes available at `/api/*`
   - Supports both HTTP (port 80) and HTTPS (port 443)
   - HTTPS uses Caddy's internal TLS

<!-- 
2. **asgoodasnew.test** (NOT CONFIGURED IN CURRENT SETUP)
   - Would require Caddyfile modification and /etc/hosts entry
   - Could be added for custom domain routing if needed
-->

### HTTPS Configuration

- Caddy automatically generates a **local Certificate Authority (CA)** on first run with `tls internal`
- Self-signed certificates are issued for `localhost`
- The CA certificate is stored in the `caddy_data` Docker volume at `/data/caddy/pki/authorities/local/root.crt`
- Use `./bin/sail trust-cert` to install the CA to your system (optional, removes browser warnings)
- Both HTTP and HTTPS work without certificate trust, but HTTPS will show security warnings until trusted

---

## 🛠️ Sail CLI Commands

The `./bin/sail` script provides Laravel Sail-like functionality:

### Container Management
```bash
./bin/sail up              # Start all services
./bin/sail down            # Stop all services
./bin/sail restart         # Restart services
./bin/sail ps              # Show service status
./bin/sail logs [service]  # View logs
./bin/sail build           # Rebuild containers
```

### Application Commands
```bash
./bin/sail shell           # Enter app container
./bin/sail php [...]       # Run PHP commands
./bin/sail composer [...]  # Run Composer
./bin/sail console [...]   # Run Symfony console
# ./bin/sail npm [...]       # Run npm in vite container (not available - no vite service)
```

### Database Commands
```bash
./bin/sail db              # Enter PostgreSQL shell
./bin/sail db-reset        # Drop and recreate database
./bin/sail migrate         # Run migrations
```

### HTTPS/Certificate Commands
```bash
./bin/sail trust-cert      # Install CA certificate (requires sudo)
./bin/sail cert-info       # Show certificate info
./bin/sail caddy [...]     # Run Caddy commands
```

### Utility Commands
```bash
./bin/sail fresh           # Fresh install (rebuild everything)
./bin/sail clear           # Clear Symfony cache
./bin/sail test [...]      # Run PHPUnit tests
./bin/sail tinker          # PHP interactive shell
```

---

## 📁 Project Structure

```
AsGoodasNewChallenge/
├── bin/
│   ├── sail              # Sail CLI wrapper
│   └── setup             # Initial setup script
├── docker/
│   └── frankenphp/
│       ├── Caddyfile     # Web server config (localhost with HTTPS)
│       └── conf.d/
│           ├── app.dev.ini   # PHP development settings
│           └── app.prod.ini  # PHP production settings
├── Dockerfile            # FrankenPHP + PHP 8.3 image
├── compose.yaml          # Docker Compose configuration
├── compose.override.yaml # Local overrides (ports, mailer)
├── src/                  # Symfony application code
├── templates/            # Twig templates
└── public/               # Public assets
```

---

## 🔧 Configuration Details

### Environment Variables

Configure via `.env` file or environment:

```bash
APP_ENV=dev
APP_SECRET=!ChangeMe!
DATABASE_URL=postgresql://app:AsGoodAsNew@database:5432/app
REDIS_URL=redis://redis:6379
# MESSENGER_TRANSPORT_DSN=amqp://guest:guest@rabbitmq:5672/%2f/messages
```

### PHP Configuration

- **Development mode** with opcache enabled for hot reload
- `opcache.validate_timestamps=1` and `opcache.revalidate_freq=0`
- Xdebug installed in dev image
- Configuration: `docker/frankenphp/conf.d/app.dev.ini`

### Caddy Configuration

- **localhost**: HTTP (port 80) and HTTPS (port 443) with internal TLS
- Serves full Symfony application and API routes at `/api/*`
- Configuration: `docker/frankenphp/Caddyfile`
<!-- - **asgoodasnew.test**: NOT CONFIGURED (would need Caddyfile modification) -->
<!-- - **Vite HMR**: NOT USED (no frontend service) -->

<!--
### Vite/Vue HMR

- Dev server runs on port 5173
- HMR works on **both** localhost and asgoodasnew.test
- WebSocket connections proxied through Caddy
- Configuration: `vite.config.js`
-->

---

## 🔍 Troubleshooting

### HTTPS Certificate Warnings

**Problem**: Browser shows "Your connection is not private" when accessing https://localhost

**Solution**:
1. Make sure containers are running: `./bin/sail up`
2. Trust the CA certificate: `./bin/sail trust-cert`
3. Restart your browser
4. Clear browser cache/SSL state if needed

**Alternative**: Use HTTP instead (http://localhost) if you don't need HTTPS for local development

<!--
### Vite HMR Not Working (NOT USED - NO FRONTEND)

**Problem**: Changes to Vue components aren't reflected immediately

**Solutions**:
1. Check Vite is running: `./bin/sail logs vite`
2. Verify port 5173 is accessible: `curl http://localhost:5173`
3. Check browser console for WebSocket errors
4. Restart Vite: `./bin/sail restart vite`
-->

<!--
### Cannot Access asgoodasnew.test (NOT CONFIGURED)

**Problem**: Browser shows "Site can't be reached"

**Note**: The current setup does not use asgoodasnew.test domain. Use localhost instead.

**Solutions (if you want to add asgoodasnew.test)**:
1. Verify `/etc/hosts` entry exists:
   ```bash
   cat /etc/hosts | grep asgoodasnew.test
   ```
2. Add if missing:
   ```bash
   sudo sh -c 'echo "127.0.0.1 asgoodasnew.test" >> /etc/hosts'
   ```
3. Modify `docker/frankenphp/Caddyfile` to add asgoodasnew.test domain
4. Restart containers: `./bin/sail restart`
-->

### Port Conflicts

**Problem**: "port is already allocated" error

**Solutions**:
1. Check which process is using the port:
   ```bash
   lsof -i :80     # or :443, :5432, etc.
   ```
2. Stop the conflicting service
3. Or modify port mappings in `compose.yaml`

### Database Connection Issues

**Problem**: "Connection refused" or "Could not connect to database"

**Solutions**:
1. Check database is running: `./bin/sail ps`
2. Wait for database to be ready (check health): `./bin/sail logs database`
3. Verify credentials match `.env` configuration
4. Test connection: `./bin/sail db`

### PHP Hot Reload Not Working

**Problem**: PHP changes require container restart

**Solutions**:
1. Verify `docker/frankenphp/conf.d/app.dev.ini` is mounted correctly
2. Check opcache settings in container:
   ```bash
   ./bin/sail php -i | grep opcache
   ```
3. Clear opcache: `./bin/sail console cache:clear`

---

## 🧪 Testing the Setup

### Verify HTTPS
```bash
curl -k https://localhost/
```

### Verify HTTP
```bash
curl http://localhost/
```

### Verify API Routing (HTTP)
```bash
curl http://localhost/api
```

### Verify API Routing (HTTPS)
```bash
curl -k https://localhost/api
```

### Verify Services
```bash
./bin/sail ps
```

### Check Logs
```bash
./bin/sail logs frankenphp
./bin/sail logs database
./bin/sail logs mailer
# ./bin/sail logs vite  # (NOT USED - NO FRONTEND)
```

---

## 🔐 Production Considerations

This setup is optimized for **local development**. For production:

1. **Use real TLS certificates** (Let's Encrypt via Caddy)
2. **Change default passwords** in `compose.yaml`
3. **Build production assets**: `npm run build` (if using frontend assets)
4. **Use production PHP settings** (target `frankenphp_prod` in Dockerfile)
5. **Enable FrankenPHP worker mode** for performance
6. **Set secure `APP_SECRET`**
7. **Use managed database** service
8. **Configure proper logging** and monitoring

---

## 📚 Additional Resources

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [FrankenPHP Documentation](https://frankenphp.dev/)
- [Caddy Documentation](https://caddyserver.com/docs/)
<!-- - [Vite Documentation](https://vitejs.dev/) -->
<!-- - [Vue 3 Documentation](https://vuejs.org/) -->

---

## 🆘 Getting Help

If you encounter issues:

1. Check this troubleshooting guide
2. Review logs: `./bin/sail logs`
3. Verify service status: `./bin/sail ps`
4. Try fresh install: `./bin/sail fresh`

---

## ✅ Next Steps

After setup is complete:

1. **Run database migrations**:
   ```bash
   ./bin/sail migrate
   ```

2. **Install dependencies**:
   ```bash
   ./bin/sail composer install
   # ./bin/sail npm install  # (NOT USED - NO FRONTEND)
   ```

3. **Start development**:
   - Application (HTTPS): https://localhost
   - Application (HTTP): http://localhost
   - API (HTTPS): https://localhost/api
   - API (HTTP): http://localhost/api
   - Check emails: http://localhost:8025
   <!-- - Custom domain: https://asgoodasnew.test (NOT CONFIGURED) -->
   <!-- - Check RabbitMQ: http://localhost:15672 (NOT INSTALLED) -->

4. **Create your first controller**:
   ```bash
   ./bin/sail console make:controller ApiController
   ```

Happy coding! 🚀
