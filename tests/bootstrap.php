<?php

require_once __DIR__ . '/../vendor/autoload.php';

class_alias('App\Database', 'database');

if (getenv('VISITORPASS_DB_PASS') === false || getenv('VISITORPASS_DB_PASS') === '') {
    putenv('VISITORPASS_DB_PASS=teste-local');
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../inc/validators.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/security.php';
require_once __DIR__ . '/../inc/login_security.php';
require_once __DIR__ . '/../inc/single_session.php';
require_once __DIR__ . '/../inc/foto_helpers.php';
