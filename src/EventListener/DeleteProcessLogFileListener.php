<?php

namespace Azuracom\ProcessBundle\EventListener;

use Azuracom\ProcessBundle\Helper\ProcessHelperInterface;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class DeleteProcessLogFileListener
{
    public function __construct(private ProcessHelperInterface $helper) {}

    public function postRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof ProcessInterface) {
            return;
        }

        $this->helper->setSubject($entity);
        // Remove the log from the configured storage and any local working copy.
        $this->helper->deleteLog();
    }
}
