<?php
namespace Dth\Crm\Policies; use Dth\Crm\Support\CrmAuthorization;
class CrmResourcePolicy {private function a(string $x,$u):bool{return app(CrmAuthorization::class)->allows($x,$u);} public function viewAny($u):bool{return $this->a('crm.view',$u);} public function view($u,$m):bool{return $this->a('crm.view',$u);} public function create($u):bool{return $this->a('crm.manage',$u);} public function update($u,$m):bool{return $this->a('crm.manage',$u);} public function delete($u,$m):bool{return $this->a('crm.manage',$u);} public function deleteAny($u):bool{return $this->a('crm.manage',$u);} }
