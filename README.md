# Library System - Group 13

A PHP and MySQL library management application for books, authors, categories,
members, loans, reservations, and fines.

## Run locally with XAMPP

1. Place this project at `C:\xampp\htdocs\Library-system`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Import `database/library_db.sql` using phpMyAdmin. This creates the
   `library_db` database and sample records.
4. If your MySQL credentials differ from the XAMPP defaults, update
   `config/database.php`.
5. Open `http://localhost/Library-system/`.

The sample sign-in is `admin` / `password123`. Change the password before
using the application outside a local demo environment.

Book covers are loaded from the Open Library Covers API using each book's cover
ISBN, or its ISBN when no separate cover ISBN is set.
