<?php

return [
    // Full feature access for seven days; afterwards the account keeps Free.
    'free_trial_days' => 7,
    'features' => [
        'free' => ['projects', 'tasks', 'members', 'search', 'checklists'],
        'pro' => ['projects', 'tasks', 'members', 'search', 'checklists', 'priorities', 'deadlines', 'reports'],
        'equipe' => ['projects', 'tasks', 'members', 'search', 'checklists', 'priorities', 'deadlines', 'reports', 'roles', 'history', 'full_reports', 'team_support'],
    ],
];
