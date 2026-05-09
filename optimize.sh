#!/bin/bash
echo "🚀 EstateFlow — Production Optimization"

echo "→ Installing production dependencies..."
composer install --optimize-autoloader --no-dev

echo "→ Running migrations..."
php artisan migrate --force

echo "→ Caching configuration, routes, views, events..."
php artisan optimize

echo "→ Building frontend assets..."
npm ci && npm run build

echo "✓ Optimization complete."