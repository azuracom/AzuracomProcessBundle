<?php

namespace Azuracom\ProcessBundle\Tests\Unit\Factory;

use Azuracom\ProcessBundle\Entity\ProcessResourceTag;
use Azuracom\ProcessBundle\Factory\ProcessResourceTagFactory;
use PHPUnit\Framework\TestCase;

class ProcessResourceTagFactoryTest extends TestCase
{
    public function testCreateNew(): void
    {
        $factory = new ProcessResourceTagFactory(ProcessResourceTag::class);

        $this->assertInstanceOf(ProcessResourceTag::class, $factory->createNew());
    }

    public function testCreateFromArray(): void
    {
        $factory = new ProcessResourceTagFactory(ProcessResourceTag::class);

        $tag = $factory->createFromArray([
            'ClassName' => 'App\\Entity\\Foo',
            'ResourceId' => '12',
            'Comment' => 'hello',
        ]);

        $this->assertSame('App\\Entity\\Foo', $tag->getClassName());
        $this->assertSame('12', $tag->getResourceId());
        $this->assertSame('hello', $tag->getComment());
    }

    public function testCreateFromResourceCopiesIdCodeAndComment(): void
    {
        $factory = new ProcessResourceTagFactory(ProcessResourceTag::class);

        $resource = new class {
            public function getId(): int
            {
                return 7;
            }

            public function getCode(): string
            {
                return 'C-7';
            }
        };

        $tag = $factory->createFromResource($resource, 'note');

        $this->assertSame('7', $tag->getResourceId());
        $this->assertSame('C-7', $tag->getResourceCode());
        $this->assertSame('note', $tag->getComment());
        $this->assertNotEmpty($tag->getClassName());
    }
}
