<?php
namespace Dth\Crm\Contracts;
use Dth\Crm\DTO\QualifiedLeadData;
interface SalesHandoffProvider { public function available():bool; public function handoff(QualifiedLeadData $lead):?string; }
