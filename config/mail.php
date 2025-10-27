<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send any email
    | messages sent by your application. Alternative mailers may be setup
    | and used as needed; however, this mailer will be used by default.
    |
    */

    'default' => env('MAIL_MAILER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers to be used while
    | sending an e-mail. You will specify which one you are using for your
    | mailers below. You are free to add additional mailers as required.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "log", "array", "failover"
    |
    */

    'mailers' => [
        'capacitaciones' => [  // capacitaciones.pnte@gmail.com
            'transport'     => env('MAIL_MAILER_CAPACITACIONES', 'smtp'),
            'host'          => env('MAIL_HOST_CAPACITACIONES'),
            'port'          => env('MAIL_PORT_CAPACITACIONES'),
            'username'      => env('MAIL_USERNAME_CAPACITACIONES'),
            'password'      => env('MAIL_PASSWORD_CAPACITACIONES'),
            'encryption'    => env('MAIL_ENCRYPTION_CAPACITACIONES'),
            'from' => [
                'address'   => env('MAIL_FROM_ADDRESS_CAPACITACIONES'),
                'name'      => env('MAIL_FROM_NAME_CAPACITACIONES'),
            ],
        ],
        'notificaciones' => [  // notificaciones.pnte@gmail.com
            'transport'     => env('MAIL_MAILER_NOTIFICACIONES', 'smtp'),
            'host'          => env('MAIL_HOST_NOTIFICACIONES'),
            'port'          => env('MAIL_PORT_NOTIFICACIONES'),
            'username'      => env('MAIL_USERNAME_NOTIFICACIONES'),
            'password'      => env('MAIL_PASSWORD_NOTIFICACIONES'),
            'encryption'    => env('MAIL_ENCRYPTION_NOTIFICACIONES'),
            'from' => [
                'address'   => env('MAIL_FROM_ADDRESS_NOTIFICACIONES'),
                'name'      => env('MAIL_FROM_NAME_NOTIFICACIONES'),
            ],
        ],
        'cyberpnte' => [    // cyberpnte@gmail.com
            'transport'     => env('MAIL_MAILER_CYBERPNTE', 'smtp'),
            'host'          => env('MAIL_HOST_CYBERPNTE'),
            'port'          => env('MAIL_PORT_CYBERPNTE'),
            'username'      => env('MAIL_USERNAME_CYBERPNTE'),
            'password'      => env('MAIL_PASSWORD_CYBERPNTE'),
            'encryption'    => env('MAIL_ENCRYPTION_CYBERPNTE'),
            'from' => [
                'address'   => env('MAIL_FROM_ADDRESS_CYBERPNTE'),
                'name'      => env('MAIL_FROM_NAME_CYBERPNTE'),
            ],
        ],


        'hostinger' => [    // capacitaciones@soporte-pnte.com
            'transport'  => env('MAIL_MAILER_HOSTINGER', 'smtp'),
            'host'       => env('MAIL_HOST_HOSTINGER', 'smtp.hostinger.com'),
            'port'       => env('MAIL_PORT_HOSTINGER', 465),
            'username'   => env('MAIL_USERNAME_HOSTINGER'),
            'password'   => env('MAIL_PASSWORD_HOSTINGER'),
            'encryption' => env('MAIL_ENCRYPTION_HOSTINGER', 'ssl'),
            'timeout'    => null,
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS_HOSTINGER', 'capacitaciones@soporte-pnte.com'),
                'name'    => env('MAIL_FROM_NAME_HOSTINGER', 'PROGRAMA NACIONAL TU EMPRESA'),
            ]
        ],

        'office365' => [   // capacitaciones_tuempresa@produce.gob.pe                      
            'transport' => env('MAIL_MAILER_OFFICE365', 'smtp'),
            'host'      => env('MAIL_HOST_OFFICE365'),
            'port'      => env('MAIL_PORT_OFFICE365'),
            'username'  => env('MAIL_USERNAME_OFFICE365'),
            'password'  => env('MAIL_PASSWORD_OFFICE365'),
            'encryption' => env('MAIL_ENCRYPTION_OFFICE365'),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS_OFFICE365'),
                'name' => env('MAIL_FROM_NAME_OFFICE365'),
            ],
        ],

        'lucho' => [    // tuempresa_temp372@produce.gob.pe
            'transport' => env('MAIL_MAILER_LUCHO', 'smtp'),
            'host' => env('MAIL_HOST_LUCHO'),
            'port' => env('MAIL_PORT_LUCHO'),
            'username' => env('MAIL_USERNAME_LUCHO'),
            'password' => env('MAIL_PASSWORD_LUCHO'),
            'encryption' => env('MAIL_ENCRYPTION_LUCHO'),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS_LUCHO'),
                'name' => env('MAIL_FROM_NAME_LUCHO'),
            ],
        ],


        // 'smtp' => [
        //     'transport' => 'smtp',
        //     'url' => env('MAIL_URL'),
        //     'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
        //     'port' => env('MAIL_PORT', 587),
        //     'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        //     'username' => env('MAIL_USERNAME'),
        //     'password' => env('MAIL_PASSWORD'),
        //     'timeout' => null,
        //     'local_domain' => env('MAIL_EHLO_DOMAIN'),
        // ],


        // CORREOS PARA CAPACITACIONES PP093




        'ses' => [
            'transport' => 'ses',
        ],

        'mailgun' => [
            'transport' => 'mailgun',
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => null,
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all e-mails sent by your application to be sent from
    | the same address. Here, you may specify a name and address that is
    | used globally for all e-mails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Programa Nacional Tu Empresa'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    |
    | If you are using Markdown based email rendering, you may configure your
    | theme and component paths here, allowing you to customize the design
    | of the emails. Or, you may simply stick with the Laravel defaults!
    |
    */

    'markdown' => [
        'theme' => 'default',

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

];
