<?php
// Objection to further letters (Art. 21 DSGVO): /stopp, linked in the letter and on the report page.
declare(strict_types=1);
require __DIR__ . '/private/lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (clean('firma_url') !== '' || !rate_limit('stopp', 10, 3600)) {
        message_page('Danke', ['Ihr Widerspruch ist angekommen.']);
        exit;
    }
    $code = normalise_code(clean('code', 20));
    $firma = clean('firma', 160);
    $adresse = clean('adresse', 200);
    $email = clean('email', 200);
    if ($code === '' && ($firma === '' || $adresse === '')) {
        message_page('Bitte ergänzen Sie Ihre Angaben', [
            'Damit wir Sie zuordnen können, brauchen wir entweder den Code aus dem Brief oder Firmenname und Anschrift.',
            '<a href="/stopp">Zurück zum Formular</a>',
        ], 422);
        exit;
    }
    $report = load_report($code);
    append_jsonl('sperrliste.jsonl', [
        'zeit' => now(), 'code' => $code, 'domain' => $report['domain'] ?? '', 'firma' => $firma ?: ($report['firma'] ?? ''), 'adresse' => $adresse,
    ]);
    // The report is no longer needed once the recipient has objected.
    if ($report) {
        @unlink(cfg('reports_dir') . '/' . $code . '.json');
    }
    // Also withdraw any email consent for the given address.
    if (valid_email($email)) {
        update_json('optins.json', function (array &$d) use ($email) {
            foreach ($d as &$o) {
                if (strcasecmp($o['email'], $email) === 0 && !$o['widerrufen']) $o['widerrufen'] = now();
            }
        });
    }
    send_mail(cfg('mail_to'), 'Widerspruch: keine Post mehr' . ($code ? " ({$code})" : ''),
        "Code: {$code}\nDomain: " . ($report['domain'] ?? '') . "\nFirma: {$firma}\nAnschrift: {$adresse}\nE-Mail: {$email}\n\n"
        . "Bitte in die Sperrliste für Briefe übernehmen und die übrigen Daten löschen.");
    message_page('Ihr Widerspruch ist angekommen', [
        'Wir schreiben Ihnen keine weiteren Briefe und verwenden Ihre Daten nicht mehr für Werbung. Wir behalten nur Firmenname und Anschrift in einer Sperrliste, damit Sie sicher keine Post mehr bekommen.',
        '<a href="/">Zur Startseite</a>',
    ]);
    exit;
}

$code = h(normalise_code((string) ($_GET['code'] ?? '')) ?: (string) ($_GET['code'] ?? ''));
page_start('Keine Post mehr');
?>
<section class="legal">
  <div class="wrap">
    <h1>Keine Post mehr</h1>
    <p>Sie möchten keine weiteren Briefe von uns? Geben Sie den Code aus dem Brief an, oder, falls Sie ihn nicht zur Hand haben, Firmenname und Anschrift. Sie können auch einfach anrufen (<?= h(cfg('phone')) ?>) oder an <?= h(cfg('reply_to')) ?> schreiben.</p>
    <form class="check" method="post" action="/stopp">
      <div class="field">
        <label for="s-code">Code aus dem Brief</label>
        <input type="text" id="s-code" name="code" value="<?= $code ?>" autocomplete="off" autocapitalize="characters" spellcheck="false">
      </div>
      <p class="hint">Ohne Code:</p>
      <div class="row">
        <div class="field">
          <label for="s-firma">Firmenname</label>
          <input type="text" id="s-firma" name="firma" autocomplete="organization">
        </div>
        <div class="field">
          <label for="s-adresse">Anschrift</label>
          <input type="text" id="s-adresse" name="adresse" autocomplete="street-address">
        </div>
      </div>
      <div class="field">
        <label for="s-mail">E-Mail-Adresse <span class="opt">(freiwillig, falls Sie auch E-Mails von uns abbestellen möchten)</span></label>
        <input type="email" id="s-mail" name="email" autocomplete="email">
      </div>
      <div class="hp" aria-hidden="true">
        <label for="s-hp">Bitte dieses Feld leer lassen</label>
        <input type="text" id="s-hp" name="firma_url" tabindex="-1" autocomplete="off">
      </div>
      <div class="form-actions"><button class="btn" type="submit">Keine Post mehr schicken</button></div>
    </form>
  </div>
</section>
<?php
page_end();
