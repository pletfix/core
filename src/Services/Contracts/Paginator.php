<?php

namespace Core\Services\Contracts;

interface Paginator
{
    /**
     * Get the total number of items.
     *
     * @return int
     */
    public function total();

    /**
     * Get the maximal number of items per page.
     *
     * @return int
     */
    public function limit();

    /**
     * Get the offset of items for the current page.
     *
     * @return int
     */
    public function offset();

    /**
     * Get the current page.
     *
     * @return int
     */
    public function currentPage();

    /**
     * Get the last page.
     *
     * @return int
     */
    public function lastPage();

    /**
     * Determine if there are enough items to split into multiple pages.
     *
     * @return bool
     */
    public function hasMultiplePages();

    /**
     * Get the pages.
     *
     * Example:
     *  [1, 2, '...', 5, 6, 7, 8, 9, 10, 11, '...', 14, 15]
     *                         |
     *                         |- current
     * @return array
     */
    public function pages();

    /**
     * Get the URL for a given page number.
     *
     * @param int $page
     * @return string
     */
    public function url($page);

    /**
     * Get the URL for the next page.
     *
     * @return string|null
     */
    public function nextUrl();

    /**
     * Get the URL for the previous page.
     *
     * @return string|null
     */
    public function previousUrl();

    /**
     * Get the query string variable used to store the page.
     *
     * @return string
     */
    public function getPageKey();

    /**
     * Set the query string variable used to store the page.
     *
     * @param string $key
     * @return $this
     */
    public function setPageKey($key);

    /**
     * Get the URL fragment.
     *
     * @return string
     */
    public function getFragment();

    /**
     * Set a URL fragment to add to all URLs.
     *
     * @param string $fragment
     * @return $this
     */
    public function setFragment($fragment);

    /**
     * Get the query parameters.
     *
     * @return array
     */
    public function getParameters();

    /**
     * Set a query parameters to add to all URLs.
     *
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters);
}