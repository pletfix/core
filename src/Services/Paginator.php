<?php

namespace Core\Services;

use Core\Services\Contracts\Paginator as PaginatorContract;
use OutOfRangeException;

/**
 * Paginator
 */
class Paginator implements PaginatorContract
{
    /**
     * The total number of items.
     *
     * @var int
     */
    private $total;

    /**
     * The maximum number of items per page.
     *
     * @var int
     */
    private $limit;

    /**
     * The current page.
     *
     * @var int
     */
    private $currentPage;

    /**
     * The last page.
     *
     * @var int
     */
    private $lastPage;

    /**
     * The base path to assign to all URLs.
     *
     * @var string
     */
    private $path;

    /**
     * The query parameters to add to all URLs.
     *
     * @var array
     */
    private $parameters = [];

    /**
     * The URL fragment to add to all URLs.
     *
     * @var string|null
     */
    private $fragment = null;

    /**
     * The query string variable used to store the page.
     *
     * @var string
     */
    private $pageKey = 'page';

    /**
     * Create a new Paginator instance.
     *
     * @param int $total The total number of items.
     * @param int $limit The number of items per page.
     * @param int|null $currentPage The current page.
     */
    public function __construct($total, $limit = 20, $currentPage = null)
    {
        $this->total    = $total;
        $this->limit    = $limit;
        $this->lastPage = (int)ceil($total / $limit);

        $this->currentPage = $currentPage ?: (isset($_GET['page']) ? (int)$_GET['page'] : 1);
        if ($this->currentPage < 1) {
            $this->currentPage = 1;
        }
        else if ($this->currentPage > $this->lastPage) {
            $this->currentPage = $this->lastPage;
        }

        $this->path = request()->path();
    }

    /**
     * @inheritdoc
     */
    public function total()
    {
        return $this->total;
    }

    /**
     * @inheritdoc
     */
    public function limit()
    {
        return $this->limit;
    }

    /**
     * @inheritdoc
     */
    public function offset()
    {
        return $this->currentPage > 0 ? $this->limit * ($this->currentPage - 1) : 0;
    }

    /**
     * @inheritdoc
     */
    public function currentPage()
    {
        return $this->currentPage;
    }

    /**
     * @inheritdoc
     */
    public function lastPage()
    {
        return $this->lastPage;
    }

    /**
     * @inheritdoc
     */
    public function hasMultiplePages()
    {
        return $this->total > $this->limit;
    }

    /**
     * @inheritdoc
     */
    public function pages()
    {
        if ($this->lastPage == 0) {
            return [];
        }

        $boundaries = 2; // number of links at beginning and at the end of the paginator
        $adjacents  = 3; // number of links around the current page to each direction
        $max = ($boundaries + $adjacents) * 2 + 3; // maximal buttons to show (2 + '...' + 3 + current + 3 + '...' + 2)

        $pages = [];

        if ($this->lastPage <= $max) {
            // without slider
            for ($page = 1; $page <= $this->lastPage; $page++) {
                $pages[] = $page;
            }
        }
        else if ($this->currentPage <= $boundaries + $adjacents + 2) {
            // slider at the left side
            for ($page = 1; $page <= $max - $boundaries - 1; $page++) {
                $pages[] = $page;
            }
            $pages[] = '...';
            for ($page = $this->lastPage - $boundaries + 1; $page <= $this->lastPage; $page++) {
                $pages[] = $page;
            }
        }
        else if ($this->currentPage >= $this->lastPage - $boundaries - $adjacents - 1) {
            // slider at the right side
            for ($page = 1; $page <= $boundaries; $page++) {
                $pages[] = $page;
            }
            $pages[] = '...';
            for ($page = $this->lastPage - $max + $boundaries + 2; $page <= $this->lastPage; $page++) {
                $pages[] = $page;
            }
        }
        else {
            // slider in the middle
            for ($page = 1; $page <= $boundaries; $page++) {
                $pages[] = $page;
            }
            $pages[] = '...';
            for ($page = $this->currentPage - $adjacents; $page <= $this->currentPage + $adjacents; $page++) {
                $pages[] = $page;
            }
            $pages[] = '...';
            for ($page = $this->lastPage - $boundaries + 1; $page <= $this->lastPage; $page++) {
                $pages[] = $page;
            }
        }

        return $pages;
    }

    /**
     * @inheritdoc
     */
    public function url($page)
    {
        if ($page < 1 || $page > $this->lastPage) {
            throw new  OutOfRangeException('Page out of range.');
        }

        $parameters = array_merge($_GET, $this->parameters, [$this->pageKey => $page]);

        return url($this->path, $parameters) . ($this->fragment !== null ? '#' . $this->fragment : '');
    }

    /**
     * @inheritdoc
     */
    public function nextUrl()
    {
        return $this->lastPage > $this->currentPage ? $this->url($this->currentPage + 1) : null;
    }

    /**
     * @inheritdoc
     */
    public function previousUrl()
    {
        return $this->currentPage > 1 ? $this->url($this->currentPage() - 1) : null;
    }

    /**
     * @inheritdoc
     */
    public function getPageKey()
    {
        return $this->pageKey;
    }

    /**
     * @inheritdoc
     */
    public function setPageKey($key)
    {
        $this->pageKey = $key;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @inheritdoc
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @inheritdoc
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }
}