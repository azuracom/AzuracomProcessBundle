<?php

namespace Azuracom\ProcessBundle\Controller;

use Azuracom\ProcessBundle\Admin\ProcessAdmin;
use Azuracom\ProcessBundle\Handler\HandlerProviderInterface;
use Azuracom\ProcessBundle\Helper\ProcessHelperInterface;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property ProcessAdmin $admin
 */
class ProcessAdminController extends CRUDController
{

    public static function getSubscribedServices(): array
    {
        return [
            'azuracom_process.helper' => ProcessHelperInterface::class,
        ] + parent::getSubscribedServices();
    }

    public function createAction(Request $request): Response
    {
        if (!$request->query->has('type')) {
            foreach ($this->admin->getAllowedCreationTypes() as $type) {
                $types[$type] = $this->admin->getProcessHelper()->getTypeLabel($type);
            }

            return $this->renderWithExtraParams('@AzuracomProcess/admin/process/create_choice.html.twig', [
                'action' => 'edit',
                'object' => null,
                'types' => $types,
                'objectId' => null,

            ]);
        }
        return parent::createAction($request);
    }

    protected function redirectTo(Request $request, object $object): RedirectResponse
    {
        if ($request->get('_route') === "admin_azuracom_process_process_create" && $object->getStatus() === ProcessInterface::STATUS_NEW) {
            return $this->redirect($this->admin->generateObjectUrl("handle", $object));
        }

        return parent::redirectTo($request, $object);
    }

    public function handleAction(
        $id,
        HandlerProviderInterface $handlerProvider,
        EntityManagerInterface $em
    ): Response {
        $object = $this->admin->getSubject();
        $this->admin->checkAccess('handle', $object);

        if (!$object || $object->getStatus() !== ProcessInterface::STATUS_NEW) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id: %s', $id));
        }

        $handler = $handlerProvider->getHandler($object);
        $handler->handle();

        $em->flush();

        return new RedirectResponse($this->admin->generateUrl('list', ['id' => $object->getId()]));
    }

    /**
     * @param $id
     */
    public function loadLogAction($id): Response
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

        return $this->renderWithExtraParams($template, [
            'rows' => $helper->getLogAsArray(),
            'object' => $object,
        ]);
    }

    /**
     * @param $id
     */
    public function exportLogAction($id): Response
    {
        $object = $this->admin->getSubject();

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id: %s', $id));
        }

        $this->admin->checkAccess('show', $object);
        /** @var ProcessHelperInterface */
        $helper = $this->container->get("azuracom_process.helper");
        $helper->setSubject($object);


        return new StreamedResponse(function () use ($helper) {
            $handle = fopen('php://output', 'w+');
            foreach ($helper->getLogAsArray() as $row) {
                $values = array_map(function ($value) {
                    return is_array($value) ? json_encode($value) : $value;
                }, array_values($row));
                fputcsv($handle, $values, ";", '"');
            }
            fclose($handle);

        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="log.csv"',
        ]);
    }
}
