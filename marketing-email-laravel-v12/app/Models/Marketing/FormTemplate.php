<?php

namespace App\Models\Marketing;

use App\Enums\Marketing\FormAudienceType;
use App\Enums\Marketing\FormTemplateStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FormTemplate extends Model
{
    protected $table = 'form_templates';

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'submit_button_text',
        'success_message',
        'redirect_url',
        'auto_tag_names',
        'auto_list_names',
        'auto_create_tags',
        'auto_create_lists',
        'auto_create_segment',
        'html_body',
        'status',
        'created_by',
        'audience_type',
        'version',
        'is_system_template',
        'schema',
    ];

    protected function casts(): array
    {
        return [
            'auto_tag_names' => 'array',
            'auto_list_names' => 'array',
            'auto_create_tags' => 'boolean',
            'auto_create_lists' => 'boolean',
            'auto_create_segment' => 'boolean',
            'audience_type' => FormAudienceType::class,
            'status' => FormTemplateStatus::class,
            'is_system_template' => 'boolean',
            'version' => 'integer',
            'schema' => 'json',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $form): void {
            if (blank($form->slug) && filled($form->name)) {
                $form->slug = Str::slug($form->name);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'landing_form_template_id')->orderBy('position')->orderBy('sort_order');
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class, 'landing_form_template_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(LandingPageSubmission::class, 'landing_form_template_id');
    }

    public static function contactMappingOptions(?string $audienceType): array
    {
        $personalFields = [
            'personal.first_name' => 'Họ (First Name)',
            'personal.last_name' => 'Tên (Last Name)',
            'personal.email' => 'Email cá nhân (Personal Email)',
            'personal.phone' => 'SĐT cá nhân (Personal Phone)',
            'personal.date_of_birth' => 'Ngày sinh (Date of Birth)',
            'personal.gender' => 'Giới tính (Gender)',
            'personal.ward' => 'Phường/Xã (Ward)',
            'personal.district' => 'Quận/Huyện (District)',
            'personal.province' => 'Tỉnh/Thành phố (Province)',
            'personal.country' => 'Quốc gia (Country)',
            'personal.occupation' => 'Ngành nghề (Occupation)',
        ];

        $businessFields = [
            'business.tax_code' => 'Mã số thuế (Tax Code)',
            'business.company_name' => 'Tên công ty (Company Name)',
            'business.company_address' => 'Địa chỉ công ty (Company Address)',
            'business.ward' => 'Phường/Xã (Ward)',
            'business.province' => 'Tỉnh/Thành phố (Province)',
            'business.legal_representative' => 'Họ tên người đại diện (Legal Representative)',
            'business.contact_position' => 'Chức vụ (Position)',
            'business.business_email' => 'Email công ty (Business Email)',
            'business.business_phone' => 'SĐT công ty (Business Phone)',
            'business.industry' => 'Lĩnh vực (Industry)',
        ];

        $groups = [
            'Chỉ lưu theo yêu cầu tư vấn' => [
                '' => 'Chỉ lưu tại Lượt gửi biểu mẫu và Lead',
            ],
            'Lead' => [
                'lead.service_interest' => 'Dịch vụ quan tâm',
            ],
        ];

        if ($audienceType === FormAudienceType::Personal->value) {
            $groups['Liên hệ cá nhân'] = $personalFields;
        } elseif ($audienceType === FormAudienceType::Business->value) {
            $groups['Liên hệ doanh nghiệp / Công ty'] = $businessFields;
        } else {
            $groups['Liên hệ cá nhân'] = $personalFields;
            $groups['Liên hệ doanh nghiệp / Công ty'] = $businessFields;
        }

        $customFields = CustomField::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name', 'key'])
            ->mapWithKeys(fn (CustomField $field): array => [
                'custom_field:'.$field->key => $field->name,
            ])
            ->all();

        if ($customFields !== []) {
            $groups['Trường tùy chỉnh Liên hệ'] = $customFields;
        }

        // Các mapping cũ chỉ để đọc/sửa template đã tồn tại. Template mới
        // phải dùng lead.service_interest để tránh ghi nhu cầu vào hồ sơ Contact.
        $groups['Tương thích dữ liệu cũ'] = [
            'personal.service_interest' => 'Dịch vụ quan tâm cá nhân (cũ)',
            'business.service_interest' => 'Dịch vụ quan tâm doanh nghiệp (cũ)',
        ];

        return $groups;
    }

}
