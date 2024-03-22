<?php

namespace DealNews\Caching;

use DealNews\GetConfig\GetConfig;
use Predis\Client as RedisClient;

/**
 * CacheInterface based class that uses the Predis\Client class.
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 */
class Redis implements CacheInterface {

    /**
     * GetConfig class
     */
    protected GetConfig $config;

    /**
     * Predis Client
     */
    protected RedisClient $redis;

    /**
     * Creates an instance of the caching object using the given cluster.
     *
     * @param string $cluster The name of the cluster to use. Most likely this
     *                        is something stored in an ini file or an
     *                        environment variable.
     */
    public function __construct(string $cluster, ?GetConfig $config = null) {
        $this->config = $config ?? GetConfig::init();

        $servers = $this->getServerList($cluster);

        $parameters = [];

        $options = $this->getOptions($cluster);

        $auth_params = '';

        if (
            isset($options['replication'])
            && 'sentinel' === $options['replication']
            && isset($options['parameters']['username'], $options['parameters']['password'])
        ) {
            $auth_params = '?'.http_build_query($options['parameters']);
        }

        foreach ($servers as $server) {
            if (false === strpos($server, '://')) {
                $server = "tcp://{$server}";
            }

            $parameters[] = $server.$auth_params;
        }

        if (1 === count($parameters)) {
            $parameters = reset($parameters);
        }

        $this->redis = new RedisClient($parameters, $options);
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
    public static function init(string $cluster): Redis {
        static $clusters = [];

        if (empty($clusters[$cluster])) {
            $clusters[$cluster] = new Redis($cluster);
        }

        return $clusters[$cluster];
    }

    /**
     * Adds a key and value if it does not exist.
     *
     * @param string $key    Key to use
     * @param mixed  $var    Value to store
     * @param int    $expire Expiration in seconds from now or unix timestamp
     */
    public function add(string $key, $var, int $expire = 0): bool {
        $exists = (bool) $this->redis->exists($key);
        if (!$exists) {
            $exists = $this->set($key, $var, $expire);
        }

        return $exists;
    }

    /**
     * Replace a key and value if the key exists.
     *
     * @param string $key    Key to use
     * @param mixed  $var    Value to store
     * @param int    $expire Expiration in seconds from now or unix timestamp
     */
    public function replace(string $key, $var, int $expire = 0): bool {
        $exists = (bool) $this->redis->exists($key);
        if ($exists) {
            $exists = $this->set($key, $var, $expire);
        }

        return $exists;
    }

    /**
     * Sets a key and value.
     *
     * @param string $key    Key to use
     * @param mixed  $var    Value to store
     * @param int    $expire Expiration in seconds from now or unix timestamp
     */
    public function set(string $key, $var, int $expire = 0): bool {
        if ($expire > 0) {
            $response = $this->redis->setex($key, $expire, json_encode($var));
        } else {
            $response = $this->redis->set($key, json_encode($var));
        }

        return 'OK' === (string) $response;
    }

    /**
     * Increments a value by $value.
     *
     * @param string $key   Key to use
     * @param int    $value Value to increment by
     *
     * @return int New value
     */
    public function increment(string $key, int $value = 1): int {
        return $this->redis->incrby($key, $value);
    }

    /**
     * Decrements a value by $value.
     *
     * @param string $key   Key to use
     * @param int    $value Value to decrement by
     *
     * @return int New value
     */
    public function decrement(string $key, int $value = 1): int {
        return $this->redis->decrby($key, $value);
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
            $data = [];
            foreach ($key as $k) {
                $data[$k] = $this->get($k);
            }
        } else {
            $data = json_decode($this->redis->get($key) ?? 'false');
        }

        return $data;
    }

    /**
     * Deletes the key from cache.
     *
     * @param string $key Key to use
     */
    public function delete(string $key): bool {
        return (bool) $this->redis->del($key);
    }

    /**
     * Read a server list for a cluster.
     *
     * @param string $cluster Cluster name
     */
    protected function getServerList(string $cluster): array {
        $servers = $this->config->get("caching.redis.{$cluster}.servers");

        if (empty($servers)) {
            throw new \Exception("Invalid cluster {$cluster} for Redis");
        }

        return explode(',', $servers);
    }

    /**
     * Gets the options from the config.
     *
     * @param string $cluster The cluster name
     *
     * @return array the options
     */
    protected function getOptions(string $cluster): array {
        $options = [
            'exceptions' => false,
        ];

        $possible_options = [
            'aggregate',
            'cluster',
            'parameters',
            'password',
            'prefix',
            'replication',
            'service',
            'username',
        ];

        foreach ($possible_options as $opt) {
            $value = $this->config->get("caching.redis.{$cluster}.{$opt}");
            if (null !== $value) {
                if ('password' === $opt || 'username' === $opt) {
                    $options['parameters'][$opt] = $value;
                } else {
                    $options[$opt] = $value;
                }
            }
        }

        return $options;
    }
}
