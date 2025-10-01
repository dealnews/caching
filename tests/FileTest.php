<?php

/**
 * Tests for the File Cache interface object.
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 */

namespace DealNews\Caching\Tests;

use DealNews\Caching\File;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 * @coversNothing
 */
#[Group('unit')]
class FileTest extends AbstractTestCase {
    public function testInterface() {
        $object = File::init('app');
        $this->interfaceTest($object);
        $dir = sys_get_temp_dir().'/caching/app';
        shell_exec("rm -rf {$dir}");
    }

    public function testBadKey() {
        $object = new File('app');
        $this->badKeyTest($object);
    }
}
