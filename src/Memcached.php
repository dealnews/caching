<?php

namespace DealNews\Caching;

use DealNews\GetConfig\GetConfig;

/**
 * CacheInterface based class that uses the PECL Memcached class.
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 */
class Memcached implements CacheInterface {

    /**
     * Memcached class
     */
    private \Memcached $memcached;

    /**
     * Creates an instance of the caching object using the given cluster.
     *
     * @param string $cluster The name of the cluster to use. Most likely this
     *                        is something stored in an ini file or an
     *                        environment variable.
     */
    public function __construct(string $cluster) {
        $this->memcached = new \Memcached($cluster);

        if ($this->memcached->isPristine()) {
            $servers = $this->getServerList($cluster);

            $server_list = [];
            foreach ($servers as $server) {
                if (strpos($server, ':')) {
                    [$server, $port] = explode(':', $server);
                } else {
                    $port = 11211;
                }

                $server_list[] = [$server, $port];
            }

            $this->memcached->addServers($server_list);
        }
    }

    /**
     * Loads a Memcache instance for the desired cluster.
     *
     * @param string $cluster The cluster of servers you want to access
     *
     * @throws \Exception
     *
     * @suppress PhanUndeclaredClassMethod
     */
    public static function init(string $cluster): Memcached {
        static $clusters = [];

        if (empty($clusters[$cluster])) {
            $clusters[$cluster] = new Memcached($cluster);
        }

        return $clusters[$cluster];
    }

    /**
     * Adds a key and value if it does not exist.
     *
     * @param string $key Key to use
     * @param mixed $var Value to store
     * @param int $expire Expiration in seconds from now or unix timestamp
     */
    public function add(string $key, $var, int $expire = 0): bool {
        $key = $this->fixKey($key);

        return $this->memcached->add($key, $var, $expire);
    }

    /**
     * Replace a key and value if the key exists.
     *
     * @param string $key Key to use
     * @param mixed $var Value to store
     * @param int $expire Expiration in seconds from now or unix timestamp
     */
    public function replace(string $key, $var, int $expire = 0): bool {
        $key = $this->fixKey($key);

        return $this->memcached->replace($key, $var, $expire);
    }

    /**
     * Sets a key and value.
     *
     * @param string $key Key to use
     * @param mixed $var Value to store
     * @param int $expire Expiration in seconds from now or unix timestamp
     */
    public function set(string $key, $var, int $expire = 0): bool {
        $key = $this->fixKey($key);

        return $this->memcached->set($key, $var, $expire);
    }

    /**
     * Increments a value by $value.
     *
     * @param string $key Key to use
     * @param int $value Value to increment by
     *
     * @return int New value
     */
    public function increment(string $key, int $value = 1): int {
        $key = $this->fixKey($key);

        return $this->memcached->increment($key, $value);
    }

    /**
     * Decrements a value by $value.
     *
     * @param string $key Key to use
     * @param int $value Value to decrement by
     *
     * @return int New value
     */
    public function decrement(string $key, int $value = 1): int {
        $key = $this->fixKey($key);

        return $this->memcached->decrement($key, $value);
    }

    /**
     * Gets a value for $key.
     *
     * @param mixed $key A single key or array of keys to get from the cache
     *
     * @return mixed Boolean false on failure. Otherwise, the object
     *               or array of objects if array of keys provided
     */
    public function get($key) {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $key[$k] = $this->fixKey($v);
            }

            return $this->memcached->getMulti($key);
        }
        $key = $this->fixKey($key);

        return $this->memcached->get($key);
    }

    /**
     * Deletes the key from cache.
     *
     * @param string $key Key to use
     */
    public function delete(string $key): bool {
        $key = $this->fixKey($key);

        return $this->memcached->delete($key);
    }

    /**
     * Prepares a key for use in memcached.
     *
     * Per the memcached spec:
     *     A key can be up to 250 bytes. It may not contain:
     *     null (0x00)
     *     space (0x20)
     *     tab (0x09)
     *     newline (0x0a)
     *     carriage-return (0x0d)
     *
     * @param string $key The key to prepare
     */
    public function fixKey(string $key): string {
        $new_key = $key;

        // replace non-visible ascii characters with the characters ordinal
        if (preg_match_all('#[^\x21-\x7E]#', $new_key, $matches)) {
            foreach ($matches[0] as $non_vis_char) {
                $new_key = str_replace($non_vis_char, '_ord' . ord($non_vis_char) . '_', $new_key);
            }
        }

        if (strlen($new_key) > 250) {
            $sha1    = sha1(substr($new_key, 205));
            $new_key = substr($new_key, 0, 205) . '_sha1' . $sha1;
        }

        return $new_key;
    }

    /**
     * Read a server list for a cluster.
     *
     * @param string $cluster Cluster name
     */
    protected function getServerList(string $cluster): array {
        $servers = GetConfig::init()->get("caching.memcache.{$cluster}.servers");

        if (empty($servers)) {
            throw new \Exception("Invalid cluster {$cluster} for Memcache");
        }

        return explode(',', $servers);
    }
}
