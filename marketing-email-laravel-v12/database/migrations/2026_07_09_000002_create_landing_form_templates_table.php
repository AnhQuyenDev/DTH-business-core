<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('submit_button_text')->default('Gửi thông tin');
            $table->text('success_message')->nullable();
            $table->string('redirect_url')->nullable();
            $table->json('auto_tag_names')->nullable();
            $table->json('auto_list_names')->nullable();
            $table->boolean('auto_create_tags')->default(true);
            $table->boolean('auto_create_lists')->default(true);
            $table->boolean('auto_create_segment')->default(false);
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_templates');
    }
};
