<?php
/**
 * app_header.php — Legacy compatibility shim
 * The application now uses config.php for all database and session setup.
 * This file is kept for backward compatibility only.
 */
require_once __DIR__ . '/config.php';

// Legacy variable $pdo for any old scripts still using it
$pdo = getPDO();
