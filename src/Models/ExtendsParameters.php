<?php

// This file is auto-generated, don't edit it. Thanks.
namespace AlibabaCloud\Dara\Models;

use AlibabaCloud\Dara\Model;

class ExtendsParameters extends Model {
    public function validate() {}
    public function toMap() {
        $res = [];
        if (null !== $this->headers) {
            $res['headers'] = $this->headers;
        }
        return $res;
    }
    /**
     * @param array $map
     * @return ExtendsParameters
     */
    public static function fromMap($map = []) {
        $model = new self();
        if(isset($map['headers'])){
            $model->headers = $map['headers'];
        }
        return $model;
    }
    public $headers;

}