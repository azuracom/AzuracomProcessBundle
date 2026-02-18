<?php

namespace Azuracom\ProcessBundle\DependencyInjection;

use Azuracom\ProcessBundle\EventListener\ORMUserMappingSubscriber;
use Symfony\Component\Config\FileLocator;
use Azuracom\ProcessBundle\Handler\HandlerInterface;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AzuracomProcessExtension extends AbstractResourceExtension
{
    public function load(array $config, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $config);
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__) . '/../config'));
        $loader->load('services.yaml');


        if (class_exists(\Sonata\AdminBundle\Controller\CRUDController::class)) {
            $loader->load('sonata_admin.yaml');

            //setUserClass to process admin
            $definition = $container->getDefinition("azuracom_process.admin.process");
            $definition->addMethodCall('setUserClass', [$config['user_class']]);
        }

        //register sylius resource
        $this->registerResources('azuracom_process', $config['driver'], $config['resources'], $container);

        //add tag to all class that implements HandlerInterface
        $container->registerForAutoconfiguration(HandlerInterface::class)
            ->addTag("azuracom_process.handler");

        //add status in parameter
        $oClass = new \ReflectionClass(ProcessInterface::class);
        $status = [];
        foreach ($oClass->getConstants() as $constName => $value) {
            if (preg_match("#^STATUS_#", $constName)) {
                $status[$value] = sprintf("azuracom_process.process.status.%s", $value);
            }
        }

        $container->setParameter('azuracom_process.status', $status);


        //set ORMUserMappingSubscriber userClassName argument
        if ($container->has(ORMUserMappingSubscriber::class)) {
            $definition = $container->getDefinition(ORMUserMappingSubscriber::class);
            $definition->replaceArgument(1, $config['user_class']);
        }
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
