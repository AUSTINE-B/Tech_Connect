# TechConnect MVP foundation

This project contains a PHP backend for XAMPP Apache/PHP and its MySQL-compatible database (MariaDB in many XAMPP releases).

## Included

- `SRS.md` - implementation software requirements specification.
- `backend/public/index.php` - PHP 8.0+ REST API and frontend file handler using PDO MySQL.
- `backend/config.local.example.php` - example local database configuration.
- `backend/bin/create_user.php` - command-line bootstrap for staff/admin accounts. Admins can also add technicians and maintain services, categories, and estimate ranges from the dashboard.
- `database/mysql/techconnect.sql` - MySQL/MariaDB schema and example catalog records.
- `frontend/` - responsive customer and staff portal.
- `docs/openapi.yaml` - REST API contract.
- `docs/IMPLEMENTATION_STATUS.md` - current scope and remaining production work.

## Set up with XAMPP on Windows

1. Start **Apache** and **MySQL** from XAMPP Control Panel. Apache must have `mod_rewrite`, PDO, and `pdo_mysql` enabled; these are enabled in a standard XAMPP installation.
2. Copy the project folder into `C:\xampp\htdocs\astra` (or use `htdocs` itself). If using a subfolder, the project supports it.
3. In phpMyAdmin (`http://localhost/phpmyadmin`), create a database named `techconnect` with `utf8mb4_unicode_ci`, select it, and import `database/mysql/techconnect.sql`. For the complete client service catalog, import `database/mysql/002_catalog_expansion.sql` after the base schema, then import `database/mysql/003_admin_categories.sql` to enable dashboard category management. Existing databases can add the Yaoundé service area by importing `database/mysql/004_yaounde_service_zone.sql`. Existing installations should apply only migrations they have not already run; do not re-import the initial schema into a working database.
4. Copy `backend/config.local.example.php` to `backend/config.local.php` and set your local database credentials. Keep `dev_payments` false except when using the local demonstration flow. `config.local.php` is ignored by Git.
5. Open `http://localhost/astra/` (or `http://localhost/` if installed directly at `htdocs`). Do not open `frontend/index.html` directly or use VS Code Live Server: those only serve static files and cannot execute the PHP API.

The PHP API and staff account CLI use PDO MySQL and default to the `techconnect` database. The backend does not create the database or apply schema migrations automatically; import `database/mysql/techconnect.sql` first.

### Create demo staff accounts

From PowerShell in the project folder:

```powershell
& C:\xampp\php\php.exe backend\bin\create_user.php --email=admin@example.test --name="Demo Admin" --role=admin
& C:\xampp\php\php.exe backend\bin\create_user.php --email=tech@example.test --name="Demo Technician" --role=technician --trades=plumbing,electrical,appliance --zones=DOUALA_BONAMOUSSADI,DOUALA_AKWA
& C:\xampp\php\php.exe backend\bin\create_user.php --email=dispatch@example.test --name="Demo Dispatcher" --role=dispatcher
```

Each command prompts for a password (minimum 12 characters). Create a customer account through the registration page, then sign in with staff accounts to dispatch and handle service requests. Use synthetic data only. On a local demo installation, the customer can use “Simulate successful payment · demo” after creating a pending payment. This advances the request to ready-to-work without contacting a payment network or moving money; the technician must still perform and submit the work, and the customer confirms completion.

## Production boundary

This is not a production deployment. Production requires reviewed MySQL/MariaDB migrations, a separate least-privilege database account, field encryption and key management, private object storage, staff MFA, rate limiting, security review, notification workers, monitoring, tested backup/restore, and a contracted payment adapter with server-side verification and settlement reconciliation. Payroll/tax/CNPS calculations, employee/customer terms, privacy obligations, and launch prices require qualified local review. The original `ASTRA-SRS.pdf` and historical project review refer to an earlier PostgreSQL proposal; this README, `SRS.md`, and the MySQL schema describe the current PHP/XAMPP implementation.
