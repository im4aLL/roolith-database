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

    protected function getConfig(): array
    {
        return [
            'type' => 'sqlite',
            'name' => ':memory:',
        ];
    }

    public function setUp(): void
    {
        $this->db = new Database($this->getConfig());
        $this->db->execute("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT)");
        $this->db->table('users')->insert(['name' => 'John Doe', 'email' => 'john@email.com']);
        $this->db->table('users')->insert(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $this->db->table('users')->insert(['name' => 'Johnny Appleseed', 'email' => 'johnny@example.com']);
    }

    public function tearDown(): void
    {
        $this->db->disconnect();
    }

    public function testShouldConstructWithConfig()
    {
        $db = new Database($this->getConfig());

        $this->assertInstanceOf(Database::class, $db);
        $db->disconnect();
    }

    public function testShouldConstructWithoutConfig()
    {
        $this->db = new Database();

        $this->assertInstanceOf(Database::class, $this->db);
    }

    public function testShouldConnect()
    {
        $db = new Database();

        $result = $db->connect($this->getConfig());
        $this->assertTrue($result);
        $db->disconnect();
    }

    public function testShouldThrowOnInvalidConfig()
    {
        $db = new Database();

        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $db->connect(['type' => 'mysql', 'host' => 'localhost']);
    }

    public function testShouldDisconnect()
    {
        $db = new Database($this->getConfig());

        $this->assertTrue($db->disconnect());
        $this->assertFalse($db->disconnect());
    }

    public function testShouldRequireConnection()
    {
        $db = new Database();

        $this->expectException(Exception::class);
        $db->table('users')->where('id', 1)->get();
    }

    public function testShouldAllowRawQuery()
    {
        $result = $this->db->query("SELECT * FROM users")->get();

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
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

    public function testShouldSelectWithBoundRawCondition()
    {
        $result = $this->db->table('users')->select([
            'condition' => 'WHERE id > :min',
            'bindings' => [':min' => 0],
        ])->get();

        $this->assertCount(3, $result);
    }

    public function testShouldSelectWithStringField()
    {
        $result = $this->db->table('users')->select([
            'field' => 'name',
        ])->get();

        $this->assertIsArray($result);
        $this->assertIsString($result[0]->name);
    }

    public function testShouldNotOverwriteCallerCondition()
    {
        $result = $this->db->table('users')->select([
            'condition' => 'WHERE id > 0',
        ])->where('name', 'John Doe')->get();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('John Doe', $result[0]->name);
    }

    public function testShouldInsert()
    {
        $result = $this->db->table('users')->insert(
            ['name' => 'Habib Hadi', 'email' => 'me@habibhadi.com']
        );

        $this->assertInstanceOf(InsertResponseInterface::class, $result);
        $this->assertTrue($result->success());
    }

    public function testShouldInsertIfRecordNotExists()
    {
        $result = $this->db->table('users')->insert(
            ['name' => 'Unique Person', 'email' => 'unique@email.com']
        );

        $previousInsertId = $result->insertedId();

        $result = $this->db->table('users')->insert(
            ['name' => 'Unique Person', 'email' => 'unique@email.com'],
            ['email']
        );

        $this->assertFalse($result->success());
        $this->db->table('users')->delete(['id' => $previousInsertId]);
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
            ['name' => 'Hadi'],
            ['id' => 1],
            ['name']
        );

        $this->assertTrue($result->success());
    }

    public function testShouldDelete()
    {
        $inserted = $this->db->table('users')->insert(
            ['name' => 'To Delete', 'email' => 'delete@me.com']
        );

        $result = $this->db->table('users')->delete(['id' => $inserted->insertedId()]);

        $this->assertInstanceOf(DeleteResponseInterface::class, $result);
        $this->assertTrue($result->success());
    }

    public function testShouldGetResultBasedOnWhere()
    {
        $result = $this->db->table('users')->where('name', '%john%', 'LIKE')->get();
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result));

        $result = $this->db->table('users')->where('id', 1)->count();
        $this->assertEquals(1, $result);

        $result = $this->db->table('users')->where('id', 2)->orWhere('email', 'johnny@example.com')->count();
        $this->assertEquals(2, $result);
    }

    public function testShouldNotLeakWhereState()
    {
        $this->db->table('users')->where('id', 1)->get();

        $result = $this->db->table('users')->query("SELECT * FROM users")->get();

        $this->assertCount(3, $result);
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
            'total' => 3,
            'pageUrl' => 'http://localhost/roolith-database/demo',
            'currentPage' => 1,
        ]);
        $this->assertInstanceOf(PaginatorInterface::class, $result);
        $this->assertCount(1, $result->items());
    }

    public function testShouldPaginateWithSelectAndLimit()
    {
        $result = $this->db->table('users')->select(['field' => ['name']])->paginate([
            'perPage' => 2,
            'total' => 3,
            'pageUrl' => 'http://localhost/roolith-database/demo',
            'currentPage' => 1,
        ]);

        $this->assertInstanceOf(PaginatorInterface::class, $result);
        $this->assertCount(2, $result->items());
    }

    public function testShouldStoreInjectionAttemptLiterally()
    {
        $evil = "' OR '1'='1";
        $this->db->table('users')->insert(['name' => $evil, 'email' => 'evil@test.com']);

        $result = $this->db->table('users')->where('name', $evil)->get();
        $this->assertCount(1, $result);
        $this->assertEquals($evil, $result[0]->name);

        $all = $this->db->table('users')->query("SELECT * FROM users")->get();
        $this->assertCount(4, $all);
    }

    public function testShouldThrowOnBadSql()
    {
        $this->expectException(\Roolith\Store\Exceptions\Exception::class);
        $this->db->query("SELECT * FROM no_such_table")->get();
    }

    public function testShouldSupportBoundWhereOperatorStyle()
    {
        $result = $this->db->table('users')->where('id', '>', 0)->get();
        $this->assertCount(3, $result);

        $result = $this->db->table('users')->where('id', 1)->get();
        $this->assertCount(1, $result);
    }

    public function testShouldSupportOrderByLimitOffsetHelpers()
    {
        $result = $this->db->table('users')->orderBy('id', 'DESC')->limit(2)->get();
        $this->assertCount(2, $result);
        $this->assertGreaterThan($result[1]->id, $result[0]->id);

        $result = $this->db->table('users')->orderBy('id', 'ASC')->limit(1)->offset(1)->get();
        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]->id);
    }

    public function testShouldSupportOffsetWithoutLimit()
    {
        $result = $this->db->table('users')->orderBy('id', 'ASC')->offset(1)->get();
        $this->assertCount(2, $result);
        $this->assertEquals(2, $result[0]->id);
    }

    public function testShouldReturnEmptyPaginateWhenPerPageZero()
    {
        $result = $this->db->table('users')->paginate([
            'perPage' => 0,
            'total' => 3,
            'pageUrl' => 'http://localhost/roolith-database/demo',
            'currentPage' => 1,
        ]);

        $this->assertCount(0, $result->items());
    }

    public function testShouldNotEchoInDebugMode()
    {
        $this->db->clearDebugLog();
        $this->db->debugMode(true);
        $this->expectOutputString('');
        $this->db->table('users')->query("SELECT * FROM users")->get();
        $this->db->debugMode(false);

        $log = $this->db->getDebugLog();
        $this->assertNotEmpty($log);
        $this->assertStringContainsString('SELECT', $log[0]['query']);
    }

    public function testShouldCommitAndRollbackTransactions()
    {
        $this->db->transaction(function ($db) {
            $db->table('users')->insert(['name' => 'Tx Commit', 'email' => 'tx-commit@test.com']);
        });

        $result = $this->db->table('users')->where('email', 'tx-commit@test.com')->get();
        $this->assertCount(1, $result);

        try {
            $this->db->transaction(function ($db) {
                $db->table('users')->insert(['name' => 'Tx Rollback', 'email' => 'tx-rollback@test.com']);
                throw new \RuntimeException('force rollback');
            });
        } catch (\RuntimeException) {
        }

        $result = $this->db->table('users')->where('email', 'tx-rollback@test.com')->get();
        $this->assertCount(0, $result);
    }

    public function testShouldRejectNestedAndStrayTransactions()
    {
        $this->assertFalse($this->db->inTransaction());

        $this->expectException(\Roolith\Store\Exceptions\Exception::class);
        $this->db->commit();
    }

    public function testShouldRejectDoubleBegin()
    {
        $this->db->beginTransaction();
        $this->assertTrue($this->db->inTransaction());

        try {
            $this->db->beginTransaction();
            $this->fail('Nested begin should throw.');
        } catch (\Roolith\Store\Exceptions\Exception) {
        } finally {
            $this->db->rollBack();
        }

        $this->assertFalse($this->db->inTransaction());
    }

    public function testShouldPluckWithWhere()
    {
        $result = $this->db->table('users')->where('name', 'John Doe')->pluck(['name']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('John Doe', $result[0]->name);
    }

    public function testShouldRejectEmptyUpdateData()
    {
        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $this->db->table('users')->update([], ['id' => 1]);
    }

    public function testShouldRejectEmptyUpdateWhere()
    {
        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $this->db->table('users')->update(['name' => 'X'], []);
    }

    public function testShouldRejectEmptyInsertData()
    {
        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $this->db->table('users')->insert([]);
    }

    public function testShouldRejectInvalidOrderDirection()
    {
        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $this->db->table('users')->orderBy('id', 'SIDEWAYS')->get();
    }

    public function testShouldRejectNegativeLimitAndOffset()
    {
        try {
            $this->db->table('users')->limit(-1)->get();
            $this->fail('Negative limit should throw.');
        } catch (\Roolith\Store\Exceptions\InvalidArgumentException) {
        }

        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $this->db->table('users')->offset(-1)->get();
    }

    public function testShouldReturnFalseFirstWhenEmpty()
    {
        $result = $this->db->table('users')->where('id', 999999)->first();

        $this->assertFalse($result);
    }

    public function testShouldRequireTable()
    {
        $db = new Database($this->getConfig());

        $this->expectException(\Roolith\Store\Exceptions\Exception::class);
        $db->find(1);
    }

    public function testShouldRejectEmptyConfig()
    {
        $db = new Database();

        $this->expectException(\Roolith\Store\Exceptions\Exception::class);
        $db->connect([]);
    }

    public function testShouldRejectUnsupportedType()
    {
        $db = new Database();

        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $db->connect(['type' => 'mysqll', 'host' => 'h', 'name' => 'n', 'user' => 'u', 'pass' => 'p']);
    }

    public function testShouldSupportExecuteWithBindings()
    {
        $ok = $this->db->execute("INSERT INTO users (name, email) VALUES (:name, :email)", [
            ':name' => 'Bound Guy',
            ':email' => 'bound@test.com',
        ]);

        $this->assertTrue((bool) $ok);

        $result = $this->db->table('users')->where('email', 'bound@test.com')->get();
        $this->assertCount(1, $result);
    }

    public function testShouldResetState()
    {
        $this->db->table('users')->where('id', 1);
        $this->db->reset();

        $result = $this->db->table('users')->query("SELECT * FROM users")->get();
        $this->assertCount(3, $result);
    }

    public function testShouldReturnTransactionValue()
    {
        $value = $this->db->transaction(function () {
            return 42;
        });

        $this->assertEquals(42, $value);
    }

    public function testShouldClearDebugLog()
    {
        $this->db->debugMode(true);
        $this->db->table('users')->query("SELECT * FROM users")->get();
        $this->assertNotEmpty($this->db->getDebugLog());

        $this->db->clearDebugLog();
        $this->assertSame([], $this->db->getDebugLog());
        $this->db->debugMode(false);
    }

    public function testShouldSupportInConditionViaWhere()
    {
        $result = $this->db->table('users')->where('id', [1, 2])->get();

        $this->assertCount(2, $result);
    }

    public function testShouldSupportFieldAliasAndWildcard()
    {
        $aliased = $this->db->table('users')->select(['field' => ['name AS username']])->get();
        $this->assertEquals($aliased[0]->username, $aliased[0]->username);

        $wild = $this->db->table('users')->select(['field' => ['users.*']])->get();
        $this->assertIsObject($wild[0]);
    }

    public function testShouldThrowOnInvalidField()
    {
        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $this->db->table('users')->select(['field' => ['bad-col!']])->get();
    }

    public function testShouldThrowOnInvalidOrderClause()
    {
        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $this->db->table('users')->select(['orderBy' => 'id; DROP'])->get();
    }

    public function testShouldThrowOnInvalidLimitClause()
    {
        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $this->db->table('users')->select(['limit' => 'DROP'])->get();
    }

    public function testShouldReturnZeroDeleteOnEmptyWhere()
    {
        $result = $this->db->table('users')->delete([]);

        $this->assertSame(0, $result->affectedRow());
        $this->assertFalse($result->success());
    }

    public function testShouldAssertResponseValues()
    {
        $inserted = $this->db->table('users')->insert(['name' => 'Vals', 'email' => 'vals@test.com']);
        $this->assertGreaterThan(0, $inserted->insertedId());
        $this->assertGreaterThan(0, $inserted->affectedRow());
        $this->assertFalse($inserted->isDuplicate());
        $this->assertTrue($inserted->success());

        $dup = $this->db->table('users')->insert(
            ['name' => 'Vals', 'email' => 'vals@test.com'],
            ['email']
        );
        $this->assertTrue($dup->isDuplicate());
        $this->assertFalse($dup->success());

        $updated = $this->db->table('users')->update(['name' => 'Vals2'], ['id' => $inserted->insertedId()]);
        $this->assertFalse($updated->isDuplicate());
        $this->assertTrue($updated->success());

        $deleted = $this->db->table('users')->delete(['id' => $inserted->insertedId()]);
        $this->assertSame(1, $deleted->affectedRow());
        $this->assertTrue($deleted->success());
    }
}
