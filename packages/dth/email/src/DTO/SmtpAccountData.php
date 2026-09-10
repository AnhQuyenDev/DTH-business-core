<?php

namespace Dth\Email\DTO;

use InvalidArgumentException;

final readonly class SmtpAccountData
{
    public function __construct(
        public string $host,
        public int $port = 587,
        public string $scheme = 'smtp',
        public ?string $username = null,
        public ?string $password = null,
        public ?int $timeout = 30,
        public ?string $localDomain = null,
    ) {
        if ($this->host === '') {
            throw new InvalidArgumentException('SMTP host is required.');
        }

        if ($this->port < 1 || $this->port > 65535) {
            throw new InvalidArgumentException('SMTP port must be between 1 and 65535.');
        }

        if (! in_array($this->scheme, ['smtp', 'smtps'], true)) {
            throw new InvalidArgumentException('SMTP scheme must be smtp or smtps.');
        }
    }

    public static function fromArray(array $config): self
    {
        return new self(
            host: trim((string) ($config['host'] ?? '')),
            port: (int) ($config['port'] ?? 587),
            scheme: (string) ($config['scheme'] ?? 'smtp'),
            username: self::nullableString($config['username'] ?? null),
            password: self::nullableString($config['password'] ?? null),
            timeout: isset($config['timeout']) ? (int) $config['timeout'] : 30,
            localDomain: self::nullableString($config['local_domain'] ?? null),
        );
    }

    public function toArray(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'scheme' => $this->scheme,
            'username' => $this->username,
            'password' => $this->password,
            'timeout' => $this->timeout,
            'local_domain' => $this->localDomain,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
