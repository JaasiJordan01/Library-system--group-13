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
reset that member's student portal password.

The sign-in page also offers public student/member registration for
demonstrations. New registrations receive a generated member number, are
activated immediately, and are signed in automatically. Public registration
cannot create librarian or administrator accounts. Students can browse the
catalog, view only their own loans and reservations, and reserve unavailable
books. Staff sign in with their existing staff username and password.

For a production deployment, replace immediate public activation with an
appropriate verification or approval process.

## Google Books catalogue

The Books page includes a Google Books search. Administrators and librarians
can choose a local category and import a result with an ISBN; each imported
title starts with one available copy. Its first listed author is matched or
created in the local authors table. Students can browse Google Books results
but cannot import them. The existing database schema is reused, and API
requests are made server-side so the key is not sent to browsers.

Google requires an API key for public Books API requests. Enable the Google
Books API in Google Cloud, create an API key, and set it as the
`GOOGLE_BOOKS_API_KEY` environment variable available to Apache. On Windows,
you can add this user environment variable through **Edit environment
variables for your account**, then fully restart the XAMPP Control Panel and
Apache. Do not put the key in the repository. The Google Books search tab
displays a configuration or quota message if the key is missing or the API
limit is reached.

See the [Google Books API documentation](https://developers.google.com/books/docs/v1/using)
for API-key setup and usage details.
