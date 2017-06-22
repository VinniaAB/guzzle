<?php
/**
 * Created by PhpStorm.
 * User: johan
 * Date: 2017-05-24
 * Time: 12:17
 */
declare(strict_types = 1);

namespace Vinnia\Guzzle;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class LogMiddleware
{

    /**
     * @var callable
     */
    private $next;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var callable
     */
    private $bodyFormatter;

    /**
     * GuzzleLogMiddleware constructor.
     * @param callable $next
     * @param LoggerInterface $logger
     */
    public function __construct(callable $next, LoggerInterface $logger)
    {
        $this->next = $next;
        $this->logger = $logger;
        $this->bodyFormatter = function (string $body) {
            return $body;
        };
    }

    /**
     * @param MessageInterface $message
     * @param int $id
     */
    private function log(MessageInterface $message, int $id): void
    {
        $data = [];

        if ($message instanceof RequestInterface) {
            $data[] = sprintf('%s %s HTTP/%s', $message->getMethod(), rawurldecode((string) $message->getUri()), $message->getProtocolVersion());
        }
        else if ($message instanceof ResponseInterface) {
            $data[] = sprintf('HTTP/%s %d %s', $message->getProtocolVersion(), $message->getStatusCode(), $message->getReasonPhrase());
        }

        foreach ($message->getHeaders() as $name => $values) {
            $data[] = sprintf('%s: %s', $name, implode(';', $values));
        }

        $data[] = call_user_func($this->bodyFormatter, (string) $message->getBody());

        $this->logger->debug(implode("\n", $data), [
            'id' => $id,
        ]);
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return mixed
     */
    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        $id = random_int(0, PHP_INT_MAX);
        $this->log($request, $id);
        $logResponse = function (MessageInterface $response) use ($id) {
            $this->log($response, $id);
            return $response;
        };

        $fn = $this->next;
        return $fn($request, $options)->then($logResponse, $logResponse);
    }

    /**
     * @param callable $formatter
     */
    public function setBodyFormatter(callable $formatter)
    {
        $this->bodyFormatter = $formatter;
    }

}
