<?php

namespace BlackBits\BestCdn\Traits;

/**
 * Implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable and Arrayable interfaces
 *
 * Trait ContainsData
 * @package BlackBits\BestCdn\Traits
 */
trait ContainsData
{
    /**
     * @var array
     */
    private $content = [];

    /*
     * \ArrayAccess
     */

    /**
     * For \ArrayAccess interface
     * @param $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data()[$offset]);
    }

    /**
     * For \ArrayAccess interface
     *
     * This method returns a reference to the variable to allow for indirect
     * array modification (e.g., $foo['bar']['baz'] = 'qux').
     *
     * @param $offset
     *
     * @return mixed|null
     */
    public function & offsetGet($offset)
    {
        if (isset($this->data()[$offset])) {
            return $this->data()[$offset];
        }

        $value = null;
        return $value;
    }

    /**
     * For \ArrayAccess interface
     *
     * @param $offset
     * @param $value
     */
    public function offsetSet($offset, $value)
    {
        $this->data()[$offset] = $value;
    }

    /**
     * For \ArrayAccess interface
     *
     * @param $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data()[$offset]);
    }


    /*
     * \Countable Interface
     */

    /**
     * For \Countable interface
     *
     * @return int
     */
    public function count()
    {
        return count($this->data());
    }


    /**
     * For \IteratorAggregate interface
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data());
    }


    /*
     * \JsonSerializable Interface
     */

    /**
     * For \JsonSerializable interface
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }


    /*
     * Arrayable Contract
     */

    /**
     * For Laravel's Arrayable contract
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data();
    }


    /*
     * Convenience methods
     */

    /**
     * Convenience accessor
     *
     * @return array
     */
    public function data()
    {
        return isset($this->content['data']) ? $this->content['data'] : [];
    }

}