<?php
use PHPUnit\Framework\TestCase;
use Roolith\Store\Drivers\PdoDriver;

class PdoDriverTest extends TestCase
{
    protected function sqliteDriver(): PdoDriver
    {
        $driver = new PdoDriver();
        $driver->connect(['type' => 'sqlite', 'name' => ':memory:']);
        $driver->execute("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT)");

        return $driver;
    }

    public function testShouldConnectViaStringDsn()
    {
        $driver = new PdoDriver();

        $this->assertTrue($driver->connect('sqlite::memory:'));
        $this->assertTrue($driver->disconnect());
    }

    public function testShouldRejectInvalidConfigType()
    {
        $driver = new PdoDriver();

        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $driver->connect(123);
    }

    public function testShouldRejectMissingSqliteName()
    {
        $driver = new PdoDriver();

        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $driver->connect(['type' => 'sqlite']);
    }

    public function testShouldRejectMissingKeys()
    {
        $driver = new PdoDriver();

        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $driver->connect(['type' => 'mysql', 'host' => 'localhost']);
    }

    public function testShouldRejectUnsupportedType()
    {
        $driver = new PdoDriver();

        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $driver->connect(['type' => 'oracle', 'host' => 'h', 'name' => 'n', 'user' => 'u', 'pass' => 'p']);
    }

    public function testShouldReturnFalseDisconnectWhenNotConnected()
    {
        $driver = new PdoDriver();

        $this->assertFalse($driver->disconnect());
    }

    public function testShouldResetAndClearWhereState()
    {
        $driver = $this->sqliteDriver();
        $driver->buildConditionQueryString(['name' => 'id', 'value' => 1]);

        $this->assertNotEmpty($driver->getWhereBindings());

        $driver->reset();
        $this->assertSame([], $driver->getWhereBindings());

        $driver->buildConditionQueryString(['name' => 'id', 'value' => 2]);
        $driver->resetConditionalQueryString();
        $this->assertSame([], $driver->getWhereBindings());
        $driver->disconnect();
    }

    public function testShouldBuildNullFragments()
    {
        $driver = new PdoDriver();

        [$frag] = $driver->buildConditionFragment(['name' => 'email', 'value' => null]);
        $this->assertStringContainsString('IS NULL', $frag);

        [$frag] = $driver->buildConditionFragment(['name' => 'email', 'value' => null, 'expression' => '!=']);
        $this->assertStringContainsString('IS NOT NULL', $frag);
    }

    public function testShouldBuildInFragment()
    {
        $driver = new PdoDriver();

        [$frag, $bindings] = $driver->buildConditionFragment(['name' => 'id', 'value' => [1, 2], 'expression' => 'IN']);
        $this->assertStringContainsString('IN', $frag);
        $this->assertCount(2, $bindings);
    }

    public function testShouldRejectEmptyInFragment()
    {
        $driver = new PdoDriver();

        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $driver->buildConditionFragment(['name' => 'id', 'value' => [], 'expression' => 'IN']);
    }

    public function testShouldRejectInvalidExpressionAndOperator()
    {
        $driver = new PdoDriver();

        try {
            $driver->buildConditionFragment(['name' => 'id', 'value' => 1, 'expression' => 'BETWEEN']);
            $this->fail('Invalid expression should throw.');
        } catch (\Roolith\Store\Exceptions\InvalidArgumentException) {
        }

        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $driver->buildConditionFragment(['name' => 'id', 'value' => 1, 'operator' => 'XOR']);
    }

    public function testShouldRejectInvalidIdentifier()
    {
        $driver = new PdoDriver();

        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $driver->buildConditionFragment(['name' => 'bad-col!', 'value' => 1]);
    }

    public function testShouldAccumulateOrCondition()
    {
        $driver = new PdoDriver();
        $driver->buildConditionQueryString(['name' => 'id', 'value' => 1]);
        $combined = $driver->buildConditionQueryString(['name' => 'id', 'value' => 2, 'operator' => 'OR']);

        $this->assertStringContainsString('OR', $combined);
        $driver->disconnect();
    }

    public function testShouldRejectInvalidConditionOperator()
    {
        $driver = new PdoDriver();

        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $driver->buildConditionQueryString(['name' => 'id', 'value' => 1, 'operator' => 'XOR']);
    }

