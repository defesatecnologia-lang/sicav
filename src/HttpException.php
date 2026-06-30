<?php

namespace App;

class HttpException extends \RuntimeException
{
    private int $statusCode;
    private string $responseBody;
    private string $contentType;
    private ?string $redirectUrl;

    public function __construct(
        int $statusCode,
        string $responseBody,
        string $contentType = 'application/json; charset=UTF-8',
        ?string $redirectUrl = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($responseBody, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
        $this->contentType = $contentType;
        $this->redirectUrl = $redirectUrl;
    }

    public static function redirect(string $url): self
    {
        return new self(302, '', 'text/html', $url);
    }

    public function isRedirect(): bool
    {
        return $this->redirectUrl !== null;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function send(): never
    {
        if ($this->redirectUrl) {
            header('Location: ' . $this->redirectUrl);
            exit;
        }

        http_response_code($this->statusCode);
        header('Content-Type: ' . $this->contentType);
        echo $this->responseBody;
        exit;
    }
}
