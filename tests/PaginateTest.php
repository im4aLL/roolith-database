<?php
use PHPUnit\Framework\TestCase;
use Roolith\Store\Paginate;

class PaginateTest extends TestCase
{
    protected $paginate;

    public function setUp(): void
    {
        $this->paginate = new Paginate([
            'perPage' => 5,
            'pageUrl' => 'http://example.com',
            'total' => 100,
        ]);
    }

    protected function getDummyItems()
    {
        return [
            ['id' => 1, 'name' => 'Hadi'],
            ['id' => 2, 'name' => 'John'],
        ];
    }

    public function testShouldGetCount()
    {
        $this->assertEquals(5, $this->paginate->count());
    }

    public function testShouldGetTotal()
    {
        $this->assertEquals(100, $this->paginate->total());
    }

    public function testShouldGetTotalPage()
    {
        $this->assertEquals(20, $this->paginate->totalPage());
    }

    public function testShouldGetCurrentPage()
    {
        $this->assertEquals(1, $this->paginate->currentPage());
    }

    public function testShouldGetFirstItem()
    {
        $this->paginate->setItems($this->getDummyItems());

        $this->assertEquals($this->getDummyItems()[0], $this->paginate->firstItem());
    }

    public function testShouldGetLastItem()
    {
        $this->paginate->setItems($this->getDummyItems());

        $this->assertEquals(array_values(array_slice($this->getDummyItems(), -1))[0], $this->paginate->lastItem());
    }

    public function testShouldGetItems()
    {
        $this->paginate->setItems($this->getDummyItems());

        $this->assertEquals($this->getDummyItems(), $this->paginate->items());
    }

    public function testShouldGetFirstPageUrl()
    {
        $this->assertEquals('http://example.com?page=1', $this->paginate->firstPageUrl());
    }

    public function testShouldGetLastPageUrl()
    {
        $this->assertEquals('http://example.com?page=20', $this->paginate->lastPageUrl());
    }

    public function testShouldGetNextPageUrl()
    {
        $this->assertEquals('http://example.com?page=2', $this->paginate->nextPageUrl());
    }

    public function testShouldGetPrevPageUrl()
    {
        $paginate = $this->getMockBuilder(Paginate::class)->setConstructorArgs([
            'perPage' => 5,
            'pageUrl' => 'http://example.com',
            'total' => 100,
        ])->onlyMethods(['currentPage', 'getCurrentPageUrl'])->getMock();
        $paginate->method('currentPage')->willReturn(5);

        $this->assertEquals('?page=4', $paginate->prevPageUrl());
    }

    public function testShouldGetPageNumbers()
    {
        $this->assertIsArray($this->paginate->pageNumbers());
    }

    public function testShouldGetLimit()
    {
        $this->assertEquals(5, $this->paginate->limit());
    }

    public function testShouldGetOffset()
    {
        $this->assertEquals(0, $this->paginate->offset());

        $paginate = $this->getMockBuilder(Paginate::class)->setConstructorArgs([
            'perPage' => 5,
            'pageUrl' => 'http://example.com',
            'total' => 100,
        ])->onlyMethods(['currentPage', 'getCurrentPageUrl'])->getMock();
        $paginate->method('currentPage')->willReturn(2);

        $this->assertEquals(20, $paginate->offset());
    }

    public function testShouldGetDetails()
    {
        $detailObject = $this->paginate->getDetails();

        $this->assertIsObject($detailObject);
        $this->assertTrue(property_exists($detailObject, 'data'));
    }

}