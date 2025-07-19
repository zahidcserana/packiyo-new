<?php

namespace App\Components;

use LogicException;

trait AccessesAttributes
{
    public function __get(string $name)
    {
        $method = 'get' . ucfirst($name) . 'Attribute';

        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            throw new LogicException('There is no accessible attribute named "' . $name . '"');
        }
    }
}
