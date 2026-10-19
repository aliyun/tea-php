<?php

namespace AlibabaCloud\Dara\Tests\Unit;

use AlibabaCloud\Dara\Parameter;
use PHPUnit\Framework\TestCase;

class ParameterTest extends TestCase
{
    public function testRealParametersAndIterator()
    {
        $parameter = new SampleParameter();
        $this->assertEquals(['user_name' => 'tea'], $parameter->getRealParameters());
        $this->assertEquals(['user_name' => 'tea'], $parameter->toArray());

        $values = [];
        foreach ($parameter as $key => $value) {
            $values[$key] = $value;
        }
        $this->assertEquals(['user_name' => 'tea'], $values);
    }
}

class SampleParameter extends Parameter
{
    /**
     * @real user_name
     */
    public $name = 'tea';
}
