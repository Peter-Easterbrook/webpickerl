<?php
// Receives the request form (index.html) and the contact form on the report page (bericht.php).
declare(strict_types=1);
require __DIR__ . '/private/lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /', true, 303);
    exit;
}
if (clean('firma_url') !== '') { // honeypot: pretend success, store nothing
    respond(true, 'Danke, Ihre Anfrage ist angekommen.');
    exit;
}
if (!rate_limit('anfrage', 5, 3600)) {
    respond(false, 'Es kamen in kurzer Zeit sehr viele Anfragen. Bitte versuchen Sie es in einer Stunde noch einmal.', 429);
    exit;
}

$typ = clean('typ', 10) === 'kontakt' ? 'kontakt' : 'check';
$name = clean('name', 120);
$email = clean('email', 200);
$telefon = clean('telefon', 40);
$optin = clean('email_einwilligung', 3) === 'ja';
$site = cfg('site_name');
$errors = [];

if ($typ === 'check') {
    $website = clean('website', 200);
    if (mb_strlen($name) < 2) $errors[] = 'Bitte geben Sie Ihren Namen an.';
    if (!valid_email($email)) $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse an.';
    if (!preg_match('~^(https?://)?([\w-]+\.)+[a-z]{2,}(/\S*)?$~i', $website)) $errors[] = 'Bitte geben Sie die Adresse Ihrer Website an.';
    if (clean('berechtigt', 3) !== 'ja') $errors[] = 'Bitte bestätigen Sie, dass Sie den Check für diese Website anfordern dürfen.';
    $record = [
        'typ' => 'check', 'zeit' => now(), 'name' => $name, 'firma' => clean('firma', 160), 'email' => $email,
        'website' => $website, 'telefon' => $telefon, 'nachricht' => clean('nachricht', 2000),
    ];
} else {
    $code = normalise_code(clean('code', 20));
    $report = load_report($code);
    $weg = clean('weg', 10);
    if (!$report) $errors[] = 'Der Code ist ungültig oder abgelaufen.';
    if (!in_array($weg, ['anruf', 'email', 'post'], true)) $errors[] = 'Bitte wählen Sie, wie wir Sie kontaktieren dürfen.';
    if ($weg === 'anruf' && !preg_match('/^[+0-9 ()\/-]{6,}$/', $telefon)) $errors[] = 'Bitte geben Sie eine Telefonnummer an.';
    if ($weg === 'email' && !valid_email($email)) $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse an.';
    if (clean('einverstanden', 3) !== 'ja') $errors[] = 'Bitte bestätigen Sie, dass wir Sie kontaktieren dürfen.';
    $optin = $weg === 'email';
    $record = [
        'typ' => 'kontakt', 'zeit' => now(), 'code' => $code, 'domain' => $report['domain'] ?? '', 'weg' => $weg,
        'name' => $name, 'email' => $email, 'telefon' => $telefon, 'nachricht' => clean('nachricht', 2000),
        'einwilligung' => sprintf(CONSENT_CONTACT, $site),
    ];
    if ($weg === 'anruf') { // consent to a marketing call, TKG 2021 § 174 (1): keep proof
        $record['einwilligung_ip'] = client_ip();
    }
}

if ($errors) {
    respond(false, implode(' ', $errors), 422);
    exit;
}

append_jsonl('anfragen.jsonl', $record);

// Notify the owner.
$lines = [];
foreach ($record as $k => $v) {
    if ($v !== '' && $k !== 'einwilligung') $lines[] = str_pad($k . ':', 16) . $v;
}
$subject = $typ === 'check' ? 'Neue Anfrage: Website-Check ' . $record['website'] : 'Kontaktwunsch zu Bericht ' . $record['code'] . ' (' . $record['weg'] . ')';
send_mail(cfg('mail_to'), $subject, implode("\n", $lines) . ($optin ? "\n\nE-Mail-Einwilligung angefragt: Bestätigungslink wurde verschickt." : ''));

// Receipt to the requester: transactional, no advertising.
if ($typ === 'check') {
    send_mail($email, 'Ihre Anfrage für den kostenlosen Website-Check', "Guten Tag,\n\n"
        . "danke für Ihre Anfrage. Wir prüfen die öffentlich sichtbaren Seiten von {$record['website']} und schicken Ihnen den Befund "
        . 'in der Regel innerhalb von ' . cfg('befund_tage') . " Werktagen an diese E-Mail-Adresse.\n\n"
        . "Wenn Sie diese Anfrage nicht gestellt haben, antworten Sie bitte kurz auf diese E-Mail. Wir löschen die Angaben dann.\n\n"
        . "Freundliche Grüße");
}

// Double opt-in for marketing email (TKG 2021 § 174, DSGVO Art. 7).
if ($optin && valid_email($email)) {
    $token = bin2hex(random_bytes(16));
    update_json('optins.json', function (array &$d) use ($token, $email, $name, $typ, $site) {
        $d[$token] = [
            'email' => $email, 'name' => $name, 'quelle' => $typ,
            'text' => $typ === 'kontakt' ? sprintf(CONSENT_CONTACT, $site) : sprintf(CONSENT_EMAIL, $site),
            'angemeldet' => now(), 'angemeldet_ip' => client_ip(),
            'bestaetigt' => null, 'bestaetigt_ip' => null, 'widerrufen' => null,
        ];
    });
    $link = cfg('base_url') . '/bestaetigen.php?t=' . $token;
    send_mail($email, 'Bitte bestätigen Sie Ihre E-Mail-Adresse', "Guten Tag,\n\n"
        . "Sie haben angegeben, dass Sie von {$site} Informationen per E-Mail bekommen möchten. "
        . "Bitte bestätigen Sie das mit einem Klick auf diesen Link:\n\n{$link}\n\n"
        . "Ohne Ihre Bestätigung schreiben wir Ihnen keine solchen E-Mails, und wir löschen diese Anmeldung nach 30 Tagen. "
        . "Wenn Sie das nicht waren, können Sie diese E-Mail einfach ignorieren.\n\nFreundliche Grüße");
}

$msg = $typ === 'check'
    ? 'Danke, Ihre Anfrage ist angekommen. Den Befund schicken wir an Ihre E-Mail-Adresse.'
    : ($optin ? 'Danke. Bitte bestätigen Sie noch den Link in der E-Mail, die wir Ihnen gerade geschickt haben.' : 'Danke, wir melden uns wie gewünscht bei Ihnen.');
if ($typ === 'check' && $optin) {
    $msg .= ' Bitte bestätigen Sie außerdem den Link in unserer E-Mail, wenn Sie weitere Informationen bekommen möchten.';
}
respond(true, $msg);
