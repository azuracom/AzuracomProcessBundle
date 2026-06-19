<?php

namespace Azuracom\ProcessBundle\Factory;

use Azuracom\ProcessBundle\Model\ProcessInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ProcessFactory implements FactoryInterface
{
    public function __construct(
        private readonly string $processClass,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function createNew(): ProcessInterface
    {
        /** @var ProcessInterface $process */
        $process = new $this->processClass();

        $user = $this->tokenStorage->getToken() && $this->tokenStorage->getToken()->getUser() instanceof UserInterface ?
            $this->tokenStorage->getToken()->getUser() :
            null;
        $process->setUser($user);

        return $process;
    }
}
