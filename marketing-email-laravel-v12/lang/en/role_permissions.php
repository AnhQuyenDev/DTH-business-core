<?php

return [
    'heading' => 'System role matrix',
    'description' => 'System roles are separated from Departments and Positions. Operational users receive business permissions from their Department and Position Authority.',
    'no_department_requirement' => 'No department requirement',
    'fixed_role_note' => 'The current version uses a fixed role set so Gates and Policies stay consistent. Custom role creation requires a permission-based RBAC migration in a separate phase.',
    'summary' => [
        'admin' => 'Manages configuration, accounts, system master data, and full-system oversight.',
        'marketing_manager' => 'Manages Marketing activities and related approval/send permissions.',
        'marketing_staff' => 'Executes Marketing, Landing Page, Campaign, and marketing-data workflows.',
        'customer_service_manager' => 'Manages post-sale Customer assignments, support Tickets, and customer-care service quality.',
        'customer_service_staff' => 'Supports post-purchase Customers, handles Tickets/follow-ups, and records post-sale interactions.',
        'sales_manager' => 'Manages Lead distribution/qualification, Service/Package/Price Book, sales operations, and quotation approval policy.',
        'sales_staff' => 'Handles assigned Leads, qualifies and advises prospects, creates/sends quotations, and follows customers through payment.',
        'finance_staff' => 'Manages bank accounts and verifies payments.',
        'viewer' => 'Read-only access to permitted business data without data-changing operations.',
        'executive' => 'Cross-department read access for executive oversight; does not automatically perform each department daily operations.',
        'user' => 'Internal operational user. Business permissions come from one or more business functions assigned to the employee.',
    ],
    'organization_rule_note' => 'Business permission = System Role + Department Function + Position Authority. Example: User + Sales + Department Head (Manager) receives Sales management permissions; User + Sales + Employee (Member) receives Sales staff permissions.',
];
