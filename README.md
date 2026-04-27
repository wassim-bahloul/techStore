# TechStore (magasin)

Small PHP + MySQL store app intended to run locally on XAMPP.

## Requirements

- XAMPP (Apache + MySQL/MariaDB)
- PHP (via XAMPP)

## Project structure

- `index.php` — main entry page
- `config.php` — configuration (DB + store settings)
- `db.sql` — database schema + seed data
- `uploads/` — uploaded files
- `images/` — static images
- `app.js` — front-end JavaScript

## Setup (local, XAMPP)

1. Put the project inside your XAMPP web root.

   Windows default:
   - `C:\xampp\htdocs\magasin`

2. Start **Apache** and **MySQL** from the XAMPP Control Panel.

3. Import the database.

   The provided `db.sql` creates/uses the database named `magasin_info`.

   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Go to **Import**
   - Select `db.sql`
   - Click **Go**

4. Verify the DB configuration in `config.php`.

   Defaults:
   - Host: `localhost`
   - Port: `3307`
   - User: `root`
   - Password: *(empty)*
   - Database: `magasin_info`

   If your local MySQL runs on port `3306`, update `DB_PORT` accordingly.

5. Ensure uploads work.

   The app will attempt to create `uploads/` automatically; if uploads fail, make sure the web server has write permissions to `uploads/`.

## Run

Open in browser:

- http://localhost/magasin/

## Notes

- Store settings (name, email, shipping fee) are in `config.php`.
- Uploaded files are stored in `uploads/`.

## Troubleshooting

- **Blank page / 500 error**: check Apache logs in XAMPP and temporarily enable PHP error display.
- **DB connection error**: confirm MySQL is running and the credentials/port in `config.php` match your setup.
- **Import fails**: ensure you have permission to create the database `magasin_info`.
