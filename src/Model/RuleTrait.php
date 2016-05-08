<?php

namespace Droid\Model;

trait RuleTrait
{
    protected $rules = [];
    
    public function addRule(Rule $rule)
    {
        $this->rules[] = $rule;
    }
    
    public function getRules()
    {
        return $this->rules;
    }
    
    public function clearRules()
    {
        $this->rules = [];
    }
}
