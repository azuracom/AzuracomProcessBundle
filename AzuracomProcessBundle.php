<?php

namespace Azuracom\ProcessBundle;

use Azuracom\ProcessBundleDependencyInjection\Compiler\ProcessHandlerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AzuracomProcessBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new ProcessHandlerPass());
    }
}
