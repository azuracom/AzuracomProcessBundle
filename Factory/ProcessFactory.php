<?php

namespace Azuracom\ProcessBundle\Factory;

use Azuracom\ProcessBundle\Model\ProcessInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ProcessFactory implements FactoryInterface
{

    /** @var FactoryInterface */
    private $factory;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(FactoryInterface $factory, TokenStorageInterface $tokenStorage)
    {
        $this->factory = $factory;
        $this->tokenStorage = $tokenStorage;
    }

    public function createNew(): ProcessInterface
    {
        /** @var ProcessInterface  */
        $process = $this->factory->createNew();
        $user = $this->tokenStorage->getToken()->getUser() instanceof UserInterface ?
            $this->tokenStorage->getToken()->getUser() :
            null;
        $process->setUser($user);
        return $process;
    }
}
