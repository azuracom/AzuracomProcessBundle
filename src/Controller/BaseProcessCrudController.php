<?php

namespace Azuracom\ProcessBundle\Controller;

use Azuracom\ProcessBundle\Handler\HandlerProviderInterface;
use Azuracom\ProcessBundle\Helper\ProcessHelperInterface;
use Azuracom\ProcessBundle\Messenger\ProcessMessage;
use Azuracom\ProcessBundle\Model\Process;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Azuracom\ProcessBundle\Factory\FactoryInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vich\UploaderBundle\Form\Type\VichFileType;

abstract class BaseProcessCrudController extends AbstractCrudController
{
    protected readonly FactoryInterface $processFactory;
    protected readonly ProcessHelperInterface $processHelper;
    protected readonly AdminUrlGenerator $adminUrlGenerator;
    protected readonly ?TranslatorInterface $translator;
    protected readonly ?MessageBusInterface $messageBus;

    #[Required]
    public function setRequiredServies(
        #[Autowire(service: 'azuracom_process.factory.process')]
        FactoryInterface $processFactory,
        ProcessHelperInterface $processHelper,
        AdminUrlGenerator $adminUrlGenerator,
        ?TranslatorInterface $translator = null,
        ?MessageBusInterface $messageBus = null,
    ) {
        $this->processFactory = $processFactory;
        $this->processHelper = $processHelper;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->translator = $translator;
        $this->messageBus = $messageBus;
    }

    public static function getEntityFqcn(): string
    {
        return Process::class;
    }

    public function configureFilters(Filters $filters): Filters
    {

        $statusChoices = [];
        foreach ($this->processHelper->getStatusList() as $statusKey => $statusLabel) {
            $statusChoices[$this->translator?->trans($statusLabel) ?? $statusLabel] = $statusKey;
        }

        return $filters
            ->add(
                ChoiceFilter::new('status', 'Statut')
                    ->setChoices($statusChoices)
            )
            ->add('originalFilename')
            ->add('useMessenger')
            ->add('resolved')
            ->add(DateTimeFilter::new('createdAt', 'Date de création'))
            ->add('user')
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['updatedAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $processType = $this->getContext()->getEntity()?->getInstance()?->getType() ?? $this->getContext()->getRequest()->query->get('type');
        yield IdField::new('id')->hideOnForm();

        yield ChoiceField::new('type', 'Type')
            ->setFormTypeOptions([
                'choices' => array_flip($this->processHelper->getTypeList()),
                'disabled' => true,
            ])
            ->formatValue(function ($value) {
                return $this->processHelper->getTypeLabel($value);
            });

        yield TextField::new('uniqueId', 'ID unique')
            ->onlyOnDetail();

        yield TextField::new('file', 'Fichier')
            ->onlyOnForms()
            ->setFormType(VichFileType::class)
            ->setFormTypeOptions([
                'constraints' => $this->getFileConstraints($processType),
            ]);

        yield TextField::new('originalFilename', 'Nom original')
            ->hideOnForm();

        yield AssociationField::new('user', 'Utilisateur')
            ->hideOnForm();

        yield BooleanField::new('useMessenger', 'Traitement asynchrones')
            ->renderAsSwitch(false);

        yield DateField::new('startedAt', 'Durée')
            ->hideOnForm()
            ->formatValue(function ($value, ProcessInterface $entity) {

                $end = $entity->getEndedAt();
                if (!$end) {
                    return null;
                }

                $duration = $entity->getStartedAt()->diff($end);

                return sprintf(
                    '%02d:%02d:%02d',
                    $duration->h + ($duration->days * 24),
                    $duration->i,
                    $duration->s
                );
            });

        yield BooleanField::new('resolved', 'Résolu')
            ->hideWhenCreating();


        $optionsType = $this->getOptionsFormType($processType);

        if ($optionsType) {
            yield HiddenField::new('options', 'Options')
                ->setFormType($optionsType)
                ->setTemplatePath('@AzuracomProcess/easy_admin/field/options.html.twig')
                ->hideOnIndex()
                ->formatValue(function ($value) use ($optionsType) {
                    return [
                        'value' => $value,
                        'form' => $this->createFormBuilder(['options' => $value])
                            ->add('options', $optionsType)
                            ->getForm()
                            ->createView()
                    ];
                });
        }

        yield DateField::new('startedAt', 'Date de début')
            ->onlyOnDetail()
            ->setFormat('dd/MM/yyyy HH:mm:ss');

        yield DateField::new('endedAt', 'Date de fin')
            ->onlyOnDetail()
            ->setFormat('dd/MM/yyyy HH:mm:ss');

        yield DateField::new('createdAt', 'Date de création')
            ->hideOnForm()
            ->hideOnIndex()
            ->setFormat('dd/MM/yyyy HH:mm:ss');

        yield DateField::new('updatedAt', 'Date de mise à jour')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm:ss');

        yield TextField::new('status', 'Statut')
            ->hideOnForm()
            ->renderAsHtml(true)
            ->formatValue(function ($value, $entity) {
                $label = $this->processHelper->getStatusLabel($value);
                return sprintf(
                    '<span class="badge" style="background-color: %s; color: #fff;">%s</span>',
                    Process::getStatusColorStatic($value),
                    $this->translator ?
                        $this->translator->trans($label) :
                        $this->processHelper->getStatusLabel($label)
                );
            });
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->remove(Crud::PAGE_INDEX, Action::EDIT);
        $actions->remove(Crud::PAGE_DETAIL, Action::EDIT);

        $handleAction = Action::new('handle', 'Traiter', 'mdi:play')
            ->linkToCrudAction('handle')
            ->displayIf(function (ProcessInterface $process) {
                return  !$process->useMessenger() && $process->getStatus() === ProcessInterface::STATUS_NEW;
            });

        $actions->add(Crud::PAGE_INDEX, $handleAction);
        $actions->add(Crud::PAGE_DETAIL, $handleAction);

        $logsAction = Action::new('logs', 'Logs', 'mdi:format-list-bulleted')
            ->linkToCrudAction('logs')
            ->displayIf(function (ProcessInterface $process) {
                return $process->getStartedAt() !== null;
            });

        $actions->add(Crud::PAGE_INDEX, $logsAction);
        $actions->add(Crud::PAGE_DETAIL, $logsAction);

        $downloadAction = Action::new('download', 'Télécharger fichier', 'mdi:download')
            ->displayIf(function (ProcessInterface $process) {
                return $process->getFilename() !== null;
            })
            ->linkToCrudAction('download');

        $actions->add(Crud::PAGE_INDEX, $downloadAction);
        $actions->add(Crud::PAGE_DETAIL, $downloadAction);

        return $actions;
    }



    #[AdminRoute(path: '/handle', name: 'handle_process')]
    public function handle(
        AdminContext $context,
        HandlerProviderInterface $handlerProvider,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        /** @var ProcessInterface $process */
        $process = $context->getEntity()->getInstance();
        $handler = $handlerProvider->getHandler($process);

        if (!$process || $process->getStatus() !== ProcessInterface::STATUS_NEW) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id: %s', $process ? $process->getId() : 'unknown'));
        }

        $handler->handle($process);
        $entityManager->flush();

        $detailsUrl = $this->adminUrlGenerator
            ->setAction(Action::DETAIL)
            ->setEntityId($process->getId())
            ->generateUrl();

        return new RedirectResponse($detailsUrl);
    }

