<?php
namespace Dth\Crm\Support; use Illuminate\Support\Str;
final class CodeGenerator {public function make(string $prefix):string{return strtoupper($prefix).'-'.now()->format('ymd').'-'.Str::upper(Str::random(6));}}
