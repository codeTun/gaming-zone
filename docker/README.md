# Gaming Zone Docker Setup

## Quick Start

1. **Copy environment file:**
   ```bash
   cp .env.docker .env
   ```

2. **Start all services:**
   ```bash
   docker-compose up -d
   ```

3. **Access your application:**
   - Frontend: http://localhost:3000
   - Backend API: http://localhost:8080
   - PHPMyAdmin: http://localhost:8081

## Services

### Frontend (Nginx)
- **Port:** 3000
- **Description:** Serves static HTML, CSS, JS files
- **Features:** 
  - Gzip compression
  - Static file caching
  - API proxy to backend
  - CORS handling

### Backend (PHP + Apache)
- **Port:** 8080
- **Description:** PHP API server
- **Features:**
  - PHP 8.2 with necessary extensions
  - Apache with mod_rewrite
  - CORS headers
  - Security headers

### Database (MySQL)
- **Port:** 3306
- **Description:** MySQL 8.0 database
- **Credentials:**
  - Root password: `root_password`
  - Database: `gaming_zone_new`
  - User: `gaming_user`
  - Password: `gaming_password`

### PHPMyAdmin
- **Port:** 8081
- **Description:** Web-based MySQL administration
- **Login:** gaming_user / gaming_password

## Development Commands

```bash
# Start services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down

# Rebuild services
docker-compose build --no-cache

# Access backend container
docker exec -it gaming_zone_backend bash

# Access database
docker exec -it gaming_zone_db mysql -u gaming_user -p gaming_zone_new
```

## Environment Variables

Update `.env` file with your configurations:
- Cloudinary credentials for image uploads
- JWT secret key for production
- Database credentials if needed

## Production Notes

1. Change default passwords in production
2. Use environment-specific .env files
3. Enable HTTPS with SSL certificates
4. Configure proper backup strategies
5. Monitor resource usage and scaling
