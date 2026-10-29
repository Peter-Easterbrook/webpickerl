<?php
// Withdraws a marketing email consent (link at the bottom of every marketing email).
declare(strict_types=1);
require __DIR__ . '/private/lib.php';

$token = preg_replace('/[^a-f0-9]/', '', (string) ($_GET['t'] ?? '')) ?? '';
$ok = strlen($token) === 32 && update_json('optins.json', function (array &$d) use ($token) {
    if (!isset($d[$token])) return false;
    $d[$token]['widerrufen'] = $d[$token]['widerrufen'] ?: now();
    return true;
});

message_page($ok ? 'Sie sind abgemeldet' : 'Link ungültig', $ok
    ? ['Sie bekommen von uns keine weiteren Informations-E-Mails. Falls gerade eine E-Mail unterwegs war, kann sie Sie noch erreichen.', '<a href="/">Zur Startseite</a>']
    : ['Diesen Abmeldelink kennen wir nicht. Schreiben Sie uns bitte formlos an ' . h(cfg('reply_to')) . ', dann melden wir Sie von Hand ab.'], $ok ? 200 : 404);
