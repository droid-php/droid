<?php

namespace Droid\Model;

trait VariableTrait
{
    protected $variables = [];
    
    public function setVariable($name, $value)
    {
        $this->variables[$name] = $value;
        return $this;
    }
    
    public function hasVariable($name)
    {
        return isset($this->variables[$name]);
    }
    
    public function getVariable($name)
    {
        if (!$this->hasVariable($name)) {
            throw new RuntimeException("No such variable: " . $name);
        }
        return $this->variables[$name];
    }
    
    public function getVariables()
    {
        return $this->variables;
    }
    
    public function getVariablesAsString()
    {
        $string = '';
        foreach ($this->getVariables() as $name => $value) {
            $string .= ' ' . $name . '=`' . $value . '`';
        }
        return trim($string);
    }
}
