# Schema Migrator

A beautiful command-line database migration tool for any PHP project. Laravel-style migrations without the framework complexity.

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

## Features

✨ **Beautiful CLI Interface** - Powered by Symfony Console with colored output and interactive commands  
🚀 **Global Installation** - Install once, use anywhere in any PHP project  
🔧 **Zero Dependencies** - No need to install Laravel or any framework in your projects  
📁 **Project-Based Config** - Each project maintains its own configuration and migrations  
🗄️ **Multi-Database Support** - MySQL, PostgreSQL, SQLite, and SQL Server  
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

### Initialize Project
```bash
schema-migrator init
```
Creates configuration and migrations directory in current project.

### Create Migration
```bash
schema-migrator make:migration CreatePostsTable
schema-migrator make:migration AddEmailToUsersTable
```
Creates a new migration file with timestamp.

### Run Migrations
```bash
schema-migrator migrate
```
Executes all pending migrations.

### Check Status
```bash
schema-migrator migrate:status
```
Shows which migrations have been executed and which are pending.

### Rollback Migrations
```bash
schema-migrator migrate:rollback
schema-migrator migrate:rollback --steps=3
```
Rolls back the last batch of migrations (or specified number of batches).

---

## Configuration

The `schema-migrator.yml` file contains your project's configuration:

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

---

## Usage Examples

### Project Structure
```
your-project/
├── schema-migrator.yml
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

## Multiple Projects

Schema Migrator works per-project. You can use it in multiple projects simultaneously:

```bash
cd /project-a
schema-migrator init      # Configure for project A
schema-migrator migrate

cd /project-b  
schema-migrator init      # Configure for project B  
schema-migrator migrate
```

Each project maintains its own configuration and migration state.

---

## Requirements

- PHP 8.1 or higher
- PDO extension for your database
- Composer (for global installation)

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

---

**Now you have Laravel-style migrations in any PHP project!** 🎉