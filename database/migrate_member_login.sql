-- Run once on an existing library_db to enable student/member sign-in.
ALTER TABLE members
    ADD COLUMN password_hash VARCHAR(255) NULL AFTER email;
