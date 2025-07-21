<?php
namespace Roolith\Store\Interfaces;


interface PaginatorInterface
{
    /**
     * Get current page item count
     *
     * @return int
     */
    public function count(): int;

    /**
     * Get total record count
     *
     * @return int
     */
    public function total(): int;

    /**
     * Get total page count
     *
     * @return int
     */
    public function totalPage(): int;

    /**
     * Current page number
     *
     * @return int
     */
    public function currentPage(): int;

    /**
     * Whether has more pages
     *
     * @return bool
     */
    public function hasPages(): bool;

    /**
     * Get first record
     *
     * @return mixed
     */
    public function firstItem();

    /**
     * Get last record
     *
     * @return mixed
     */
    public function lastItem();

    /**
     * Get records
     *
     * @return array
     */
    public function items(): array;

    /**
     * Set items
     *
     * @param array $items
     * @return $this
     */
    public function setItems(array $items): PaginatorInterface;

    /**
     * First page url
     *
     * @return string
     */
    public function firstPageUrl(): string;

    /**
     * Last page url
     *
     * @return string
     */
    public function lastPageUrl(): string;

    /**
     * Next page url
     *
     * @return string
     */
    public function nextPageUrl(): string;

    /**
     * Previous page url
     *
     * @return string
     */
    public function prevPageUrl(): string;

    /**
     * Limit pagination number
     * < 1 | 2 ... 37 | 38 | 39 | 40 | 41 | 42 ... 82 | 83 >
     *
     * @param int $limit
     * @return array
     */
    public function pageNumbers(int $limit = 15): array;

    /**
     * Get limit
     *
     * @return int
     */
    public function limit(): int;

    /**
     * Get offset
     *
     * @return int
     */
    public function offset(): int;

    /**
     * @return int
     */
    public function getFirstPageNumber(): int;

    /**
     * @return int
     */
    public function getLastPageNumber(): int;

    /**
     * @return int
     */
    public function getNextPageNumber(): int;

    /**
     * @return int
     */
    public function getPrevPageNumber(): int;

    /**
     * Get details
     *
     * @return object
     * {
        "total": 50,
        "perPage": 15,
        "currentPage": 1,
        "lastPage": 4,
        "firstPageUrl": "http://example.com?page=1",
        "lastPageUrl": "http://example.com?page=4",
        "nextPageUrl": "http://example.com?page=2",
        "prevPageUrl": null,
        "path": "http://example.com",
        "from": 1,
        "to": 15,
        "data":[
            // records
        ]
     }
     */
    public function getDetails(): object;
}