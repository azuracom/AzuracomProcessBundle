<?php

namespace Azuracom\ProcessBundle\Tests\Functional;

use Azuracom\ProcessBundle\Entity\Process;
use Azuracom\ProcessBundle\Factory\ProcessFactory;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Azuracom\ProcessBundle\Tests\Functional\Fixtures\TestUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class BundleIntegrationTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        $this->bootKernel(['user_class' => TestUser::class]);
    }

    public function testBundleServicesAreWired(): void
    {
        $container = $this->kernel->getContainer();

        $this->assertInstanceOf(ProcessFactory::class, $container->get('azuracom_process.factory.process'));
        $this->assertInstanceOf(EntityManagerInterface::class, $container->get('azuracom_process.manager.process'));
        $this->assertTrue($container->has('azuracom_process.repository.process'));
        $this->assertTrue($container->has('azuracom_process.repository.process_resource_tag'));
        $this->assertTrue($container->has('azuracom_process.helper'));
    }

    public function testCommandsAreRegistered(): void
    {
        $application = new Application($this->kernel);
        $names = array_keys($application->all());

        $this->assertContains('azuracom:process:clear', $names);
        $this->assertContains('azuracom:process:defer-handle', $names);
    }

    public function testProcessEntityIsMappedToTheProcessTable(): void
    {
        $metadata = $this->em()->getClassMetadata(Process::class);

        $this->assertSame('process', $metadata->getTableName());
        $this->assertTrue($metadata->hasField('type'));
        $this->assertTrue($metadata->hasField('status'));
        $this->assertTrue($metadata->hasField('createdAt'));
        $this->assertTrue($metadata->hasAssociation('resourceTags'));
    }

    public function testResourceInterfacesResolveToDefaultEntities(): void
    {
        $this->assertSame(Process::class, $this->em()->getClassMetadata(ProcessInterface::class)->getName());
    }

    public function testProcessCanBePersistedAndRetrieved(): void
    {
        $em = $this->em();
        $this->createSchema();

        $process = new Process('import');
        $em->persist($process);
        $em->flush();
        $id = $process->getId();
        $em->clear();

        /** @var Process|null $loaded */
        $loaded = $em->getRepository(Process::class)->find($id);

        $this->assertNotNull($loaded);
        $this->assertSame('import', $loaded->getType());
        $this->assertNotNull($loaded->getUniqueId());
        // Filled by the Gedmo Timestampable listener wired through StofDoctrineExtensionsBundle.
        $this->assertNotNull($loaded->getCreatedAt());
    }

    private function em(): EntityManagerInterface
    {
        return $this->kernel->getContainer()->get('azuracom_process.manager.process');
    }

    private function createSchema(): void
    {
        $em = $this->em();
        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());
    }
}
