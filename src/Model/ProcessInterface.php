<?php

namespace Azuracom\ProcessBundle\Model;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\UserInterface;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query;

interface ProcessInterface
{
    const DEFER_OPTION_NAME = 'defer';

    const STATUS_NEW = "new";
    const STATUS_HAS_WARNING = 'has_warning';
    const STATUS_HAS_ERROR = 'has_error';
    const STATUS_SUCCEDED = 'succeded';
    const STATUS_WAITING_DEFERRED = 'waiting_deferred';
    const STATUS_IN_PROGRESS = 'in_progress';


    public function generateUniqueId(): void;

    public function addRessourceTag(ProcessResourceTagInterface $resourceTag): self;

    public function resetRessourceTags(): self;

    /**
     * @return Collection<int, ProcessResourceTagInterface>
     */
    public function getResourceTags(): Collection;

    public function removeResourceTag(ProcessResourceTagInterface $resourceTag): ProcessInterface;

    public function startProcess(): self;

    public function endProcess(): self;

    public function getStatusColor(): string;

    public static function getStatusColorStatic(string $status): string;

    public function getId(): ?int;

    public function setType(string $type): self;

    public function getType(): string;

    public function setUser(?UserInterface $user = null);

    public function getUser(): ?UserInterface;

    public function setFile(?File $file = null): self;

    public function getFile(): ?File;

    public function setFilename(?string $filename): self;

    public function getFilename(): ?string;

    public function setCreatedAt(?DateTime $createdAt): self;

    public function getCreatedAt(): ?DateTime;

    public function setUpdatedAt(?DateTime $updatedAt): self;

    public function getUpdatedAt(): ?DateTime;

    public function setOptions(array $options): self;

    public function getOptions(): array;

    public function setOption(string $key, mixed $value): self;

    public function getOption(string $key): mixed;

    public function setOriginalFilename(?string $originalFilename = null): self;

    public function getOriginalFilename(): ?string;

    public function getStartedAt(): ?DateTime;

    public function setStartedAt(?DateTime $startedAt): self;

    public function getEndedAt(): ?DateTime;

    public function setEndedAt(?DateTime $endedAt): self;

    public function getStatus(): string;

    public function setStatus(string $status): self;

    public function isResolved(): bool;

    public function setResolved(bool $resolved): self;

    public function getUniqueId(): ?string;

    public function setUniqueId(?string $uniqueId): self;

    /**
     * @return Query[]
     */
    public function getQueries(): array;

    /**
     * @param Query[] $queries
     */
    public function setQueries(array $queries): self;

    public function addQuery(Query $query): self;

    public function removeQuery(Query $query): self;

    public function useMessenger(): bool;

    public function setUseMessenger(bool $useMessenger): self;
}
