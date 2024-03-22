<?php

/**
 * Tests for the Redis Cache interface object.
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 */

namespace DealNews\Caching\Tests;

use DealNews\Caching\Redis;
use DealNews\GetConfig\GetConfig;
use Predis\Connection\ConnectionException;

/**
 * @internal
 *
 * @coversNothing
 */
class RedisTest extends AbstractTestCase {
    public function setUp(): void {
        // loop and try to connect as the
        // sandbox can take a bit to start up
        $tries = 5;
        for ($x = 1; $x <= $tries; ++$x) {
            try {
                $object = new Redis('test');
                $success = $object->set('setup_test', 1);
            } catch (ConnectionException $e) {
                _debug($e->getMessage());
                $success = FALSE;
            }
            if ($success) {
                break;
            }
            if ($x < $tries) {
                fwrite(STDERR, "Waiting for Redis to start (try $x)...\n");
                sleep(5);
            } else {
                $this->assertTrue($success);
            }
        }
    }

    public function testBadCluster() {
        $this->expectException(\Exception::class);
        $redis = new Redis('badname');
    }

    public function testGetOptions() {
        putenv('CACHING_REDIS_TEST2_SERVERS=127.0.0.1');
        putenv('CACHING_REDIS_TEST2_USERNAME=foo');
        putenv('CACHING_REDIS_TEST2_PASSWORD=foo2');
        putenv('CACHING_REDIS_TEST2_PREFIX=bar');
        putenv('CACHING_REDIS_TEST2_REPLICATION=sentinel');

        $redis = new class('test2', new GetConfig()) extends Redis {
            public function getOptions(string $cluster): array {
                return parent::getOptions($cluster);
            }
        };

        $opts = $redis->getOptions('test2');

        $this->assertEquals(
            [
                'exceptions' => FALSE,
                'prefix' => 'bar',
                'replication' => 'sentinel',
                'parameters' => [
                    'username' => 'foo',
                    'password' => 'foo2',
                ],
            ],
            $opts
        );
    }

    public function testInterface() {
        $object = Redis::init('test');
        $this->interfaceTest($object);
    }

    public function testBadKey() {
        $object = new Redis('test');
        $this->badKeyTest($object);
    }
}
