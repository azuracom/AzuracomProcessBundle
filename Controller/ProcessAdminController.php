<?php

namespace Azuracom\ProcessBundle\Controller;

use Azuracom\ProcessBundle\Helper\ProcessHelperInterface;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProcessAdminController extends CRUDController
{

    public static function getSubscribedServices(): array
    {
        return [
            'azuracom_process.helper' => ProcessHelperInterface::class,
        ] + parent::getSubscribedServices();
    }

    /**
     * @param $id
     */
    public function loadLogAction($id) : Response
    {
        $object = $this->admin->getSubject();

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id: %s', $id));
        }

        $this->admin->checkAccess('show', $object);
        /** @var ProcessHelperInterface */
        $helper = $this->container->get("azuracom_process.helper");
        $helper->setSubject($object);

        $template = $this->admin->getTemplateRegistry()->getTemplate('log_list');

        return $this->renderWithExtraParams($template,[
            'rows'=> $helper->getLogAsArray(),
            'object'=> $object,
        ]);
    }
}