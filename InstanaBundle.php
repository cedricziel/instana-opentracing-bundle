<?php

namespace CedricZiel\InstanaBundle;

use CedricZiel\InstanaBundle\DependencyInjection\Compiler\DoctrineLoggerPass;
use CedricZiel\InstanaBundle\DependencyInjection\Compiler\OpenTracerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class InstanaBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OpenTracerPass());
        $container->addCompilerPass(new DoctrineLoggerPass());
    }
}
