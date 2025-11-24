#!/bin/bash
set -e

echo "🚀 Starting Local 39 Apprenticeship System..."

# Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
until php artisan db:show 2>/dev/null; do
    echo "Database is unavailable - sleeping"
    sleep 2
done

echo "✅ Database is ready!"

# Check if .env exists, if not create from example
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    php artisan key:generate --ansi
fi

# Create storage directories
echo "📁 Creating storage directories..."
mkdir -p storage/app/private/applications/documents
mkdir -p storage/app/private/applications/biometric
mkdir -p storage/app/private/indentures
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs

# Set correct permissions
echo "🔒 Setting permissions..."
chown -R local39user:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Create storage link if it doesn't exist
if [ ! -L public/storage ]; then
    echo "🔗 Creating storage symbolic link..."
    php artisan storage:link
fi

# Run database migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Check if database is empty (no users) and seed if needed
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();")
if [ "$USER_COUNT" -eq "0" ]; then
    echo "🌱 Seeding database with initial data..."
    php artisan db:seed --force
else
    echo "✅ Database already contains data, skipping seeding."
fi

# Clear and cache configurations (for production)
if [ "$APP_ENV" = "production" ]; then
    echo "⚡ Optimizing for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
else
    echo "🔧 Running in development mode..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
fi

echo "✨ Application setup complete!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🌐 Application: http://localhost:8000"
echo "📧 Mailpit: http://localhost:8025"
echo "🗄️  PHPMyAdmin: http://localhost:8080"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Execute the main command (supervisord)
exec "$@"
