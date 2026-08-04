<?php

return [
    'default_source' => 'manual',

    'contact_statuses' => [
        'active',
        'inactive',
        'archived',
    ],

    'contact_consent_statuses' => [
        'subscribed',
        'unsubscribed',
        'pending',
        'bounced',
        'complained',
        'do_not_contact',
    ],

    'campaign_statuses' => [
        'draft',
        'testing',
        'scheduled',
        'preparing',
        'sending',
        'sent',
        'paused',
        'cancelled',
        'failed',
    ],

    'campaign_recipient_statuses' => [
        'pending',
        'queued',
        'sent',
        'delivered',
        'opened',
        'clicked',
        'failed',
        'bounced',
        'unsubscribed',
        'skipped',
    ],

    'email_event_types' => [
        'queued',
        'sent',
        'delivered',
        'opened',
        'clicked',
        'failed',
        'bounced',
        'complained',
        'unsubscribed',
        'skipped',
    ],

    'sending_account_statuses' => [
        'active',
        'inactive',
        'testing',
    ],

    'dns_statuses' => [
        'unknown',
        'pending',
        'verified',
        'failed',
    ],

    'suppression_reasons' => [
        'unsubscribe',
        'bounce',
        'complaint',
        'manual',
        'invalid_email',
        'do_not_contact',
    ],

    'landing_page_statuses' => ['draft', 'published', 'archived'],
    'landing_form_template_statuses' => ['draft', 'active', 'archived'],
    'landing_form_field_types' => ['text', 'email', 'phone', 'textarea', 'select', 'checkbox', 'hidden'],
    'landing_submission_statuses' => ['received', 'processed', 'failed', 'spam'],
    'landing_contact_actions' => ['created', 'updated', 'skipped'],
];
