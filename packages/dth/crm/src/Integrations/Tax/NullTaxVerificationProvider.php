<?php
namespace Dth\Crm\Integrations\Tax; use Dth\Crm\Contracts\TaxVerificationProvider; final class NullTaxVerificationProvider implements TaxVerificationProvider {public function available():bool{return false;}public function verify(string $taxCode):array{return ['status'=>'manual_review','data'=>[],'message'=>'Tax verification provider is not configured.'];}}
