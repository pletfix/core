<?php

namespace Core\Services;

use ArrayAccess;
use Core\Services\Contracts\Arrayable;
use Core\Services\Contracts\Collection as CollectionContract;
use Core\Services\Contracts\Jsonable;
use ArrayIterator;
use Closure;
use InvalidArgumentException;
use JsonSerializable;
use Traversable;

// todo statistik funktionen hinzufügen

/**
 * Collection
 *
 * License: MIT
 *
 * This code based on Laravel's Collection 5.3.
 *
 * @see https://laravel.com/docs/5.3/collections Laravel's Documentation
 * @see https://github.com/illuminate/support/blob/5.3/Collection.php Laravel's Collection on GitHub by Taylor Otwell
 */
class Collection implements CollectionContract
{
    /**
     * The items contained in the collection.
     *
     * @var array
     */
    private $items = [];

    /**
     * Create a new Collection.
     *
     * @param mixed $items
     */
    public function __construct($items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }

    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Results array of items from Collection or Arrayable.
     *
     * @param  mixed  $items
     * @return array
     */
    private function getArrayableItems($items)
    {
        if (is_array($items)) {
            return $items;
        }
        elseif ($items instanceof self) {
            return $items->all();
        }
        elseif ($items instanceof Arrayable) {
            return $items->toArray();
        }
        elseif ($items instanceof Jsonable) {
            return json_decode($items->toJson(), true);
        }
        elseif ($items instanceof JsonSerializable) {
            return $items->jsonSerialize();
        }
        elseif ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        return (array)$items;
    }

