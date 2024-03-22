<?php

/**
 * Tests for the Memcached Cache interface object.
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 */

namespace DealNews\Caching\Tests;

use DealNews\Caching\Memcached;

/**
 * @internal
 *
 * @coversNothing
 */
class MemcachedTest extends AbstractTestCase {

    public static function setUpBeforeClass(): void {

        // loop and try to connect as the
        // sandbox can take a bit to start up
        $tries = 5;
        for ($x = 1; $x <= $tries; ++$x) {
            $object = new Memcached('test');
            $success = $object->set('setup_test', 1);
            if ($success) {
                break;
            }
            if ($x < $tries) {
                fwrite(STDERR, "Waiting for Memcached to start (try {$x})...\n");
                sleep(5);
            }
        }
    }

    /**
     * @group unit
     */
    public function testKeyFix() {
        $cache = Memcached::init('test');
        $value = $cache->fixKey('badkey'.chr(32));
        $this->assertEquals('badkey_ord32_', $value);

        $value = $cache->fixKey('long key '.str_repeat('x', 320));
        $this->assertEquals('long_ord32_key_ord32_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx_sha129657c827683a98bac548732c683c3c52d777807', $value);
    }

    public function testBadCluster() {
        $this->expectException(\Exception::class);
        $redis = new Memcached('badname');
    }

    public function testInterface() {
        $object = Memcached::init('test');
        $this->interfaceTest($object);
    }

    public function testBadKey() {
        $object = new Memcached('test');
        $this->badKeyTest($object);
    }
}
