<?php

namespace Azuracom\ProcessBundle\Admin;

use Azuracom\ProcessBundle\Controller\ProcessAdminController;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\DoctrineORMAdminBundle\Filter\ModelAutocompleteFilter;
use Sonata\Form\Type\DateRangePickerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ProcessAdmin extends AbstractAdmin
{
    protected $datagridValues = array(
        '_sort_order' => 'DESC',
        '_sort_by' => 'createdAt',
    );

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection
            ->remove('show')
            ->remove('export')
            //->remove('delete')
            ->remove('edit')
            ->remove('create')
            ->add('loadLog', $this->getRouterIdParameter().'/load-log');
    }

    public function getPersistentParameters()
    {
        $parameters = parent::getPersistentParameters();

        if ($this->request->query->has('type')) {
            $parameters = array_merge($parameters, [
                'type' => $this->request->get('type'),
            ]);
        }
        return $parameters;
    }

    public function getStatusList()
    {
        $statuss = $this->getConfigurationPool()->getContainer()->getParameter("azuracom_process.status");
        $list = [];
        foreach($statuss as $status){
            $list[$status] = $this->getStatusLabel($status);
        }

        return $list;
    }

    public function getStatusLabel($status)
    {
        return str_replace('_',' ',$status);
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id', null, array('show_filter' => true))
            ->add('type', null, array('show_filter' => true), ChoiceType::class, array(
                'choices' => array_flip($this->getTypeList())
            ))
            ->add('status', null, array('show_filter' => true, 'label' => 'Statut'), ChoiceType::class, array(
                'choices' => array_flip($this->getStatusList())
            ))
            ->add(
                'user',
                ModelAutocompleteFilter::class,
                array('show_filter' => true, 'label' => 'Utilisateur'),
                null,
                array(
                    'property'    => 'email',
                )
            )
            ->add('createdAt', 'doctrine_orm_datetime_range', array(
                'show_filter' => true,
                'field_type' => DateRangePickerType::class,
                'label' => 'Date de création',
            ))
            ->add(
                'withFile',
                'doctrine_orm_callback',
                array(
                    'show_filter' => true,
                    'label' => 'Avec fichier',
                    'callback' => function ($queryBuilder, $alias, $field, $value) {
                        if ($value['value'] === null) {
                            return;
                        }

                        $not = $value['value'] ? "NOT" : "";
                        $queryBuilder->andWhere("$alias.fileName IS $not NULL");

                        return true;
                    }
                ),
                ChoiceType::class,
                array('choices' => array('oui' => 1, 'non' => 0))
            )
            ->add(
                'unFinished',
                'doctrine_orm_callback',
                array(
                    'show_filter' => true,
                    'label' => 'Terminé',
                    'callback' => function ($queryBuilder, $alias, $field, $value) {
                        if ($value['value'] === null) {
                            return;
                        }

                        $not = $value['value'] ? "NOT" : "";
                        $queryBuilder->andWhere("$alias.startedAt IS NOT NULL AND $alias.endedAt IS $not NULL");

                        return true;
                    }
                ),
                ChoiceType::class,
                array('choices' => array('oui' => 1, 'non' => 0))
            )
            ->add('resolved', null, array('label' => 'Résolu', 'show_filter' => true))
            ->add('resourceTags.className', null, ['show_filter' => true, 'label' => 'Tag type'])
            ->add('resourceTags.resourceId', null, ['show_filter' => true, 'label' => 'Tag id'])
            ->add('resourceTags.resourceCode', null, ['show_filter' => true, 'label' => 'Tag code']);
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        unset($this->listModes['mosaic']);

        $listMapper
            ->add('id')
            ->add('type', null, array(
                'label' => 'Type',
                'template'=>'@AzuracomProcess/admin/process/list__field_type.html.twig'  
            ))
            ->add('user', 'text', array('label' => 'Utilisateur'))
            ->add('originalFileName', null, array('label' => 'Nom fichier'))
            ->add('createdAt', null, array('label' => 'Date de création', 'format' => 'd/m/Y H:i:s'))
            ->add('executionTime', null, array(
                'label' => "Temps d'exécution",
                'template' => '@AzuracomProcess/admin/process/list__field_exec_time.html.twig'
            ))
            ->add('status', null, array(
                'label' => 'Statut',
                'template'=>'@AzuracomProcess/admin/process/list__field_status.html.twig'                
            ))
            ->add('resolved', null, array('label' => 'Résolu', 'editable' => true))
            ->add('options',null,array(
                'template'=>'@AzuracomProcess/admin/process/list__field_options.html.twig'
            ))
            ->add('_action', null, array(
                'actions' => array(                    
                    'log' => array('template' => '@AzuracomProcess/admin/process/list__action_log.html.twig'),
                    'file' => array('template' => '@AzuracomProcess/admin/process/list__action_file.html.twig'),
                    'tag' => array('template' => '@AzuracomProcess/admin/process/list__action_tag.html.twig'),
                    'delete' => [],
                )
            ));
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('options', HiddenType::class, array('mapped' => false));
    }

    public function getNewInstance()
    {
        $process = parent::getNewInstance();
        $process->setUser($this->getCurrentUser());
        $process->setType($this->getRequest()->get('type'));

        return $process;
    }

    public function configure()
    {
        parent::configure();
        $this->setTemplate('list',"@AzuracomProcess/admin/process/list.html.twig");
        $this->setTemplate('log_list',"@AzuracomProcess/admin/process/log_list.html.twig");
        $this->setBaseControllerName(ProcessAdminController::class);
    }

    public function getTypeList()
    {
        return $this->getConfigurationPool()->getContainer()->getParameter("azuracom_process.types");
    }
}
