<?php

namespace Azuracom\ProcessBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Azuracom\ProcessBundle\Handler\HandlerInterface;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AzuracomProcessExtension extends AbstractResourceExtension
{
    public function load(array $config, ContainerBuilder $container)
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $config);
        
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__) . '/Resources/config'));
        $loader->load('services.yaml');
        $loader->load('admin.yaml');

        $this->registerResources('azuracom_process', $config['driver'], $config['resources'], $container);

        $container->registerForAutoconfiguration(HandlerInterface::class)
            ->addTag("azuracom_process.handler");

        $oClass = new \ReflectionClass(ProcessInterface::class);
        $status = [];
        foreach($oClass->getConstants() as $constName => $value){
            if(preg_match("#^STATUS_#",$constName)){
                $status[] = $value;
            }
        }
        
        $container->setParameter('azuracom_process.status', $status);
    }

    protected function registerResources(
        string $applicationName,
        string $driver,
        array $registeredResources,
        ContainerBuilder $container
    ): void {
        parent::registerResources($applicationName, $driver, $registeredResources, $container);

        foreach ($registeredResources as $resourceName => $resourceConfig) {
            if (!isset($resourceConfig['classes']['admin'])) {
                continue;
            }
            $container->setParameter(sprintf("azuracom_process.admin.%s.class", $resourceName), $resourceConfig['classes']['admin']);
        }
    }
}
