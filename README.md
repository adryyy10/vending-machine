# Vending Machine

CLI vending machine: insert coins, select a product, get change.

---

## How to run the solution

The fastest way to evaluate the project is Docker. PHP 8.5, Composer, and the test tooling are inside the image.

### 1. Start Docker Desktop

Leave it running so the engine is available.

### 2. Start the machine

From the project root:

```bash
docker compose run --rm app
```

You get an interactive prompt. Type comma-separated actions, one line at a time, then press Enter. Inserting coins prints nothing; a purchase or `RETURN-COIN` prints the result. Press `Ctrl+D` to exit.

### 3. Try the spec examples

```text
1, GET-WATER
→ WATER, 0.25, 0.10

0.10, 0.10, RETURN-COIN
→ 0.10, 0.10

1, 0.25, 0.25, GET-SODA
→ SODA
```

Valid tokens: `0.05`, `0.10`, `0.25`, `1`, `GET-WATER`, `GET-JUICE`, `GET-SODA`, `RETURN-COIN`, `SERVICE`.

Pipe a line instead of typing:

```bash
echo '1, GET-WATER' | docker compose run --rm -T app
```

### 4. Run the test suite

```bash
docker compose run --rm test
```

Quality checks (same as CI):

```bash
docker compose run --rm app vendor/bin/phpstan analyse src tests --level=8
docker compose run --rm app vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no
```

### Troubleshooting Docker

If `docker compose` is unknown, or you see `docker-credential-desktop: executable file not found`, Homebrew's `docker` is ahead of Docker Desktop on your `PATH`. In that shell:

```bash
export PATH="/Applications/Docker.app/Contents/Resources/bin:$PATH"
docker compose run --rm app
```

### Without Docker

Requires PHP 8.5 and Composer installed locally:

```bash
composer install
php bin/vending-machine
vendor/bin/phpunit
```

---

## Tech stack

| Layer | Choice |
| --- | --- |
| Language | PHP 8.5 |
| Dependencies | Composer 2 |
| Architecture | DDD / hexagonal: `Domain`, `Application`, `Infrastructure` |
| Delivery | CLI (`bin/vending-machine`) |
| Tests | PHPUnit 13 |
| Static analysis | PHPStan 2 (level 8) |
| Coding style | PHP CS Fixer 3 |
| Runtime for reviewers | Docker + Compose (`php:8.5-cli`) |
| CI | GitHub Actions (PHPStan, CS Fixer, PHPUnit) |