    #[AdminRoute(path: '/logs', name: 'logs_process')]
    public function logs(
        AdminContext $context,
    ): Response {

        /** @var ProcessInterface $process */
        $process = $context->getEntity()->getInstance();

        $this->processHelper->setSubject($process);

        return $this->render('@AzuracomProcess/easy_admin/crud/logs.html.twig', [
            'rows' => $this->processHelper->getLogAsArray(),
            'object' => $process,
        ]);
    }

    // Download action is dependent on the storage, so we inject the filesystem operator directly in the action
    #[AdminRoute(path: '/download', name: 'download_process')]
    public function download(
        AdminContext $context,
        ?FilesystemOperator $processStorage = null,
    ): Response {

        if (!$processStorage) {
            throw new \RuntimeException("No filesystem operator found for process storage");
        }

        /** @var ProcessInterface $process */
        $process = $context->getEntity()->getInstance();
        $path = $process->getFilename();

        // Optionnel : si tu veux un type plus précis
        $mimeType = $processStorage->mimeType($path) ?? 'application/octet-stream';

        $response = new StreamedResponse(function () use ($path, $processStorage) {
            $stream = $processStorage->readStream($path);

            if ($stream === false) {
                // Ici on ne peut pas "return Response", on est dans le callback.
                // On déclenche une exception -> Symfony renverra 500 (à adapter si tu veux 404)
                throw new \RuntimeException('Unable to open stream for download.');
            }

            // Stream vers la sortie HTTP sans charger en mémoire
            stream_copy_to_stream($stream, fopen('php://output', 'wb'));

            if (is_resource($stream)) {
                fclose($stream);
            }
        });

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $process->getOriginalFilename()
        );

        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Disposition', $disposition);

        // Optionnel : taille si dispo (utile pour barre de progression)
        try {
            $response->headers->set('Content-Length', (string) $processStorage->fileSize($path));
        } catch (\Throwable) {
            // ignore si l'adapter ne supporte pas/échoue
        }


        return $response;
    }

    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        /** @var ProcessInterface $entity */
        $entity = $context->getEntity()->getInstance();
        if ($action === Action::NEW && !$entity->useMessenger() && $entity->getStatus() === ProcessInterface::STATUS_NEW) {
            // Redirect to handle
            $url = $this->adminUrlGenerator
                ->setAction('handle')
                ->setEntityId($entity->getId())
                ->generateUrl();

            return new RedirectResponse($url);
        }

        return parent::getRedirectResponseAfterSave($context, $action);
    }

    protected function getOptionsFormType(?string $processType = null): ?string
    {
        return null;
    }

    protected function getFileConstraints(?string $processType = null): array
    {
        return [
            new File(
                maxSize: '2M',
                mimeTypes: [
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/xml',
                    'text/csv',
                    'text/plain',
                ],
            )
        ];
    }

    public function new(AdminContext $context): KeyValueStore|Response
    {
        if (!$context->getRequest()->query->has('type')) {
            return $this->render('@AzuracomProcess/easy_admin/crud/new_choice.html.twig', [
                'choices' => $this->getCreateChoices(),
                'choiceKey' => 'type'
            ]);
        }

        return parent::new($context);
    }

    protected function getCreateChoices(): array
    {
        return [];
    }

    /**
     * @param ProcessInterface $entityInstance
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::persistEntity($entityManager, $entityInstance);
        //Defered process with messenger
        if ($entityInstance->useMessenger()) {
            $entityInstance->setStatus(ProcessInterface::STATUS_WAITING_DEFERRED);
            if (!$this->messageBus) {
                throw new \RuntimeException("No message bus found, try running 'composer require symfony/messenger'");
            }

            $entityManager->flush();
            $this->messageBus->dispatch(new ProcessMessage($entityInstance->getId()));
        }
    }



    public function createEntity(string $entityFqcn): object
    {
        /** @var ProcessInterface $process */
        $process = $this->processFactory->createNew();
        $process->setType($this->getContext()->getRequest()->query->get('type'));
        $process->setUseMessenger(true); //default to async process, but can be override by form
        return $process;
    }
}
