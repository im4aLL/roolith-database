<?php
use PHPUnit\Framework\TestCase;
use Roolith\Store\Database;
use Roolith\Store\Interfaces\DeleteResponseInterface;
use Roolith\Store\Interfaces\InsertResponseInterface;
use Roolith\Store\Interfaces\PaginatorInterface;
use Roolith\Store\Interfaces\UpdateResponseInterface;

class DatabaseTest extends TestCase
{
    protected $db;
    protected $config = [
        'host' => 'localhost',
        'name' => 'roolith_database',
        'user' => 'root',
        'pass' => 'hadi',
    ];

    public function setUp(): void
    {
        $this->db = new Database($this->config);
    }

    public function tearDown(): void
    {
        $this->db->disconnect();
    }

    protected function connect()
    {
        $this->db = new Database($this->config);
    }

    public function testShouldConstructWithConfig()
    {
        $this->db = new Database($this->config);

        $this->assertInstanceOf(Database::class, $this->db);
    }

    public function testShouldConstructWithoutConfig()
    {
        $this->db = new Database();

        $this->assertInstanceOf(Database::class, $this->db);
    }

    public function testShouldConnect()
    {
        $this->db = new Database();

        $result = $this->db->connect($this->config);
        $this->assertTrue($result);

        $config = $this->config;
        $config['pass'] = 'something_else';
        $result = $this->db->connect($config);
        $this->assertFalse($result);
    }

    public function testShouldDisconnect()
    {
        $this->db = new Database($this->config);

        $result = $this->db->disconnect();

        $this->assertTrue($result);
    }

    public function testShouldAllowRawQuery()
    {
        $result = $this->db->query("SELECT * FROM users")->get();

        $this->assertIsArray($result);
    }

    public function testShouldReturnFirstResult()
    {
        $result = $this->db->query("SELECT * FROM users")->first();

        $this->assertIsObject($result);
    }

    public function testShouldSelect()
    {
        $result = $this->db->table('users')->select([
            'field' => ['name', 'email'],
            'condition' => 'WHERE id > 0',
            'limit' => '0, 10',
            'orderBy' => 'name',
            'groupBy' => 'name',
        ])->get();

        $this->assertIsArray($result);
    }

    public function testShouldInsert()
    {
        $result = $this->db->table('users')->insert(
            ['name' => 'Habib Hadi', 'email' => 'me@habibhadi.com']
        );

        $this->assertInstanceOf(InsertResponseInterface::class, $result);
        $this->db->delete(['id' => $result->insertedId()]);
    }

    public function testShouldInsertIfRecordNotExists()
    {
        $result = $this->db->table('users')->insert(
            ['name' => 'Habib Hadi', 'email' => 'me@habibhadi.com']
        );

        $previousInsertId = $result->insertedId();

        $result = $this->db->table('users')->insert(
            ['name' => 'Habib Hadi', 'email' => 'me@habibhadi.com'],
            ['email']
        );

        $this->assertFalse($result->success());
        $this->db->delete(['id' => $previousInsertId]);
    }

    public function testShouldUpdate()
    {
        $result = $this->db->table('users')->update(
            ['name' => 'Habib Hadi', 'email' => 'john@email.com'],
            ['id' => 1]
        );

        $this->assertInstanceOf(UpdateResponseInterface::class, $result);
    }

    public function testShouldUpdateIfRecordNotExists()
    {
        $result = $this->db->table('users')->update(
            ['name' => 'john'],
            ['id' => 1],
            ['name']
        );

        $this->assertFalse($result->success());

        $result = $this->db->table('users')->update(
            ['name' => 'Hadi'],
            ['id' => 1],
            ['name']
        );

        $this->assertTrue($result->success());
    }

    public function testShouldDelete()
    {
        $result = $this->db->table('users')->delete(['id' => 102]);

        $this->assertInstanceOf(DeleteResponseInterface::class, $result);
    }

    public function testShouldGetResultBasedOnWhere()
    {
        $result = $this->db->table('users')->where('name', '%john%', 'LIKE')->get();
        $this->assertIsArray($result);

        $result = $this->db->table('users')->where('id', 1)->count();
        $this->assertEquals(1, $result);

        $result = $this->db->table('users')->where('id', 2)->orWhere('email', 'hailie41@yahoo.com')->count();
        $this->assertEquals(2, $result);
    }

    public function testShouldGetResultByFind()
    {
        $result = $this->db->table('users')->find(1);
        $this->assertIsObject($result);

        $result = $this->db->table('users')->find(100000);
        $this->assertFalse($result);
    }

    public function testShouldPluckByFieldName()
    {
        $result = $this->db->table('users')->pluck(['name', 'email']);

        $this->assertIsArray($result);
        $this->assertIsString($result[0]->name);
        $this->assertIsString($result[0]->email);
        $this->assertFalse(isset($result[0]->id));
    }

    public function testShouldPaginate()
    {
        $result = $this->db->table('users')->paginate([
            'perPage' => 1,
            'total' => 100,
            'pageUrl' => 'http://localhost/roolith-database/demo'
        ]);
        $this->assertInstanceOf(PaginatorInterface::class, $result);
    }
}