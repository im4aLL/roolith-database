<?php
use Roolith\Database;

require __DIR__ .'/../vendor/autoload.php';

function dd($d) {
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

$db = new Database([
    'host' => 'localhost',
    'name' => 'support',
    'user' => 'root',
    'pass' => '',
]);

//try {
//    $db->connect([
//        'host' => 'localhost',
//        'name' => 'localflix',
//        'user' => 'root',
//        'pass' => '',
//    ]);
//} catch (\Roolith\Exceptions\Exception $e) {
//    echo $e->getMessage();
//}

//$result = $db->query("SELECT * FROM users")->get();
//dd($result);

//$result = $db->table('users')->select([
//    'field' => ['name', 'email'],
//    'condition' => 'WHERE id > 0',
//    'limit' => '0, 10',
//    'orderBy' => 'name',
//    'groupBy' => 'name',
//])->first();
//
//dd($result);
//dd($db->debug());

//$result = $db->table('users')->insert(
//    ['name' => 'Habib Hadi', 'email' => 'john4@email.com'],
//    ['name']
//);
//dd($result);

//$result = $db->table('users')->update(
//    ['name' => 'Habib Hadi', 'email' => 'john@email.com'],
//    ['id' => 3],
//    ['name']
//);
//
//dd($result);

//$result = $db->table('users')->delete(['id' => 3]);
//dd($result);

//$result = $db->debugMode()->table('users')->where('name', '%Hadi%', 'LIKE')->get();
//dd($result);

//$result = $db->debugMode()->table('users')->find(1);
//dd($result);

//$result = $db->debugMode()->table('users')->pluck(['name', 'email']);
//dd($result);

$result = $db->debugMode()->query("SELECT * FROM users")->paginate(1);
dd($result);

$db->disconnect();
