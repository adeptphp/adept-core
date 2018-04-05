<?php

namespace Adept\Http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;

/**
 * Class Message
 *
 * @package Atom\Http\Message
 */
abstract class Message implements MessageInterface
{
    protected static $protocolVersions = ['1.0', '1.1', '2.0'];
    protected $protocolVersion = '1.1';

    protected $body;

    protected $headers;

    public function __construct(StreamInterface $body = null, array $headers = [])
    {
        $this->body = $body;
        $this->headers = $headers;
    }

    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version)
    {
        if (!in_array($version, static::$protocolVersions)) {
            throw new InvalidArgumentException('Invalid HTTP version. Valid versions: '.implode(', ', array_keys(static::$protocolVersions)));
        }

        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    public function getHeaders()
    {
        return $this->headers->all();
    }

    public function hasHeader($name)
    {
        return $this->headers->has($name);
    }

    public function getHeader($name)
    {
        return $this->headers->get($name);
    }

    public function getHeaderLine($name)
    {
        return implode(',', $this->headers->get($name));
    }

    public function withHeader($name, $value)
    {
        $clone = clone $this;
        $clone->headers->set($name, $value);

        return $clone;
    }

    public function withAddedHeader($name, $value)
    {
        $clone = clone $this;
        $clone->headers->add($name, $value);

        return $clone;
    }

    public function withoutHeader($name)
    {
        $clone = clone $this;
        $clone->headers->remove($name);

        return $clone;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body)
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }
}
