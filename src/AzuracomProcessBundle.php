<?php

namespace Azuracom\ProcessBundle;

use Azuracom\ProcessBundle\DependencyInjection\Compiler\ProcessHandlerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AzuracomProcessBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ProcessHandlerPass());

        // Doctrine mappings (Model superclasses + default entities) are registered through
        // doctrine.orm.mappings in AzuracomProcessExtension::prepend(), so the default concrete
        // entities can be skipped when a project overrides them.
    }
}
