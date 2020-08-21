<?php
namespace Roolith;

use Roolith\Interfaces\PaginatorInterface;

class Paginate implements PaginatorInterface
{
    protected $perPage;
    protected $pageUrl;
    protected $primaryColumn;
    protected $pageParam;
    protected $total;
    protected $items;

    public function __construct($param)
    {
        $this->perPage = isset($param['perPage']) ? $param['perPage'] : 20;
        $this->pageUrl = isset($param['pageUrl']) ? $param['pageUrl'] : $this->getCurrentPageUrl();
        $this->primaryColumn = isset($param['primaryColumn']) ? $param['primaryColumn'] : 'id';
        $this->pageParam = isset($param['pageParam']) ? $param['pageParam'] : 'page';
        $this->total = isset($param['total']) ? $param['total'] : 0;
    }

    /**
     * Get current page url
     *
     * @return string
     */
    protected function getCurrentPageUrl()
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return $this->perPage;
    }

    /**
     * @inheritDoc
     */
    public function total()
    {
        return $this->total;
    }

    /**
     * @inheritDoc
     */
    public function totalPage()
    {
        return ceil($this->total() / $this->count());
    }

    /**
     * @inheritDoc
     */
    public function currentPage()
    {
        $currentPageNumber = isset($_GET[$this->pageParam]) ? intval($_GET[$this->pageParam]) : 1;

        return $currentPageNumber === 0 ? 1 : $currentPageNumber;
    }

    /**
     * @inheritDoc
     */
    public function hasPages()
    {
        return $this->currentPage() < $this->totalPage();
    }

    /**
     * @inheritDoc
     */
    public function firstItem()
    {
        if (count($this->items()) > 0) {
            return $this->items()[0];
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function lastItem()
    {
        $length = count($this->items());

        if ($length > 0) {
            return $this->items()[$length - 1];
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function items()
    {
        return $this->items;
    }

    /**
     * @inheritDoc
     */
    public function setItems($items)
    {
        $this->items = $items;

        return $this;
    }

    protected function getPageUrlWithParam()
    {
        return $this->pageUrl . '?' . $this->pageParam . '=';
    }

    /**
     * @inheritDoc
     */
    public function firstPageUrl()
    {
        return $this->getPageUrlWithParam() . $this->getFirstPageNumber();
    }

    /**
     * @inheritDoc
     */
    public function lastPageUrl()
    {
        return $this->getPageUrlWithParam() . $this->getLastPageNumber();
    }

    /**
     * @inheritDoc
     */
    public function nextPageUrl()
    {
        return $this->getPageUrlWithParam() . $this->getNextPageNumber();
    }

    /**
     * @inheritDoc
     */
    public function prevPageUrl()
    {
        return $this->getPageUrlWithParam() . $this->getPrevPageNumber();
    }

    /**
     * @inheritDoc
     */
    public function pageNumbers($limit = 15)
    {
        $pageNumbers = [];

        if ($limit >= $this->totalPage() || $this->totalPage() < 10) {
            for ($i = 1; $i <= $this->totalPage(); $i++) {
                $pageNumbers[] = $i;
            }
        } else {
            $pageNumbers = $this->getSmartPageNumbers($this->currentPage(), $this->totalPage());
        }

        return $pageNumbers;
    }

    /**
     * Get smart style page number by current and total page
     *
     * @param $currentPage
     * @param $totalPage
     * @return array
     */
    private function getSmartPageNumbers($currentPage, $totalPage)
    {
        $pageNumbers = [];
        $diff = 2;

        $firstChunk = [1, 2, 3];
        $lastChunk = [$totalPage - 2, $totalPage - 1, $totalPage];

        if ($currentPage < $totalPage) {
            $loopStartAt = $currentPage - $diff;
            if ($loopStartAt < 1) {
                $loopStartAt = 1;
            }

            $loopEndAt = $loopStartAt + ($diff * 2);
            if ($loopEndAt > $totalPage) {
                $loopEndAt = $totalPage;
                $loopStartAt = $loopEndAt - ($diff * 2);
            }

            if (!in_array($loopStartAt, $firstChunk)) {
                foreach ($firstChunk as $i) {
                    $pageNumbers[] = $i;
                }

                $pageNumbers[] = '.';
            }

            for ($i = $loopStartAt; $i <= $loopEndAt; $i++) {
                $pageNumbers[] = $i;
            }

            if (!in_array($loopEndAt, $lastChunk)) {
                $pageNumbers[] = '.';

                foreach ($lastChunk as $i) {
                    $pageNumbers[] = $i;
                }
            }
        }

        return $pageNumbers;
    }

    /**
     * @inheritDoc
     */
    public function limit()
    {
        return $this->perPage;
    }

    /**
     * @inheritDoc
     */
    public function offset()
    {
        return $this->limit() * $this->currentPage() - $this->limit();
    }

    /**
     * @inheritDoc
     */
    public function getFirstPageNumber()
    {
        return 1;
    }

    /**
     * @inheritDoc
     */
    public function getLastPageNumber()
    {
        return $this->totalPage();
    }

    /**
     * @inheritDoc
     */
    public function getNextPageNumber()
    {
        $nextPageNumber = $this->currentPage() + 1;

        if ($nextPageNumber > $this->totalPage()) {
            $nextPageNumber = $this->totalPage();
        }

        return $nextPageNumber;
    }

    /**
     * @inheritDoc
     */
    public function getPrevPageNumber()
    {
        $prevPageNumber = $this->currentPage() - 1;

        if ($prevPageNumber < 1) {
            $prevPageNumber = 1;
        }

        return $prevPageNumber;
    }

    /**
     * @inheritDoc
     */
    public function getDetails()
    {
        return [
            "total" => $this->total(),
            "perPage" => $this->count(),
            "currentPage" => $this->currentPage(),
            "lastPage" => $this->getLastPageNumber(),
            "firstPageUrl" => $this->firstPageUrl(),
            "lastPageUrl" => $this->lastPageUrl(),
            "nextPageUrl" => $this->nextPageUrl(),
            "prevPageUrl" => $this->prevPageUrl(),
            "path" => $this->pageUrl,
            "from" => $this->offset() + 1,
            "to" => $this->offset() + $this->limit(),
            "data" => $this->items(),
        ];
    }
}