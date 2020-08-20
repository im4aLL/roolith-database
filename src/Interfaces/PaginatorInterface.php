<?php
namespace Roolith\Interfaces;


interface PaginatorInterface
{
    /**
     * Get current page item count
     *
     * @return int
     */
    public function count();

    /**
     * Get total record count
     *
     * @return int
     */
    public function total();

    /**
     * Get total page count
     *
     * @return int
     */
    public function totalPage();

    /**
     * Current page number
     *
     * @return int
     */
    public function currentPage();

    /**
     * Whether has more pages
     *
     * @return bool
     */
    public function hasPages();

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
    public function items();

    /**
     * Set items
     *
     * @param array $items
     * @return $this
     */
    public function setItems($items);

    /**
     * First page url
     *
     * @return string
     */
    public function firstPageUrl();

    /**
     * Last page url
     *
     * @return string
     */
    public function lastPageUrl();

    /**
     * Next page url
     *
     * @return string
     */
    public function nextPageUrl();

    /**
     * Previous page url
     *
     * @return string
     */
    public function prevPageUrl();

    /**
     * Limit pagination number
     * < 1 | 2 ... 37 | 38 | 39 | 40 | 41 | 42 ... 82 | 83 >
     *
     * @param int $limit
     * @return array
     */
    public function pageNumbers($limit = 15);

    /**
     * Get limit
     *
     * @return int
     */
    public function limit();

    /**
     * Get offset
     *
     * @return int
     */
    public function offset();

    /**
     * @return int
     */
    public function getFirstPageNumber();

    /**
     * @return int
     */
    public function getLastPageNumber();

    /**
     * @return int
     */
    public function getNextPageNumber();

    /**
     * @return int
     */
    public function getPrevPageNumber();

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
    public function getDetails();
}