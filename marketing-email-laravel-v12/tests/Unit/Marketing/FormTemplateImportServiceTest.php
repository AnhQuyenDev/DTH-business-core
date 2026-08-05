<?php

namespace Tests\Unit\Marketing;

use App\Models\User;
use App\Services\Marketing\FormTemplateImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormTemplateImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private FormTemplateImportService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FormTemplateImportService::class);
        $this->user = User::factory()->create();
    }

    public function test_it_generates_stable_keys_for_fields_without_name(): void
    {
        $html = <<<'HTML'
<form>
    <div>
        <label>Họ và Tên</label>
        <input type="text" placeholder="Nhập họ và tên" required>
    </div>
    <div>
        <label>Email cá nhân</label>
        <input type="email" placeholder="example@gmail.com" required>
    </div>
    <button type="submit">Đăng ký</button>
</form>
HTML;

        $template = $this->service->import([
            'name' => 'Test form',
            'slug' => 'test-form',
            'audience_type' => 'personal',
        ], $html, $this->user->id);

        $fieldKeys = $template->fields->pluck('field_key')->all();

        // Should generate keys from labels/placeholders
        $this->assertCount(2, $fieldKeys);
        $this->assertStringContainsString('{{fields}}', $template->html_body);

        // Verify keys are stable and not field_0, field_1
        foreach ($fieldKeys as $key) {
            $this->assertNotEquals('field_0', $key);
            $this->assertNotEquals('field_1', $key);
        }
    }

    public function test_it_uses_existing_name_attribute(): void
    {
        $html = <<<'HTML'
<form>
    <input type="text" name="full_name" placeholder="Họ và tên" required>
    <input type="email" name="email" placeholder="Email" required>
    <button type="submit">Gửi</button>
</form>
HTML;

        $template = $this->service->import([
            'name' => 'Test form',
            'slug' => 'test-form-2',
            'audience_type' => 'personal',
        ], $html, $this->user->id);

        $fieldKeys = $template->fields->pluck('field_key')->all();

        $this->assertSame(['full_name', 'email'], $fieldKeys);
        $this->assertStringContainsString('{{fields}}', $template->html_body);
    }

    public function test_it_normalizes_field_types(): void
    {
        $html = <<<'HTML'
<form>
    <input type="text" name="full_name" required>
    <input type="email" name="email" required>
    <input type="tel" name="phone" required>
    <textarea name="message"></textarea>
    <select name="package">
        <option value="basic">Basic</option>
        <option value="pro">Pro</option>
    </select>
    <input type="checkbox" name="agree" required>
    <button type="submit">Gửi</button>
</form>
HTML;

        $template = $this->service->import([
            'name' => 'Test form',
            'slug' => 'test-form-3',
            'audience_type' => 'personal',
        ], $html, $this->user->id);

        $fields = $template->fields->keyBy('field_key');

        $this->assertEquals('text', $fields['full_name']->field_type->value);
        $this->assertEquals('email', $fields['email']->field_type->value);
        $this->assertEquals('phone', $fields['phone']->field_type->value);
        $this->assertEquals('textarea', $fields['message']->field_type->value);
        $this->assertEquals('select', $fields['package']->field_type->value);
        $this->assertEquals('checkbox', $fields['agree']->field_type->value);
    }

    public function test_it_extracts_select_options(): void
    {
        $html = <<<'HTML'
<form>
    <select name="service_package">
        <option value="">-- Chọn --</option>
        <option value="vps">VPS</option>
        <option value="dedicated">Dedicated</option>
    </select>
    <button type="submit">Gửi</button>
</form>
HTML;

        $template = $this->service->import([
            'name' => 'Test form',
            'slug' => 'test-form-4',
            'audience_type' => 'personal',
        ], $html, $this->user->id);

        $field = $template->fields->where('field_key', 'service_package')->first();

        $this->assertNotNull($field->options);
        $this->assertCount(2, $field->options);
        $this->assertEquals('vps', $field->options[0]['value']);
        $this->assertEquals('VPS', $field->options[0]['label']);
        $this->assertEquals('dedicated', $field->options[1]['value']);
        $this->assertEquals('Dedicated', $field->options[1]['label']);
    }

    public function test_it_sets_required_from_html_attribute(): void
    {
        $html = <<<'HTML'
<form>
    <input type="text" name="full_name" required>
    <input type="email" name="email">
    <button type="submit">Gửi</button>
</form>
HTML;

        $template = $this->service->import([
            'name' => 'Test form',
            'slug' => 'test-form-5',
            'audience_type' => 'personal',
        ], $html, $this->user->id);

        $fields = $template->fields->keyBy('field_key');

        $this->assertTrue($fields['full_name']->is_required);
        $this->assertFalse($fields['email']->is_required);
    }

    public function test_it_throws_on_multiple_forms(): void
    {
        $html = <<<'HTML'
<form><input name="a"></form>
<form><input name="b"></form>
HTML;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Mỗi Form Template chỉ được chứa một thẻ <form>');

        $this->service->import([
            'name' => 'Test',
            'slug' => 'test',
            'audience_type' => 'personal',
        ], $html, $this->user->id);
    }

    public function test_it_throws_on_no_form(): void
    {
        $html = '<div>No form here</div>';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File HTML không chứa thẻ <form>');

        $this->service->import([
            'name' => 'Test',
            'slug' => 'test',
            'audience_type' => 'personal',
        ], $html, $this->user->id);
    }

    public function test_it_throws_on_invalid_audience_type(): void
    {
        $html = '<form><input name="test"></form>';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Form Template chỉ được thuộc loại Cá nhân hoặc Doanh nghiệp.');

        $this->service->import([
            'name' => 'Test',
            'slug' => 'test',
            'audience_type' => 'invalid',
        ], $html, $this->user->id);
    }

    public function test_it_assigns_sort_order_by_dom_order(): void
    {
        $html = <<<'HTML'
<form>
    <input type="text" name="first">
    <input type="text" name="second">
    <input type="text" name="third">
    <button type="submit">Gửi</button>
</form>
HTML;

        $template = $this->service->import([
            'name' => 'Test form',
            'slug' => 'test-form-6',
            'audience_type' => 'personal',
        ], $html, $this->user->id);

        $fields = $template->fields->sortBy('position')->values();

        $this->assertEquals('first', $fields[0]->field_key);
        $this->assertEquals('second', $fields[1]->field_key);
        $this->assertEquals('third', $fields[2]->field_key);

        $this->assertEquals(0, $fields[0]->sort_order);
        $this->assertEquals(1, $fields[1]->sort_order);
        $this->assertEquals(2, $fields[2]->sort_order);
    }

    public function test_it_generates_unique_keys_for_duplicate_names(): void
    {
        $html = <<<'HTML'
<form>
    <input type="text" name="email">
    <input type="text" name="email">
    <button type="submit">Gửi</button>
</form>
HTML;

        $template = $this->service->import([
            'name' => 'Test form',
            'slug' => 'test-form-7',
            'audience_type' => 'personal',
        ], $html, $this->user->id);

        $fieldKeys = $template->fields->pluck('field_key')->all();

        $this->assertCount(2, $fieldKeys);
        $this->assertEquals('email', $fieldKeys[0]);
        $this->assertEquals('email_2', $fieldKeys[1]);
    }

    public function test_it_sets_field_key_and_label_for_rendering(): void
    {
        $html = <<<'HTML'
<form>
    <label>Email</label>
    <input type="email" name="email">
    <button type="submit">Gửi</button>
</form>
HTML;

        $template = $this->service->import([
            'name' => 'Test form',
            'slug' => 'test-form-8',
            'audience_type' => 'personal',
        ], $html, $this->user->id);

        // Field should have proper field_key and label for rendering
        $field = $template->fields->where('field_key', 'email')->first();
        $this->assertNotNull($field);
        $this->assertEquals('email', $field->field_key);
        $this->assertEquals('Email', $field->label);

        // html_body should be a shell with {{fields}} placeholder
        $this->assertStringContainsString('{{fields}}', $template->html_body);
    }
}
