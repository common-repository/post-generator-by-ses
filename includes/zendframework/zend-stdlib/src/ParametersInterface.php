<?php
namespace Zend\Stdlib;
use ArrayAccess;
use Countable;
use Serializable;
use Traversable;
interface ParametersInterface extends ArrayAccess, Countable, Serializable, Traversable
{
    public function __construct(array $values = null);
    public function fromArray(array $values);
    public function fromString($string);
    public function toArray();
    public function toString();
    public function get($name, $default = null);
    public function set($name, $value);
}
