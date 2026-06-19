<?php

namespace Azuracom\ProcessBundle\Model;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Base mapped superclass. Projects may define a concrete entity extending this class; otherwise the
 * bundle's default Azuracom\ProcessBundle\Entity\ProcessResourceTag is used.
 */
#[ORM\MappedSuperclass]
class ProcessResourceTag implements ProcessResourceTagInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    protected ?string $className = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    protected ?string $resourceId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $resourceCode = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $comment = null;

    #[ORM\ManyToOne(targetEntity: ProcessInterface::class, inversedBy: 'resourceTags')]
    #[ORM\JoinColumn(name: 'process_id', referencedColumnName: 'id')]
    protected ?ProcessInterface $process = null;

    public function getClassName(): string
    {
        return $this->className;
    }

    public function setClassName(string $className): ProcessResourceTagInterface
    {
        $this->className = $className;

        return $this;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): ProcessResourceTagInterface
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function getResourceCode(): ?string
    {
        return $this->resourceCode;
    }

    public function setResourceCode(?string $resourceCode): ProcessResourceTagInterface
    {
        $this->resourceCode = $resourceCode;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): ProcessResourceTagInterface
    {
        $this->comment = $comment;

        return $this;
    }


    public function getProcess(): ProcessInterface
    {
        return $this->process;
    }

    public function setProcess(ProcessInterface $process): ProcessResourceTagInterface
    {
        $this->process = $process;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
