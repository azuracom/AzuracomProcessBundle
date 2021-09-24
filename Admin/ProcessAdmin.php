<?php

namespace Azuracom\ProcessBundle\Admin;

use Azuracom\ProcessBundle\Controller\ProcessAdminController;
use Azuracom\ProcessBundle\Form\StatusChoiceType;
use Azuracom\ProcessBundle\Form\TypeChoiceType;
use Azuracom\ProcessBundle\Helper\ProcessHelperInterface;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\DoctrineORMAdminBundle\Filter\ModelAutocompleteFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\Form\Type\DateRangePickerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProcessAdmin extends AbstractAdmin
{
    protected $datagridValues = array(
        '_sort_order' => 'DESC',
        '_sort_by' => 'createdAt',
    );

    /** @var ProcessHelperInterface */
    protected $processHelper;

    /** @var string */
    protected $userClass;
    
    /** @var TokenStorageInterface */
    private $tokenStorage;

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection
            ->remove('show')
            ->remove('export')
            //->remove('delete')
            ->remove('edit')
            ->remove('create')
            ->add('loadLog', $this->getRouterIdParameter() . '/load-log');
    }

    public function configurePersistentParameters(): array
    {
        if (!$this->getRequest()->query->has('type')) {
            return [];
        }

        return [
            'type' => $this->getRequest()->get('type'),
        ];
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
                'format'=> '%H:%I:%S',
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

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('options', HiddenType::class, array('mapped' => false));
    }

    public function createNewInstance(): object
    {
        $process = parent::createNewInstance();
        $user = $this->tokenStorage->getToken()->getUser();
        $process->setUser($user);
        $process->setType($this->getRequest()->get('type'));

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

    public function setTokenStorage(TokenStorageInterface $tokenStorage)
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
}