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
        $this->perPage = isset($param['perPage']) ? max(0, (int) $param['perPage']) : 20;
        $this->primaryColumn = $param['primaryColumn'] ?? 'id';
        $this->pageParam = $param['pageParam'] ?? 'page';
        $this->total = isset($param['total']) ? max(0, (int) $param['total']) : 0;
        $this->currentPage = isset($param['currentPage']) ? max(1, (int) $param['currentPage']) : 1;
        $this->pageUrl = $param['pageUrl'] ?? '/';
    }

    /**
     * Decoupled factory that never touches superglobals.
     *
     * Pass $server (e.g. ['REQUEST_URI' => '/users?foo=1']) and
     * $query (e.g. ['page' => 2]) explicitly for CLI/tests.
     *
     * Path plus existing query params (minus pageParam) are preserved.
     * Pass a full pageUrl to fully control the base URL.
     *
     * @param array $params perPage/total/pageParam/primaryColumn + optional pageUrl/currentPage
     * @param array $server server vars used only for pageUrl fallback
     * @param array $query query vars used only for currentPage fallback
     */
    public static function fromRequest(array $params = [], array $server = [], array $query = []): self
    {
        $pageParam = $params['pageParam'] ?? 'page';

        if (!array_key_exists('pageUrl', $params)) {
            $params['pageUrl'] = self::resolvePageUrl($server, (string) $pageParam);
        }

        if (!array_key_exists('currentPage', $params)) {
            $params['currentPage'] = isset($query[$pageParam]) ? (int) $query[$pageParam] : 1;
        }

        return new self($params);
    }

    /**
     * Explicit superglobal-backed factory for legacy web usage.
     *
     * Prefer fromRequest() with explicit $server/$query instead.
     */
    public static function fromGlobals(array $params = []): self
    {
        return self::fromRequest($params, $_SERVER, $_GET);
    }

    protected static function resolvePageUrl(array $server, string $pageParam = ""): string
    {
        $uri = $server['REQUEST_URI'] ?? '/';

        if (!is_string($uri) || $uri === '') {
            return '/';
        }

        $path = parse_url($uri, PHP_URL_PATH);
        $base = is_string($path) && $path !== '' ? $path : '/';

        $queryString = parse_url($uri, PHP_URL_QUERY);
        if (!is_string($queryString) || $queryString === '') {
            return $base;
        }

        parse_str($queryString, $queryParams);

        if ($pageParam !== "") {
            unset($queryParams[$pageParam]);
        }

        if (count($queryParams) === 0) {
            return $base;
        }

        return $base . "?" . http_build_query($queryParams);
    }

    /**
     * Get current page url
     *
     * @deprecated Prefer Paginate::fromRequest() / fromGlobals(). Reads $_SERVER only for BC.
     *
     * @return string
     */
    protected function getCurrentPageUrl(): string
    {
        return self::resolvePageUrl($_SERVER);
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
        return $this->currentPage < 1 ? 1 : $this->currentPage;
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
        if (str_contains($this->pageUrl, $questionMark)) {
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
     * Limit pagination number
     * < 1 | 2 ... 37 | 38 | 39 | 40 | 41 | 42 ... 82 | 83 >
     *
     * Ellipsis gaps are marked with '...' (string) while pages are ints.
     *
     * @param int $limit
     * @return array<int|string>
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
     * @return array<int|string>
     */
    private function getSmartPageNumbers($currentPage, $totalPage): array
    {
        $pageNumbers = [];

        if ($totalPage <= 0) {
            return [];
        }

        $currentPage = max(1, min((int) $currentPage, (int) $totalPage));
        $diff = 2;

        $firstChunk = [1, 2, 3];
        $lastChunk = [$totalPage - 2, $totalPage - 1, $totalPage];

        $loopStartAt = $currentPage - $diff;
        if ($loopStartAt < 1) {
            $loopStartAt = 1;
        }

        $loopEndAt = $loopStartAt + ($diff * 2);
        if ($loopEndAt > $totalPage) {
            $loopEndAt = $totalPage;
            $loopStartAt = max(1, $loopEndAt - ($diff * 2));
        }

        if (!in_array($loopStartAt, $firstChunk)) {
            foreach ($firstChunk as $i) {
                $pageNumbers[] = $i;
            }

            $pageNumbers[] = '...';
        }

        for ($i = $loopStartAt; $i <= $loopEndAt; $i++) {
            if (!in_array($i, $pageNumbers, true)) {
                $pageNumbers[] = $i;
            }
        }

        if (!in_array($loopEndAt, $lastChunk)) {
            $pageNumbers[] = '...';

            foreach ($lastChunk as $i) {
                if (!in_array($i, $pageNumbers, true)) {
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

        if ($total <= 0 || $limit <= 0 || $offset >= $total) {
            $from = 0;
            $to = 0;
        } else {
            $from = $offset + 1;
            $to = min($offset + $limit, $total);
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
        return str_contains((string) $string, (string) $piece);
    }
}