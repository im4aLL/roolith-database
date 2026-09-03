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
        $paginate = $this->getMockBuilder(Paginate::class)->setConstructorArgs([[
            'perPage' => 5,
            'pageUrl' => 'http://example.com',
            'total' => 100,
        ]])->onlyMethods(['currentPage'])->getMock();
        $paginate->method('currentPage')->willReturn(5);

        $this->assertEquals('http://example.com?page=4', $paginate->prevPageUrl());
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

        $paginate = $this->getMockBuilder(Paginate::class)->setConstructorArgs([[
            'perPage' => 5,
            'pageUrl' => 'http://example.com',
            'total' => 100,
        ]])->onlyMethods(['currentPage'])->getMock();
        $paginate->method('currentPage')->willReturn(2);

        $this->assertEquals(5, $paginate->offset());
    }

    public function testShouldGetDetails()
    {
        $detailObject = $this->paginate->getDetails();

        $this->assertIsObject($detailObject);
        $this->assertTrue(property_exists($detailObject, 'data'));
    }

    public function testShouldBuildFromRequestWithoutSuperglobals()
    {
        $paginate = Paginate::fromRequest(
            ['perPage' => 5, 'total' => 100],
            ['REQUEST_URI' => '/users?foo=1'],
            ['page' => 3],
        );

        $this->assertEquals('/users', $paginate->firstPageUrl() ? parse_url($paginate->firstPageUrl(), PHP_URL_PATH) : '/users');
        $this->assertEquals(3, $paginate->currentPage());
        $this->assertEquals(10, $paginate->offset());
    }

    public function testShouldUseEllipsisStringAndCoverLastPage()
    {
        $numbers = $this->paginate->pageNumbers();
        $this->assertContains('...', $numbers);
        $this->assertNotContains('.', $numbers);

        foreach ($numbers as $entry) {
            $this->assertTrue(is_int($entry) || $entry === '...');
        }

        $last = Paginate::fromRequest(
            ['perPage' => 5, 'total' => 100],
            ['REQUEST_URI' => '/'],
            ['page' => 20],
        );

        $this->assertNotEmpty($last->pageNumbers());
        $this->assertContains(20, $last->pageNumbers());
    }

    public function testShouldGuardPerPageZero()
    {
        $paginate = new Paginate([
            'perPage' => 0,
            'pageUrl' => 'http://example.com',
            'total' => 100,
        ]);

        $this->assertEquals(0, $paginate->totalPage());
        $this->assertEquals(0, $paginate->offset());
    }

    public function testShouldClampDetailsPastLastPage()
    {
        $paginate = Paginate::fromRequest(
            ['perPage' => 5, 'total' => 100],
            ['REQUEST_URI' => '/users'],
            ['page' => 100],
        );

        $details = $paginate->getDetails();
        $this->assertEquals(0, $details->from);
        $this->assertEquals(0, $details->to);
    }

    public function testShouldPreserveQueryParamsMinusPageParam()
    {
        $paginate = Paginate::fromRequest(
            ['perPage' => 5, 'total' => 100],
            ['REQUEST_URI' => '/users?foo=1&page=2&bar=3'],
            ['page' => 3],
        );

        $firstUrl = $paginate->firstPageUrl();
        $this->assertStringContainsString('foo=1', $firstUrl);
        $this->assertStringContainsString('bar=3', $firstUrl);
        $this->assertStringContainsString('page=1', $firstUrl);
        $this->assertEquals(1, substr_count($firstUrl, 'page='));
    }
}