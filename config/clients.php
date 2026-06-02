<?php

return [

    'alamo' => [
        'hubspot' => [
            'portal_id' => env('ALAMO_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('ALAMO_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('ALAMO_RECAPTCHA_SECRET'),
    ],

    'baltico' => [
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
        'hubspot' => [
            'portal_id' => env('COA_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('COA_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('COA_RECAPTCHA_SECRET'),
    ],

    'cornea' => [
        'hubspot' => [
            'portal_id' => env('CORNEA_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('CORNEA_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('CORNEA_RECAPTCHA_SECRET'),
    ],

    'daryl' => [
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
        'hubspot' => [
            'portal_id' => env('HARTEETH_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('HARTEETH_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('HARTEETH_RECAPTCHA_SECRET'),
    ],

    'hipotecaperfecta' => [
        'activecampaign' => [
            'url'   => env('HIPOTECA_AC_URL'),
            'token' => env('HIPOTECA_AC_TOKEN'),
            'tags'  => [74], // ID de 'Web-VSL'
            'lists' => [16], // ID de 'Clientes Problemas Financieros'
        ],
        'recaptcha_secret' => env('HIPOTECA_RECAPTCHA_SECRET'),
    ],

    'iberosaltillo' => [
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
            // UTMs de Contacto de Torreón
            'utm_medium'      => 28,
            'utm_campaign'    => 29,
            'utm_source'      => 30,
            'utm_term'        => 31,
            'utm_content'     => 32,
            // Campos extra del formulario de Torreón
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
        'hubspot' => [
            'portal_id' => env('INTEGRA_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('INTEGRA_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('INTEGRA_RECAPTCHA_SECRET'),
    ],

    'jupplo' => [
        'hubspot' => [
            'portal_id' => env('JUPPLO_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('JUPPLO_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('JUPPLO_RECAPTCHA_SECRET'),
    ],

    'maquiteck' => [
        'hubspot' => [
            'portal_id' => env('MAQUITECK_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('MAQUITECK_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('MAQUITECK_RECAPTCHA_SECRET'),
    ],

    'mercadomedico' => [
        'odoo' => [
            'url'      => env('MERCADOMEDICO_ODOO_URL'),
            'db'       => env('MERCADOMEDICO_ODOO_DB'),
            'username' => env('MERCADOMEDICO_ODOO_USER'),
            'password' => env('MERCADOMEDICO_ODOO_PASSWORD'),
        ],
        'recaptcha_secret' => env('MERCADOMEDICO_RECAPTCHA_SECRET'),
    ],

    'remar' => [
        'hubspot' => [
            'portal_id' => env('REMAR_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('REMAR_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('REMAR_RECAPTCHA_SECRET'),
    ],

    'retiroestrategico' => [
        'hubspot' => [
            'portal_id' => env('RETIRO_HUBSPOT_PORTAL_ID'),
            'form_id'   => env('RETIRO_HUBSPOT_FORM_ID'),
        ],
        'recaptcha_secret' => env('RETIRO_RECAPTCHA_SECRET'),
    ],

    'tradelossa' => [
        'hubspot' => [
            'portal_id'      => env('TRADELOSSA_HUBSPOT_PORTAL_ID'),
            'form_contacto'  => env('TRADELOSSA_HUBSPOT_FORM_CONTACTO'),
            'form_cotizacion'=> env('TRADELOSSA_HUBSPOT_FORM_COTIZACION'),
        ],
        'recaptcha_secret' => env('TRADELOSSA_RECAPTCHA_SECRET'),
    ],

    'vijusa' => [
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
