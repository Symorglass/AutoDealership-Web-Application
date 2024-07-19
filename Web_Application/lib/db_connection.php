<?php

define('DB_HOST', "localhost");
define('DB_PORT', "3306");
define('DB_USER', "adminUser");
define('DB_PASS', "password123456");
define('DB_SCHEMA', "AutoDealerDB");

$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_SCHEMA, DB_PORT);

if (mysqli_connect_errno())
{
    echo "Failed to connect to MySQL: " . mysqli_connect_error() . NEWLINE;
    echo "Running on: ". DB_HOST . ":". DB_PORT . '<br>' . "Username: " . DB_USER . '<br>' . "Password: " . DB_PASS . '<br>' ."Database: " . DB_SCHEMA;
    phpinfo();   //unsafe, but verbose for learning. 
    exit();
}

?>
