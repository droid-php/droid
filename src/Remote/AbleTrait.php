<?php

namespace Droid\Remote;

/**
 * Provide methods to mark an object as being able (or not) to remotely execute
 * remote droid commands and to determine whether or not the object is enabled.
 */
trait AbleTrait
{
    protected $able = false;
    protected $workingDir = '/tmp';
    protected $droidPrefix;

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

    /**
     * Set the path to the directory from where droid is remotely executed.
     *
     * @param string $path
     */
    public function setWorkingDirectory($path)
    {
        $this->workingDir = rtrim($path, '/\\');
    }

    /**
     * Get the path to the directory from where droid is remotely executed.
     *
     * @return string
     */
    public function getWorkingDirectory()
    {
        return $this->workingDir;
    }

    /**
     * Set the command prefix for remotely executing Droid.
     *
     * @param string $prefix
     */
    public function setDroidCommandPrefix($prefix)
    {
        $this->droidPrefix = $prefix;
    }

    /**
     * Get the command prefix for remotely executing Droid.
     *
     * @return string
     */
    public function getDroidCommandPrefix()
    {
        return $this->droidPrefix;
    }
}
