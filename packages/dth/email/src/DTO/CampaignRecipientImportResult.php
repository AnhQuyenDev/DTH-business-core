<?php

namespace Dth\Email\DTO;

final readonly class CampaignRecipientImportResult
{
    public function __construct(
        public int $rows,
        public int $added,
        public int $updated,
        public int $invalid,
    ) {}
}