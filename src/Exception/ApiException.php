<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Exception;

use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiException extends RuntimeException
{
    public function __construct(
        private readonly ResponseInterface $response,
    )
    {
        // @see resources/f2-rest-docs/f2-rest-docs-v13s.html#16
        /*
         * Error handling
         *
         * F2-REST makes extensive use of standard HTTP codes to flag error conditions — but also adds an
         * additional payload with more detailed information about the error.
         * The payload for error conditions is a document having a single Message element, an optional
         * Details element, an optional ReasonCode and optionally an array of links related to the error
         * condition.
         *
         * (p. 16)
         */

        $previous = null;
        $message = self::class;
        $code = $this->response->getStatusCode();
        try {
            $sxe = new \SimpleXMLElement($response->getContent(throw: false));
            $message = (string) $sxe->Message;
        } catch (\Throwable $t) {
            $previous = $t;
        }
        parent::__construct($message, $code, $previous);
    }
}
