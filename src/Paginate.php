<?php
namespace Roolith\Store;

use Roolith\Store\Interfaces\PaginatorInterface;

class Paginate implements PaginatorInterface
{
    protected $perPage;
    protected $pageUrl;
    protected $primaryColumn;
    protected $pageParam;
    protected $total;
    protected $items = [];
    protected $currentPage;

    public function __construct($param)
    {
        $this->perPage = $param['perPage'] ?? 20;
        $this->primaryColumn = $param['primaryColumn'] ?? 'id';
        $this->pageParam = $param['pageParam'] ?? 'page';
        $this->total = $param['total'] ?? 0;
        $this->currentPage = isset($param['currentPage']) ? (int) $param['currentPage'] : null;
        $this->pageUrl = $param['pageUrl'] ?? $this->getCurrentPageUrl();
    }

    /**
     * Get current page url
     *
     * @return string
     */
    protected function getCurrentPageUrl(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        if (!is_string($uri) || $uri === '') {
            return '/';
        }

        $path = parse_url($uri, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->perPage;
    }

    /**
     * @inheritDoc
     */
    public function total(): int
    {
        return $this->total;
    }

    /**
     * @inheritDoc
     */
    public function totalPage(): int
    {
        if ($this->count() <= 0) {
            return 0;
        }

        return (int) ceil($this->total() / $this->count());
    }

    /**
     * @inheritDoc
     */
    public function currentPage(): int
    {
        if ($this->currentPage !== null) {
            return $this->currentPage < 1 ? 1 : $this->currentPage;
        }

        $currentPageNumber = isset($_GET[$this->pageParam]) ? (int) $_GET[$this->pageParam] : 1;

        return $currentPageNumber < 1 ? 1 : $currentPageNumber;
    }

    public function setCurrentPage(int $page): PaginatorInterface
    {
        $this->currentPage = $page < 1 ? 1 : $page;

        return $this;
    }

    public function setTotal(int $total): PaginatorInterface
    {
        $this->total = $total < 0 ? 0 : $total;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasPages(): bool
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
    public function items(): array
    {
        return $this->items ?? [];
    }

    /**
     * @inheritDoc
     */
    public function setItems(array $items): PaginatorInterface
    {
        $this->items = $items;

        return $this;
    }

    protected function getPageUrlWithParam(): string
    {
        $questionMark = '?';
        $andMark = '&';

        $joinMark = $questionMark;
        if ($this->stringContains($this->pageUrl, $questionMark)) {
            $joinMark = $andMark;
        }

        return $this->pageUrl . $joinMark . $this->pageParam . '=';
    }

    /**
     * @inheritDoc
     */
    public function firstPageUrl(): string
    {
        return $this->getPageUrlWithParam() . $this->getFirstPageNumber();
    }

    /**
     * @inheritDoc
     */
    public function lastPageUrl(): string
    {
        return $this->getPageUrlWithParam() . $this->getLastPageNumber();
    }

    /**
     * @inheritDoc
     */
    public function nextPageUrl(): string
    {
        return $this->getPageUrlWithParam() . $this->getNextPageNumber();
    }

    /**
     * @inheritDoc
     */
    public function prevPageUrl(): string
    {
        return $this->getPageUrlWithParam() . $this->getPrevPageNumber();
    }

    /**
     * @inheritDoc
     */
    public function pageNumbers(int $limit = 15): array
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
    private function getSmartPageNumbers($currentPage, $totalPage): array
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
    public function limit(): int
    {
        return $this->perPage;
    }

    /**
     * @inheritDoc
     */
    public function offset(): int
    {
        if ($this->limit() <= 0) {
            return 0;
        }

        $offset = $this->limit() * $this->currentPage() - $this->limit();

        return $offset < 0 ? 0 : $offset;
    }

    /**
     * @inheritDoc
     */
    public function getFirstPageNumber(): int
    {
        return 1;
    }

    /**
     * @inheritDoc
     */
    public function getLastPageNumber(): int
    {
        return $this->totalPage();
    }

    /**
     * @inheritDoc
     */
    public function getNextPageNumber(): int
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
    public function getPrevPageNumber(): int
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
    public function getDetails(): object
    {
        $total = $this->total();
        $limit = $this->limit();
        $offset = $this->offset();

        if ($total <= 0 || $limit <= 0) {
            $from = 0;
            $to = 0;
        } else {
            $from = $offset + 1;

            if ($from > $total) {
                $from = $total + 1;
            }

            $to = min($offset + $limit, $total);

            if ($from > $to) {
                $from = $to + 1;

                if ($from > $total + 1) {
                    $from = $total + 1;
                }
            }
        }

        return (object) [
            "total" => $this->total(),
            "perPage" => $this->count(),
            "currentPage" => $this->currentPage(),
            "lastPage" => $this->getLastPageNumber(),
            "firstPageUrl" => $this->firstPageUrl(),
            "lastPageUrl" => $this->lastPageUrl(),
            "nextPageUrl" => $this->nextPageUrl(),
            "prevPageUrl" => $this->prevPageUrl(),
            "path" => $this->pageUrl,
            "from" => $from,
            "to" => $to,
            "data" => $this->items(),
        ];
    }

    /**
     * If string contains a piece
     *
     * @param $string
     * @param $piece
     * @return bool
     */
    protected function stringContains($string, $piece): bool
    {
        return strpos($string, $piece) !== false;
    }
}