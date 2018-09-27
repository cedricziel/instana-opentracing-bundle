<?php declare(strict_types=1);

namespace CedricZiel\InstanaBundle\EventListener;

use Instana\OpenTracing\InstanaTracer;
use OpenTracing\Scope;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

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

    public function __construct(LoggerInterface $logger)
    {

        $this->logger = $logger;
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        \OpenTracing\GlobalTracer::set(InstanaTracer::getDefault());

        $this->requestScope = \OpenTracing\GlobalTracer::get()->startActiveSpan('http-request');
        $parentSpan = $this->requestScope->getSpan();
        $parentSpan->setTag(\Instana\OpenTracing\InstanaTags\SERVICE, "example");
        $parentSpan->setTag(\OpenTracing\Tags\COMPONENT, 'PHP simple example app');
        $parentSpan->setTag(\OpenTracing\Tags\SPAN_KIND, \OpenTracing\Tags\SPAN_KIND_RPC_SERVER);
        $parentSpan->setTag(\OpenTracing\Tags\PEER_HOSTNAME, $request->getHost());
        $parentSpan->setTag(\OpenTracing\Tags\HTTP_URL, $request->getPathInfo());
        $parentSpan->setTag(\OpenTracing\Tags\HTTP_METHOD, $request->getMethod());

        $parentSpan->log(['event' => 'bootstrap', 'type' => 'kernel.load', 'waiter.millis' => 1500]);
    }

    public function onKernelTerminate(PostResponseEvent $event): void
    {
        try {
            \OpenTracing\GlobalTracer::get()->flush();
        } catch (\ErrorException $e) {
            // something went wrong.
            $this->logger->error($e->getMessage());
        }
    }

    public function onKernelFinishrequest(FinishRequestEvent $ba)
    {
        $this->requestScope->close();
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {

    }
}
