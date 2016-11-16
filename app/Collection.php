<?php
namespace YesWikiRepo;

abstract class Collection implements \ArrayAccess, \Iterator
{
    public $elements;

    public function __construct()
    {
        $this->elements = array();
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->elements[] = $value;
            return;
        }
        $this->elements[$offset] = $value;
    }

    public function offsetExists($offset) {
        return isset($this->elements[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->elements[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->elements[$offset]) ? $this->elements[$offset] : null;
    }

    public function rewind()
    {
        return reset($this->elements);
    }

    public function current()
    {
        return current($this->elements);
    }

    public function key()
    {
        return key($this->elements);
    }

    public function valid()
    {
        return isset($this->elements[$this->key()]);
    }

    public function next()
    {
        return next($this->elements);
    }
}
