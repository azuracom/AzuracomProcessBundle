<?php

namespace Azuracom\ProcessBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * Drops the Process "user" association when no user class is configured. When a user class IS
 * configured, the UserInterface target is resolved through doctrine.orm.resolve_target_entities
 * (registered by AzuracomProcessExtension), so nothing has to be done here.
 * Wired as a Doctrine "loadClassMetadata" listener; its arguments are injected by the extension.
 */
class ORMUserMappingSubscriber
{
    public function __construct(
        private readonly string $processClass,
        private readonly ?string $userClassName = null,
    ) {}

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        if ($this->userClassName) {
            return;
        }

        $metadata = $eventArgs->getClassMetadata();

        if ($metadata->getName() !== $this->processClass) {
            return;
        }

        if (isset($metadata->associationMappings['user'])) {
            unset($metadata->associationMappings['user']);
        }
    }
}
