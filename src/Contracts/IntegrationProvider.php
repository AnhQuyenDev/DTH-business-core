<?php

namespace Dth\Marketing\Contracts;

interface IntegrationProvider
{
    /**
     * True only when the provider can return authoritative data for its domain.
     */
    public function available(): bool;

    /**
     * @return array<string, bool>
     */
    public function capabilities(): array;
}
