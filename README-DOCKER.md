# Docker Setup - Local 39 Apprenticeship System

Complete Docker configuration for running the Local 39 Stationary Engineers Apprenticeship Management System in containers.

## 📋 Table of Contents

- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Services Overview](#services-overview)
- [Development Workflow](#development-workflow)
- [Production Deployment](#production-deployment)
- [Useful Commands](#useful-commands)
- [Troubleshooting](#troubleshooting)

---

## 🎯 Prerequisites

Before you begin, ensure you have installed:

- **Docker** (20.10+): [Install Docker](https://docs.docker.com/get-docker/)
- **Docker Compose** (2.0+): [Install Docker Compose](https://docs.docker.com/compose/install/)

Verify installation:
```bash
docker --version
docker-compose --version
```

---

## 🚀 Quick Start

### 1. Clone the Repository (if not already done)

```bash
git clone <repository-url>
cd Devops
```

### 2. Copy Environment File

```bash
cp .env.docker .env
```

**Important:** Edit `.env` and set `APP_KEY` if needed (the entrypoint script will generate it automatically if not set).

### 3. Build and Start All Services

```bash
docker-compose up -d
```

This single command will:
- Build the Docker images
- Start all services (app, database, redis, mailpit)
- Run database migrations
- Seed the database with test users
- Set up storage directories and permissions

### 4. Access the Application

Once all containers are running:

| Service | URL | Description |
|---------|-----|-------------|
| **Application** | http://localhost:8000 | Main Laravel/Vue.js app |
| **Mailpit** | http://localhost:8025 | Email testing interface |
| **PHPMyAdmin** | http://localhost:8080 | Database management |
| **Vite Dev Server** | http://localhost:5173 | Hot Module Replacement |

### 5. Default Test Users

After the initial setup, these accounts are available:

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@local39.org | password |
| Admin | testadmin@local39.org | password |
| Chief | chief@local39.org | password |
| Applicant | applicant@local39.org | password |

---

## 🐳 Services Overview

### Application Service (`app`)

- **Image**: Custom PHP 8.2-FPM + Nginx
- **Ports**:
  - `8000`: Nginx web server
  - `5173`: Vite dev server (HMR)
- **Features**:
  - PHP 8.2 with all required extensions
  - Nginx for serving Laravel
  - Node.js 20 for Vite
  - Supervisor for process management
  - Queue workers (2 processes)

### Database Service (`db`)

- **Image**: MySQL 8.0
- **Port**: `3306`
- **Credentials**:
  - Database: `local39_apprenticeship`
  - User: `local39user`
  - Password: `local39pass`
  - Root Password: `rootpassword`
- **Volume**: Persistent data in `dbdata` volume

### Redis Service (`redis`)

- **Image**: Redis 7 Alpine
- **Port**: `6379`
- **Usage**: Caching and session storage
- **Volume**: Persistent data in `redisdata` volume

### Mailpit Service (`mailpit`)

- **Image**: axllent/mailpit
- **Ports**:
  - `8025`: Web UI for viewing emails
  - `1025`: SMTP server
- **Usage**: Catch all outgoing emails for testing

### PHPMyAdmin Service (`phpmyadmin`)

- **Image**: Official PHPMyAdmin
- **Port**: `8080`
- **Usage**: Database management interface

---

## 💻 Development Workflow

### Starting Development

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# View logs for specific service
docker-compose logs -f app
```

### Running Artisan Commands

```bash
# Run any artisan command
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan tinker

# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### Running Composer Commands

```bash
# Install dependencies
docker-compose exec app composer install

# Add new package
docker-compose exec app composer require vendor/package

# Update dependencies
docker-compose exec app composer update
```

### Running NPM Commands

```bash
# Install Node.js dependencies
docker-compose exec app npm install

# Run Vite dev server (with HMR)
docker-compose exec app npm run dev

# Build for production
docker-compose exec app npm run build
```

### Database Operations

```bash
# Run migrations
docker-compose exec app php artisan migrate

# Run fresh migrations with seeding
docker-compose exec app php artisan migrate:fresh --seed

# Create new migration
docker-compose exec app php artisan make:migration create_table_name

# Access MySQL CLI
docker-compose exec db mysql -u local39user -plocal39pass local39_apprenticeship

# Or use root user
docker-compose exec db mysql -u root -prootpassword local39_apprenticeship

# Backup database
docker-compose exec db mysqldump -u root -prootpassword local39_apprenticeship > backup.sql

# Restore database
docker-compose exec -T db mysql -u root -prootpassword local39_apprenticeship < backup.sql
```

### Accessing Container Shell

```bash
# Access app container
docker-compose exec app bash

# Access as local39user
docker-compose exec -u local39user app bash

# Access database container
docker-compose exec db bash
```

### File Permissions Issues

If you encounter permission issues:

```bash
# Fix storage permissions
docker-compose exec app chown -R local39user:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

---

## 🚀 Production Deployment

### 1. Update Environment Variables

Edit `.env` for production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Use strong passwords
DB_PASSWORD=strong_random_password

# Production cache/session drivers
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Real SMTP settings
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-mail-provider.com
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

### 2. Build Production Image

```bash
# Build with production target
docker build --target base -t local39-app:latest .
```

### 3. Use Production Docker Compose

Create `docker-compose.prod.yml`:

```yaml
version: '3.8'

services:
  app:
    image: local39-app:latest
    restart: always
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    # ... other production settings

  db:
    restart: always
    # Add backup volumes
    volumes:
      - dbdata:/var/lib/mysql
      - ./backups:/backups
```

### 4. Run Production Stack

```bash
docker-compose -f docker-compose.prod.yml up -d
```

### 5. SSL/TLS Configuration

For production, add a reverse proxy (Traefik, Nginx Proxy Manager, or Caddy) to handle SSL certificates.

Example using Nginx Proxy Manager:
- Install Nginx Proxy Manager
- Add proxy host pointing to `app:80`
- Enable SSL with Let's Encrypt

---

## 🛠️ Useful Commands

### Container Management

```bash
# Stop all services
docker-compose down

# Stop and remove volumes (WARNING: deletes data)
docker-compose down -v

# Restart all services
docker-compose restart

# Restart specific service
docker-compose restart app

# View running containers
docker-compose ps

# View resource usage
docker stats
```

### Logs and Debugging

```bash
# View all logs
docker-compose logs -f

# View app logs only
docker-compose logs -f app

# View last 100 lines
docker-compose logs --tail=100 app

# View Laravel logs
docker-compose exec app tail -f storage/logs/laravel.log

# View queue worker logs
docker-compose exec app tail -f storage/logs/queue-worker.log
```

### Rebuilding Containers

```bash
# Rebuild without cache
docker-compose build --no-cache

# Rebuild and restart
docker-compose up -d --build

# Rebuild specific service
docker-compose build app
```

### Database Maintenance

```bash
# Create database backup
docker-compose exec db mysqldump -u root -prootpassword local39_apprenticeship | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz

# List all databases
docker-compose exec db mysql -u root -prootpassword -e "SHOW DATABASES;"

# Check database size
docker-compose exec db mysql -u root -prootpassword -e "SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.TABLES GROUP BY table_schema;"
```

### Cleanup

```bash
# Remove stopped containers
docker-compose rm

# Remove unused images
docker image prune -a

# Remove unused volumes
docker volume prune

# Remove everything (careful!)
docker system prune -a --volumes
```

---

## 🐛 Troubleshooting

### Issue 1: Port Already in Use

**Error**: `Bind for 0.0.0.0:8000 failed: port is already allocated`

**Solution**:
```bash
# Find process using the port
lsof -i :8000
# or
netstat -tulpn | grep 8000

# Kill the process or change port in docker-compose.yml
ports:
  - "8001:80"  # Use port 8001 instead
```

### Issue 2: Database Connection Refused

**Error**: `SQLSTATE[HY000] [2002] Connection refused`

**Solution**:
```bash
# Check if database container is running
docker-compose ps db

# Check database logs
docker-compose logs db

# Wait for database to be fully ready (usually takes 10-30 seconds)
docker-compose exec app php artisan db:show

# Restart database container
docker-compose restart db
```

### Issue 3: Permission Denied Errors

**Error**: `Permission denied` when writing files

**Solution**:
```bash
# Fix ownership and permissions
docker-compose exec app chown -R local39user:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Issue 4: Composer/NPM Install Fails

**Error**: Out of memory or timeout

**Solution**:
```bash
# Increase Docker memory limit in Docker Desktop settings (4GB+)

# Or install dependencies locally then rebuild
composer install
npm install
docker-compose build --no-cache app
```

### Issue 5: Frontend Not Hot Reloading

**Issue**: Changes to Vue files not reflected

**Solution**:
```bash
# Make sure Vite server is running
docker-compose exec app npm run dev

# Check if port 5173 is accessible
curl http://localhost:5173

# Restart the container
docker-compose restart app
```

### Issue 6: "Class not found" Errors

**Solution**:
```bash
# Regenerate autoloader
docker-compose exec app composer dump-autoload

# Clear and recache
docker-compose exec app php artisan optimize:clear
docker-compose exec app php artisan optimize
```

### Issue 7: Queue Jobs Not Processing

**Solution**:
```bash
# Check queue worker logs
docker-compose exec app tail -f storage/logs/queue-worker.log

# Check if supervisor is running
docker-compose exec app supervisorctl status

# Restart queue workers
docker-compose exec app supervisorctl restart laravel-queue:*
```

### Issue 8: Can't Access Mailpit

**Solution**:
```bash
# Check if mailpit is running
docker-compose ps mailpit

# Check mailpit logs
docker-compose logs mailpit

# Restart mailpit
docker-compose restart mailpit
```

### Debugging Tips

```bash
# Enter container and inspect
docker-compose exec app bash

# Check PHP version and extensions
docker-compose exec app php -v
docker-compose exec app php -m

# Check Nginx status
docker-compose exec app nginx -t
docker-compose exec app nginx -s reload

# Check environment variables
docker-compose exec app env

# Test database connection
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

---

## 📂 Project Structure

```
Devops/
├── docker/
│   ├── nginx/
│   │   └── default.conf              # Nginx configuration
│   ├── php/
│   │   └── local.ini                 # PHP configuration
│   ├── mysql/
│   │   └── my.cnf                    # MySQL configuration
│   ├── supervisor/
│   │   └── supervisord.conf          # Supervisor configuration
│   └── docker-entrypoint.sh          # Container initialization script
├── Dockerfile                         # Multi-stage Dockerfile
├── docker-compose.yml                 # Docker Compose configuration
├── .dockerignore                      # Files to ignore in Docker build
├── .env.docker                        # Docker environment template
└── README-DOCKER.md                   # This file
```

---

## 🔒 Security Considerations

### Development

- Default credentials are used (fine for local development)
- Debug mode is enabled
- All ports are exposed

### Production

1. **Change all default passwords** in `.env`
2. **Disable debug mode**: `APP_DEBUG=false`
3. **Use SSL/TLS**: Set up reverse proxy with Let's Encrypt
4. **Restrict database access**: Don't expose port 3306 publicly
5. **Use secrets management**: Docker secrets or environment management
6. **Regular backups**: Automate database and file backups
7. **Update regularly**: Keep Docker images and dependencies updated

---

## 📊 Performance Optimization

### For Development

The default configuration is optimized for development with:
- Hot module replacement (HMR)
- Source maps
- Debug logging
- Opcache with revalidation

### For Production

Update these settings:

```dockerfile
# In Dockerfile, use production stage
FROM base as production
RUN composer install --no-dev --optimize-autoloader
RUN npm run build
```

```env
# In .env
APP_ENV=production
APP_DEBUG=false
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

---

## 🆘 Getting Help

If you encounter issues:

1. **Check logs**: `docker-compose logs -f`
2. **Verify environment**: Check `.env` settings
3. **Restart services**: `docker-compose restart`
4. **Rebuild containers**: `docker-compose up -d --build`
5. **Clean slate**: `docker-compose down -v && docker-compose up -d`

---

## 📝 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Docker Documentation](https://docs.docker.com/)
- [Vue.js Documentation](https://vuejs.org/)
- [Vite Documentation](https://vitejs.dev/)

---

## ✅ Quick Checklist

- [ ] Docker and Docker Compose installed
- [ ] `.env` file created from `.env.docker`
- [ ] Run `docker-compose up -d`
- [ ] Access http://localhost:8000
- [ ] Login with test credentials
- [ ] Check Mailpit at http://localhost:8025
- [ ] View database in PHPMyAdmin at http://localhost:8080

---

**Happy Developing! 🚀**

Your Local 39 Apprenticeship System is now containerized and ready for development!
