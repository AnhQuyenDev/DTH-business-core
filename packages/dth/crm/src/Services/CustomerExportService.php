<?php
namespace Dth\Crm\Services; use Illuminate\Support\Collection;
final class CustomerExportService {public function toCsv(Collection $customers):string{$f=fopen('php://temp','r+');fputcsv($f,['customer_code','type','display_name','email','phone','status','source','total_revenue']);foreach($customers as $c)fputcsv($f,[$c->customer_code,$c->customer_type,$c->display_name,$c->email,$c->phone,$c->status,$c->acquisition_source,$c->total_revenue]);rewind($f);$out=stream_get_contents($f);fclose($f);return $out?:'';}}
