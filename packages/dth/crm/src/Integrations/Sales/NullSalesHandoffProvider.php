<?php
namespace Dth\Crm\Integrations\Sales;
use Dth\Crm\Contracts\SalesHandoffProvider; use Dth\Crm\DTO\QualifiedLeadData;
final class NullSalesHandoffProvider implements SalesHandoffProvider { public function available():bool{return false;} public function handoff(QualifiedLeadData $lead):?string{return null;} }
