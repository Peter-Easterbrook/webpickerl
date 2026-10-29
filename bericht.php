<?php
// Personal report page for letter recipients: /check/CODE (rewritten to bericht.php?code=CODE).
declare(strict_types=1);
require __DIR__ . '/private/lib.php';

$raw = (string) ($_GET['code'] ?? '');
$code = normalise_code($raw);

if (!rate_limit('bericht', 30, 3600)) {
    message_page('Bitte kurz warten', ['Es wurden in kurzer Zeit sehr viele Codes abgefragt. Bitte versuchen Sie es in einer Stunde noch einmal.'], 429);
    exit;
}
$report = load_report($code);
if (!$report) {
    message_page('Bericht nicht gefunden', [
        'Zu diesem Code haben wir keinen Bericht gefunden. Bitte prüfen Sie den Code aus dem Brief (vier Zeichen, Bindestrich, vier Zeichen). Berichte sind sechs Monate ab dem Briefdatum abrufbar.',
        '<form class="code-form" method="get" action="/bericht.php"><label for="c-code">Code aus dem Brief</label><div class="code-row"><input type="text" id="c-code" name="code" value="' . h($raw) . '" autocomplete="off" autocapitalize="characters" spellcheck="false"><button class="btn" type="submit">Bericht öffnen</button></div></form>',
        'Sie können auch ohne Code einen <a href="/#anfordern">kostenlosen Check anfordern</a>.',
    ], 404);
    exit;
}

$lib = json_decode((string) file_get_contents(__DIR__ . '/private/befunde.json'), true);
$tiers = [
    'Essentials' => ['Rechtssicher und sicher', 'Datenschutzerklärung, Einwilligung, Impressum, HTTPS, PHP-Absicherung und Datensicherung, ohne Ihre Website umzubauen.'],
    'Professional' => ['Schnell, mobil, gefunden', 'Alles aus Essentials, dazu volle Handy-Tauglichkeit, kurze Ladezeiten, Suchmaschinen-Grundlagen und aktuelle PHP-Version.'],
    'Complete' => ['Fit für 2030', 'Alles aus Professional, dazu Umzug auf ein aktuelles System, volle Barrierefreiheit und eine Auffrischung des Designs.'],
];
$rank = array_flip(array_keys($tiers));
$paket = 'Essentials';
$befunde = [];
foreach ($report['befunde'] as $b) {
    $info = $lib[$b['code'] ?? ''] ?? null;
    $befunde[] = ['text' => $b['text'], 'info' => $info];
    if ($info && $rank[$info['paket']] > $rank[$paket]) $paket = $info['paket'];
}
$labels = ['bad' => 'Handeln', 'warn' => 'Verbessern', 'ok' => 'In Ordnung'];
$site = h(cfg('site_name'));
$domain = h($report['domain']);

page_start('Website-Check für ' . $domain, '<li><a href="#kontakt">Kontakt</a></li>');
?>
<section class="report-page">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">Ihr Website-Check</p>
      <h1>Bericht für <?= $domain ?></h1>
      <p class="report-meta">Öffentliche Seiten geprüft am <?= h($report['audit_datum']) ?> · Code <?= h($code) ?></p>
      <p class="lead">Wir haben uns die Seiten Ihrer Website angesehen, die jede Besucherin und jeder Besucher sehen kann. Hier finden Sie die Punkte aus unserem Brief, ausführlicher erklärt.</p>
    </div>

    <ol class="findings">
<?php foreach ($befunde as $b): $i = $b['info']; $s = $i['schwere'] ?? 'warn'; ?>
      <li class="finding-item">
        <p class="finding-area"><span class="dot <?= $s ?>" aria-hidden="true"></span><?= h($i['bereich'] ?? 'Hinweis') ?> · <span class="verdict <?= $s ?>"><?= $labels[$s] ?></span></p>
        <h2><?= h($i['titel'] ?? 'Hinweis aus dem Brief') ?></h2>
        <p class="finding-letter"><?= h($b['text']) ?></p>
<?php if ($i): ?>
        <h3>Was das bedeutet</h3>
        <p><?= h($i['erklaerung']) ?></p>
        <h3>Was hilft</h3>
        <p><?= h($i['hilfe']) ?></p>
<?php endif; ?>
      </li>
