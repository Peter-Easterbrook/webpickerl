<?php
// Shared helpers: config, escaping, page layout, storage, rate limits and mail.
declare(strict_types=1);

const CONSENT_EMAIL = 'Ja, ich möchte auch später Informationen zu Website-Themen und Angeboten von %s per E-Mail bekommen.';
const CONSENT_CONTACT = 'Ich bin damit einverstanden, dass %s mich zum Website-Check und zum Angebot „Website Upgrade 2026“ auf dem gewählten Weg kontaktiert. Ich kann das jederzeit widerrufen.';

function cfg(string $key)
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    return $config[$key];
}

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function security_headers(): void
{
    header('Cache-Control: no-store, private');
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
}

function page_start(string $title, string $nav = ''): void
{
    security_headers();
    header('Content-Type: text/html; charset=utf-8');
    $site = h(cfg('site_name'));
    echo <<<HTML
<!doctype html>
<html lang="de-AT">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{$title}</title>
<meta name="robots" content="noindex, nofollow">
<meta name="color-scheme" content="light dark">
<link rel="stylesheet" href="/assets/styles.css">
<link rel="icon" href="/favicon.ico" sizes="32x32">
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
</head>
<body>
<a class="skip" href="#inhalt">Zum Inhalt springen</a>
<header class="site-header">
  <div class="wrap">
    <a class="brand" href="/"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>{$site}</span></a>
    <nav class="site-nav" aria-label="Hauptnavigation">
      <ul>
        <li><a href="/">Startseite</a></li>
        {$nav}
      </ul>
    </nav>
  </div>
</header>
<main id="inhalt">

HTML;
}

function page_end(): void
{
    $site = h(cfg('site_name'));
    $year = date('Y');
    echo <<<HTML

</main>
<footer class="site-footer">
  <div class="wrap">
    <p>© {$year} {$site} · Diese Seite setzt keine Cookies und lädt keine Inhalte von Drittanbietern.</p>
    <ul>
      <li><a href="/impressum.html">Impressum</a></li>
      <li><a href="/datenschutz.html">Datenschutz</a></li>
      <li><a href="/barrierefreiheit.html">Barrierefreiheit</a></li>
    </ul>
  </div>
</footer>
<script src="/assets/main.js" defer></script>
</body>
</html>
HTML;
}

/** Simple page with a heading and paragraphs (paragraph HTML must already be escaped). */
function message_page(string $title, array $paragraphs, int $status = 200): void
{
    http_response_code($status);
    page_start(h($title));
    echo "<section class=\"legal\"><div class=\"wrap\">\n<h1>" . h($title) . "</h1>\n";
    foreach ($paragraphs as $p) {
        echo str_starts_with($p, '<form') ? $p . "\n" : "<p>{$p}</p>\n";
    }
    echo "</div></section>\n";
    page_end();
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function ip_hash(): string
{
    return substr(hash('sha256', cfg('ip_salt') . client_ip() . date('Y-m-d')), 0, 16);
}

function now(): string
{
    return date('c');
}

function data_path(string $name): string
{
    $dir = cfg('data_dir');
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    return $dir . '/' . $name;
}

function append_jsonl(string $file, array $record): void
{
    file_put_contents(data_path($file), json_encode($record, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

/** Read-modify-write a JSON object file under an exclusive lock. */
function update_json(string $file, callable $fn)
{
    $fp = fopen(data_path($file), 'c+');
    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $data = $raw ? (json_decode($raw, true) ?: []) : [];
    $result = $fn($data);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    flock($fp, LOCK_UN);
    fclose($fp);
    return $result;
}

/** Returns false once $max hits for $bucket happened within $window seconds. */
function rate_limit(string $bucket, int $max, int $window): bool
{
    $key = $bucket . ':' . ip_hash();
    return update_json('ratelimit.json', function (array &$d) use ($key, $max, $window) {
        $t = time();
        foreach ($d as $k => $hits) {
            $d[$k] = array_values(array_filter($hits, fn($h) => $h > $t - 86400));
            if (!$d[$k]) unset($d[$k]);
        }
        $recent = array_filter($d[$key] ?? [], fn($h) => $h > $t - $window);
        if (count($recent) >= $max) {
            return false;
        }
        $d[$key][] = $t;
        return true;
    });
}

function clean(string $field, int $max = 200): string
{
    $v = trim((string) ($_POST[$field] ?? ''));
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) ?? '';
    return mb_substr($v, 0, $max);
}

function valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function normalise_code(string $code): string
{
    $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    return strlen($code) === 8 ? substr($code, 0, 4) . '-' . substr($code, 4) : '';
}

function load_report(string $code): ?array
{
    if ($code === '') return null;
    $file = cfg('reports_dir') . '/' . $code . '.json';
    if (!is_file($file)) return null;
    $r = json_decode((string) file_get_contents($file), true);
    if (!is_array($r)) return null;
    if (!empty($r['gueltig_bis']) && $r['gueltig_bis'] < date('Y-m-d')) return null;
    return $r;
}

function send_mail(string $to, string $subject, string $body): bool
{
    $from = cfg('mail_from');
    $headers = [
        'From' => mb_encode_mimeheader(cfg('site_name'), 'UTF-8', 'Q') . " <{$from}>",
        'Reply-To' => cfg('reply_to'),
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => '8bit',
    ];
    $body .= "\n\n-- \n" . cfg('site_name') . "\n" . cfg('base_url') . "\nImpressum: " . cfg('base_url') . "/impressum.html\n";
    return mail($to, mb_encode_mimeheader($subject, 'UTF-8', 'Q'), $body, $headers, '-f' . $from);
}

function wants_json(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function respond(bool $ok, string $message, int $status = 200): void
{
    if (wants_json()) {
        security_headers();
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
        return;
    }
    message_page($ok ? 'Danke' : 'Das hat nicht funktioniert', [h($message), '<a href="/">Zurück zur Startseite</a>'], $status);
}
