<?php

namespace Droid\Test;

/**
 * A base test case which makes an autoloader available as a protected property.
 * The autoloader is obtained from the phpunit bootstrap.
 */
abstract class AutoloaderAwareTestCase extends \PHPUnit_Framework_TestCase
{
    protected $autoloader;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        global $droid_test_autoloader;
        $this->autoloader = $droid_test_autoloader;
        return parent::__construct($name, $data, $dataName);
    }
}
