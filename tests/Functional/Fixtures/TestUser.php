<?php

namespace Azuracom\ProcessBundle\Tests\Functional\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Minimal user entity used to exercise the Process "user" association resolution.
 */
#[ORM\Entity]
#[ORM\Table(name: 'test_user')]
class TestUser implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getUserIdentifier(): string
    {
        return 'test-user';
    }

    /**
     * Kept for compatibility with Symfony < 8 (removed from UserInterface in 8.0).
     */
    public function eraseCredentials(): void
    {
    }
}
