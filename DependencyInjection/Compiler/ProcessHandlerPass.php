<?php

namespace Azuracom\ProcessBundleDependencyInjection\Compiler;

use Azuracom\ProcessBundleProcess\HandlerProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ProcessHandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(HandlerProvider::class)) {
            return;
        }

        $definition = $container->findDefinition(HandlerProvider::class);

        $taggedServices = $container->findTaggedServiceIds('azuracom_process.handler');
        $types = [];
        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall('addHandler', [new Reference($id)]);

            $handlerDefinition = $container->findDefinition($id);
            $types[] = [
                'type'=> call_user_func($handlerDefinition->getClass(). '::getType'),
                'label' => call_user_func($handlerDefinition->getClass(). '::getTypeLabel'),
            ];

        }

        $container->setParameter('azuracom_process.types',$types);
    }
}
