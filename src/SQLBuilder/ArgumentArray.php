<?php
namespace SQLBuilder;
use ArrayIterator;
use ArrayAccess;
use SQlBuilder\Bind;
use IteratorAggregate;

class ArgumentArray implements ArrayAccess, IteratorAggregate
{

    /**
     * @var array
     *
     *   {
     *      :name => 'John',
     *      :phone => 'Phone',
     *   }
     *
     */
    protected $args = array();

    /**
     * @var Bind[]
     */
    protected $bindings = array();

    public function getIterator() {
        return new ArrayIterator($this->args);
    }

    public function add(Bind $bind) { 
        $this->bindings[] = $bind;
        $this->args[$bind->getMark()] = $bind->getValue();
    }

    public function getBindings() {
        return $this->bindings;
    }

    public function offsetSet($name, $value)
    {
        $this->args[$name] = $value;
    }
    
    public function offsetExists($name)
    {
        return isset($this->args[ $name ]);
    }
    
    public function offsetGet($name)
    {
        return $this->args[ $name ];
    }
    
    public function offsetUnset($name)
    {
        unset($this->args[$name]);
    }

    public function getArgs()
    {
        return $this->args;
    }
    
    public function toArray() {
        return $this->args;
    }
}



