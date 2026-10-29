<?php
// Settings for the form handler and report pages. Fill in every [placeholder] before going live.
// On All-Inkl: create the sender mailbox in KAS first and enable DKIM for the domain.
return [
    'site_name'   => 'Webpickerl',
    'base_url'    => 'https://webpickerl.at',     // no trailing slash
    'mail_from'   => 'noreply@webpickerl.at',     // must be a mailbox on this domain
    'mail_to'     => 'office@webpickerl.at',      // where new requests are sent
    'reply_to'    => 'office@webpickerl.at',
    'phone'       => '+43 680 3123714',
    'befund_tage' => 5,                              // promised working days for the report
    // used to pseudonymise IPs for rate limits; the real value lives in the git-ignored salt.php
    'ip_salt'     => is_file(__DIR__ . '/salt.php') ? (require __DIR__ . '/salt.php') : 'CHANGE-ME-to-a-long-random-string',
    'data_dir'    => __DIR__ . '/data',
    'reports_dir' => __DIR__ . '/reports',
];
