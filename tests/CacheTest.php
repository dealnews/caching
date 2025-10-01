<?php

/**
 * Tests for the Cache factory object.
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 */

namespace DealNews\Caching\Tests;

use DealNews\Caching\Cache;
use DealNews\Caching\File;
use DealNews\Caching\Memcached;
use DealNews\Caching\Redis;
use DealNews\GetConfig\GetConfig;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
class CacheTest extends TestCase {
    /**
     * @param mixed      $expect
     * @param mixed      $type
     * @param null|mixed $servers
     * @param mixed      $cluster
     * @param null|mixed $exception
     * @param null|mixed $code
     */
    #[Group('unit')]
    #[DataProvider('factoryData')]
    public function testFactory($expect, $type, $servers = null, $cluster = 'test', $exception = null, $code = null) {
        if (!empty($exception)) {
            $this->expectException($exception);
            $this->expectExceptionCode($code);
        }

        if (!empty($servers)) {
            $base = 'CACHING_'.strtoupper($type).'_'.strtoupper($cluster);
            putenv($base."_SERVERS={$servers}");
        } else {
            putenv("CACHING_CACHE_TEST_TYPE={$type}");
        }

        $client = Cache::factory($cluster, new GetConfig());

        if (empty($exception)) {
            $this->assertTrue($client instanceof $expect, 'Class is '.get_class($client));
        }

        if (!empty($servers)) {
            putenv($base.'_SERVERS=');
        } else {
            putenv('CACHING_CACHE_TEST_TYPE=');
        }
    }

    public static function factoryData() {
        return [
            'Memcache with Type Set' => [
                Memcached::class,
                'memcache',
            ],

            'Redis with Type Set' => [
                Redis::class,
                'redis',
            ],

            'Memcache without Type Set' => [
                Memcached::class,
                'memcache',
                'server',
                'memcache-cluster',
            ],

            'Redis without Type Set' => [
                Redis::class,
                'redis',
                'server',
                'redis-cluster',
            ],

            'Bad Cluster' => [
                File::class,
                '',
                null,
                'foo',
                \LogicException::class,
                1,
            ],

            'Bad Type' => [
                File::class,
                'asdf',
                null,
                'test',
                \LogicException::class,
                2,
            ],
        ];
    }
}
