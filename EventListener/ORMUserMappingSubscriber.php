<?php

namespace Azuracom\ProcessBundle\EventListener;

use Azuracom\ProcessBundle\Model\Process;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Sylius\Bundle\ResourceBundle\EventListener\AbstractDoctrineSubscriber;
use Sylius\Component\Resource\Metadata\RegistryInterface;

class ORMUserMappingSubscriber extends AbstractDoctrineSubscriber
{
    private $userClassName;

    public function __construct(RegistryInterface $resourceRegistry,$userClassName = null)
    {
        parent::__construct($resourceRegistry);
        $this->userClassName = $userClassName;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $metadata = $eventArgs->getClassMetadata();
        if($metadata->getName() == Process::class){
            if(!$this->userClassName){
                unset($metadata->associationMappings['user']);
            }else{
                $metadata->associationMappings['user']['targetEntity'] = $this->userClassName;
            }
        }
    }
}