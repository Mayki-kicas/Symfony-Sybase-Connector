# sybase-connector/doctrine-bundle

Symfony bundle providing Doctrine DBAL integration for **SAP SQL Anywhere** via **PDO_ODBC + FreeTDS**.

---

## How it works (end to end)

### The problem

SAP SQL Anywhere is not MySQL or PostgreSQL — Doctrine doesn't support it natively. Key differences:

- SQL Anywhere uses `SELECT TOP N START AT M` instead of `LIMIT/OFFSET`
- Different types: `BIT` instead of `BOOLEAN`, `MONEY`, `LONG VARCHAR`, etc.
- Connection goes through ODBC via FreeTDS (a C library that speaks the TDS protocol)
- FreeTDS quirks: no server-side prepared statements, only one active cursor per connection

### The connection stack

```
Your PHP code (Symfony / Doctrine)
        |
This bundle (SybaseDriver -> SybaseConnection -> SQLAnywherePlatform)
        |
PDO_ODBC (PHP extension)
        |
unixODBC (system ODBC driver manager)
        |
FreeTDS (ODBC driver speaking TDS 5.0 protocol)
        | TCP
SAP SQL Anywhere (port 2639)
```

### Bundle components

| File | Role |
|------|------|
| `SybaseDriver` | Creates the PDO_ODBC connection, builds the DSN, retries on transient network errors |
| `SybaseConnection` | PDO wrapper that auto-closes the previous cursor before each query (FreeTDS "Invalid cursor state" bug) |
| `SQLAnywherePlatform` | Translates Doctrine SQL to SQL Anywhere dialect (`TOP/START AT`, `CURRENT DATE`, `BIT`, `ISNULL()`, types...) |
| `SQLAnywhereSchemaManager` | Schema introspection via system views (`sys.systable`, `sys.systabcol`, etc.) |
| `SybaseConnectorExtension` | Auto-configures a Doctrine DBAL connection via `PrependExtensionInterface` |

---

## Installation

### System prerequisites (FreeTDS + ODBC)

**Option A: Docker (recommended)** — everything included, nothing to install:

```bash
docker compose build
docker compose run --rm php composer install
```

**Option B: macOS (Homebrew)**:

```bash
brew install freetds unixodbc

# Register the FreeTDS driver in ODBC
cat >> $(odbcinst -j | grep DRIVERS | awk '{print $2}') <<'EOF'
[FreeTDS]
Description=FreeTDS ODBC Driver
Driver=/opt/homebrew/lib/libtdsodbc.so
Setup=/opt/homebrew/lib/libtdsodbc.so
UsageCount=1
EOF
```

**Option C: Linux (Debian/Ubuntu)**:

```bash
apt-get install unixodbc tdsodbc freetds-dev
# The driver registers itself automatically
```

### Install the bundle

```bash
composer require sybase-connector/doctrine-bundle
```

### Configure

`.env`:
```
SYBASE_DSN=odbc:Driver=FreeTDS;Server=your-server.example.com;Port=2639;Database=YOUR_DB;TDS_Version=5.0
SYBASE_USER=your_user
SYBASE_PASSWORD=your_password
```

`config/packages/sybase_connector.yaml`:
```yaml
sybase_connector:
    dsn: '%env(SYBASE_DSN)%'
    user: '%env(SYBASE_USER)%'
    password: '%env(SYBASE_PASSWORD)%'
```

That's it. The bundle auto-configures a Doctrine DBAL connection named `sqlanywhere` (customizable via `connection_name`).

### Usage

**DBAL query (raw SQL)**:

```php
// In a controller or service
public function __construct(
    #[Autowire(service: 'doctrine.dbal.sqlanywhere_connection')]
    private readonly Connection $connection,
) {}

public function searchCustomers(string $name): array
{
    return $this->connection->executeQuery(
        "SELECT TOP 50 CustomerID, FullName FROM customer WHERE FullName LIKE ?",
        ['%' . $name . '%']
    )->fetchAllAssociative();
}
```

**With Doctrine ORM** (define your own entities):

```php
// config/packages/doctrine.yaml
doctrine:
    orm:
        entity_managers:
            sqlanywhere:
                connection: sqlanywhere
                mappings:
                    App:
                        type: attribute
                        dir: '%kernel.project_dir%/src/Entity/SQLAnywhere'
                        prefix: App\Entity\SQLAnywhere
```

---

## Quick testing

### Without Symfony (standalone script)

```bash
# Configure .env with your credentials, then:
php bin/test-query.php                    # Top 10 customers
php bin/test-query.php "%SMITH%"          # Search by name
php bin/test-query.php "SELECT TOP 5 * FROM your_table"  # Raw SQL
```

### Demo page (browser)

```bash
# Start the built-in PHP server
docker compose run --rm -p 8080:8080 php php -S 0.0.0.0:8080 -t demo/public

# Or locally if FreeTDS is installed:
php -S localhost:8080 -t demo/public
```

Open http://localhost:8080 — type a name — results come from your SQL Anywhere database.

### Automated tests

```bash
# Unit tests (no database needed)
./vendor/bin/phpunit --testsuite=unit

# Integration tests (requires .env configured)
./vendor/bin/phpunit --testsuite=integration
```

