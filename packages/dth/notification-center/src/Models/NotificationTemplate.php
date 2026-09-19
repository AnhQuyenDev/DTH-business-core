<?php

namespace Dth\NotificationCenter\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $table = 'dth_notification_templates';

    protected $fillable = [
        'code', 'name', 'source_module', 'type', 'priority', 'channels', 'title_template', 'body_template',
        'email_subject_template', 'email_body_template', 'action_label_template', 'is_mandatory', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
