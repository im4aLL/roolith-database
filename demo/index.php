<?php
use Roolith\Store\Database;

require __DIR__ .'/../vendor/autoload.php';

function dd($d) {
    echo '<pre>';
    print_r($d);
    echo '</pre>';
}

$db = new Database();

$db->connect([
    'host' => 'localhost',
    'name' => 'roolith_database',
    'user' => 'root',
    'pass' => '',
]);

$result = $db->query(" SELECT * FROM users")->get();
dd($result);

//$result = $db->debugMode()->table('users')->select([
//    'field' => ['name', 'email'],
//    'condition' => 'WHERE id > 0',
//    'limit' => '0, 10',
//    'orderBy' => 'name',
//    'groupBy' => 'name',
//])->first();
//
//dd($result);

//$result = $db->table('users')->insert(
//    ['name' => 'Brannon Bruen', 'email' => 'bschmeler@pacocha.net'],
//    ['email']
//);
//dd($result);

//$result = $db->table('users')->update(
//    ['name' => 'Habib Hadi', 'email' => 'john@email.com'],
//    ['id' => 1],
//    ['name']
//);
//
//dd($result->success() ? 'true' : 'false');

//$result = $db->table('users')->delete(['id' => 3]);
//dd($result);

//$result = $db->debugMode()->table('users')->where('name', '%Hadi%', 'LIKE')->get();
//dd($result);

//$result = $db->debugMode()->table('users')->find(1);
//dd($result);

//$result = $db->debugMode()->table('users')->pluck(['name', 'email']);
//dd($result);

//$total = $db->query("SELECT id FROM users")->count();
//$result = $db->debugMode()->query("SELECT id, name FROM users")->paginate([
//    'perPage' => 5,
//    'pageUrl' => 'http://localhost/roolith-database/demo',
//    'primaryColumn' => 'id',
//    'pageParam' => 'page',
//    'total' => $total,
//]);
//dd($result);

//$total = $db->query("SELECT id FROM users")->count();
//$result = $db->debugMode()->table('users')->select([
//    'field' => ['id', 'name']
//])->paginate([
//    'perPage' => 1,
//    'pageUrl' => 'http://localhost/roolith-database/demo',
//    'primaryColumn' => 'id',
//    'pageParam' => 'page',
//    'total' => $total,
//]);
//dd($result->getDetails());
//dd($result->pageNumbers());

$db->disconnect();
