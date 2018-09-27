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
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TraceableArgumentResolver implements ArgumentResolverInterface
{
    private $resolver;

    private $tracer;

    public function __construct(ArgumentResolverInterface $resolver, Tracer $tracer)
    {
        $this->resolver = $resolver;
        $this->tracer = $tracer;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(Request $request, $controller)
    {
        $e = $this->tracer->startActiveSpan('controller.get_arguments');

        $ret = $this->resolver->getArguments($request, $controller);

        $e->getSpan()->log(['event' => 'controller.get_arguments']);

        $e->close();

        return $ret;
    }
}
