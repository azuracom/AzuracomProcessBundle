<?php

namespace Azuracom\ProcessBundle\Admin;

use Azuracom\ProcessBundle\Controller\ProcessAdminController;
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

class ProcessAdmin extends AbstractAdmin
{
    protected $datagridValues = array(
        '_sort_order' => 'DESC',
        '_sort_by' => 'createdAt',
    );

    /** @var array */
    protected $typeList = [];

    /** @var array */
    protected $statusList = [];

    /** @var string */
    protected $userClass;

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

    public function getStatusList()
    {
        $list = [];
        foreach ($this->statusList as $status) {
            $list[$status] = $this->getStatusLabel($status);
        }

        return $list;
    }

    public function getStatusLabel($status)
    {
        return str_replace('_', ' ', $status);
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('id', null, array('show_filter' => true))
            ->add('type', null, array(
                'show_filter' => true,
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => array_flip($this->getTypeList())
                ]
            ))
            ->add('status', null, array(
                'show_filter' => true,
                'label' => 'Statut',
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => array_flip($this->getStatusList())
                ]
            ));

        if ($this->getUserClass()) {
            $datagridMapper->add('user', ModelAutocompleteFilter::class, array(
                'show_filter' => true,
                'label' => 'Utilisateur',
                'field_options' => [
                    'property' => 'email',
                ]
            ));
        }
        $datagridMapper->add('createdAt', DateRangeFilter::class, array(
            'show_filter' => true,
            'field_type' => DateRangePickerType::class,
            'label' => 'Date de création',
        ))
            ->add('withFile', CallbackFilter::class, [
                'show_filter' => true,
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => ['oui' => 1, 'non' => 0]
                ],
                'label' => 'Avec fichier',
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
                'label' => 'Terminé',
                'callback' => function ($queryBuilder, $alias, $field, FilterData $filterData) {
                    if ($filterData->getValue() === null) {
                        return;
                    }

                    $not = $filterData->getValue() ? "NOT" : "";
                    $queryBuilder->andWhere("$alias.startedAt IS NOT NULL AND $alias.endedAt IS $not NULL");

                    return true;
                }
            ])
            ->add('resolved', null, array('label' => 'Résolu', 'show_filter' => true))
            ->add('resourceTags.className', null, ['show_filter' => true, 'label' => 'Tag type'])
            ->add('resourceTags.resourceId', null, ['show_filter' => true, 'label' => 'Tag id'])
            ->add('resourceTags.resourceCode', null, ['show_filter' => true, 'label' => 'Tag code']);
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        unset($this->listModes['mosaic']);

        $listMapper
            ->add('id')
            ->add('type', null, array(
                'label' => 'Type',
                'template' => '@AzuracomProcess/admin/process/list__field_type.html.twig'
            ))
            ->add('user', 'string', array('label' => 'Utilisateur'))
            ->add('originalFileName', null, array('label' => 'Nom fichier'))
            ->add('createdAt', null, array('label' => 'Date de création', 'format' => 'd/m/Y H:i:s'))
            ->add('executionDiff', null, array(
                'label' => "Temps d'exécution",
                'template' => '@AzuracomProcess/admin/process/list__field_exec_time.html.twig',
                'format'=> '%H:%I:%S',
            ))
            ->add('status', null, array(
                'label' => 'Statut',
                'template' => '@AzuracomProcess/admin/process/list__field_status.html.twig'
            ))
            ->add('resolved', null, array('label' => 'Résolu', 'editable' => true))
            ->add('options', null, array(
                'template' => '@AzuracomProcess/admin/process/list__field_options.html.twig'
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
        $process->setUser($this->getCurrentUser());
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


    /**
     * Get the value of typeList
     */
    public function getTypeList()
    {
        return $this->typeList;
    }

    /**
     * Set the value of typeList
     *
     * @return  self
     */
    public function setTypeList($typeList)
    {
        $this->typeList = $typeList;

        return $this;
    }

    /**
     * Set the value of statusList
     *
     * @return  self
     */
    public function setStatusList($statusList)
    {
        $this->statusList = $statusList;

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
}
