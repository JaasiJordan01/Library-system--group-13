<?php
class GoogleBooksApiException extends RuntimeException {
}

function google_books_api_request($resource, array $parameters = []) {
    $apiKey = getenv('GOOGLE_BOOKS_API_KEY');
    if (!is_string($apiKey) || trim($apiKey) === '') {
        throw new GoogleBooksApiException(
            'Google Books is not configured. Set GOOGLE_BOOKS_API_KEY in the Apache environment and restart Apache.'
        );
    }

    $resource = trim($resource, '/');
    if (!preg_match('~\Avolumes(?:/[A-Za-z0-9_-]{1,128})?\z~', $resource)) {
        throw new GoogleBooksApiException('The requested Google Books resource is invalid.');
    }

    $parameters['key'] = trim($apiKey);
    $url = 'https://www.googleapis.com/books/v1/' . $resource
        . '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    $handle = curl_init($url);
    if ($handle === false) {
        throw new GoogleBooksApiException('Google Books could not be contacted.');
    }

    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'LibrarySystem/1.0',
    ]);

    $response = curl_exec($handle);
    $curlError = $response === false ? curl_error($handle) : '';
    $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);

    if ($response === false) {
        throw new GoogleBooksApiException(
            $curlError !== '' ? 'Google Books request failed: ' . $curlError : 'Google Books request failed.'
        );
    }
    if ($status === 429) {
        throw new GoogleBooksApiException('Google Books rate limit reached. Check the API key quota and try again later.');
    }
    if ($status === 403 || $status === 401) {
        throw new GoogleBooksApiException('Google Books rejected the API key. Check that the Books API is enabled and the key is valid.');
    }
    if ($status === 404) {
        throw new GoogleBooksApiException('Google Books could not find that volume.');
    }
    if ($status < 200 || $status >= 300) {
        throw new GoogleBooksApiException('Google Books returned an unexpected HTTP status (' . $status . ').');
    }

    try {
        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new GoogleBooksApiException('Google Books returned invalid JSON.', 0, $exception);
    }

    if (!is_array($data)) {
        throw new GoogleBooksApiException('Google Books returned an unexpected response.');
    }

    return $data;
}

function google_books_extract_metadata(array $volume) {
    $info = isset($volume['volumeInfo']) && is_array($volume['volumeInfo'])
        ? $volume['volumeInfo']
        : [];
    $isbn = null;
    $identifiers = isset($info['industryIdentifiers']) && is_array($info['industryIdentifiers'])
        ? $info['industryIdentifiers']
        : [];

    foreach (['ISBN_13', 'ISBN_10'] as $preferredType) {
        foreach ($identifiers as $identifier) {
            if (!is_array($identifier) || ($identifier['type'] ?? '') !== $preferredType) {
                continue;
            }
            $candidate = strtoupper(preg_replace('/[\s-]+/', '', (string)($identifier['identifier'] ?? '')));
            if (($preferredType === 'ISBN_13' && preg_match('/\A[0-9]{13}\z/', $candidate))
                || ($preferredType === 'ISBN_10' && preg_match('/\A[0-9]{9}[0-9X]\z/', $candidate))) {
                $isbn = $candidate;
                break 2;
            }
        }
    }

    $authors = isset($info['authors']) && is_array($info['authors'])
        ? array_values(array_filter(array_map(
            static function ($author) {
                return is_string($author) ? trim($author) : '';
            },
            $info['authors']
        )))
        : [];
    $imageLinks = isset($info['imageLinks']) && is_array($info['imageLinks'])
        ? $info['imageLinks']
        : [];
    $coverUrl = $imageLinks['thumbnail'] ?? '';
    if (!is_string($coverUrl) || !preg_match('~\Ahttps?://~i', $coverUrl)) {
        $coverUrl = '';
    } else {
        $coverUrl = preg_replace('~\Ahttp://~i', 'https://', $coverUrl);
    }
    $previewUrl = $info['previewLink'] ?? '';
    if (!is_string($previewUrl) || !preg_match('~\Ahttps?://~i', $previewUrl)) {
        $previewUrl = '';
    }

    $publishedYear = null;
    $publishedDate = $info['publishedDate'] ?? '';
    if (is_string($publishedDate) && preg_match('/\A([0-9]{4})/', $publishedDate, $yearMatch)) {
        $year = (int)$yearMatch[1];
        if ($year >= 1000 && $year <= 2155) {
            $publishedYear = $year;
        }
    }

    return [
        'id' => is_string($volume['id'] ?? null) ? $volume['id'] : '',
        'title' => is_string($info['title'] ?? null) ? trim($info['title']) : '',
        'authors' => $authors,
        'publisher' => is_string($info['publisher'] ?? null) ? trim($info['publisher']) : '',
        'published_year' => $publishedYear,
        'isbn' => $isbn,
        'cover_url' => $coverUrl,
        'preview_url' => $previewUrl,
    ];
}
