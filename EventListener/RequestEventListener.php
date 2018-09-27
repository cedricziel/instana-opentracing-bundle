<?php

declare(strict_types=1);

namespace CedricZiel\InstanaBundle\EventListener;

use OpenTracing\Scope;
use OpenTracing\Tracer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Opens tracing spans for individual requests and closes them, when the request finishes
 * or can't be finished gracefully.
 */
class RequestEventListener
{
    /**
     * @var Scope
     */
    protected $requestScope;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Tracer
     */
    private $tracer;

    public function __construct(LoggerInterface $logger, Tracer $tracer)
    {
        $this->logger = $logger;
        $this->tracer = $tracer;
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        $this->requestScope = $this->tracer->startActiveSpan('http-request');
        $parentSpan = $this->requestScope->getSpan();
        $parentSpan->setTag(\Instana\OpenTracing\InstanaTags\SERVICE, 'example');
        $parentSpan->setTag(\OpenTracing\Tags\COMPONENT, 'PHP simple example app');
        $parentSpan->setTag(\OpenTracing\Tags\PEER_HOSTNAME, $request->getHost());
        $parentSpan->setTag(\OpenTracing\Tags\PEER_PORT, $request->getPort());
        $parentSpan->setTag(\OpenTracing\Tags\HTTP_URL, $request->getPathInfo());
        $parentSpan->setTag(\OpenTracing\Tags\HTTP_METHOD, $request->getMethod());
    }

    public function onKernelTerminate(PostResponseEvent $event): void
    {
        $this->flushAndCatch();
    }

    protected function flushAndCatch(): void
    {
        try {
            $this->tracer->flush();
        } catch (\ErrorException $e) {
            // something went wrong.
            $this->logger->error($e->getMessage());
        }
    }

    public function onKernelFinishrequest(FinishRequestEvent $ba)
    {
        $this->requestScope->getSpan()->finish();

        $this->flushAndCatch();
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exceptionName = $event->getException()->getCode().' '.$event->getException()->getMessage();
        $this->requestScope->getSpan()->setTag(\OpenTracing\Tags\ERROR, $exceptionName);
        $this->requestScope->close();
    }
}
