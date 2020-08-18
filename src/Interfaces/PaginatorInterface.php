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
     * @return iterable
     */
    public function items();

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
}