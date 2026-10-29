<?php
// Double opt-in: confirms a marketing email consent from the link in the confirmation email.
// The link opens a page with a button; only the button press (POST) confirms, so mail scanners
// that open links automatically cannot confirm on the person's behalf.
declare(strict_types=1);
require __DIR__ . '/private/lib.php';

$token = preg_replace('/[^a-f0-9]/', '', (string) ($_REQUEST['t'] ?? '')) ?? '';
$pending = strlen($token) === 32 && update_json('optins.json', function (array &$d) use ($token) {
    return isset($d[$token]) && !$d[$token]['widerrufen']
        && ($d[$token]['bestaetigt'] || strtotime($d[$token]['angemeldet']) >= time() - 30 * 86400);
});

if (!$pending) {
    message_page('Link ungültig oder abgelaufen', [
        'Dieser Bestätigungslink funktioniert nicht mehr. Er gilt 30 Tage. Sie können sich über das Formular auf der Startseite erneut anmelden.',
        '<a href="/">Zur Startseite</a>',
    ], 404);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    update_json('optins.json', function (array &$d) use ($token) {
        if (!$d[$token]['bestaetigt']) {
            $d[$token]['bestaetigt'] = now();
            $d[$token]['bestaetigt_ip'] = client_ip();
        }
    });
    message_page('E-Mail-Adresse bestätigt', [
        'Danke. Wir schicken Ihnen ab jetzt gelegentlich Informationen zu Website-Themen. In jeder E-Mail finden Sie einen Link, mit dem Sie sich wieder abmelden können.',
        '<a href="/">Zur Startseite</a>',
    ]);
    exit;
}

message_page('E-Mail-Adresse bestätigen', [
    'Bitte bestätigen Sie, dass Sie von ' . h(cfg('site_name')) . ' gelegentlich Informationen zu Website-Themen per E-Mail bekommen möchten. Sie können sich jederzeit wieder abmelden.',
    '<form method="post" action="/bestaetigen.php"><input type="hidden" name="t" value="' . h($token) . '"><button class="btn" type="submit">Ja, ich möchte E-Mails bekommen</button></form>',
]);
