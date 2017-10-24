<?php
/**
 * Created by PhpStorm.
 * User: johan
 * Date: 2017-05-24
 * Time: 12:17
 */
declare(strict_types = 1);

namespace Vinnia\Guzzle;

use function foo\func;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use function GuzzleHttp\Promise\rejection_for;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Closure;
use Psr\Log\LogLevel;

class LogMiddleware
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var mixed
     */
    private $level;

    /**
     * @var callable
     */
    private $bodyFormatter;

    /**
     * GuzzleLogMiddleware constructor.
     * @param LoggerInterface $logger
     * @param mixed $level
     */
    public function __construct(LoggerInterface $logger, $level = LogLevel::DEBUG)
    {
        $this->logger = $logger;
        $this->level = $level;
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

        $this->logger->log($this->level, implode("\n", $data), [
            'id' => $id,
        ]);
    }

    public function __invoke(callable $next): Closure
    {
        return function (RequestInterface $request, array $options) use ($next): PromiseInterface {
            $id = random_int(0, PHP_INT_MAX);
            $this->log($request, $id);
            return $next($request, $options)->then(function (MessageInterface $message) use ($id) {
                $this->log($message, $id);
                return $message;
            }, function ($error) use ($id) {
                if ($error instanceof RequestException && $response = $error->getResponse()) {
                    $this->log($response, $id);
                }
                return rejection_for($error);
            });
        };
    }

    /**
     * @param callable $formatter
     */
    public function setBodyFormatter(callable $formatter)
    {
        $this->bodyFormatter = $formatter;
    }

}
