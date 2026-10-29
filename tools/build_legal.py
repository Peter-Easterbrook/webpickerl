"""Builds impressum.html, datenschutz.html and barrierefreiheit.html with the shared header and footer.
Edit the text in legal/*.html (body fragments), then run: python3 tools/build_legal.py"""
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
PAGES = {
    "impressum": "Impressum",
    "datenschutz": "Datenschutzerklärung",
    "barrierefreiheit": "Erklärung zur Barrierefreiheit",
}

TEMPLATE = """<!doctype html>
<html lang="de-AT">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{title}</title>
<meta name="robots" content="noindex, follow">
<meta name="color-scheme" content="light dark">
<link rel="stylesheet" href="assets/styles.css">
<link rel="icon" href="favicon.ico" sizes="32x32">
<link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
</head>
<body>
<a class="skip" href="#inhalt">Zum Inhalt springen</a>

<!-- ENTWURF: diesen Hinweis vor dem Livegang entfernen -->
<div class="draft" role="note"><div class="wrap">Entwurf: Angaben in <span class="ph">[eckigen Klammern]</span> sind Platzhalter. Rechtstexte vor der Veröffentlichung von einer Rechtsanwältin, einem Rechtsanwalt oder dem WKO-Rechtsservice prüfen lassen.</div></div>

<header class="site-header">
  <div class="wrap">
    <a class="brand" href="index.html"><img class="brand-mark" src="assets/favicon.svg" alt="" width="32" height="32">Webpickerl</a>
    <nav class="site-nav" aria-label="Hauptnavigation">
      <ul>
        <li><a href="index.html">Startseite</a></li>
        <li><a href="index.html#anfordern">Check anfordern</a></li>
      </ul>
    </nav>
  </div>
</header>

<main id="inhalt" class="legal">
  <div class="wrap">
    <h1>{title}</h1>
{body}
  </div>
</main>

<footer class="site-footer">
  <div class="wrap">
    <p>© 2026 Webpickerl · Diese Seite setzt keine Cookies und lädt keine Inhalte von Drittanbietern.</p>
    <ul>
      <li><a href="impressum.html"{cur_impressum}>Impressum</a></li>
      <li><a href="datenschutz.html"{cur_datenschutz}>Datenschutz</a></li>
      <li><a href="barrierefreiheit.html"{cur_barrierefreiheit}>Barrierefreiheit</a></li>
    </ul>
  </div>
</footer>
</body>
</html>
"""

for slug, title in PAGES.items():
    body = (ROOT / "legal" / f"{slug}.html").read_text(encoding="utf-8")
    cur = {f"cur_{s}": (' aria-current="page"' if s == slug else "") for s in PAGES}
    html = TEMPLATE.format(title=title, body=body.rstrip(), **cur)
    (ROOT / f"{slug}.html").write_text(html, encoding="utf-8")
    print("wrote", slug + ".html")
