# Laravel-Style Migrations for Any PHP Project

This setup allows you to use **Laravel-style migrations** in your existing plain PHP project — without installing the entire Laravel framework.

---

## **Installation**

1. **Install Dependencies**

```bash
composer require --dev illuminate/database illuminate/events
```

2. **Project Structure**

```
project-root/
├── bootstrap/
│   └── db.php
├── database/
│   └── migrations/
├── migrate (CLI script)
├── vendor/
└── composer.json
```

3. **Configure Database**
   Edit `bootstrap/db.php`:

```php
<?php
use Illuminate\Database\Capsule\Manager as Capsule;

require __DIR__ . '/../vendor/autoload.php';

$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql', // or pgsql/sqlite/sqlsrv
    'host'      => '127.0.0.1',
    'database'  => 'your_db',
    'username'  => 'your_user',
    'password'  => 'your_pass',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();
```

4. **Make `migrate` Script Executable**

```bash
chmod +x migrate
```

---

## **Usage**

### **1. Create a New Migration**

```bash
./migrate make CreateUsersTable
```

This will create a file in `database/migrations` like:

```
2025_08_09_123456_CreateUsersTable.php
```

### **2. Edit the Migration**

Example:

```php
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up() {
        Capsule::schema()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    public function down() {
        Capsule::schema()->dropIfExists('users');
    }
};
```

### **3. Run Migrations**

```bash
./migrate migrate
```

Applies all migrations that haven’t been run yet.

### **4. Rollback Migrations**

```bash
./migrate rollback
```

Reverts the migrations in reverse order.

---

## **Notes**

* Migrations are timestamped for proper ordering.
* This is **not** a full Laravel install — only the DB + schema builder.
* Works with MySQL, PostgreSQL, SQLite, and SQL Server.
* Commit migration files to version control so you can rebuild your database anytime.

---

## **Example Commands**

```bash
./migrate make AddPostsTable
./migrate migrate
./migrate rollback
```

---

**Now you have Laravel migrations in any PHP project!** 🎉
