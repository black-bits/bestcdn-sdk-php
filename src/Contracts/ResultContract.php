<?php
namespace BlackBits\BestCdn\Contracts;

use Illuminate\Contracts\Support\Arrayable;

interface ResultContract extends \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, Arrayable
{
    /**
     * Returns a printable representation of the result data
     *
     * @return string
     */
    public function __toString();

    /**
     * Return the result data as array
     *
     * @return array
     */
    public function toArray();

    /**
     * Check if the result data contains a key by name
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasKey($name);

    /**
     * Get a specific key value from the result data
     *
     * @param string $key
     *
     * @return mixed|null The value of the key | NULL if not found
     */
    public function get($key);

};
