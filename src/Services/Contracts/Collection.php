<?php

namespace Core\Services\Contracts;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;

/**
 * Collection
 *
 * License: MIT
 *
 * This code based on Laravel's Collection 5.3 (see copyright notice license-laravel.md)
 *
 * @see https://laravel.com/docs/master/collections Laravel's Documentation
 * @see https://github.com/illuminate/support/blob/master/Collection.php Laravel's Collection on GitHub by Taylor Otwell
 */
interface Collection extends ArrayAccess, Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
//    /**
//     * Create a new Collection.
//     *
//     * @param mixed $items
//     */
//    public function __construct($items = []);

    /*
     * --------------------------------------------------------------------------------------------------------------
     * Collection Query
     *
     * The following methods execute a collection query and returns the result.
     * The collection is not modified by this methods.
     * --------------------------------------------------------------------------------------------------------------
     */

    /**
     * Get all of the items in the collection as an array.
     *
     * @return array
     */
    public function all();

    /**
     * Get the average value of a given key.
     *
     * @param  callable|string|null  $callback
     * @return float|null
     */
    public function avg($callback = null);

    /**
     * Determine if an item exists in the collection.
     *
     * @param  string|callable  $value
     * @param  int|string|null  $key
     * @return bool
     */
    public function contains($value, $key = null);

    /**
     * Determine if an item exists in the collection using strict comparison.
     *
     * @param  string|callable  $value
     * @param  int|string|null  $key
     * @return bool
     */
    public function containsStrict($value, $key = null);

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count();

    /**
     * Execute a callback over each item.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function each(callable $callback);

    /**
     * Get the first item from the collection.
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function first(callable $callback = null, $default = null);

    /**
     * Get an item from the collection by key.
     *
     * @param  int|string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param  int|string|null  $key
     * @return bool
     */
    public function has($key);

    /**
     * Concatenate values of a given key as a string.
     *
     * @param  string  $value
     * @param  string  $glue
     * @return string
     */
    public function implode($value, $glue = null);

    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Get the last item from the collection.
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function last(callable $callback = null, $default = null);

    /**
     * Get the max value of a given key.
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function max($callback = null);

    /**
     * Get the median of a given key.
     *
     * @param  string|null $key
     * @return mixed|null
     */
    public function median($key = null);

    /**
     * Get the min value of a given key.
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function min($callback = null);

//    /**
//     * Get the mode of a given key.
//     *
//     * @param  string|null $key
//     * @return array
//     */
//    public function mode($key = null);

    /**
     * Get one or more items randomly from the collection.
     *
     * @param  int  $amount
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function random($amount = 1);

    /**
     * Reduce the collection to a single value.
     *
     * @param  callable  $callback
     * @param  mixed     $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null);

    /**
     * Search the collection for a given value and return the corresponding key if successful, false otherwise.
     *
     * @param  mixed  $value
     * @param  bool   $strict
     * @return mixed
     */
    public function search($value, $strict = false);

    /**
     * Get the sum of the given values.
     *
     * @param  callable|string|null $callback Callable function or key using "dot" notation
     * @return mixed
     */
    public function sum($callback = null);

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray();

    /**
     * Get the collection of items as JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0);

    /*
     * --------------------------------------------------------------------------------------------------------------
     * Collection Manipulation (a)
     *
     * The following methods returns a new modified Collection instance, allowing you to preserve the original copy
     * of the collection when necessary.
     * --------------------------------------------------------------------------------------------------------------
     */

    /**
     * Chunk the underlying collection array.
     *
     * @param  int $size
     * @return static
     */
    public function chunk($size);

    /**
     * Collapse the collection of items into a single array.
     *
     * @return static
     */
    public function collapse();

    /**
     * Create a collection by using this collection for keys and another for its values.
     *
     * @param  mixed $values
     * @return static
     */
    public function combine($values);

    /**
     * Get the items in the collection that are not present in the given items.
     *
     * @param  mixed $items
     * @return static
     */
    public function diff($items);

    /**
     * Get the items in the collection whose keys are not present in the given items.
     *
     * @param  mixed $items
     * @return static
     */
    public function diffKeys($items);

    /**
     * Create a new collection consisting of every n-th element.
     *
     * @param  int $step
     * @param  int $offset
     * @return static
     */
    public function every($step, $offset = 0);

    /**
     * Get all items except for those with the specified keys.
     *
     * @param  array|string|int $keys
     * @return static
     */
    public function except($keys);

    /**
     * Run a filter over each of the items.
     *
     * @param  callable|null $callback
     * @return static
     */
    public function filter(callable $callback = null);

    /**
     * Map a collection and flatten the result by a single level.
     *
     * @param  callable $callback
     * @return static
     */
    public function flatMap(callable $callback);

    /**
     * Get a flattened array of the items in the collection.
     *
     * @param  int $depth
     * @return static
     */
    public function flatten($depth = INF);

    /**
     * Flip the items in the collection.
     *
     * @return static
     */
    public function flip();

    /**
     * "Paginate" the collection by slicing it into a smaller collection.
     *
     * @param  int $page
     * @param  int $perPage
     * @return static
     */
    public function forPage($page, $perPage);

    /**
     * Group an associative array by a field or using a callback.
     *
     * @param  callable|string $groupBy
     * @param  bool $preserveKeys
     * @return static
     */
    public function groupBy($groupBy, $preserveKeys = false);

    /**
     * Intersect the collection with the given items.
     *
     * @param  mixed $items
     * @return static
     */
    public function intersect($items);

    /**
     * Key an associative array by a field or using a callback.
     *
     * @param  callable|string $keyBy
     * @return static
     */
    public function keyBy($keyBy);

    /**
     * Get the keys of the collection items.
     *
     * @return static
     */
    public function keys();

    /**
     * Run a map over each of the items.
     *
     * @param  callable $callback
     * @return static
     */
    public function map(callable $callback);

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param  callable $callback
     * @return static
     */
    public function mapWithKeys(callable $callback);

    /**
     * Merge the collection with the given items.
     *
     * @param  mixed $items
     * @return static
     */
    public function merge($items);

    /**
     * Get the items with the specified keys.
     *
     * @param  array|string $keys
     * @return static
     */
    public function only($keys);

    /**
     * Get the values of a given key.
     *
     * @param  int|string $keyOfValue
     * @param  int|string|null $keyOfKey
     * @return static
     */
    public function pluck($keyOfValue, $keyOfKey = null);

    /**
     * Reverse items order.
     *
     * @return static
     */
    public function reverse();

    /**
     * Shuffle the items in the collection.
     *
     * @param int $seed
     * @return static
     */
    public function shuffle($seed = null);

    /**
     * Slice the underlying collection array.
     *
     * @param  int $offset
     * @param  int $length
     * @return static
     */
    public function slice($offset, $length = null);

    /**
     * Sort through each item with a callback.
     *
     * @param  callable|null $callback
     * @return static
     */
    public function sort(callable $callback = null);

    /**
     * Sort the collection using the given callback.
     *
     * @param  callable|string $callback
     * @param  int $options
     * @param  bool $descending
     * @return static
     */
    public function sortBy($callback, $options = SORT_REGULAR, $descending = false);

    /**
     * Sort the collection in descending order using the given callback.
     *
     * @param  callable|string $callback
     * @param  int $options
     * @return static
     */
    public function sortByDesc($callback, $options = SORT_REGULAR);

    /**
     * Splice a portion of the underlying collection array.
     *
     * @param  int $offset
     * @param  int|null $length
     * @param  mixed $replacement
     * @return static
     */
    public function splice($offset, $length = null, $replacement = []);

    /**
     * Split a collection into a certain number of groups.
     *
     * @param  int $numberOfGroups
     * @return static
     */
    public function split($numberOfGroups);

    /**
     * Take the first or last {$limit} items.
     *
     * @param  int $limit
     * @return static
     */
    public function take($limit);

    /**
     * Union the collection with the given items.
     *
     * @param  mixed $items
     * @return static
     */
    public function union($items);

    /**
     * Return only unique items from the collection array.
     *
     * @param  string|callable|null $keyOrCallback
     * @param  bool $strict
     *
     * @return static
     */
    public function unique($keyOrCallback = null, $strict = false);

    /**
     * Reset the keys on the underlying array.
     *
     * @return static
     */
    public function values();

    /**
     * Filter items by the given key value pair.
     *
     * @param  string $key
     * @param  mixed $operator
     * @param  mixed $value
     * @return static
     */
    public function where($key, $operator, $value);

    /**
     * Filter items by the given key value pair.
     *
     * @param  string $key
     * @param  mixed $value
     * @return static
     */
    public function whereEqual($key, $value = null);

    /**
     * Filter items by the given key value pair.
     *
     * @param  string $key
     * @param  mixed $values
     * @param  bool $strict
     * @return static
     */
    public function whereIn($key, $values, $strict = false);

    /**
     * Zip the collection together with one or more arrays.
     *
     * e.g. new Collection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]]
     *
     * @param  mixed ...$items
     * @return static
     */
    public function zip($items);

    /*
     * --------------------------------------------------------------------------------------------------------------
     * Collection Manipulation (b)
     *
     * Unlike most other collection methods, the following method does not return a new modified collection; it
     * modifies the collection that is called.
     * --------------------------------------------------------------------------------------------------------------
     */

    /**
     * Remove an item from the collection by key.
     *
     * #Notice
     * This method modifies and returns the collection that it called.
     *
     * @param  array|string|int|null $keys
     * @return $this
     */
    public function forget($keys = null);

    /**
     * Get and remove the last item from the collection.
     *
     * #Notice
     * This method modifies the collection that is called.
     *
     * @return mixed
     */
    public function pop();

    /**
     * Push an item onto the beginning of the collection.
     *
     * #Notice
     * This method modifies and returns the collection that it called.
     *
     * @param  mixed $value
     * @param  int|string|null $key
     * @return $this
     */
    public function prepend($value, $key = null);

    /**
     * Get and remove an item from the collection.
     *
     * #Notice
     * This method modifies the collection it is called on.
     *
     * @param  int|string $key
     * @param  mixed $default
     * @return mixed
     */
    public function pull($key, $default = null);

    /**
     * Push an item onto the end of the collection.
     *
     * #Notice
     * This method modifies and returns the collection that it called.
     *
     * @param  mixed $value
     * @return $this
     */
    public function push($value);

    /**
     * Put an item in the collection by key.
     *
     * #Notice
     * This method modifies and returns the collection that it called.
     *
     * @param  int|string $key
     * @param  mixed $value
     * @return $this
     */
    public function put($key, $value);

    /**
     * Get and remove the first item from the collection.
     *
     * #Notice
     * This method modifies the collection it is called on.
     *
     * @return mixed
     */
    public function shift();

    /**
     * Transform each item in the collection using a callback.
     *
     * #Notice
     * This method modifies and returns the collection that it called.
     *
     * @param  callable $callback
     * @return $this
     */
    public function transform(callable $callback);
}