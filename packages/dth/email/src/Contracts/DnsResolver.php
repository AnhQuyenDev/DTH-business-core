<?php

namespace Dth\Email\Contracts;

interface DnsResolver
{
    /**
     * @return list<string>
     */
    public function txt(string $host): array;
}
