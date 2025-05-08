<?php
ini_set('error_reporting', E_ALL);

// Setting up the time zone
date_default_timezone_set('America/Los_Angeles');


$dbhost = 'localhost';
$dbname = 'malousdb';
$dbuser = 'root';
$dbpass = '';
if (!defined('BASE_URL')) define('BASE_URL', '...');
if (!defined('ADMIN_URL')) define('ADMIN_URL', '...');

// define("BASE_URL", "");
// define("ADMIN_URL", BASE_URL . "admin" . "/");

try {
    $pdo = new PDO("mysql:host={$dbhost};dbname={$dbname}", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch( PDOException $exception ) {
    echo "Connection error :" . $exception->getMessage();
}