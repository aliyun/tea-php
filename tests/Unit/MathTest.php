<?php

namespace AlibabaCloud\Dara\Tests;

use AlibabaCloud\Dara\Util\MathUtil;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class MathTest extends TestCase
{

    public function testRandomIsWithinRange()
    {
        for ($i = 0; $i < 100; $i++) {
            $result = MathUtil::random();
            $this->assertGreaterThanOrEqual(0, $result);
            $this->assertLessThan(1, $result);
        }
    }
}