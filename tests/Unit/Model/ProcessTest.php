<?php

namespace Azuracom\ProcessBundle\Tests\Unit\Model;

use Azuracom\ProcessBundle\Entity\Process;
use Azuracom\ProcessBundle\Entity\ProcessResourceTag;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Doctrine\ORM\Query;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{
    public function testConstructorSetsTypeUniqueIdAndAutoStarts(): void
    {
        $process = new Process('import');

        $this->assertSame('import', $process->getType());
        $this->assertNotNull($process->getUniqueId());
        $this->assertNotNull($process->getStartedAt());
        $this->assertCount(0, $process->getResourceTags());
        $this->assertSame(ProcessInterface::STATUS_NEW, $process->getStatus());
    }

    public function testConstructorWithoutAutoStartDoesNotStart(): void
    {
        $process = new Process('import', false);

        $this->assertNull($process->getStartedAt());
    }

    public function testGenerateUniqueIdProducesAValue(): void
    {
        $process = new Process();
        $first = $process->getUniqueId();
        $process->generateUniqueId();

        $this->assertNotNull($first);
        $this->assertNotNull($process->getUniqueId());
    }

    public function testEndProcessMarksSucceededAndSetsEndedAt(): void
    {
        $process = new Process('import');
        $process->endProcess();

        $this->assertSame(ProcessInterface::STATUS_SUCCEDED, $process->getStatus());
        $this->assertNotNull($process->getEndedAt());
    }

    public function testEndProcessKeepsAnAlreadyResolvedStatus(): void
    {
        $process = new Process('import');
        $process->setStatus(ProcessInterface::STATUS_HAS_ERROR);
        $process->endProcess();

        $this->assertSame(ProcessInterface::STATUS_HAS_ERROR, $process->getStatus());
    }

    #[DataProvider('statusColorProvider')]
    public function testStatusColor(string $status, string $expectedColor): void
    {
        $this->assertSame($expectedColor, Process::getStatusColorStatic($status));
    }

    public static function statusColorProvider(): array
    {
        return [
            'warning' => [ProcessInterface::STATUS_HAS_WARNING, '#ffc107'],
            'error' => [ProcessInterface::STATUS_HAS_ERROR, '#dc3545'],
            'succeeded' => [ProcessInterface::STATUS_SUCCEDED, '#28a745'],
            'default' => [ProcessInterface::STATUS_NEW, '#6c757d'],
        ];
    }

    public function testSetOptionsWithDeferSwitchesToWaitingDeferred(): void
    {
        $process = new Process('import');
        $process->setOptions([ProcessInterface::DEFER_OPTION_NAME => true]);

        $this->assertSame(ProcessInterface::STATUS_WAITING_DEFERRED, $process->getStatus());
    }

    public function testSetOptionsWithoutDeferKeepsStatus(): void
    {
        $process = new Process('import');
        $process->setOptions(['foo' => 'bar']);

        $this->assertSame(ProcessInterface::STATUS_NEW, $process->getStatus());
        $this->assertSame(['foo' => 'bar'], $process->getOptions());
    }

    public function testSetAndGetOption(): void
    {
        $process = new Process();
        $process->setOption('key', 'value');

        $this->assertSame('value', $process->getOption('key'));
        $this->assertNull($process->getOption('missing'));
    }

    public function testSetOptionIgnoresNonStringValue(): void
    {
        $process = new Process();
        $process->setOption('key', 123);

        $this->assertNull($process->getOption('key'));
    }

    public function testResourceTagsCanBeAddedRemovedAndReset(): void
    {
        $process = new Process('import');
        $tag = new ProcessResourceTag();

        $process->addRessourceTag($tag);
        $this->assertCount(1, $process->getResourceTags());

        $process->removeResourceTag($tag);
        $this->assertCount(0, $process->getResourceTags());

        $process->addRessourceTag($tag);
        $process->resetRessourceTags();
        $this->assertCount(0, $process->getResourceTags());
    }

    public function testQueriesCanBeAddedAndRemoved(): void
    {
        $process = new Process();
        $query = $this->createStub(Query::class);

        $process->addQuery($query);
        $this->assertSame([$query], $process->getQueries());

        $process->removeQuery($query);
        $this->assertSame([], $process->getQueries());
    }

    public function testUseMessenger(): void
    {
        $process = new Process();
        $this->assertFalse($process->useMessenger());

        $process->setUseMessenger(true);
        $this->assertTrue($process->useMessenger());
    }

    public function testGetExecutionDiffIsNullUntilEnded(): void
    {
        $process = new Process('import');
        $this->assertNull($process->getExecutionDiff());

        $process->endProcess();
        $this->assertInstanceOf(\DateInterval::class, $process->getExecutionDiff());
    }
}
