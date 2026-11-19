# Schema Migrator

A beautiful command-line database migration tool for any PHP project. Laravel-style migrations without the framework complexity.

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

## Features

✨ **Beautiful CLI Interface** - Powered by Symfony Console with colored output and interactive commands  
🚀 **Global Installation** - Install once, use anywhere in any PHP project  
🔧 **Zero Dependencies** - No need to install Laravel or any framework in your projects  
📁 **Project & Environment Config** - Support for default and custom config files (`--config` / `-c`)  
🗄️ **Multi-Database Support** - MySQL, PostgreSQL, SQLite, and SQL Server with driver-specific defaults  
🔄 **Transactional Migrations** - Driver-aware transaction support; atomic rollbacks on failure for PostgreSQL and SQL Server  
🔤 **Automatic PostgreSQL Collation** - Applies connection collation to string/text columns seamlessly  
⚙️ **Configurable Migration Storage** - Customizable migrations table name and migrations directory path  
⚡ **Laravel-Compatible** - Uses Laravel's proven migration syntax and features  

---

## Global Installation

Install Schema Migrator globally via Composer:

```bash
composer global require dconco/schema-migrator
```

Make sure your global Composer bin directory is in your PATH:

```bash
# Add to your ~/.bashrc or ~/.zshrc
export PATH="$PATH:$HOME/.composer/vendor/bin"
```

---

## Quick Start

### 1. Initialize in Your Project

Navigate to your PHP project and initialize Schema Migrator:

```bash
cd /path/to/your/project
schema-migrator init
```

This will:
- Create a `schema-migrator.yml` configuration file
- Create a `database/migrations` directory
- Prompt you for database connection details

You can also initialize with a custom configuration path:
```bash
schema-migrator init --config schema-migrator-postgres.yml
```

### 2. Create Your First Migration

```bash
schema-migrator make:migration CreateUsersTable
```

### 3. Edit the Migration

Edit the generated file in `database/migrations/`:

```php
<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

return new class extends Migration {
    public function up(): void
    {
        Capsule::schema()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('users');
    }
};
```

### 4. Run Migrations

```bash
schema-migrator migrate
```

---

## Commands

All commands accept the `-c` or `--config` option to specify a custom configuration file.

### Initialize Project
```bash
schema-migrator init
schema-migrator init -c config/database.yml
```
Creates configuration file and migrations directory in the current project.

### Create Migration
```bash
schema-migrator make:migration CreatePostsTable
schema-migrator make:migration AddEmailToUsersTable -c schema-migrator-postgres.yml
```
Creates a new timestamped migration file in the configured migrations directory.

### Run Migrations
```bash
schema-migrator migrate
schema-migrator migrate -c schema-migrator-postgres.yml
```
Executes all pending migrations in order. If supported by the database engine, each migration runs inside a transaction.

### Check Status
```bash
schema-migrator migrate:status
schema-migrator migrate:status -c schema-migrator-postgres.yml
```
Displays an overview table showing which migrations have been executed and which are pending.

### Rollback Migrations
```bash
schema-migrator migrate:rollback
schema-migrator migrate:rollback --steps=3
schema-migrator migrate:rollback -c schema-migrator-postgres.yml
```
Rolls back the last batch of migrations (or the specified number of batches).

---

## Configuration

By default, Schema Migrator looks for `schema-migrator.yml` in the current working directory. You can specify a different configuration using `--config` / `-c`.

### MySQL Configuration
```yaml
database:
  driver: mysql          # mysql, pgsql, sqlite, sqlsrv
  host: 127.0.0.1
  port: 3306
  database: your_database
  username: your_username
  password: your_password
  charset: utf8mb4
  collation: utf8mb4_unicode_ci
  prefix: ''
migrations:
  table: migrations
  path: database/migrations
```

