<?php

return [
    'version' => env('LEGAL_VERSION', '2026-10-22'),
    'support_email' => env('SUPPORT_EMAIL', 'Suporte@escopofacil.com.br'),
    'whatsapp_url' => env('SUPPORT_WHATSAPP_URL'),
    'whatsapp_label' => env('SUPPORT_WHATSAPP_LABEL', 'WhatsApp do suporte'),
    'responsible' => env('LEGAL_RESPONSIBLE', 'Guilherme Augusto Tavares Cavalcante e Nicollas Ferreira'),
    'institution' => env('LEGAL_INSTITUTION', 'Centro Universitário Senac — Tecnologia em Sistemas para Internet'),
    'contact_emails' => array_values(array_filter(array_map('trim', explode(',', env('LEGAL_CONTACT_EMAILS', 'Guilhermeaugusto@escopofacil.com.br,Nicollasramos@escopofacil.com.br,Natanoliveira@escopofacil.com.br'))))),
    'retention_end' => env('LEGAL_RETENTION_END'),
    'documents' => [
        '2026-10-22' => [
            'published_on' => '22/10/2026',
            'terms' => 'legal.versions.v20261022.terms',
            'privacy' => 'legal.versions.v20261022.privacy',
        ],
    ],
];
