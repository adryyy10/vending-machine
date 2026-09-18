# Vending Machine

CLI vending machine for inserting coins, purchasing products, getting change, and returning coins.

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

You get an interactive prompt with a welcome screen listing every accepted action. Type comma-separated actions, one line at a time, then press Enter. Inserting coins prints nothing; a purchase or `RETURN-COIN` prints the result. Press `Ctrl+D` to exit.

---

## What you can do

By default the machine is stocked with **5 WATER**, **5 JUICE**, and **5 SODA**, and a hopper of **25 × 0.05**, **10 × 0.10**, **5 × 0.25**, and **2 × 1**.

| Action | Accepted wording | What happens |
| --- | --- | --- |
| Insert a coin | `0.05`, `0.10`, `0.25`, `1` | The coin is added to the current session. Nothing is printed. |
| Return coins | `RETURN-COIN` | All coins inserted so far are returned, largest denomination first (for example `0.10, 0.10`). |
| Select a product | `GET-WATER`, `GET-JUICE`, `GET-SODA` | See [Selecting a product](#selecting-a-product). |
| Service the machine | `SERVICE` | Interactive restock of hopper coins and every catalog product. |

Default catalog:

| Product | Price | Selector |
| --- | --- | --- |
| WATER | 0.65 | `GET-WATER` |
| JUICE | 1 | `GET-JUICE` |
| SODA | 1.50 | `GET-SODA` |

You can combine actions on one line: `1, 0.25, 0.25, GET-SODA`.

### Selecting a product

On a successful purchase the machine prints the product first, then any change (`WATER, 0.25, 0.10`). Inserted coins are then cleared.

If the selection cannot be completed, the machine **does not vend**, **does not take the money**, and **keeps every coin you have already inserted**. You can insert more coins, try another product, or type `RETURN-COIN`.

| Situation | Printed message | Inserted coins |
| --- | --- | --- |
| Selector is not in the catalog (for example `GET-TEA`) | `UNKNOWN SELECTION` | Unchanged |
| The product exists but quantity is 0 | `OUT OF STOCK` | Unchanged |
| Inserted total is less than the price | `INSUFFICIENT FUNDS` | Unchanged |
| The price is covered but the hopper cannot make exact change | `EXACT CHANGE UNAVAILABLE` | Unchanged |

`SERVICE` is also refused while coins are inserted (`ACTIVE CUSTOMER SESSION`). Those coins stay in the machine until you vend or return them.

### Spec examples

```text
1, GET-WATER
→ WATER, 0.25, 0.10

0.10, 0.10, RETURN-COIN
→ 0.10, 0.10

1, 0.25, 0.25, GET-SODA
→ SODA
```

### Service mode

`SERVICE` asks how many coins of each denomination to load, then how many of each catalog product to stock. Answers must be non-negative whole numbers.

```text
SERVICE
How many coins of 0.05 we will have?
3
How many coins of 0.10 we will have?
7
How many coins of 0.25 we will have?
7
How many coins of 1 we will have?
3
How many WATER products we will have?
5
How many JUICE products we will have?
8
How many SODA products we will have?
2
```

Pipe a line instead of typing:

```bash
echo '1, GET-WATER' | docker compose run --rm -T app
```

### Run the test suite

```bash
docker compose run --rm test
```

PHPUnit has two suites: `unit` (domain, application, CLI internals) and `acceptance` (customer and technician flows through `bin`-equivalent `run()`).

```bash
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit --testsuite acceptance
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
