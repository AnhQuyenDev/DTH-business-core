<?php
namespace Dth\Crm\Services; use Dth\Crm\Models\{Contact,Company,Lead,Customer,ContactQualification};
final class CrmAnalyticsService {public function snapshot():array{return ['contacts'=>Contact::count(),'companies'=>Company::count(),'leads'=>Lead::count(),'new_leads'=>Lead::where('intake_status','new')->count(),'active_leads'=>Lead::where('intake_status','active')->count(),'qualified'=>ContactQualification::where('status','qualified')->count(),'customers'=>Customer::count(),'active_customers'=>Customer::where('status','active')->count()];}}
