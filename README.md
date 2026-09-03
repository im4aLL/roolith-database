# roolith-database
PHP database driver

#### Install
```text
composer require roolith/database
```

#### Usage
```php
use Roolith\Store\Database;

$db = new Database();
$db->connect([
    'host' => 'host',
    'name' => 'dbname',
    'user' => 'username',
    'pass' => 'password',
]);

// Get all users
$users = $db->query("SELECT * FROM users")->get();
print_r($users);

// Get all usernames
$usernames = $db->table('users')->select([
    'field' => 'name',
])->get();
print_r($usernames);

// Disconnect
$db->disconnect();
```

##### Select
```php
$db->query("SELECT * FROM users")->get();
```

```php
$db->table('users')->select([
    'field' => ['name', 'email'],
    'condition' => 'WHERE id > :min',
    'bindings' => [':min' => 0],
    'limit' => '0, 10',
    'orderBy' => 'name',
    'groupBy' => 'name',
])->get();
```
Note: `condition` is a trusted SQL literal escape hatch. Never interpolate input into it, pass variables via `bindings`.

##### Insert
```php
$result = $db->table('users')->insert(
    ['name' => 'Brannon Bruen', 'email' => 'bschmeler@pacocha.net']
);

print_r($result->success());
```

Insert data when supplied email `john@email.com` not exists in table `users`:
```php
$result = $db->table('users')->insert(
    ['name' => 'John doe', 'email' => 'john@email.com'],
    ['email']
);
```

###### Response:
```php
$result->affectedRow();
$result->insertedId();
$result->isDuplicate();
$result->success();
```

##### Update
```php
$result = $db->table('users')->update(
    ['name' => 'Habib Hadi', 'email' => 'john@email.com'],
    ['id' => 1]
);
```
Note: array `where` only. Raw string where is unsupported to prevent injection.

update username if nobody else is using same username

```php
$result = $db->table('users')->update(
    ['username' => 'johndoe'],
    ['id' => 4],
    ['username']
);
```

###### Response:
```php
$result->affectedRow();
$result->isDuplicate();
$result->success();
```

##### Delete
```php
$result = $db->table('users')->delete(['id' => 4]);
```

###### Response:
```php
$result->affectedRow();
$result->success();
```

##### Connect
```php
$db = new Database();
$db->connect([
    'host' => 'host',
    'name' => 'dbname',
    'user' => 'username',
    'pass' => 'password',
]);
```
or
```php
$db = new Database([
   'host' => 'host',
   'name' => 'dbname',
   'user' => 'username',
   'pass' => 'password',
]);
```

##### Disconnect
```php
$db->disconnect();
```

##### Others
Search users table with `LIKE` operator
```php
$db->table('users')->where('name', '%Hadi%', 'LIKE')->get();
// new bound style also works
$db->table('users')->where('age', '>', 18)->get();
$db->table('users')->orderBy('id', 'DESC')->limit(10)->offset(5)->get();
```

Get user by id 1
```php
$db->table('users')->find(1);
```

Pluck name and email from users table
```php
$db->table('users')->pluck(['name', 'email']);
```

Get total record of users table
```php
$db->query("SELECT id FROM users")->count();
```

##### Pagination
```php
$total = $db->query("SELECT id FROM users")->count();
$result = $db->query("SELECT * FROM users")->paginate([
    'perPage' => 5,
    'pageUrl' => 'http://domain.com',
    'primaryColumn' => 'id',
    'pageParam' => 'page',
    'total' => $total,
]);
```
or 
```php
$total = $db->query("SELECT id FROM users")->count();
$result = $db->query("SELECT * FROM users")->paginate([
    'perPage' => 5, // default 20
    'total' => $total,
]);
```

CLI / test safe pagination without `$_GET` / `$_SERVER`:
```php
use Roolith\Store\Paginate;

$paginate = Paginate::fromRequest(
    ['perPage' => 5, 'total' => $total],
    ['REQUEST_URI' => '/users'],
    ['page' => 2],
);
```

##### Transactions
```php
$db->transaction(function ($db) {
    $db->table('users')->insert(['name' => 'A', 'email' => 'a@test.com']);
});
// or manual
$db->beginTransaction();
$db->commit(); // $db->rollBack();
```

##### Bindings
Values are always bound, never interpolated:
```php
$db->query("SELECT * FROM users WHERE email = :email", null, [':email' => $email])->get();
$db->execute("DELETE FROM users WHERE id = :id", [':id' => $id]);
```

```php
print_r($result->getDetails());
```

```text
{
    "total": 50,
    "perPage": 15,
    "currentPage": 1,
    "lastPage": 4,
    "firstPageUrl": "http://domain.com?page=1",
    "lastPageUrl": "http://domain.com?page=4",
    "nextPageUrl": "http://domain.com?page=2",
    "prevPageUrl": null,
    "path": "http://domain.com",
    "from": 1,
    "to": 15,
    "data":[
        // records
    ]
}
```

##### Debug mode
```php
$db->debugMode()->table('users')->find(1);
print_r($db->getDebugLog());
```
Note: Once debug-mode is active queries are collected via `getDebugLog()` with no echo output!

#### Upgrade to 2.0
Breaking: `update()` requires array `where` (string where removed), `delete()` return shape drops `debug` key, `pageNumbers()` ellipsis is `'...'` (was `'.'`), `new Paginate` no longer reads `$_GET`/`$_SERVER` (use `Paginate::fromGlobals()` for legacy web or `Paginate::fromRequest()`), new required interface methods (`buildConditionFragment`, transactions, debug log, `orderBy`/`limit`/`offset`), requires `php >= 8.0`.
Notes: `getDetails()` returns `from=0,to=0` past the last page, `fromRequest()` preserves query params minus `pageParam`, transactions reject nesting and stray `commit`/`rollBack` (check `inTransaction()`).

#### Development

```
PHPUnit 9.3.7 by Sebastian Bergmann and contributors.

Database
 ✔ Should construct with config
 ✔ Should construct without config
 ✔ Should connect
 ✔ Should disconnect
 ✔ Should allow raw query
 ✔ Should return first result
 ✔ Should select
 ✔ Should insert
 ✔ Should insert if record not exists
 ✔ Should update
 ✔ Should update if record not exists
 ✔ Should delete
 ✔ Should get result based on where
 ✔ Should get result by find
 ✔ Should pluck by field name
 ✔ Should paginate

Paginate
 ✔ Should get count
 ✔ Should get total
 ✔ Should get total page
 ✔ Should get current page
 ✔ Should get first item
 ✔ Should get last item
 ✔ Should get items
 ✔ Should get first page url
 ✔ Should get last page url
 ✔ Should get next page url
 ✔ Should get prev page url
 ✔ Should get page numbers
 ✔ Should get limit
 ✔ Should get offset
 ✔ Should get details

Time: 00:00.144, Memory: 6.00 MB

OK (31 tests, 41 assertions)
```