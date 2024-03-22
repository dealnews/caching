<?php

namespace DealNews\Caching;

use DealNews\GetConfig\GetConfig;

/**
 * Factory for creating a caching object.
 *
 * @author      Brian Moon <brianm@caching.com>
 * @copyright   1997-Present DealNews.com, Inc
 */
class Cache {

    /**
     * map of cache types to classes
     *
     * @var        array
     */
    protected const TYPE_MAP = [
        // File as intentionally left out of this list
        'memcache' => Memcached::class,
        'redis' => Redis::class,
    ];

    /**
     * Returns a CacheInterface Object for the given cluster.
     *
     * @param string $cluster The cluster
     *
     * @return CacheInterface the cache interface
     *
     * @throws \LogicException Thrown when the cache type is invalid
     */
    public static function factory(string $cluster, ?GetConfig $config = NULL): CacheInterface {
        $config ??= GetConfig::init();
        $type = $config->get("caching.cache.{$cluster}.type");

        if (empty($type)) {
            // @phan-suppress-next-line PhanUnusedVariableValueOfForeachWithKey
            foreach (self::TYPE_MAP as $type_key => $class) {
                $servers = $config->get("caching.{$type_key}.{$cluster}.servers");
                if (!empty($servers)) {
                    $type = $type_key;

                    break;
                }
            }
        }

        if (empty($type)) {
            throw new \LogicException("Invalid cache cluster {$cluster}", 1);
        }
        if (empty(self::TYPE_MAP[$type])) {
            throw new \LogicException("Invalid cache cluster type {$type}", 2);
        }

        return (self::TYPE_MAP[$type])::init($cluster);
    }
}