    /**
     * Get a value retrieving callback.
     *
     * @param  string  $value
     * @return callable
     */
    private function valueRetriever($value)
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }

        return function ($item) use ($value) {
            if ($value === null) {
                return $item;
            }

            $item = (array)$item;
            return isset($item[$value]) ? $item[$value] : null;
        };
    }

    /**
     * Determine if the given value is callable, but not a string.
     *
     * @param  mixed  $value
     * @return bool
     */
    private function useAsCallable($value)
    {
        return !is_string($value) && is_callable($value);
    }

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
    public function all()
    {
        return $this->items;
    }

    /**
     * Get the average value of a given key.
     *
     * @param  callable|string|null  $callback
     * @return float|null
     */
    public function avg($callback = null)
    {
        $count = $this->count();

        return $count ? $this->sum($callback) / $count : null;
    }

    /**
     * Determine if an item exists in the collection.
     *
     * @param  string|callable $value
     * @param  int|string|null $key
     * @param bool $strict
     * @return bool
     */
    public function contains($value, $key = null, $strict = false)
    {
        if ($key !== null) {
            foreach ($this->items as $item) {
                $v = isset($item[$key]) ? $item[$key] : null;
                if ((!$strict && $v == $value) || $v === $value) {
                    return true;
                }
            }
            return false;
        }

        if ($this->useAsCallable($value)) {
            return $this->first($value) !== null;
        }

        return in_array($value, $this->items, $strict);
    }

    /**
     * Determine if an item exists in the collection using strict comparison.
     *
     * @param  string|callable  $value
     * @param  int|string|null  $key
     * @return bool
     */
    public function containsStrict($value, $key = null)
    {
        return $this->contains($value, $key, true);
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Execute a callback over each item.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Get the first item from the collection.
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function first(callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            foreach ($this->items as $item) {
                return $item;
            }
        }
        else {
            foreach ($this->items as $key => $value) {
                if (call_user_func($callback, $value, $key)) {
                    return $value;
                }
            }
        }

        return $default instanceof Closure ? $default() : $default;
    }

    /**
     * Get an item from the collection by key.
     *
     * @param  int|string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        return $default instanceof Closure ? $default() : $default;
    }

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param  int|string|null  $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Concatenate values of a given key as a string.
     *
     * @param  string  $value
     * @param  string  $glue
     * @return string
     */
    public function implode($value, $glue = null)
    {
        $first = $this->first();
        if (is_array($first) || is_object($first)) {
            return implode($glue, $this->pluck($value)->all());
        }

        return implode($value, $this->items);
    }

    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Get the last item from the collection.
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function last(callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (!empty($this->items)) {
                return end($this->items);
            }
        }
        else {
            foreach (array_reverse($this->items, true) as $key => $value) {
                if (call_user_func($callback, $value, $key)) {
                    return $value;
                }
            }
        }

        return $default instanceof Closure ? $default() : $default;
    }

    /**
     * Get the max value of a given key.
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function max($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        return $this->filter(function ($value) {
            return $value !== null;
        })->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);
            return $result === null || $value > $result ? $value : $result;
        });
    }

    /**
     * Get the median of a given key.
     *
     * @param  string|null $key
     * @return mixed|null
     */
    public function median($key = null)
    {
        $count = $this->count();
        if ($count == 0) {
            return null;
        }

        $values = isset($key) ? $this->pluck($key)->sort()->values() : $this->sort()->values();
        $middle = (int)($count / 2);
        if ($count % 2) {
            return $values->get($middle);
        }

        return (new static([$values->get($middle - 1), $values->get($middle)]))->avg();
    }

    /**
     * Get the min value of a given key.
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function min($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        return $this->filter(function ($value) {
            return $value !== null;
        })->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);
            return $result === null || $value < $result ? $value : $result;
        });
    }

//    /**
//     * Get the mode of a given key.
//     *
//     * @param  string|null $key
//     * @return array
//     */
//    public function mode($key = null)
//    {
//        $count = $this->count();
//
//        if ($count == 0) {
//            return;
//        }
//
//        $collection = isset($key) ? $this->pluck($key) : $this;
//
//        $counts = new self;
//
//        $collection->each(function ($value) use ($counts) {
//            $counts[$value] = isset($counts[$value]) ? $counts[$value] + 1 : 1;
//        });
//
//        $sorted = $counts->sort();
//
//        $highestValue = $sorted->last();
//
//        return $sorted->filter(function ($value) use ($highestValue) {
//            return $value == $highestValue;
//        })->sort()->keys()->all();
//    }

    /**
     * Get one or more items randomly from the collection.
     *
     * @param  int  $amount
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function random($amount = 1)
    {
        if ($amount > ($count = $this->count())) {
            throw new InvalidArgumentException("You requested {$amount} items, but there are only {$count} items in the collection");
        }

        $keys = array_rand($this->items, $amount);

        if (count(func_get_args()) == 0) {
            return $this->items[$keys];
        }

        return new static(array_intersect_key($this->items, array_flip((array)$keys)));
    }

    /**
     * Reduce the collection to a single value.
     *
     * @param  callable  $callback
     * @param  mixed     $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Search the collection for a given value and return the corresponding key if successful, false otherwise.
     *
     * @param  mixed  $value
     * @param  bool   $strict
     * @return mixed
     */
    public function search($value, $strict = false)
    {
        if (!$this->useAsCallable($value)) {
            return array_search($value, $this->items, $strict);
        }

        foreach ($this->items as $key => $item) {
            if (call_user_func($value, $item, $key)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Get the sum of the given values.
     *
     * @param  callable|string|null $callback Callable function or key using "dot" notation
     * @return mixed
     */
    public function sum($callback = null)
    {
        if (is_null($callback)) {
            return array_sum($this->items);
        }

        $callback = $this->valueRetriever($callback); // closure the key

        return $this->reduce(function ($result, $item) use ($callback) {
            return $result + $callback($item);
        }, 0);
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            return $value instanceof Arrayable ? $value->toArray() : $value;
        }, $this->items);
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /*
     * --------------------------------------------------------------------------------------------------------------
     * Collection Manipulation (Immutable Functions)
     *
     * The following methods returns a new modified Collection instance, allowing you to preserve the original copy
     * of the collection when necessary.
     * --------------------------------------------------------------------------------------------------------------
     */

    /**
     * Chunk the underlying collection array.
     *
     * @param  int   $size
     * @return static
     */
    public function chunk($size)
    {
        if ($size <= 0) {
            return new static;
        }

        $chunks = [];
        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    /**
     * Collapse the collection of items into a single array.
     *
     * @return static
     */
    public function collapse()
    {
        $results = [];
        foreach ($this->items as $item) {
            if ($item instanceof CollectionContract) {
                $item = $item->all();
            }
            elseif (!is_array($item)) {
                continue;
            }
            $results = array_merge($results, $item);
        }

        return new static($results);
    }

    /**
     * Create a collection by using this collection for keys and another for its values.
     *
     * @param  mixed  $values
     * @return static
     */
    public function combine($values)
    {
        return new static(array_combine($this->all(), $this->getArrayableItems($values)));
    }

    /**
     * Get the items in the collection that are not present in the given items.
     *
     * @param  mixed  $items
     * @return static
     */
    public function diff($items)
    {
        return new static(array_diff($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Get the items in the collection whose keys are not present in the given items.
     *
     * @param  mixed  $items
     * @return static
     */
    public function diffKeys($items)
    {
        return new static(array_diff_key($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Create a new collection consisting of every n-th element.
     *
     * @param  int  $step
     * @param  int  $offset
     * @return static
     */
    public function every($step, $offset = 0)
    {
        $results  = [];
        $position = 0;
        foreach ($this->items as $item) {
            if ($position % $step === $offset) {
                $results[] = $item;
            }
            $position++;
        }

        return new static($results);
    }

    /**
     * Get all items except for those with the specified keys.
     *
     * @param  array|string|int  $keys
     * @return static
     */
    public function except($keys)
    {
        $results = $this->items;
        foreach ((array)$keys as $key) {
            unset($results[$key]);
        }

        return new static($results);
    }

    /**
     * Run a filter over each of the items.
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function filter(callable $callback = null)
    {
        if ($callback) {
            return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
        }

        return new static(array_filter($this->items));
    }

    /**
     * Map a collection and flatten the result by a single level.
     *
     * @param  callable  $callback
     * @return static
     */
    public function flatMap(callable $callback)
    {
        return $this->map($callback)->collapse();
    }

    /**
     * Get a flattened array of the items in the collection.
     *
     * @param  int  $depth
     * @return static
     */
    public function flatten($depth = INF)
    {
        return new static(static::arrayFlatten($this->items, $depth));
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param  array  $array
     * @param  int  $depth
     * @return array
     */
    private static function arrayFlatten($array, $depth = INF)
    {
        return array_reduce($array, function ($result, $item) use ($depth) {
            if ($item instanceof CollectionContract) {
                $item = $item->all();
            }
            if (!is_array($item)) {
                return array_merge($result, [$item]);
            }
            elseif ($depth === 1) {
                return array_merge($result, array_values($item));
            }
            else {
                return array_merge($result, static::arrayFlatten($item, $depth - 1));
            }
        }, []);
    }

    /**
     * Flip the items in the collection.
     *
     * @return static
     */
    public function flip()
    {
        return new static(array_flip($this->items));
    }

    /**
     * "Paginate" the collection by slicing it into a smaller collection.
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return static
     */
    public function forPage($page, $perPage)
    {
        return $this->slice(($page - 1) * $perPage, $perPage);
    }

    /**
     * Group an associative array by a field or using a callback.
     *
     * @param  callable|string  $groupBy
     * @param  bool  $preserveKeys
     * @return static
     */
    public function groupBy($groupBy, $preserveKeys = false)
    {
        $groupBy = $this->valueRetriever($groupBy);
        $results = [];

        foreach ($this->items as $key => $value) {
            $groupKeys = $groupBy($value, $key);

            if (!is_array($groupKeys)) {
                $groupKeys = [$groupKeys];
            }

            foreach ($groupKeys as $groupKey) {
                if (! array_key_exists($groupKey, $results)) {
                    $results[$groupKey] = new static;
                }

                $results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
            }
        }

        return new static($results);
    }

    /**
     * Intersect the collection with the given items.
     *
     * @param  mixed  $items
     * @return static
     */
    public function intersect($items)
    {
        return new static(array_intersect($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Key an associative array by a field or using a callback.
     *
     * @param  callable|string  $keyBy
     * @return static
     */
    public function keyBy($keyBy)
    {
        $keyBy   = $this->valueRetriever($keyBy);
        $results = [];

        foreach ($this->items as $key => $item) {
            $results[$keyBy($item, $key)] = $item;
        }

        return new static($results);
    }

    /**
     * Get the keys of the collection items.
     *
     * @return static
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * Run a map over each of the items.
     *
     * @param  callable  $callback
     * @return static
     */
    public function map(callable $callback)
    {
        $keys  = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);

        return new static(array_combine($keys, $items));
    }

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param  callable  $callback
     * @return static
     */
    public function mapWithKeys(callable $callback)
    {
        $result = [];
        foreach ($this->items as $key => $value) {
            $assoc = $callback($value, $key);
            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }

        return new static($result);
    }

    /**
     * Merge the collection with the given items.
     *
     * @param  mixed  $items
     * @return static
     */
    public function merge($items)
    {
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Get the items with the specified keys.
     *
     * @param  array|string $keys
     * @return static
     */
    public function only($keys)
    {
        if (is_null($keys)) {
            return new static($this->items);
        }

        return new static(array_intersect_key($this->items, array_flip((array)$keys)));
    }

    /**
     * Get the values of a given key.
     *
     * @param  int|string  $keyOfValue
     * @param  int|string|null $keyOfKey
     * @return static
     */
    public function pluck($keyOfValue, $keyOfKey = null)
    {
        $results = [];
        foreach ($this->items as $item) {
            if (!is_array($item) && !($item instanceof ArrayAccess)) {
                if (is_object($item)) {
                    $item = [
                        $keyOfValue => isset($item->{$keyOfValue}) ? $item->{$keyOfValue} : null,
                        $keyOfKey => isset($item->{$keyOfKey}) ? $item->{$keyOfKey} : null,
                    ];
                }
                else {
                    $item = $this->getArrayableItems($item);
                }
            }
            if ($keyOfKey === null) {
                $results[] = isset($item[$keyOfValue]) ? $item[$keyOfValue] : null;
            }
            else {
                $results[isset($item[$keyOfKey]) ? $item[$keyOfKey] : null] = isset($item[$keyOfValue]) ? $item[$keyOfValue] : null;
            }
        }

        return new static($results);
    }

    /**
     * Reverse items order.
     *
     * @return static
     */
    public function reverse()
    {
        return new static(array_reverse($this->items, true));
    }

    /**
     * Shuffle the items in the collection.
     *
     * @param int $seed
     * @return static
     */
    public function shuffle($seed = null)
    {
        $items = $this->items;

        if (is_null($seed)) {
            shuffle($items);
        }
        else {
            srand($seed);
            usort($items, function () {
                return rand(-1, 1);
            });
        }

        return new static($items);
    }

    /**
     * Slice the underlying collection array.
     *
     * @param  int   $offset
     * @param  int   $length
     * @return static
     */
    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Sort through each item with a callback.
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function sort(callable $callback = null)
    {
        $items = $this->items;

        $callback ? uasort($items, $callback) : asort($items);

        return new static($items);
    }

    /**
     * Sort the collection using the given callback.
     *
     * @param  callable|string  $callback
     * @param  int   $options
     * @param  bool  $descending
     * @return static
     */
    public function sortBy($callback, $options = SORT_REGULAR, $descending = false)
    {
        $results = [];

        $callback = $this->valueRetriever($callback);

        // First we will loop through the items and get the comparator from a callback
        // function which we were given. Then, we will sort the returned values and
        // and grab the corresponding values for the sorted keys from this array.
        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value, $key);
        }

        $descending ? arsort($results, $options)
            : asort($results, $options);

        // Once we have sorted all of the keys in the array, we will loop through them
        // and grab the corresponding model so we can set the underlying items list
        // to the sorted version. Then we'll just return the collection instance.
        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }

        return new static($results);
    }

    /**
     * Sort the collection in descending order using the given callback.
     *
     * @param  callable|string  $callback
     * @param  int  $options
     * @return static
     */
    public function sortByDesc($callback, $options = SORT_REGULAR)
    {
        return $this->sortBy($callback, $options, true);
    }

    /**
     * Splice a portion of the underlying collection array.
     *
     * @param  int  $offset
     * @param  int|null  $length
     * @param  mixed  $replacement
     * @return static
     */
    public function splice($offset, $length = null, $replacement = [])
    {
        if (func_num_args() == 1) {
            return new static(array_splice($this->items, $offset));
        }

        return new static(array_splice($this->items, $offset, $length, $replacement));
    }

    /**
     * Split a collection into a certain number of groups.
     *
     * @param  int  $numberOfGroups
     * @return static
     */
    public function split($numberOfGroups)
    {
        if ($this->isEmpty()) {
            return new static;
        }

        $groupSize = ceil($this->count() / $numberOfGroups);

        return $this->chunk($groupSize);
    }

    /**
     * Take the first or last {$limit} items.
     *
     * @param  int  $limit
     * @return static
     */
    public function take($limit)
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    /**
     * Union the collection with the given items.
     *
     * @param  mixed  $items
     * @return static
     */
    public function union($items)
    {
        return new static($this->items + $this->getArrayableItems($items));
    }

    /**
     * Return only unique items from the collection array.
     *
     * @param  string|callable|null  $keyOrCallback
     * @param  bool  $strict
     *
     * @return static
     */
    public function unique($keyOrCallback = null, $strict = false)
    {
        if ($keyOrCallback === null) {
            return new static(array_unique($this->items, SORT_REGULAR));
        }

        $callback = $this->valueRetriever($keyOrCallback);
        $exists = [];
        return $this->filter(function ($item, $key) use ($callback, $strict, &$exists) {
            $id = $callback($item, $key);
            if (in_array($id, $exists, $strict)) {
                return false;
            }
            $exists[] = $id;
            return true;
        });
    }

    /**
     * Reset the keys on the underlying array.
     *
     * @return static
     */
    public function values()
    {
        return new static(array_values($this->items));
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed $value
     * @param string $operator
     * @return static
     */
    public function where($key, $value, $operator = '==')
    {
        return $this->filter(function ($item) use ($key, $operator, $value) {
            $retrieved = isset($item[$key]) ? $item[$key] : null;
            switch ($operator) {
                default:
                case '=':
                case '==':  return $retrieved == $value;
                case '!=':
                case '<>':  return $retrieved != $value;
                case '<':   return $retrieved < $value;
                case '>':   return $retrieved > $value;
                case '<=':  return $retrieved <= $value;
                case '>=':  return $retrieved >= $value;
                case '===': return $retrieved === $value;
                case '!==': return $retrieved !== $value;
            }
        });
    }

    /**
     * @inheritdoc
     */
    public function whereIn($key, $values, $strict = false)
    {
        $values = $this->getArrayableItems($values);

        return $this->filter(function ($item) use ($key, $values, $strict) {
            return in_array(isset($item[$key]) ? $item[$key] : null, $values, $strict);
        });
    }

    /**
     * @inheritdoc
     */
    public function whereNotIn($key, $values, $strict = false)
    {
        $values = $this->getArrayableItems($values);

        return $this->filter(function ($item) use ($key, $values, $strict) {
            return !in_array(isset($item[$key]) ? $item[$key] : null, $values, $strict);
        });
    }

    /**
     * @inheritdoc
     */
    public function zip($items)
    {
        $arrayableItems = array_map(function ($items) {
            return $this->getArrayableItems($items);
        }, func_get_args());

        $params = array_merge([function () {
            return new static(func_get_args());
        }, $this->items], $arrayableItems);

        return new static(call_user_func_array('array_map', $params));
    }

    /*
     * --------------------------------------------------------------------------------------------------------------
     * Collection Manipulation (Mutable Functions)
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
     * @param  array|string|int|null  $keys
     * @return $this
     */
    public function forget($keys = null)
    {
        if (is_null($keys)) {
            $this->items = [];
            return $this;
        }

        foreach ((array)$keys as $key) {
            unset($this->items[$key]);
        }

        return $this;
    }

    /**
     * Get and remove the last item from the collection.
     *
     * #Notice
     * This method modifies the collection that is called.
     *
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Push an item onto the beginning of the collection.
     *
     * #Notice
     * This method modifies and returns the collection that it called.
     *
     * @param  mixed  $value
     * @param  int|string|null  $key
     * @return $this
     */
    public function prepend($value, $key = null)
    {
        if (is_null($key)) {
            array_unshift($this->items, $value);
        }
        else {
            $this->items = [$key => $value] + $this->items;
        }

        return $this;
    }

    /**
     * Get and remove an item from the collection.
     *
     * #Notice
     * This method modifies the collection it is called on.
     *
     * @param  int|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->forget($key);

        return $value;
    }

    /**
     * Push an item onto the end of the collection.
     *
     * #Notice
     * This method modifies and returns the collection that it called.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function push($value)
    {
        $this->items[] = $value;

        return $this;
    }

    /**
     * Put an item in the collection by key.
     *
     * #Notice
     * This method modifies and returns the collection that it called.
     *
     * @param  int|string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function put($key, $value)
    {
        $this->items[$key] = $value;

        return $this;
    }

    /**
     * Get and remove the first item from the collection.
     *
     * #Notice
     * This method modifies the collection it is called on.
     *
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * Transform each item in the collection using a callback.
     *
     * #Notice
     * This method modifies and returns the collection that it called.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function transform(callable $callback)
    {
        $this->items = $this->map($callback)->all();

        return $this;
    }

    /*
     * --------------------------------------------------------------------------------------------------------------
     * ArrayAccess Implementation
     * --------------------------------------------------------------------------------------------------------------
     */

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        }
        else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

    /*
     * --------------------------------------------------------------------------------------------------------------
     * IteratorAggregate Implementation
     * --------------------------------------------------------------------------------------------------------------
     */

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /*
     * --------------------------------------------------------------------------------------------------------------
     *  JsonSerializable Implementation
     * --------------------------------------------------------------------------------------------------------------
     */

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            }
            elseif ($value instanceof Jsonable) {
                return json_decode($value->toJson(), true);
            }
            elseif ($value instanceof Arrayable) {
                return $value->toArray();
            }
            else {
                return $value;
            }
        }, $this->items);
    }
}