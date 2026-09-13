<?php
namespace Dth\Crm\Integrations\Marketing;
use Dth\Marketing\Contracts\LeadProvider;
use Dth\Marketing\DTO\{LeadIntakeData,LeadReference};
use Dth\Crm\Services\{LeadService,CompanyService};
use Dth\Crm\Models\Contact;
final class DthMarketingLeadProvider implements LeadProvider {
 public function __construct(private LeadService $leads, private CompanyService $companies){}
 public function available():bool{return true;}
 public function capabilities():array{return ['lead_intake'=>true,'company_resolution'=>true,'deduplication'=>true,'qualification'=>true,'distribution'=>true];}
 public function createOrUpdateFromMarketing(LeadIntakeData $d):?LeadReference {
  $contact=is_numeric($d->contactReference)?Contact::find((int)$d->contactReference):null;
  $meta=$d->metadata; $semantic=(array)($meta['semantic_data']??[]); $mapped=(array)($meta['mapped_values']??[]);
  $companyId=is_numeric($d->companyReference)?(int)$d->companyReference:null;
  $companyName=$meta['company_name']??data_get($semantic,'company.name')??($mapped['lead.company_name']??null);
  $taxCode=data_get($semantic,'company.tax_code')??($mapped['lead.tax_code']??null);
  if(!$companyId && ($companyName||$taxCode) && $contact){
   $company=$this->companies->resolveOrCreate(['company_name'=>$companyName?:'Doanh nghiệp','tax_code'=>$taxCode,'business_email'=>$contact->email,'business_phone'=>$contact->phone,'contact_position'=>data_get($semantic,'company.position')??($mapped['lead.position']??null),'metadata'=>['marketing_submission_reference'=>$d->submissionReference]],$contact,$d->submissionReference);
   $companyId=$company->id;
  }
  $source=$d->attribution['source']??$d->attribution['utm_source']??'landing_page';
  $lead=$this->leads->create(['submission_reference'=>$d->submissionReference,'contact_id'=>$contact?->id,'company_id'=>$companyId,'source'=>$source,'source_detail'=>$d->landingPageReference,'title'=>$meta['display_name']??$companyName,'service_reference'=>$d->serviceReference,'service_interest'=>$d->serviceLabel,'form_answers'=>$d->formAnswers,'attribution'=>$d->attribution,'metadata'=>$d->metadata+['marketing_campaign_reference'=>$d->marketingCampaignReference,'service_context'=>$d->serviceContext]]);
  return new LeadReference((string)$lead->id,$lead->lead_code,$lead->intake_status,['qualification_id'=>$lead->qualification?->id,'company_id'=>$companyId]);
 }
}
