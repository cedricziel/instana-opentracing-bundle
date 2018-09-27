<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CedricZiel\InstanaBundle\Controller;

use OpenTracing\Tracer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TraceableControllerResolver implements ControllerResolverInterface
{
    private $resolver;

    /**
     * @var Tracer
     */
    private $tracer;

    public function __construct(ControllerResolverInterface $resolver, Tracer $tracer)
    {
        $this->resolver = $resolver;
        $this->tracer = $tracer;
    }

    /**
     * {@inheritdoc}
     */
    public function getController(Request $request)
    {
        $e = $this->tracer->startActiveSpan('controller.get_callable');

        $ret = $this->resolver->getController($request);

        $e->getSpan()->log(['event' => 'controller.get_callable']);

        $e->close();

        return $ret;
    }
}
