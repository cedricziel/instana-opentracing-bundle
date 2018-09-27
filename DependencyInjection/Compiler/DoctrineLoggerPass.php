<?php

namespace CedricZiel\InstanaBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineLoggerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('doctrine')) {
            return;
        }

        $doctrineLoggerChain = $container->getDefinition('doctrine.dbal.logger.chain');
        $doctrineLoggerChain->addMethodCall('addLogger', [
            new Reference('CedricZiel\InstanaBundle\Doctrine\DbalLogger'),
        ]);
    }
}
