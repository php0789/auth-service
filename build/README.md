# Container build

The shared Compose file is `../docker-compose.yml.txt`, beside the `auth-service` directory. It contains `auth-service`, `nginx`, `mysql`, and `redis`; add future services there. The `.txt` suffix is supported when the filename is passed with `-f`.

## Layout

- `Dockerfile`: builds PHP 8.3 with production Composer dependencies, Supervisor, and the PHP Redis extension. It does not contain the Nginx or Redis servers.
- `supervisord.conf`: manages PHP-FPM and the optional authentication queue worker.
- `nginx.conf`: server configuration mounted into the separate official Nginx image.
- `php-fpm.conf`, `php.ini`: PHP configuration. FPM listens on port 9000 inside the Compose network, without a published host port.
- `healthcheck.sh`: tests FPM's internal ping endpoint.
- `entrypoint.sh`: checks runtime settings and prepares writable directories.
- `Dockerfile.dockerignore`: excludes local secrets, dependencies, and runtime data from the image.
- `.env.example`: template for Compose variables, with auth-specific names to avoid collisions with future services.

Nginx forwards requests to `auth-service:9000`. It mounts only the local `public/` directory read-only for static files. PHP executes application code copied into its image. Rebuild after code changes and keep mounted public assets from the same application revision. Vite is not run by this backend build; compile any required frontend assets into `public/build` separately.

## Build and configure

Run these commands from the `auth-service` repository root, with Docker Desktop using Linux containers:

```powershell
docker build -f build/Dockerfile -t auth-service:local .
Copy-Item build/.env.example build/.env
docker run --rm auth-service:local php artisan key:generate --show
```

Set `AUTH_APP_KEY` in `build/.env` to the generated key. Set `AUTH_DB_PASSWORD` and `MYSQL_ROOT_PASSWORD` to distinct non-empty values. Keep the application key across restarts. This file is separate from Laravel's host `.env` and is ignored by Git.

## Start and migrate

```powershell
docker compose --env-file build/.env -f ../docker-compose.yml.txt up -d --build
docker compose --env-file build/.env -f ../docker-compose.yml.txt exec --user www-data auth-service php artisan migrate --force
```

Open http://localhost:8080, or use http://localhost:8080/up for the application health endpoint. Change both `HTTP_PORT` and `AUTH_APP_URL` to use a different port. The app container health check tests FPM; the Nginx health check tests Laravel through HTTP.

After migrations, set `AUTH_QUEUE_WORKER_ENABLED=true` and apply it:

```powershell
docker compose --env-file build/.env -f ../docker-compose.yml.txt up -d auth-service nginx
docker compose --env-file build/.env -f ../docker-compose.yml.txt exec nginx nginx -s reload
docker compose --env-file build/.env -f ../docker-compose.yml.txt logs -f auth-service nginx
```

Reload Nginx after recreating the PHP container so it resolves the current upstream address. The worker timeout is 60 seconds, below Laravel's default Redis retry interval of 90 seconds. Keep that relationship when changing queue settings.

## Shared infrastructure

Compose resolves build and mount paths relative to the shared file in `ecommerce/`. All four services use its default network. Only Nginx publishes a port, bound to localhost. Redis persists its data and the authentication application uses an `auth_service_` key prefix.

MySQL initializes the auth database and user on its first startup with an empty volume. Adding another service later requires provisioning its database/user separately; changing these variables does not update an existing MySQL volume. Keep separate application keys and Redis prefixes for future services.

This is a local setup: email goes to logs, public files are bind-mounted, and HTTPS is not configured. Supply deployment secrets, email settings, HTTPS, and matching immutable application/static assets when deploying. Migrations remain explicit; container startup does not run them.

## Stop

```powershell
docker compose --env-file build/.env -f ../docker-compose.yml.txt down
```

This stops the entire shared project, including services added later. To stop only authentication and its HTTP entry point, use `stop auth-service nginx`. Named volumes persist after `down`.

References: [Compose networking](https://docs.docker.com/compose/how-tos/networking/), [PHP-FPM configuration](https://www.php.net/manual/en/install.fpm.configuration.php).