<?php endforeach; ?>
    </ol>

    <div class="recommend">
      <p class="eyebrow">Was zuerst sinnvoll wäre</p>
      <h2>Paket <?= $paket ?>: <?= $tiers[$paket][0] ?></h2>
      <p><?= $tiers[$paket][1] ?> <a href="/#pakete">Alle Pakete im Vergleich</a></p>
    </div>

    <p class="note">Dieser Bericht beruht auf einem Blick von außen. Er ist keine Rechtsberatung und keine abschließende Beurteilung. Manche Dinge, etwa die Einstellungen auf Ihrem Server, sehen wir erst mit Ihrer Erlaubnis. Ob und wie dringend etwas zu tun ist, hängt von Ihrem Betrieb ab. Rechtliche Fragen klären Sie bitte mit Ihrer Rechtsberatung oder dem Rechtsservice der Wirtschaftskammer.</p>
  </div>
</section>

<section id="kontakt" aria-labelledby="h-kontakt">
  <div class="wrap form-wrap">
    <div class="form-intro">
      <p class="eyebrow">Wie geht es weiter?</p>
      <h2 id="h-kontakt">Dürfen wir uns melden?</h2>
      <p>Wenn Sie möchten, besprechen wir den Bericht mit Ihnen oder schicken Ihnen ein unverbindliches Angebot. Wählen Sie einfach, wie wir Sie erreichen dürfen.</p>
      <p>Wenn nicht, müssen Sie nichts tun. Sie hören von uns zu diesem Thema nichts mehr.</p>
      <p class="legal-small">Keine Post mehr von uns? <a href="/stopp?code=<?= h($code) ?>">Hier widersprechen</a>. Details in der <a href="/datenschutz.html">Datenschutzerklärung</a>.</p>
    </div>
    <form class="check" id="kontakt-form" action="/anfrage.php" method="post">
      <input type="hidden" name="typ" value="kontakt">
      <input type="hidden" name="code" value="<?= h($code) ?>">
      <fieldset>
        <legend>Wie dürfen wir Sie kontaktieren?</legend>
        <div class="check-item"><input type="radio" id="k-anruf" name="weg" value="anruf" required><label for="k-anruf">Bitte rufen Sie mich an</label></div>
        <div class="check-item"><input type="radio" id="k-email" name="weg" value="email"><label for="k-email">Bitte schreiben Sie mir eine E-Mail</label></div>
        <div class="check-item"><input type="radio" id="k-post" name="weg" value="post"><label for="k-post">Bitte schicken Sie mir ein Angebot per Post an die Adresse aus dem Brief</label></div>
      </fieldset>
      <div class="field">
        <label for="k-name">Ihr Name <span class="opt">(freiwillig)</span></label>
        <input type="text" id="k-name" name="name" autocomplete="name">
      </div>
      <div class="row">
        <div class="field">
          <label for="k-tel">Telefon <span class="opt">(für einen Anruf)</span></label>
          <input type="tel" id="k-tel" name="telefon" autocomplete="tel">
        </div>
        <div class="field">
          <label for="k-mail">E-Mail <span class="opt">(für eine E-Mail)</span></label>
          <input type="email" id="k-mail" name="email" autocomplete="email">
        </div>
      </div>
      <div class="field">
        <label for="k-msg">Wann passt es Ihnen, oder gibt es eine Frage? <span class="opt">(freiwillig)</span></label>
        <textarea id="k-msg" name="nachricht" rows="3"></textarea>
      </div>
      <div class="hp" aria-hidden="true">
        <label for="k-hp">Bitte dieses Feld leer lassen</label>
        <input type="text" id="k-hp" name="firma_url" tabindex="-1" autocomplete="off">
      </div>
      <div class="check-item">
        <input type="checkbox" id="k-ok" name="einverstanden" value="ja" required>
        <label for="k-ok"><?= h(sprintf(CONSENT_CONTACT, cfg('site_name'))) ?></label>
      </div>
      <p class="hint">Bei E-Mail schicken wir Ihnen zuerst einen Bestätigungslink. Erst nach dem Klick darauf schreiben wir Ihnen.</p>
      <div><button class="btn" type="submit">Absenden</button></div>
    </form>
  </div>
</section>
<?php
page_end();
