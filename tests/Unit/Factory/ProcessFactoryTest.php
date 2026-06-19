<?php

namespace Azuracom\ProcessBundle\Tests\Unit\Factory;

use Azuracom\ProcessBundle\Entity\Process;
use Azuracom\ProcessBundle\Factory\ProcessFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ProcessFactoryTest extends TestCase
{
    public function testCreateNewAssignsTheCurrentUser(): void
    {
        $user = $this->createStub(UserInterface::class);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $factory = new ProcessFactory(Process::class, $tokenStorage);
        $process = $factory->createNew();

        $this->assertInstanceOf(Process::class, $process);
        $this->assertSame($user, $process->getUser());
    }

    public function testCreateNewLeavesUserNullWithoutToken(): void
    {
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $factory = new ProcessFactory(Process::class, $tokenStorage);
        $process = $factory->createNew();

        $this->assertNull($process->getUser());
    }
}
