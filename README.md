# OrderIn - Restaurant Ordering System API

Laravel-based REST API untuk sistem pemesanan restoran dengan Docker deployment dan CI/CD.

## 🚀 Tech Stack

- **Backend:** Laravel 11.x
- **PHP:** 8.4 FPM
- **Web Server:** Nginx
- **Database:** MySQL (External Container/Server)
- **Storage:** ImageKit
- **Container:** Docker & Docker Compose
- **CI/CD:** GitHub Actions + Self-Hosted Runner
- **Registry:** GitHub Container Registry (GHCR)

## 📁 Struktur Project

```
orderin/
├── .github/workflows/
│   └── deploy.yml              # CI/CD workflow
├── docker/
│   └── nginx/
│       └── nginx.conf          # Nginx config
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   └── Services/
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
├── docker-compose.yml          # Production (GHCR image)
├── docker-compose.local.yml    # Local development
├── Dockerfile                  # PHP 8.4 FPM image
├── SETUP-RUNNER.md            # Self-hosted runner guide
└── README.md
```

## 🏃 Quick Start

### Local Development

1. **Clone repository:**
```bash
git clone <repository-url>
cd orderin
```

2. **Copy environment file:**
```bash
cp .env.example .env
```

3. **Update .env dengan credentials lokal:**
```env
APP_KEY=base64:...
APP_ENV=local
APP_DEBUG=true

DB_HOST=localhost  # atau IP MySQL container
DB_PORT=3306
DB_DATABASE=orderin
DB_USERNAME=root
DB_PASSWORD=

IMAGEKIT_PUBLIC_KEY=
IMAGEKIT_PRIVATE_KEY=
IMAGEKIT_URL_ENDPOINT=
```

4. **Build dan run dengan Docker:**
```bash
# Menggunakan docker-compose.local.yml
docker compose -f docker-compose.local.yml up -d --build

# Run migrations
docker compose -f docker-compose.local.yml exec app php artisan migrate

# Run seeders
docker compose -f docker-compose.local.yml exec app php artisan db:seed
```

5. **Access application:**
```
http://localhost:8080
```

### Production Deployment

Lihat **[SETUP-RUNNER.md](SETUP-RUNNER.md)** untuk panduan lengkap setup production dengan self-hosted runner.

#### Quick Summary:

1. Setup self-hosted runner di server
2. Setup GitHub Secrets
3. Push ke branch `stagging` atau `main`
4. GitHub Actions akan otomatis:
   - Build Docker image
   - Push ke GHCR
   - Deploy ke server
   - Run migrations
   - Optimize caches

## 🔧 Available Commands

### Docker Commands

```bash
# Start containers
docker compose up -d

# Stop containers
docker compose down

# View logs
docker compose logs -f

# Rebuild
docker compose up -d --build

# Execute artisan
docker compose exec app php artisan [command]
```

### Laravel Artisan

```bash
# Run migrations
docker compose exec app php artisan migrate

# Run seeders
docker compose exec app php artisan db:seed

# Clear caches
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear

# Optimize for production
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

## 🔐 GitHub Secrets Required

Setup di: **Repository → Settings → Secrets and variables → Actions**

### Application Secrets:
- `APP_KEY` - Laravel app key
- `APP_ENV` - `production` / `staging`
- `APP_DEBUG` - `false`
- `APP_URL` - Application URL

### Database Secrets:
- `DB_CONNECTION` - `mysql`
- `DB_HOST` - MySQL host/IP
- `DB_PORT` - `3306`
- `DB_DATABASE` - Database name
- `DB_USERNAME` - DB username
- `DB_PASSWORD` - DB password

### ImageKit Secrets:
- `IMAGEKIT_PUBLIC_KEY`
- `IMAGEKIT_PRIVATE_KEY`
- `IMAGEKIT_URL_ENDPOINT`

**Note:** `GITHUB_TOKEN` otomatis tersedia untuk GHCR access.

## 🌐 API Endpoints

### Authentication
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/user` - Get authenticated user

### Tables
- `GET /api/tables` - List all tables
- `GET /api/tables/{id}` - Get table detail

### Foods
- `GET /api/foods` - List all foods
- `GET /api/foods/{id}` - Get food detail

### Orders
- `GET /api/orders` - List orders
- `POST /api/orders` - Create order
- `GET /api/orders/{id}` - Get order detail
- `PUT /api/orders/{id}` - Update order
- `DELETE /api/orders/{id}` - Cancel order

### Order Items
- `POST /api/orders/{id}/items` - Add item to order
- `PUT /api/order-items/{id}` - Update item status
- `DELETE /api/order-items/{id}` - Remove item

## 📊 Database Schema

### Tables
- `users` - User accounts (admin, waiter, cashier)
- `tables` - Restaurant tables
- `foods` - Menu items
- `orders` - Customer orders
- `order_items` - Items in each order

## 🔄 CI/CD Workflow

### Branches:
- `main` - Production deployment
- `stagging` - Staging deployment

### Pipeline Steps:

**Build & Push (ubuntu-latest):**
1. Checkout code
2. Setup Docker Buildx
3. Login to GHCR
4. Build & push Docker image
5. Tag: `latest`, `sha-xxx`, `branch-name`

**Deploy (self-hosted):**
1. Checkout code
2. Login to GHCR
3. Pull latest image
4. Deploy with docker-compose
5. Run migrations
6. Optimize caches
7. Cleanup old images

## 🐛 Troubleshooting

### Container tidak start
```bash
docker compose ps
docker compose logs app
docker compose logs nginx
```

### Database connection error
- Check `DB_HOST` accessibility
- Verify MySQL container running
- Check credentials

### Permission issues
```bash
docker compose exec app chown -R www-data:www-data /var/www/html/storage
docker compose exec app chmod -R 775 /var/www/html/storage
```

### Image pull failed
```bash
# Login to GHCR
echo "YOUR_TOKEN" | docker login ghcr.io -u YOUR_USERNAME --password-stdin

# Pull manually
docker pull ghcr.io/YOUR-USERNAME/orderin:latest
```
