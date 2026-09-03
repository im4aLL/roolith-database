<?php
use PHPUnit\Framework\TestCase;
use Roolith\Store\Responses\DeleteResponse;
use Roolith\Store\Responses\InsertResponse;
use Roolith\Store\Responses\UpdateResponse;

class ResponsesTest extends TestCase
{
    public function testShouldHandleInsertDefaults()
    {
        $response = new InsertResponse();

        $this->assertSame(0, $response->affectedRow());
        $this->assertSame(0, $response->insertedId());
        $this->assertFalse($response->isDuplicate());
        $this->assertFalse($response->success());
    }

    public function testShouldHandleInsertSuccess()
    {
        $response = new InsertResponse(['affectedRow' => 1, 'insertedId' => 7, 'isDuplicate' => false]);

        $this->assertSame(1, $response->affectedRow());
        $this->assertSame(7, $response->insertedId());
        $this->assertTrue($response->success());
    }

    public function testShouldHandleInsertDuplicate()
    {
        $response = new InsertResponse(['affectedRow' => 0, 'insertedId' => 7, 'isDuplicate' => true]);

        $this->assertTrue($response->isDuplicate());
        $this->assertFalse($response->success());
    }

    public function testShouldHandleUpdateDefaultsAndSuccess()
    {
        $empty = new UpdateResponse();

        $this->assertSame(0, $empty->affectedRow());
        $this->assertFalse($empty->isDuplicate());
        $this->assertTrue($empty->success());

        $dup = new UpdateResponse(['affectedRow' => 0, 'isDuplicate' => true]);
        $this->assertFalse($dup->success());
    }

    public function testShouldHandleDeleteDefaultsAndSuccess()
    {
        $empty = new DeleteResponse();

        $this->assertSame(0, $empty->affectedRow());
        $this->assertFalse($empty->success());

        $deleted = new DeleteResponse(['affectedRow' => 2]);
        $this->assertSame(2, $deleted->affectedRow());
        $this->assertTrue($deleted->success());
    }
}
