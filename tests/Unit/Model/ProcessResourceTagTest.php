<?php

namespace Azuracom\ProcessBundle\Tests\Unit\Model;

use Azuracom\ProcessBundle\Entity\Process;
use Azuracom\ProcessBundle\Entity\ProcessResourceTag;
use PHPUnit\Framework\TestCase;

class ProcessResourceTagTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $process = new Process('import');
        $tag = new ProcessResourceTag();

        $tag->setClassName('App\\Entity\\Foo')
            ->setResourceId('42')
            ->setResourceCode('CODE')
            ->setComment('a comment')
            ->setProcess($process);

        $this->assertSame('App\\Entity\\Foo', $tag->getClassName());
        $this->assertSame('42', $tag->getResourceId());
        $this->assertSame('CODE', $tag->getResourceCode());
        $this->assertSame('a comment', $tag->getComment());
        $this->assertSame($process, $tag->getProcess());
        $this->assertNull($tag->getId());
    }

    public function testNullableFields(): void
    {
        $tag = new ProcessResourceTag();
        $tag->setResourceCode(null)->setComment(null);

        $this->assertNull($tag->getResourceCode());
        $this->assertNull($tag->getComment());
    }
}
