<?php

return [
    // null means the Free tier remains available after the introductory trial.
    'free_trial_days' => null,
    'features' => [
        'free' => ['projects', 'tasks', 'members', 'search', 'checklists'],
        'pro' => ['projects', 'tasks', 'members', 'search', 'checklists', 'priorities', 'deadlines', 'reports'],
        'equipe' => ['projects', 'tasks', 'members', 'search', 'checklists', 'priorities', 'deadlines', 'reports', 'roles', 'history', 'full_reports', 'team_support'],
    ],
];
