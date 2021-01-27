<?php

namespace Azuracom\ProcessBundle\Factory;

use Azuracom\ProcessBundle\Model\Process;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProcessFactory
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function createNew($type)
    {
        $process = new Process($type);
        $process->setUser($this->tokenStorage->getToken()->getUser());

        return $process;
    }
}