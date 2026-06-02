<?php

return [

    'alamo' => [
        'api_token' => env('ALAMO_API_TOKEN'),
        'hubspot' => [
            'portal_id' => env('ALAMO_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('ALAMO_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('ALAMO_RECAPTCHA_SECRET'),
    ],

    'baltico' => [
        'api_token' => env('BALTICO_API_TOKEN'),
        'portal_id' => env('BALTICO_HUBSPOT_PORTAL_ID'),
        'hubspot' => [
            'contacto' => [
                'portal_id' => env('BALTICO_HUBSPOT_PORTAL_ID'),
                'form_id'   => env('BALTICO_HUBSPOT_FORM_CONTACTO'),
            ],
            'bolsa' => [
                'portal_id' => env('BALTICO_HUBSPOT_PORTAL_ID'),
                'form_id'   => env('BALTICO_HUBSPOT_FORM_BOLSA'),
            ],
        ],
        'recaptcha_secret' => env('BALTICO_RECAPTCHA_SECRET'),
    ],

    'coa' => [
        'api_token' => env('COA_API_TOKEN'),
        'hubspot' => [
            'portal_id' => env('COA_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('COA_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('COA_RECAPTCHA_SECRET'),
    ],

    'cornea' => [
        'api_token' => env('CORNEA_API_TOKEN'),
        'hubspot' => [
            'portal_id' => env('CORNEA_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('CORNEA_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('CORNEA_RECAPTCHA_SECRET'),
    ],

    'daryl' => [
        'api_token' => env('DARYL_API_TOKEN'),
        'mailchimp' => [
            'api_key'     => env('DARYL_MAILCHIMP_API_KEY'),
            'server'      => env('DARYL_MAILCHIMP_SERVER'),
            'audience_id' => env('DARYL_MAILCHIMP_AUDIENCE_ID'),
        ],
        'etiquetas' => [
            101 => 'Eleva el valor de tu nombre',
            102 => 'Teoria de la felicidad y la innovacion',
            103 => 'Usa la IA para diversificar tus ingresos y mantenerte relevante',
            104 => 'Test: Identifica qué está frenando tu libertad profesional hoy',
        ]
    ],

    'harteeth' => [
        'api_token' => env('HARTEETH_API_TOKEN'),
        'hubspot' => [
            'portal_id' => env('HARTEETH_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('HARTEETH_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('HARTEETH_RECAPTCHA_SECRET'),
    ],

    'hipotecaperfecta' => [
        'api_token'        => env('HIPOTECA_PERFECTA_API_TOKEN'),
        'activecampaign' => [
            'url'   => env('HIPOTECA_AC_URL'),
            'token' => env('HIPOTECA_AC_TOKEN'),
            'tags'  => [74], // ID de 'Web-VSL'
            'lists' => [16], // ID de 'Clientes Problemas Financieros'
        ],
        'recaptcha_secret' => env('HIPOTECA_RECAPTCHA_SECRET'),
    ],

    'iberosaltillo' => [
        'api_token' => env('IBEROSALTILLO_API_TOKEN'),
        'activecampaign' => [
            'url'               => env('IBEROSALTILLO_AC_URL'),
            'token'             => env('IBEROSALTILLO_AC_TOKEN'),
            'lists'             => [1],
            'tags'              => [12],
            'field_nuevo_value' => '1. Nuevo'
        ],
        'recaptcha_secret' => env('IBEROSALTILLO_RECAPTCHA_SECRET'),
    ],

    'iberotorreon' => [
        'api_token' => env('IBERO_TORREON_API_TOKEN'),
        'activecampaign' => [
            'url'         => env('IBEROTORREON_AC_URL'),
            'token'       => env('IBEROTORREON_AC_TOKEN'),
            'lists'       => [10],
            'pipeline_id' => 5,
            'stage_id'    => 47,
            'owner_id'    => 1,
        ],
        'contact_fields' => [
            'departamento'    => 12,
            'area'            => 17,
            'nivel_academico' => 1,
            'programa'        => 2,
            'utm_medium'      => 28,
            'utm_campaign'    => 29,
            'utm_source'      => 30,
            'utm_term'        => 31,
            'utm_content'     => 32,
            'profession'      => 33,
            'comments'        => 9,
            'pref_contact'    => 38,
            'another_program' => 3,
        ],
        'deal_fields' => [
            'departamento' => 7,
            'area'         => 8,
            'oferta'       => 9,
            'programa'     => 6,
            'modalidad'    => 12,
            'costo'        => 10,
            'fecha_inicio' => 11,
            'horario'      => 13,
        ],
        'tags_by_source' => [
            'Kino'             => [3, 8, 31],
            'diplomado_evento' => [3, 8, 32],
        ]
    ],

    'integra' => [
        'api_token' => env('INTEGRA_API_TOKEN'),
        'hubspot' => [
            'portal_id' => env('INTEGRA_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('INTEGRA_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('INTEGRA_RECAPTCHA_SECRET'),
    ],

    'jupplo' => [
        'api_token' => env('JUPPLO_API_TOKEN'),
        'hubspot' => [
            'portal_id' => env('JUPPLO_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('JUPPLO_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('JUPPLO_RECAPTCHA_SECRET'),
    ],

    'maquiteck' => [
        'api_token' => env('MAQUITECK_API_TOKEN'),
        'hubspot' => [
            'portal_id' => env('MAQUITECK_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('MAQUITECK_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('MAQUITECK_RECAPTCHA_SECRET'),
    ],

    'mercadomedico' => [
        'api_token' => env('MERCADOMEDICO_API_TOKEN'),
        'odoo' => [
            'url'      => env('MERCADOMEDICO_ODOO_URL'),
            'db'       => env('MERCADOMEDICO_ODOO_DB'),
            'username' => env('MERCADOMEDICO_ODOO_USER'),
            'password' => env('MERCADOMEDICO_ODOO_PASSWORD'),
        ],
        'recaptcha_secret' => env('MERCADOMEDICO_RECAPTCHA_SECRET'),
    ],

    'remar' => [
        'api_token' => env('REMAR_API_TOKEN'),
        'hubspot' => [
            'portal_id' => env('REMAR_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('REMAR_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('REMAR_RECAPTCHA_SECRET'),
    ],

    'retiroestrategico' => [
        'api_token'        => env('RETIRO_ESTRATEGICO_API_TOKEN'),
        'hubspot' => [
            'portal_id' => env('RETIRO_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('RETIRO_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('RETIRO_RECAPTCHA_SECRET'),
    ],

    'tradelossa' => [
        'api_token'        => env('TRADELOSSA_API_TOKEN'),
        'hubspot' => [
            'portal_id'      => env('TRADELOSSA_HUBSPOT_PORTAL_ID'),
            'form_contacto'  => env('TRADELOSSA_HUBSPOT_FORM_CONTACTO'),
            'form_cotizacion'=> env('TRADELOSSA_HUBSPOT_FORM_COTIZACION'),
        ],
        'recaptcha_secret' => env('TRADELOSSA_RECAPTCHA_SECRET'),
    ],

    'vijusa' => [
        'api_token'        => env('VIJUSA_API_TOKEN'),
        'copper' => [
            'url'             => env('VIJUSA_COPPER_URL'),
            'token'           => env('VIJUSA_COPPER_TOKEN'),
            'user_email'      => env('VIJUSA_COPPER_USER'),
            'contact_type_id' => 2089772,
            'assignee_id'     => 3005,
        ],
        'recaptcha_secret' => env('VIJUSA_RECAPTCHA_SECRET'),
    ],
];
