<?php

namespace Azuracom\ProcessBundle\Factory;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

class ProcessFactory implements FactoryInterface
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var string */
    protected $className;

    public function __construct($className, TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
        $this->className = $className;
    }

    public function createNew()
    {
        $process =  new $this->className();
        $process->setUser($this->tokenStorage->getToken()->getUser());
        return $process;
    }

    public function createNewWithType($type)
    {
        $process = $this->createNew();
        $process->setType($type);

        return $process;
    }
}
