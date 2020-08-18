<?php
require __DIR__ .'/../vendor/autoload.php';

function dd($d) {
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

$driver = new \Roolith\Drivers\PdoDriver();
$database = new \Roolith\Database($driver);

try {
    $database->connect([
        'host' => 'localhost',
        'name' => 'support',
        'user' => 'root',
        'pass' => '',
    ]);
} catch (\Roolith\Exceptions\Exception $e) {
    echo $e->getMessage();
}

$database->query("SELECT * FROM users")->get();