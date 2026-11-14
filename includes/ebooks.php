<?php
define('EBOOK_MAX_FILE_SIZE', 30 * 1024 * 1024);

function ebook_storage_directory() {
    $configuredPath = getenv('LIBRARY_EBOOK_STORAGE');
    if (is_string($configuredPath) && trim($configuredPath) !== '') {
        return rtrim($configuredPath, '/\\');
    }

    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $storageParent = $documentRoot !== ''
        ? dirname(rtrim($documentRoot, '/\\'))
        : dirname(__DIR__, 3);

    return $storageParent . DIRECTORY_SEPARATOR . 'library-system-private'
        . DIRECTORY_SEPARATOR . 'ebooks';
}

function ebook_resolve_path($storedPath) {
    if (!is_string($storedPath)
        || !preg_match('/\A[a-f0-9]{32}\.(?:pdf|epub)\z/', $storedPath)) {
        return null;
    }

    $storageDirectory = realpath(ebook_storage_directory());
    if ($storageDirectory === false) {
        return null;
    }

    $filePath = realpath($storageDirectory . DIRECTORY_SEPARATOR . $storedPath);
    if ($filePath === false || !is_file($filePath)) {
        return null;
    }

    $sameDirectory = DIRECTORY_SEPARATOR === '\\'
        ? strcasecmp(dirname($filePath), $storageDirectory) === 0
        : dirname($filePath) === $storageDirectory;

    return $sameDirectory ? $filePath : null;
}

function ebook_validate_epub($filePath) {
    if (!class_exists('ZipArchive')) {
        return 'EPUB uploads require the PHP ZIP extension. Enable extension=zip in php.ini and restart Apache.';
    }

    $archive = new ZipArchive();
    if ($archive->open($filePath, ZipArchive::CHECKCONS) !== true) {
        return 'This file is not a valid EPUB archive.';
    }

    $valid = $archive->numFiles > 1
        && $archive->getNameIndex(0) === 'mimetype'
        && $archive->getFromIndex(0) === 'application/epub+zip';
    $firstEntry = $archive->statIndex(0);
    $valid = $valid
        && is_array($firstEntry)
        && $firstEntry['comp_method'] === ZipArchive::CM_STORE
        && $archive->locateName('META-INF/container.xml') !== false;

    $totalUncompressedBytes = 0;
    if ($valid && $archive->numFiles <= 10000) {
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $entry = $archive->statIndex($index);
            if (!is_array($entry)) {
                $valid = false;
                break;
            }

            $totalUncompressedBytes += (int)$entry['size'];
            if ($totalUncompressedBytes > 100 * 1024 * 1024) {
                $valid = false;
                break;
            }
        }
    } else {
        $valid = false;
    }

    if ($valid) {
        $container = $archive->getFromName('META-INF/container.xml');
        $valid = is_string($container)
            && preg_match(
                '/<rootfile\b[^>]*\bfull-path\s*=\s*(["\'])(.*?)\1/i',
                $container,
                $matches
            ) === 1;

        if ($valid) {
            $packagePath = html_entity_decode($matches[2], ENT_QUOTES | ENT_XML1, 'UTF-8');
            $valid = $packagePath !== ''
                && $packagePath[0] !== '/'
                && preg_match('/(?:\A|\/)\.\.(?:\/|\z)|\\\\/', $packagePath) !== 1
                && $archive->locateName($packagePath) !== false;
        }
    }

    $archive->close();
    return $valid ? '' : 'This file does not contain a valid EPUB book package.';
}

function ebook_validate_upload($upload, &$error) {
    if (!is_array($upload)
        || !isset($upload['error'], $upload['name'], $upload['tmp_name'])
        || !is_int($upload['error'])
        || !is_string($upload['name'])
        || !is_string($upload['tmp_name'])) {
        $error = 'Choose a PDF or EPUB file to upload.';
        return null;
    }

    if ($upload['error'] !== UPLOAD_ERR_OK) {
        $error = $upload['error'] === UPLOAD_ERR_INI_SIZE
            || $upload['error'] === UPLOAD_ERR_FORM_SIZE
            ? 'The upload exceeds the server limit. Choose a smaller file.'
            : ($upload['error'] === UPLOAD_ERR_NO_FILE
                ? 'Choose a PDF or EPUB file to upload.'
                : 'The ebook upload did not complete. Please try again.');
        return null;
    }

    $extension = strtolower(pathinfo(basename($upload['name']), PATHINFO_EXTENSION));
    if (!in_array($extension, ['pdf', 'epub'], true)) {
        $error = 'Only PDF and EPUB files are supported.';
        return null;
    }

    if (!is_uploaded_file($upload['tmp_name'])) {
        $error = 'The uploaded file could not be verified.';
        return null;
    }

    $fileSize = filesize($upload['tmp_name']);
    if ($fileSize === false || $fileSize < 1 || $fileSize > EBOOK_MAX_FILE_SIZE) {
        $error = 'Choose a non-empty ebook no larger than 30 MB.';
        return null;
    }

    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($fileInfo === false) {
        $error = 'The server cannot verify uploaded file types. Contact the administrator.';
        return null;
    }

    $mimeType = finfo_file($fileInfo, $upload['tmp_name']);
    finfo_close($fileInfo);

    if ($extension === 'pdf') {
        $handle = fopen($upload['tmp_name'], 'rb');
        $signature = $handle === false ? false : fread($handle, 5);
        if (is_resource($handle)) {
            fclose($handle);
        }
        if ($mimeType !== 'application/pdf' || $signature !== '%PDF-') {
            $error = 'The selected file is not a valid PDF.';
            return null;
        }
    } else {
        $handle = fopen($upload['tmp_name'], 'rb');
        $signature = $handle === false ? false : fread($handle, 4);
        if (is_resource($handle)) {
            fclose($handle);
        }
        if ($signature !== "PK\x03\x04") {
            $error = 'The selected file is not a valid EPUB archive.';
            return null;
        }

        $epubError = ebook_validate_epub($upload['tmp_name']);
        if ($epubError !== '') {
            $error = $epubError;
            return null;
        }
    }

    return $extension;
}
