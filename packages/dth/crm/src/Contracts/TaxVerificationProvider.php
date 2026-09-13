<?php
namespace Dth\Crm\Contracts; interface TaxVerificationProvider {public function available():bool; /** @return array{status:string,data:array,message:?string} */ public function verify(string $taxCode):array;}
