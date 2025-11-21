<?php
declare(strict_types=1);

// Simple bootstrap: sessions, autoload, config, db
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';


