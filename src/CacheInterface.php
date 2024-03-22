<?php

namespace DealNews\Caching;

/**
 * Standard Memcached style Interface for caching
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 */
interface CacheInterface {

    /**
     * Creates an instance of the caching object using the given cluster.
     *
     * @param string $cluster The name of the cluster to use. Most likely this
     *                        is something stored in an ini file or an
     *                        environment variable.
     */
    public function __construct(string $cluster);

    /**
     * Adds a key and value if it does not exist.
     *
     * @param string $key    Key to use
     * @param mixed  $var    Value to store
     * @param int    $expire Expiration in seconds from now or unix timestamp
     */
    public function add(string $key, $var, int $expire = 0): bool;

    /**
     * Replace a key and value if the key exists.
     *
     * @param string $key    Key to use
     * @param mixed  $var    Value to store
     * @param int    $expire Expiration in seconds from now or unix timestamp
     */
    public function replace(string $key, $var, int $expire = 0): bool;

    /**
     * Sets a key and value.
     *
     * @param string $key    Key to use
     * @param mixed  $var    Value to store
     * @param int    $expire Expiration in seconds from now or unix timestamp
     */
    public function set(string $key, $var, int $expire = 0): bool;

    /**
     * Increments a value by $value.
     *
     * @param string $key   Key to use
     * @param int    $value Value to increment by
     *
     * @return int New value
     */
    public function increment(string $key, int $value = 1): int;

    /**
     * Decrements a value by $value.
     *
     * @param string $key   Key to use
     * @param int    $value Value to decrement by
     *
     * @return int New value
     */
    public function decrement(string $key, int $value = 1): int;

    /**
     * Gets a value for $key.
     *
     * @param mixed $key A single key or array of keys to get from the cache
     *
     * @return mixed Boolean false on failure. Otherwise, the object
     *               or array of objects if array of keys provided
     */
    public function get($key);

    /**
     * Deletes the key from cache.
     *
     * @param string $key Key to use
     */
    public function delete(string $key): bool;
}
