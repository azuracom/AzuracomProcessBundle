<?php

namespace Azuracom\ProcessBundle;

use Azuracom\ProcessBundle\DependencyInjection\Compiler\ProcessHandlerPass;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AzuracomProcessBundle extends AbstractResourceBundle
{

    public function getSupportedDrivers(): array
    {
        return [
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
        ];
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new ProcessHandlerPass());
    }

    protected function getConfigFilesPath(): string
    {
        $path= sprintf(
            '%s/../config/doctrine/%s',
            $this->getPath(),
            strtolower($this->getDoctrineMappingDirectory()),
        );

        return $path;
    }
}
