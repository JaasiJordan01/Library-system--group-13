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

## Student member access

For an existing database, run `database/migrate_member_login.sql` once to add
the nullable password-hash column required for member sign-in. Fresh installs
already include this column in `database/library_db.sql`.

Administrators and librarians can add or edit a member and optionally set or
reset that member's student portal password. Students sign in with their member
number or email and the password provided by library staff; there is no public
self-registration. Students can browse the catalog, view only their own loans
and reservations, and reserve unavailable books. Staff sign in with their
existing staff username and password.
