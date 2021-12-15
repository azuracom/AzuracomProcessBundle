<?php

namespace Azuracom\ProcessBundle\Admin;

use Azuracom\ProcessBundle\Controller\ProcessAdminController;
use Azuracom\ProcessBundle\Form\StatusChoiceType;
use Azuracom\ProcessBundle\Form\TypeChoiceType;
use Azuracom\ProcessBundle\Handler\HandlerProviderInterface;
use Azuracom\ProcessBundle\Helper\ProcessHelperInterface;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\DoctrineORMAdminBundle\Filter\ModelAutocompleteFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\Form\Type\DateRangePickerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\Form\Type\ImmutableArrayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints\File;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ProcessAdmin extends AbstractAdmin
{
    /** @var ProcessHelperInterface */
    protected $processHelper;

    /** @var HandlerProviderInterface */
    protected $handlerProvider;

    /** @var string */
    protected $userClass;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'createdAt';
    }

    protected function getAccessMapping(): array
    {
        return [
            'handle' => 'HANDLE'
        ];
    }

    public function getAllowedCreationTypes(): array
    {
        return [];
    }

    public function getDeferTypes(): array
    {
        return [];
    }

    public function getFormOptionsKeys($type): array
    {
        return [];
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $type = $this->getSubject()->getType();
        $formMapper
            ->add('file', VichFileType::class, array(
                'constraints' => $this->getFileConstraints($type)
            ));

        $keys = $this->getFormOptionsKeys($type);
        if (isset($this->getDeferTypes()[$type]) && $this->getDeferTypes()[$type] == 'choice') {
            $keys[] = [ProcessInterface::DEFER_OPTION_NAME, CheckboxType::class, [
                'label' => 'azuracom_process.action.defer',
                'required' => false,
            ]];
        }
        if (count($keys)) {
            $formMapper->add('options', ImmutableArrayType::class, [
                'keys' => $keys,
            ]);
        }
    }

    protected function getFileConstraints($type)
    {
        return [
            new File([
                'maxSize' => '2M',
                'mimeTypes' => [
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    "text/xml"
                ],
            ])
        ];
    }


    public function configurePersistentParameters(): array
    {
        if (preg_match('#create#', $this->getRequest()->get('_route'))) {
            return [
                'type' => $this->getRequest()->get('type'),
            ];
        }
        return [];
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection
            ->remove('show')
            ->remove('export')
            ->remove('edit')
            ->add('loadLog', $this->getRouterIdParameter() . '/load-log')
            ->add('handle', $this->getRouterIdParameter() . '/handle');
        if (count($this->getAllowedCreationTypes()) == 0) {
            $collection->remove('create');
        }
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('id', null, array('show_filter' => true))
            ->add('type', null, array(
                'show_filter' => true,
                'field_type' => TypeChoiceType::class,
            ))
            ->add('status', null, array(
                'show_filter' => true,
                'label' => 'azuracom_process.process.label.status',
                'field_type' => StatusChoiceType::class,
            ));

        if ($this->getUserClass()) {
            $datagridMapper->add('user', ModelAutocompleteFilter::class, array(
                'show_filter' => true,
                'label' => 'azuracom_process.process.label.user',
                'field_options' => [
                    'property' => 'email',
                ]
            ));
        }
        $datagridMapper->add('createdAt', DateRangeFilter::class, array(
            'show_filter' => true,
            'field_type' => DateRangePickerType::class,
            'label' => 'azuracom_process.process.label.created_at',
        ))
            ->add('withFile', CallbackFilter::class, [
                'show_filter' => true,
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => ['oui' => 1, 'non' => 0]
                ],
                'label' => 'azuracom_process.process.label.with_file',
                'callback' => function ($queryBuilder, $alias, $field, FilterData $filterData) {
                    if ($filterData->getValue() === null) {
                        return;
                    }

                    $not = $filterData->getValue() ? "NOT" : "";
                    $queryBuilder->andWhere("$alias.fileName IS $not NULL");

                    return true;
                }

            ])
            ->add('unFinished', CallbackFilter::class, [
                'show_filter' => true,
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => ['oui' => 1, 'non' => 0]
                ],
                'label' => 'azuracom_process.process.label.unfinished',
                'callback' => function ($queryBuilder, $alias, $field, FilterData $filterData) {
                    if ($filterData->getValue() === null) {
                        return;
                    }

                    $not = $filterData->getValue() ? "NOT" : "";
                    $queryBuilder->andWhere("$alias.startedAt IS NOT NULL AND $alias.endedAt IS $not NULL");

                    return true;
                }
            ])
            ->add('resolved', null, array('label' => 'azuracom_process.process.label.resolved', 'show_filter' => true))
            ->add('resourceTags.className', null, ['show_filter' => true, 'label' => 'azuracom_process.resource_tag.label.class_name'])
            ->add('resourceTags.resourceId', null, ['show_filter' => true, 'label' => 'azuracom_process.resource_tag.label.resource_id'])
            ->add('resourceTags.resourceCode', null, ['show_filter' => true, 'label' => 'azuracom_process.resource_tag.label.resource_code']);
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        unset($this->listModes['mosaic']);
        $listMapper
            ->add('id')
            ->add('type', null, array(
                'label' => 'azuracom_process.process.label.type',
                'template' => '@AzuracomProcess/admin/process/list__field_type.html.twig'
            ))
            ->add('user', 'string', array('label' => 'azuracom_process.process.label.user'))
            ->add('originalFileName', null, array('label' => 'azuracom_process.process.label.filename'))
            ->add('createdAt', null, array('label' => 'azuracom_process.process.label.created_at', 'format' => 'd/m/Y H:i:s'))
            ->add('executionDiff', null, array(
                'label' => "azuracom_process.process.label.execution_diff",
                'template' => '@AzuracomProcess/admin/process/list__field_exec_time.html.twig',
                'format' => '%H:%I:%S',
            ))
            ->add('status', null, array(
                'label' => 'azuracom_process.process.label.status',
                'template' => '@AzuracomProcess/admin/process/list__field_status.html.twig'
            ))
            ->add('resolved', null, array('label' => 'azuracom_process.process.label.resolved', 'editable' => true))
            ->add('options', null, array(
                'template' => '@AzuracomProcess/admin/process/list__field_options.html.twig',
                'label' => 'azuracom_process.process.label.options'
            ))
            ->add(ListMapper::NAME_ACTIONS, null, array(
                'actions' => array(
                    'log' => array('template' => '@AzuracomProcess/admin/process/list__action_log.html.twig'),
                    'file' => array('template' => '@AzuracomProcess/admin/process/list__action_file.html.twig'),
                    'tag' => array('template' => '@AzuracomProcess/admin/process/list__action_tag.html.twig'),
                    'delete' => [],
                )
            ));
    }

    public function createNewInstance(): object
    {
        /** @var ProcessInterface */
        $process = parent::createNewInstance();
        $user = $this->tokenStorage->getToken()->getUser();
        $process->setUser($user);
        $type = $this->getRequest()->get('type');
        $process->setType($type);
        if (isset($this->getDeferTypes()[$type]) && $this->getDeferTypes()[$type] == 'force') {
            $process->setStatus(ProcessInterface::STATUS_WAITING_DEFERRED);
        }

        return $process;
    }

    public function configure(): void
    {
        parent::configure();
        $this->setTemplate('list', "@AzuracomProcess/admin/process/list.html.twig");
        $this->setTemplate('log_list', "@AzuracomProcess/admin/process/log_list.html.twig");
        $this->setBaseControllerName(ProcessAdminController::class);
        $this->addChild($this->getConfigurationPool()->getAdminByAdminCode("azuracom_process.admin.process_resource_tag"), 'process');
    }

    public function setTokenStorage($tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;

        return $this;
    }


    /**
     * Get the value of userClass
     */
    public function getUserClass()
    {
        return $this->userClass;
    }

    /**
     * Set the value of userClass
     *
     * @return  self
     */
    public function setUserClass($userClass)
    {
        $this->userClass = $userClass;

        return $this;
    }

    /**
     * Get the value of processHelper
     */
    public function getProcessHelper()
    {
        return $this->processHelper;
    }

    /**
     * Set the value of processHelper
     *
     * @return  self
     */
    public function setProcessHelper($processHelper)
    {
        $this->processHelper = $processHelper;

        return $this;
    }

    /**
     * Get the value of handlerProvider
     */
    public function getHandlerProvider()
    {
        return $this->handlerProvider;
    }

    /**
     * Set the value of handlerProvider
     *
     * @return  self
     */
    public function setHandlerProvider($handlerProvider)
    {
        $this->handlerProvider = $handlerProvider;

        return $this;
    }
}