### PostgreSQL Configuration
```yaml
database:
  driver: pgsql
  host: 127.0.0.1
  port: 5432
  database: your_database
  username: postgres
  password: your_password
  charset: UTF8
  collation: 'und-u-ks-level2'  # Automatically applied to string/text columns
  schema: public
  sslmode: prefer
  prefix: ''
migrations:
  table: schema_migrations      # Custom tracking table name
  path: database/migrations     # Custom migrations directory path
```

### Configuration Options Reference

| Section | Key | Default | Description |
| :--- | :--- | :--- | :--- |
| `database` | `driver` | `mysql` | Driver name: `mysql`, `pgsql`, `sqlite`, or `sqlsrv` |
| `database` | `host` | `127.0.0.1` | Database server hostname or IP |
| `database` | `port` | `3306` (`5432` for pgsql, `1433` for sqlsrv) | Database server port |
| `database` | `database` | `""` | Database name (or file path / `:memory:` for sqlite) |
| `database` | `username` | `""` | Database user |
| `database` | `password` | `""` | Database password |
| `database` | `charset` | `utf8mb4` (`utf8` for pgsql) | Character encoding |
| `database` | `collation` | `null` | Database/column collation |
| `database` | `schema` | `public` | Search path / schema (PostgreSQL) |
| `database` | `sslmode` | `prefer` | SSL mode (PostgreSQL) |
| `migrations` | `table` | `migrations` | Table used to track migration history |
| `migrations` | `path` | `database/migrations` | Path where migration files are stored (relative or absolute) |

---

## Transactional Migrations & Engine Support

### How Transactions Work
When running `migrate` or `migrate:rollback`:
- **PostgreSQL & SQL Server**: Natively support transactional DDL. Each migration's schema changes and its tracking record insertion/deletion are wrapped inside a database transaction. If an error occurs midway through `up()` or `down()`, all changes are rolled back automatically. This prevents half-applied migrations and avoids `"column already exists"` errors on retry.
- **MySQL / MariaDB**: Statements such as `ALTER TABLE` and `CREATE TABLE` trigger an **implicit commit** in MySQL, meaning DDL cannot be rolled back by transactions. Schema Migrator detects this and avoids rolling back non-transactional DDL to prevent `PDOException: There is no active transaction` errors.
- **Disabling Transactions per Migration**: If you have a migration that cannot run inside a transaction, set the `withinTransaction` property to `false`:
  ```php
  return new class extends Migration {
      public $withinTransaction = false;

      public function up(): void
      {
          // ...
      }
  };
  ```

---

## Multiple Environments & Custom Configs

You can maintain separate configuration files for different environments (local, staging, testing, CI/CD) or different database engines:

```bash
# Local MySQL development
schema-migrator migrate

# Staging PostgreSQL database
schema-migrator migrate --config config/schema-migrator-staging.yml

# Testing SQLite database
schema-migrator migrate -c schema-migrator.testing.yml
```

---

## Usage Examples

### Project Structure
```
your-project/
├── schema-migrator.yml
├── schema-migrator-postgres.yml   # Optional alternate config
├── database/
│   └── migrations/
│       ├── 2025_01_01_000000_create_users.php
│       ├── 2025_01_01_000001_create_posts.php
│       └── 2025_01_01_000002_add_category_to_posts.php
├── src/
└── composer.json
```

### Migration Examples

**Create a table:**
```php
Capsule::schema()->create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->foreignId('user_id')->constrained();
    $table->timestamps();
});
```

**Add columns:**
```php
Capsule::schema()->table('users', function (Blueprint $table) {
    $table->string('phone')->nullable();
    $table->boolean('is_active')->default(true);
});
```

**Create indexes:**
```php
Capsule::schema()->table('posts', function (Blueprint $table) {
    $table->index('title');
    $table->index(['user_id', 'created_at']);
});
```

---

## Requirements

- PHP 8.1 or higher
- PDO extension for your database (`pdo_mysql`, `pdo_pgsql`, `pdo_sqlite`, `pdo_sqlsrv`)
- Composer (for global installation)

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

---

**Now you have Laravel-style migrations in any PHP project!** 🎉
