<?php

namespace Azuracom\ProcessBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ProcessHandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has("azuracom_process.handler_provider")) {
            return;
        }

        $definition = $container->findDefinition("azuracom_process.handler_provider");
        $taggedServices = $container->findTaggedServiceIds('azuracom_process.handler');
        $types = [];
        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall('addHandler', [new Reference($id)]);

            $handlerDefinition = $container->findDefinition($id);

            //add helper to all handler
            if ($container->has('azuracom_process.helper')) {
                $handlerDefinition->addMethodCall('setHelper', [new Reference('azuracom_process.helper')]);
            }

            //set available types in parameter
            foreach(call_user_func($handlerDefinition->getClass() . '::getTypes') as $type){
                $label = call_user_func($handlerDefinition->getClass() . '::getTypeLabel',$type);
                $types[$type] =  $label;
            }
        }

        $container->setParameter('azuracom_process.types', $types);
    }
}
