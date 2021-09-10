<?php

namespace Azuracom\ProcessBundle\Admin;

use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Route\RouteCollectionInterface;

class ProcessResourceTagAdmin extends AbstractAdmin
{

    protected function configureRoutes(RouteCollectionInterface $collection) :void
    {
        $collection
            ->remove('show')
            ->remove('export')
            ->remove('delete')
            ->remove('create')
            ->remove('edit');
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper) :void
    {
        $datagridMapper
            ->add('className', null, ['show_filter' => true, 'label' => 'Tag type'])
            ->add('resourceId', null, ['show_filter' => true, 'label' => 'Tag id'])
            ->add('resourceCode', null, ['show_filter' => true, 'label' => 'Tag code']);
    }

    protected function configureListFields(ListMapper $listMapper) : void
    {
        unset($this->listModes['mosaic']);

        $listMapper
            ->add('className', null, ['label' => 'Type'])
            ->add('resourceId', null, ['label' => 'Id'])
            ->add('resourceCode', null, ['label' => 'Code'])
            ->add('comment', null, ['label' => 'Info']);
    }
}
