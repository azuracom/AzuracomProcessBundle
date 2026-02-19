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
        $url = $this->helper->getHandler()->getUrl();
        //remove file when entity is deleted
        try {
            unlink($url);
        } catch (\Exception $e) {
        };
    }
}
