<?php

/**
 * Tests for Cache interface objects.
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 */

namespace DealNews\Caching\Tests;

use DealNews\Caching\CacheInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase {

    protected function interfaceTest(CacheInterface $object) {
        $key = 'testing_'.uniqid();
        $var = rand(1, 100);

        $object->add($key, $var, 3600);
        $this->assertEquals(
            $var,
            $object->get($key)
        );

        $var = rand(1, 100);
        $object->replace($key, $var);
        $this->assertEquals(
            $var,
            $object->get($key)
        );

        $var = rand(1, 100);
        $object->set($key, $var);
        $this->assertEquals(
            $var,
            $object->get($key)
        );

        $object->increment($key);
        $this->assertEquals(
            $var + 1,
            $object->get($key)
        );

        $object->decrement($key);
        $this->assertEquals(
            $var,
            $object->get($key)
        );

        $object->delete($key);
        $this->assertEquals(
            false,
            $object->get($key)
        );

        $keys = [];
        for ($x = 1; $x <= 10; ++$x) {
            $keys["test_key_{$x}"] = $x;
            $object->set("test_key_{$x}", $x);
        }

        $result = $object->get(array_keys($keys));
        $this->assertEquals($keys, $result);

        $long_key = "{$key}_".str_repeat('X', 500);
        $object->add($long_key, 'long_key');
        $this->assertEquals(
            'long_key',
            $object->get($long_key)
        );
    }

    protected function badKeyTest(CacheInterface $object) {
        $key = 'This is a key with bad chars in it. 😘'.uniqid();
        $result = $object->set($key, $key);

        $this->assertEquals(
            true,
            $result
        );

        $this->assertEquals(
            $key,
            $object->get($key)
        );
    }
}
