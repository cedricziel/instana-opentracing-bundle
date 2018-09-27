<?php
/**
 * Created by IntelliJ IDEA.
 * User: cedricziel
 * Date: 27.09.18
 * Time: 22:36.
 */

namespace CedricZiel\InstanaBundle\Doctrine;

use Doctrine\DBAL\Logging\SQLLogger;
use OpenTracing\Scope;
use OpenTracing\Tracer;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DbalLogger implements SQLLogger
{
    const MAX_STRING_LENGTH = 32;
    const BINARY_DATA_VALUE = '(binary value)';

    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * @var Scope
     */
    private $tracingSpan;

    public function __construct(Tracer $tracer)
    {
        $this->tracer = $tracer;
    }

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->tracingSpan = $this->tracer->startActiveSpan('doctrine');

        $queryParameters = null === $params ? array() : $this->normalizeParams($params);
        $arr = [
            '_query' => $sql,
        ];

        $span = $this->tracingSpan->getSpan();
        $span->setTag(\OpenTracing\Tags\DATABASE_STATEMENT, $sql);

        $span->log(array_merge($arr, $queryParameters));
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
        $this->tracingSpan->close();
    }

    private function normalizeParams(array $params)
    {
        foreach ($params as $index => $param) {
            // normalize recursively
            if (\is_array($param)) {
                $params[$index] = $this->normalizeParams($param);
                continue;
            }

            if (!\is_string($params[$index])) {
                continue;
            }

            // non utf-8 strings break json encoding
            if (!preg_match('//u', $params[$index])) {
                $params[$index] = self::BINARY_DATA_VALUE;
                continue;
            }

            // detect if the too long string must be shorten
            if (self::MAX_STRING_LENGTH < mb_strlen($params[$index], 'UTF-8')) {
                $params[$index] = mb_substr($params[$index], 0, self::MAX_STRING_LENGTH - 6, 'UTF-8').' [...]';
                continue;
            }
        }

        return $params;
    }
}
