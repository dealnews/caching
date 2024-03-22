<?php

/**
 * Tests for the File Cache interface object.
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 */

namespace DealNews\Caching\Tests;

use DealNews\Caching\File;

/**
 * @internal
 *
 * @coversNothing
 */
class FileTest extends AbstractTestCase {
    /**
     * @group unit
     */
    public function testInterface() {
        $object = File::init('app');
        $this->interfaceTest($object);
        $dir = sys_get_temp_dir().'/caching/app';
        shell_exec("rm -rf {$dir}");
    }

    /**
     * @group unit
     */
    public function testBadKey() {
        $object = new File('app');
        $this->badKeyTest($object);
    }
}
