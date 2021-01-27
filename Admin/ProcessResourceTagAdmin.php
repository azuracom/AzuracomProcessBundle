<?php

namespace Azuracom\ProcessBundle\Admin;

use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Admin\AbstractAdmin;

class ProcessResourceTagAdmin extends AbstractAdmin
{
    protected $parentAssociationMapping = 'process';

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection
            ->remove('show')
            ->remove('export')
            ->remove('delete')
            ->remove('create')
            ->remove('edit');
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('className', null, ['show_filter' => true, 'label' => 'Tag type'])
            ->add('resourceId', null, ['show_filter' => true, 'label' => 'Tag id'])
            ->add('resourceCode', null, ['show_filter' => true, 'label' => 'Tag code']);
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        unset($this->listModes['mosaic']);

        $listMapper
            ->add('className', null, ['label' => 'Type'])
            ->add('resourceId', null, ['label' => 'Id'])
            ->add('resourceCode', null, ['label' => 'Code'])
            ->add('comment', null, ['label' => 'Info']);
    }
}
