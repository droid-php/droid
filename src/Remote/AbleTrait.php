<?php

namespace Droid\Remote;

/**
 * Provide methods to mark an object as being able (or not) to remotely execute
 * remote droid commands and to determine whether or not the object is enabled.
 */
trait AbleTrait
{
    protected $able = false;

    public function able()
    {
        $this->able = true;
    }

    public function unable()
    {
        $this->able = false;
    }

    public function enabled()
    {
        return $this->able === true;
    }
}