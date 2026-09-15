<?php

return [
    'enabled' => env('DTH_HR_ENABLED', true),

    'features' => [
        'employees' => true,
        'departments' => true,
        'positions' => true,
        'availability' => true,
        'business_functions' => true,
        'analytics' => true,
    ],

    // auto: authenticated users are allowed until the host registers explicit
    // hr.* Gate abilities. strict: require host abilities. off: bypass checks.
    'authorization_mode' => env('DTH_HR_AUTHORIZATION_MODE', 'auto'),

    'employee_code_prefix' => env('DTH_HR_EMPLOYEE_CODE_PREFIX', 'EMP'),
    'department_code_prefix' => env('DTH_HR_DEPARTMENT_CODE_PREFIX', 'DEPT'),
    'position_code_prefix' => env('DTH_HR_POSITION_CODE_PREFIX', 'JOB'),
];
