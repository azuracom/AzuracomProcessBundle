<?php

namespace Azuracom\ProcessBundle\DependencyInjection;

use Azuracom\ProcessBundle\Admin\ProcessAdmin;
use Azuracom\ProcessBundle\Admin\ProcessResourceTagAdmin;
use Azuracom\ProcessBundle\Entity\Process;
use Azuracom\ProcessBundle\Entity\ProcessResourceTag;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Azuracom\ProcessBundle\Model\ProcessResourceTagInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('azuracom_process');
        $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('user_class')->defaultValue(null)->end()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('process')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(Process::class)->cannotBeEmpty()->end()
                                        ->scalarNode('interface')->defaultValue(ProcessInterface::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultNull()->end()
                                        ->scalarNode('factory')->defaultNull()->end()
                                        ->scalarNode('admin')->defaultValue(ProcessAdmin::class)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('process_resource_tag')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(ProcessResourceTag::class)->cannotBeEmpty()->end()
                                        ->scalarNode('interface')->defaultValue(ProcessResourceTagInterface::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultNull()->end()
                                        ->scalarNode('factory')->defaultNull()->end()
                                        ->scalarNode('admin')->defaultValue(ProcessResourceTagAdmin::class)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
