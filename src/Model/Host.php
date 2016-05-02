<?php

namespace Droid\Model;

use RuntimeException;

use Droid\Remote\AbleInterface;
use Droid\Remote\AbleTrait;
use Droid\Remote\SshClientTrait;

class Host implements AbleInterface
{
    private $name;
    private $address;
    private $port;
    private $username;
    private $password;
    private $keyFile;
    private $keyPass;
    private $auth;

    use AbleTrait;
    use SshClientTrait;
    use VariableTrait;

    public function __construct($name)
    {
        $this->name = $name;
        $this->address = $name;
        $this->port = 22;
        $this->username = 'root';
        $this->auth = 'agent';
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }


    public function getPort()
    {
        return $this->port;
    }

    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    public function getAuth()
    {
        return $this->auth;
    }

    public function setAuth($auth)
    {
        $this->auth = $auth;
        return $this;
    }

    public function getKeyFile()
    {
        return $this->keyFile;
    }

    public function setKeyFile($keyFile)
    {
        $this->keyFile = $keyFile;
        return $this;
    }

    public function getKeyPass()
    {
        return $this->keyPass;
    }

    public function setKeyPass($keyPass)
    {
        $this->keyPass = $keyPass;
        return $this;
    }
}
