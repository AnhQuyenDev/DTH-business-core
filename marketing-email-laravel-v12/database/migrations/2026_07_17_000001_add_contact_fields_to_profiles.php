<?php

use App\Enums\Crm\ContactType;
use App\Models\Marketing\Contact;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_contact_profiles', function (Blueprint $table) {
            $table->string('first_name', 255)->nullable()->after('contact_id');
            $table->string('last_name', 255)->nullable()->after('first_name');
            $table->string('full_name', 255)->nullable()->after('last_name');
            $table->string('email', 255)->nullable()->after('full_name');
            $table->string('normalized_email', 255)->nullable()->index()->after('email');
            $table->timestamp('email_verified_at')->nullable()->after('normalized_email');
            $table->string('phone', 255)->nullable()->after('email_verified_at');
            $table->string('normalized_phone', 30)->nullable()->index()->after('phone');
            $table->timestamp('phone_verified_at')->nullable()->after('normalized_phone');
            $table->string('job_title', 255)->nullable()->after('phone_verified_at');
            $table->string('source', 255)->nullable()->index()->after('job_title');
            $table->string('form_source_type', 50)->nullable()->after('source');
            $table->unsignedBigInteger('form_source_id')->nullable()->after('form_source_type');
            $table->json('raw_data')->nullable()->after('form_source_id');
            $table->text('notes')->nullable()->after('raw_data');
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete()->after('notes');
        });

        Schema::table('business_contact_profiles', function (Blueprint $table) {
            $table->string('source', 255)->nullable()->index()->after('contact_id');
            $table->string('form_source_type', 50)->nullable()->after('source');
            $table->unsignedBigInteger('form_source_id')->nullable()->after('form_source_type');
            $table->json('raw_data')->nullable()->after('form_source_id');
            $table->text('notes')->nullable()->after('raw_data');
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete()->after('notes');
        });

        // Migrate data from contacts to profiles
        $contactTypePersonal = ContactType::Personal->value;
        $contactTypeBusiness = ContactType::Business->value;

        Contact::query()
            ->withTrashed()
            ->orderBy('id')
            ->chunk(100, function ($contacts) use ($contactTypePersonal, $contactTypeBusiness) {
                foreach ($contacts as $contact) {
                    $type = $contact->contact_type?->value ?? $contactTypePersonal;

                    if ($type === $contactTypePersonal || $type === 'personal') {
                        $personalFields = array_filter([
                            'first_name' => $contact->first_name,
                            'last_name' => $contact->last_name,
                            'full_name' => $contact->full_name,
                            'email' => $contact->email,
                            'normalized_email' => $contact->normalized_email,
                            'email_verified_at' => $contact->email_verified_at,
                            'phone' => $contact->phone,
                            'normalized_phone' => $contact->normalized_phone,
                            'phone_verified_at' => $contact->phone_verified_at,
                            'job_title' => $contact->job_title,
                            'source' => $contact->source,
                            'form_source_type' => $contact->form_source_type,
                            'form_source_id' => $contact->form_source_id,
                            'raw_data' => $contact->raw_data,
                            'notes' => $contact->notes,
                            'owner_user_id' => $contact->owner_user_id,
                        ], fn ($v) => $v !== null);

                        if (! empty($personalFields)) {
                            DB::table('personal_contact_profiles')
                                ->updateOrInsert(
                                    ['contact_id' => $contact->id],
                                    $personalFields
                                );
                        }
                    } elseif ($type === $contactTypeBusiness || $type === 'business') {
                        $businessFields = array_filter([
                            'source' => $contact->source,
                            'form_source_type' => $contact->form_source_type,
                            'form_source_id' => $contact->form_source_id,
                            'raw_data' => $contact->raw_data,
                            'notes' => $contact->notes,
                            'owner_user_id' => $contact->owner_user_id,
                        ], fn ($v) => $v !== null);

                        if (! empty($businessFields)) {
                            DB::table('business_contact_profiles')
                                ->updateOrInsert(
                                    ['contact_id' => $contact->id],
                                    $businessFields
                                );
                        }
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('personal_contact_profiles', function (Blueprint $table) {
            $table->dropForeign(['owner_user_id']);
            $table->dropColumn([
                'first_name', 'last_name', 'full_name', 'email',
                'normalized_email', 'email_verified_at', 'phone',
                'normalized_phone', 'phone_verified_at', 'job_title',
                'source', 'form_source_type', 'form_source_id',
                'raw_data', 'notes', 'owner_user_id',
            ]);
        });

        Schema::table('business_contact_profiles', function (Blueprint $table) {
            $table->dropForeign(['owner_user_id']);
            $table->dropColumn([
                'source', 'form_source_type', 'form_source_id',
                'raw_data', 'notes', 'owner_user_id',
            ]);
        });
    }
};
