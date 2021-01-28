<?php

namespace Azuracom\ProcessBundle\EventListener;

use Azuracom\ProcessBundle\Helper\ProcessHelperInterface;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class ProcessSubscriber implements EventSubscriber
{
    /** @var ProcessHelperInterface */
    private $helper;

    public function __construct(ProcessHelperInterface $helper)
    {
        $this->helper = $helper;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postRemove,
        ];
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof ProcessInterface) {
            return;
        }

        $this->helper->setSubject($entity);
        $url = $this->helper->getHandler()->getUrl();
        //remove file when entity is deleted
        try {
            unlink($url);
        } catch (\Exception $e) {
        };
    }
}
