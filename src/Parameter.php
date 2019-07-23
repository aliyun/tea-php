<?php

namespace HttpX\Tea;

use Stringy\Stringy;
use ReflectionObject;
use ReflectionProperty;

/**
 * Class Parameter
 *
 * @package HttpX\Tea
 */
abstract class Parameter
{
    /**
     * @return array
     */
    public function getRealParameters()
    {
        $array      = [];
        $obj        = new ReflectionObject($this);
        $properties = $obj->getProperties();

        /**
         * @var $property ReflectionProperty
         */
        foreach ($properties as $property) {
            $docComment  = $property->getDocComment();
            $key         = (string)Stringy::create($docComment)->between('@real ', "\n");
            $key         = trim($key);
            $value       = $property->getValue($this);
            $array[$key] = $value;
        }

        return $array;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->getRealParameters();
    }
}
