<?php
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function show_flash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        echo '<div class="alert alert-' . e($f['type']) . '">' . e($f['message']) . '</div>';
        unset($_SESSION['flash']);
    }
}

function validate_required($fields) {
    $errors = [];
    foreach ($fields as $name => $value) {
        if (trim($value) === '') {
            $errors[] = ucfirst($name) . ' is required.';
        }
    }
    return $errors;
}

function text_length($value) {
    $matched = preg_match_all('/./us', (string)$value, $characters);
    return $matched === false ? strlen((string)$value) : $matched;
}

function role_label($role) {
    if ($role === 'admin') {
        return 'Administrator';
    }
    if ($role === 'librarian') {
        return 'Librarian';
    }
    if ($role === 'student') {
        return 'Student member';
    }
    return 'Library user';
}

function csrf_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf_token($token) {
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}