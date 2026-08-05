<?php

namespace Azuracom\ProcessBundle\Model;

use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute as Vich;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Process
 *
 * Base mapped superclass. Projects may define a concrete entity extending this class to add
 * their own fields; otherwise the bundle's default Azuracom\ProcessBundle\Entity\Process is used.
 *
 * Abstract on purpose: a mapped superclass must never be instantiated. Doctrine would happily
 * persist such an instance (it still gets an id generator, and the naming strategy resolves the
 * table to "process", the very table of the concrete entity), but the Gedmo listeners bail out on
 * mapped superclasses, so createdAt/updatedAt and every other behavior would stay silently unset.
 * Always instantiate the concrete entity, or better, use the azuracom_process.factory.process
 * service, which builds the class configured in azuracom_process.resources.process.classes.model.
 */
#[ORM\MappedSuperclass]
#[Vich\Uploadable]
abstract class Process implements ProcessInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    protected ?string $type = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $uniqueId = null;

    #[Vich\UploadableField(mapping:"process", fileNameProperty:"filename")]
    protected ?File $file = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $originalFilename = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $filename = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTime $endedAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $status = self::STATUS_NEW;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $resolved = true;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected ?array $options = [];

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    protected ?UserInterface $user = null;

    /** @var Collection<int, ProcessResourceTagInterface> */
    #[ORM\OneToMany(targetEntity: ProcessResourceTagInterface::class, mappedBy: 'process', orphanRemoval: true, cascade: ['persist'])]
    protected Collection $resourceTags;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'create')]
    protected ?\DateTime $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    protected ?\DateTime $updatedAt = null;

    /** @var Query[] */
    protected array $queries = [];

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $useMessenger = false;


    public function __construct(?string $type = null, bool $autoStart = true)
    {
        $this->resourceTags = new ArrayCollection();
        $this->type = $type;
        $this->generateUniqueId();
        if ($autoStart) {
            $this->startProcess();
        }
    }

    public function __toString(): string
    {
        return $this->id ? $this->getType() . " - " . $this->id : "Nouveau processus";
    }

    public function getExecutionDiff(): ?\DateInterval
    {
        if (!$this->startedAt || !$this->endedAt) {
            return null;
        }

        return $this->startedAt->diff($this->endedAt);
    }

    public function generateUniqueId(): void
    {
        $this->uniqueId = uniqid();
    }


    public function startProcess(): ProcessInterface
    {
        $this->startedAt = new \DateTime();
        $this->resetRessourceTags();

        return $this;
    }

    public function endProcess(): ProcessInterface
    {
        if ($this->status == self::STATUS_NEW || $this->status == self::STATUS_IN_PROGRESS) {
            $this->status = self::STATUS_SUCCEDED;
        }

        $this->endedAt = new \DateTime();

        return $this;
    }

    public function resetRessourceTags(): ProcessInterface
    {
        $this->resourceTags = new ArrayCollection();

        return $this;
    }

    public function getResourceTags(): Collection
    {
        return $this->resourceTags;
    }

    public function addRessourceTag(ProcessResourceTagInterface $resourceTag): ProcessInterface
    {
        $this->resourceTags->add($resourceTag);

        return $this;
    }

    public function removeResourceTag(ProcessResourceTagInterface $resourceTag): ProcessInterface
    {
        $this->resourceTags->removeElement($resourceTag);

        return $this;
    }

    public function getStatusColor(): string
    {
        return self::getStatusColorStatic($this->status);
    }

    public static function getStatusColorStatic(string $status): string
    {
        $status = strtolower($status);
        if (preg_match("#warning#", $status)) {
            return '#ffc107';
        }

        if (preg_match("#error#", $status)) {
            return '#dc3545';
        }

        if (preg_match("#succeded|notice#", $status)) {
            return '#28a745';
        }

        return '#6c757d';
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function setType(string $type): ProcessInterface
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }


    public function setUser(?UserInterface $user = null): ProcessInterface
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     */
    public function setFile(?File $file = null): ProcessInterface
    {
        $this->file = $file;

        if (null !== $file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime();

            if ($file instanceof UploadedFile) {
                $this->originalFilename = $file->getClientOriginalName();
            }
        }

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFilename(?string $filename): ProcessInterface
    {
        $this->filename = $filename;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }


    public function setCreatedAt(?\DateTime $createdAt): ProcessInterface
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): ProcessInterface
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setOptions(array $options): ProcessInterface
    {
        $this->options = $options;
        $deferOption = isset($options[self::DEFER_OPTION_NAME]) ? $options[self::DEFER_OPTION_NAME] : null;
        if ($deferOption && $this->status == self::STATUS_NEW) {
            $this->status = self::STATUS_WAITING_DEFERRED;
        }

        return $this;
    }

    public function getOptions(): array
    {
        if (!is_array($this->options)) {
            $this->options = [];
        }

        return $this->options;
    }

    public function setOption(string $key, mixed $value): ProcessInterface
    {
        if (is_string($key) && is_string($value)) {
            $this->options[$key] = $value;
        }

        return $this;
    }

    public function getOption(string $key): mixed
    {
        if (isset($this->getOptions()[$key])) {
            return $this->getOptions()[$key];
        }

        return null;
    }

    public function setOriginalFilename(?string $originalFilename = null): ProcessInterface
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function getStartedAt(): ?\DateTime
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTime $startedAt): ProcessInterface
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt(): ?\DateTime
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTime $endedAt): ProcessInterface
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): ProcessInterface
    {
        $this->status = $status;

        return $this;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function setResolved(bool $resolved): ProcessInterface
    {
        $this->resolved = $resolved;

        return $this;
    }

    public function getUniqueId(): ?string
    {
        return $this->uniqueId;
    }

    public function setUniqueId(?string $uniqueId): ProcessInterface
    {
        $this->uniqueId = $uniqueId;

        return $this;
    }

    /**
     * @return Query[]
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * @param Query[] $queries
     */
    public function setQueries(array $queries): ProcessInterface
    {
        $this->queries = $queries;

        return $this;
    }

    public function addQuery(Query $query): ProcessInterface
    {
        $this->queries[] = $query;

        return $this;
    }

    public function removeQuery(Query $query): ProcessInterface
    {
        foreach ($this->queries as $key => $tmp) {
            if ($tmp === $query) {
                unset($this->queries[$key]);
                break;
            }
        }

        return $this;
    }

    public function useMessenger(): bool
    {
        return $this->useMessenger;
    }

    public function setUseMessenger(bool $useMessenger): ProcessInterface
    {
        $this->useMessenger = $useMessenger;

        return $this;
    }
}
