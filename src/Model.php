<?php

namespace AlibabaCloud\Tea;

class Model
{
    protected $_name     = [];
    protected $_required = [];

    public function __construct($config = [])
    {
        if (!empty($config)) {
            foreach ($config as $k => $v) {
                $this->{$k} = $v;
            }
        }
    }

    public function toMap()
    {
        return get_object_vars($this);
    }

    public function validate()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $k => $v) {
            if (isset($this->_required[$k]) && $this->_required[$k] && empty($v)) {
                throw new \InvalidArgumentException("$k is required.");
            }
        }
    }

    public static function toModel($map, $model)
    {
        foreach ($map as $key => $value) {
            $model->{$key} = $value;
        }
        return $model;
    }
}