    public function testShouldThrowOnInvalidSelectClauses()
    {
        $driver = $this->sqliteDriver();

        try {
            $driver->select('users', ['orderBy' => 'id; DROP']);
            $this->fail('Bad order should throw.');
        } catch (\Roolith\Store\Exceptions\InvalidArgumentException) {
        }

        try {
            $driver->select('users', ['limit' => 'DROP']);
            $this->fail('Bad limit should throw.');
        } catch (\Roolith\Store\Exceptions\InvalidArgumentException) {
        }

        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $driver->select('users', ['field' => ['bad-col!']]);
    }

    public function testShouldSupportSelectVariantsAndQuerySuffix()
    {
        $driver = $this->sqliteDriver();
        $driver->insert('users', ['name' => 'A', 'email' => 'a@test.com']);

        $all = $driver->select('users', []);
        $this->assertGreaterThanOrEqual(1, $all['total']);

        $suffix = $driver->getQuerySuffix('SELECT 1', 'id > 1', 5, 2);
        $this->assertSame('5 OFFSET 2', $suffix['limit']);
        $this->assertStringContainsString('LIMIT', $suffix['string']);
        $this->assertStringContainsString('WHERE', $suffix['string']);

        $driver->disconnect();
    }

    public function testShouldThrowOnBadQueryAndExecute()
    {
        $driver = $this->sqliteDriver();

        try {
            $driver->query('SELECT * FROM missing_table');
            $this->fail('Bad query should throw.');
        } catch (\Roolith\Store\Exceptions\Exception) {
        }

        $this->expectException(\Roolith\Store\Exceptions\Exception::class);
        $driver->execute('SELECT * FROM missing_table');
    }

    public function testShouldSupportQueryWithBindings()
    {
        $driver = $this->sqliteDriver();
        $driver->insert('users', ['name' => 'B', 'email' => 'b@test.com']);

        $result = $driver->query('SELECT * FROM users WHERE email = :email', PDO::FETCH_OBJ, [':email' => 'b@test.com']);
        $this->assertGreaterThanOrEqual(1, $result['total']);
        $driver->disconnect();
    }

    public function testShouldRejectUniqueMissingAndBadValues()
    {
        $driver = $this->sqliteDriver();

        try {
            $driver->insert('users', ['name' => 'X'], ['email']);
            $this->fail('Missing unique field should throw.');
        } catch (\Roolith\Store\Exceptions\InvalidArgumentException) {
        }

        try {
            $driver->insert('users', ['name' => ['array'], 'email' => 'x@test.com'], ['name']);
            $this->fail('Array unique value should throw.');
        } catch (\Roolith\Store\Exceptions\InvalidArgumentException) {
        }

        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $driver->update('users', ['name' => 'Y'], ['id' => new stdClass()]);
    }

    public function testShouldRejectEmptyInWhereArray()
    {
        $driver = $this->sqliteDriver();
        $driver->insert('users', ['name' => 'C', 'email' => 'c@test.com']);

        $this->expectException(\Roolith\Store\Exceptions\InvalidArgumentException::class);
        $driver->update('users', ['name' => 'D'], ['id' => []]);
    }

    public function testShouldSupportNullWhereMatch()
    {
        $driver = $this->sqliteDriver();
        $driver->insert('users', ['name' => 'NullGuy', 'email' => null]);

        $result = $driver->delete('users', ['email' => null]);
        $this->assertGreaterThanOrEqual(1, $result['data']['affectedRow']);
        $driver->disconnect();
    }

    public function testShouldTrackDebugLog()
    {
        $driver = $this->sqliteDriver();
        $driver->setDebugMode(true);
        $driver->clearDebugLog();
        $driver->query('SELECT 1');

        $this->assertNotEmpty($driver->getDebugLog());

        $driver->clearDebugLog();
        $this->assertSame([], $driver->getDebugLog());
        $driver->disconnect();
    }

    public function testShouldRejectStrayRollback()
    {
        $driver = $this->sqliteDriver();

        $this->expectException(\Roolith\Store\Exceptions\Exception::class);
        $driver->rollBack();
    }
}
