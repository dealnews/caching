<?php

namespace DealNews\Caching;

/**
 * CacheInterface based class that uses the filesystem. This is
 * mostly used as a "mock" cache storage in integration tests which
 * use the other cache interfaces. And, it could be useful in some
 * situations.
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 */
class File implements CacheInterface {

    /**
     * Directory where cache files are stored.
     *
     * @var string
     */
    protected $dir = '';

    /**
     * Creates an instance of the caching object using the given cluster.
     *
     * @param string $cluster The name of the cluster to use. Most likely this
     *                        is something stored in an ini file or an
     *                        environment variable.
     */
    public function __construct(string $cluster) {
        $this->dir = sys_get_temp_dir() . "/caching/{$cluster}";
        if (!file_exists($this->dir)) {
            mkdir($this->dir, 0775, true);
        }
    }

    /**
     * Loads a File instance for the desired cluster.
     *
     * @param string $cluster The cluster of servers you want to access
     *
     * @throws \Exception
     *
     * @suppress PhanUndeclaredClassMethod
     */
    public static function init(string $cluster): File {
        static $clusters = [];

        if (empty($clusters[$cluster])) {
            $clusters[$cluster] = new File($cluster);
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
        $return = false;

        $value = $this->get($key);
        if (false === $value) {
            $return = $this->set($key, $var, $expire);
        }

        return $return;
    }

    /**
     * Replace a key and value if the key exists.
     *
     * @param string $key Key to use
     * @param mixed $var Value to store
     * @param int $expire Expiration in seconds from now or unix timestamp
     */
    public function replace(string $key, $var, int $expire = 0): bool {
        $return = false;

        $value = $this->get($key);
        if (false !== $value) {
            $return = $this->set($key, $var, $expire);
        }

        return $return;
    }

    /**
     * Sets a key and value.
     *
     * @param string $key Key to use
     * @param mixed $var Value to store
     * @param int $expire Expiration in seconds from now or unix timestamp
     */
    public function set(string $key, $var, int $expire = 0): bool {
        if (0 !== $expire && $expire < 86500 * 30) {
            $expire = time() + $expire;
        }
        $struct  = [
            'value'   => $var,
            'expires' => $expire,
        ];
        $key     = $this->fixKey($key);
        $success = file_put_contents($this->dir . '/' . $key, serialize($struct));

        return false !== $success;
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
        $current_value = (int)$this->get($key);
        $new_value     = $current_value + $value;
        if ($this->set($key, $new_value)) {
            $current_value = $new_value;
        }

        return $current_value;
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
        return $this->increment($key, $value * -1);
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
            $value = [];
            foreach ($key as $k) {
                $value[$k] = $this->realGet($k);
            }
        } else {
            $value = $this->realGet($key);
        }

        return $value;
    }

    /**
     * Deletes the key from cache.
     *
     * @param string $key Key to use
     */
    public function delete(string $key): bool {
        $key = $this->fixKey($key);
        if (file_exists($this->dir . '/' . $key)) {
            unlink($this->dir . '/' . $key);
        }

        return true;
    }

    /**
     * Prepares a key for use as a file name.
     *
     * @param string $key The key to prepare
     */
    public function fixKey(string $key): string {
        // base64 encode it and replace
        // some characters that could
        // cause issues in a file system
        $new_key = str_replace(
            ['+', '/'],
            ['-', '_'],
            rtrim(
                base64_encode($key),
                '='
            )
        );
        // keep keys to a max of 100 characters
        if (strlen($new_key) > 100) {
            $sha1    = sha1(substr($new_key, 55));
            $new_key = substr($new_key, 0, 55) . '_sha1' . $sha1;
        }

        return $new_key;
    }

    /**
     * Reads the cache file off disk.
     *
     * @param string $key Key to read
     *
     * @return mixed
     */
    protected function realGet(string $key) {
        $key   = $this->fixKey($key);
        $value = false;
        if (file_exists($this->dir . '/' . $key)) {
            $struct = unserialize(file_get_contents($this->dir . '/' . $key));
            if (
                is_array($struct)
                && isset($struct['value'], $struct['expires'])
                && (
                    0 === $struct['expires']
                    || $struct['expires'] > time()
                )
            ) {
                $value = $struct['value'];
            } else {
                unlink($this->dir . '/' . $key);
            }
        }

        return $value;
    }
}
