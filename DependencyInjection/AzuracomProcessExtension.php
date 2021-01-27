<?php

namespace Azuracom\ProcessBundle\DependencyInjection;

use Azuracom\ProcessBundle\Handler\HandlerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class AzuracomProcessExtension extends Extension
{
    public function load(array $config, ContainerBuilder $container)
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $config);
        $container->registerForAutoconfiguration(HandlerInterface::class)
            ->addTag("azuracom_process.handler");
    }
}
