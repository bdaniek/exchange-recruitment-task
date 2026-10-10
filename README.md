# Exchange

## About

Exchange is a currency exchange platform where clients can hold accounts with **multi-currency wallets** and convert
funds between supported currencies.

The company earns revenue on every exchange through a **spread** — the difference between the market rate and the rate
offered to the client. The spread is calculated dynamically based on the liquidity of the traded currency pair:

- A **base spread of 0.5%** is applied to every exchange.
- Each currency has a **liquidity score** (USD = 1.00 being the most liquid, HUF = 0.40 the least). Less liquid pairs
  receive a higher spread, because they carry more risk and are harder to hedge.
- The formula: `spread = price × (0.5% ÷ average pair liquidity)`

**Example:** exchanging PLN (liquidity 0.55) to HUF (liquidity 0.40) gives an average pair liquidity of 0.475, so the
spread applied is `0.5% ÷ 0.475 ≈ 1.05%` of the transaction value — compared to only `0.5% ÷ 0.975 ≈ 0.51%` for a
USD/EUR pair.

Earnings across all wallets are tracked via the `app:company-wallet` console command.

---

## Prerequisites

Make sure you have [Docker](https://www.docker.com/get-started) and [Docker Compose](https://docs.docker.com/compose/)
installed on your machine.

> **Warning:** Check that you don't have any other services running on port **80** before starting the containers.

---

## Getting started

### 1. Start the containers

Run this command from the project root directory:

```bash
docker compose up -d
```

### 2. Install dependencies

Once the containers are up, install PHP dependencies inside the container:

```bash
docker exec -it php-fpm composer install
```

### 3. Run database migrations

Apply the database schema:

```bash
docker exec -it php-fpm php bin/console doctrine:migrations:migrate
```

### 4. Open the application

The application is available at:

```
http://localhost
```

---

## Running commands inside the PHP container

All PHP/Symfony commands must be run inside the `php-fpm` container. The general pattern is:

```bash
docker exec -it php-fpm <command>
```

**Example** — running tests:

```bash
docker exec -it php-fpm composer tests
```

---

## API Endpoints

All endpoints require Bearer token authentication. Obtain a token with `app:create-user`.

| Method | Path                        | Description                                                                                                                                                                            |
|--------|-----------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `GET`  | `/api/wallets`              | List all wallets belonging to the authenticated user.                                                                                                                                  |
| `POST` | `/api/wallets`              | Create a new wallet. Body: `{ "currency": "PLN" }`. Supported currencies: `PLN`, `EUR`, `USD`, `GBP`, `JPY`, `CHF`, `HUF`. Returns `409` if a wallet for that currency already exists. |
| `POST` | `/api/wallets/{id}/deposit` | Deposit funds into a wallet. Body: `{ "amount": "500.00" }`. Maximum single deposit: `10000`. Returns `422` if the wallet is blocked.                                                  |
| `POST` | `/api/wallets/transfer`     | Transfer funds between two wallets of the authenticated user (currency exchange supported). Body: `{ "fromWalletId": 1, "toWalletId": 2, "amount": "100.00" }`.                        |

A ready-to-use Postman collection is available at [`exchange-api.postman_collection.json`](./exchange-api.postman_collection.json).
Set the `authToken` variable to the token returned by `app:create-user`.

---

## Available console commands

| Command                    | Description                                                                                       |
|----------------------------|---------------------------------------------------------------------------------------------------|
| `app:create-user`          | Creates a user and returns an API token. Use this token when testing endpoints (e.g. in Postman). |
| `app:process-transactions` | Processes pending transactions — either approves or rejects them.                                 |
| `app:company-wallet`       | Displays the company wallets and shows how much the company has earned.                           |

**How to run a console command:**

```bash
docker exec -it php-fpm php bin/console <command-name>
```

**Example:**

```bash
docker exec -it php-fpm php bin/console app:create-user
```
