<?php

namespace Azuracom\ProcessBundle\Tests\Functional;

use Azuracom\ProcessBundle\Entity\Process;
use Azuracom\ProcessBundle\Tests\Functional\Fixtures\TestUser;

class UserMappingTest extends FunctionalTestCase
{
    public function testUserAssociationIsDroppedWhenNoUserClassConfigured(): void
    {
        $this->bootKernel(['user_class' => null]);

        $metadata = $this->kernel->getContainer()
            ->get('azuracom_process.manager.process')
            ->getClassMetadata(Process::class);

        $this->assertFalse($metadata->hasAssociation('user'));
    }

    public function testUserAssociationResolvesToConfiguredUserClass(): void
    {
        $this->bootKernel(['user_class' => TestUser::class]);

        $metadata = $this->kernel->getContainer()
            ->get('azuracom_process.manager.process')
            ->getClassMetadata(Process::class);

        $this->assertTrue($metadata->hasAssociation('user'));
        $this->assertSame(TestUser::class, $metadata->getAssociationTargetClass('user'));
    }
}
