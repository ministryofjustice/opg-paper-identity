<?php

declare(strict_types=1);

namespace Application\Auth;

use Application\Model\Entity\Problem;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\Validator;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Listener extends AbstractListenerAggregate
{
    /**
     * @var string[]
     */
    public const ALLOW_LIST = [
        '/health-check',
        '/health-check/service',
        '/health-check/dependencies',
    ];

    /**
     * @param non-empty-string $passphrase
     */
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
        private readonly string $passphrase,
    ) {
    }

    /**
     * @param int $priority
     */
    public function attach(EventManagerInterface $events, $priority = 100): void
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            [$this, 'checkAuth'],
            $priority
        );
    }

    public function detach(EventManagerInterface $events): void
    {
        $events->detach(
            [$this, 'checkAuth'],
            MvcEvent::EVENT_ROUTE
        );
    }

    public function checkAuth(MvcEvent $e): ?Response
    {
        /** @var Request $request */
        $request = $e->getRequest();

        /** @var Response $response */
        $response = $e->getResponse();

        if (in_array($request->getUri()->getPath(), self::ALLOW_LIST)) {
            return null;
        }

        if (! $this->isAuthed($request)) {
            $problem = new Problem('Unauthorized', '', 401);

            $response->setContent(json_encode($problem));
            $response->setStatusCode(Response::STATUS_CODE_401);

            return $response;
        }

        return null;
    }

    private function isAuthed(Request $request): bool
    {
        $authorization = $request->getHeader("Authorization");

        if (! ($authorization instanceof HeaderInterface)) {
            return false;
        }

        $token = str_replace('Bearer ', '', $authorization->getFieldValue());
        if (empty($token)) {
            return false;
        }

        $parser = new Parser(new JoseEncoder());

        try {
            $token = $parser->parse($token);
        } catch (Throwable) {
            return false;
        }

        if (! ($token instanceof UnencryptedToken)) {
            return false;
        }

        $validator = new Validator();

        try {
            $validator->assert(
                $token,
                new StrictValidAt($this->clock),
                new SignedWith(new Sha256(), InMemory::plainText($this->passphrase)),
            );
        } catch (Throwable $e) {
            $this->logger->warning('Authorization failed', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }
}
