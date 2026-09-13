<?php
namespace Dth\Crm\DTO;
final readonly class QualifiedLeadData { public function __construct(public string $leadReference, public string $leadCode, public ?string $contactReference=null, public ?string $companyReference=null, public ?string $serviceReference=null, public ?float $estimatedValue=null, public array $metadata=[]){} }
