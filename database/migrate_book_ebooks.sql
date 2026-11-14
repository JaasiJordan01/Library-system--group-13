-- Run once on an existing library_db to enable PDF and EPUB reader uploads.
ALTER TABLE books
    ADD COLUMN digital_file_path VARCHAR(255) NULL AFTER cover_isbn;
