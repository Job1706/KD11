# WMS Full (Frontend + Backend) on Docker

## Run
```bash
docker compose up -d --build
```
- App (frontend+backend PHP): http://localhost:8080
- phpMyAdmin:                http://localhost:8081

## Default login
- admin / 1234
- user  / 1234

## Tech
- PHP 8.2 + Apache, PDO MySQL
- MySQL 8
- Everything configured via ENV, with DB bootstrap & schema auto-fix (UNSIGNED + FKs)

## Useful
- If ports busy, edit docker-compose.yml (e.g. 8082:80 or 3307:3306) and run:
  ```bash
  docker compose down -v
  docker compose up -d --build
  ```
