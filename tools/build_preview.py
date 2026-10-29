"""Writes a preview copy of index.html without the document skeleton (for the Claude Artifact preview).
Usage: python3 tools/build_preview.py OUT_DIR"""
import re, sys
from pathlib import Path
ROOT = Path(__file__).resolve().parent.parent
out = Path(sys.argv[1]); out.mkdir(parents=True, exist_ok=True)
html = (ROOT / "index.html").read_text(encoding="utf-8")
head = re.search(r"<head>(.*?)</head>", html, re.S).group(1)
body = re.search(r"<body>(.*?)</body>", html, re.S).group(1)
head = "\n".join(l for l in head.splitlines() if not re.match(r"\s*<meta (charset|name=\"viewport\")", l))
body = body.replace('action="anfrage.php"', 'action="[vorschau]"')
body = re.sub(r'<form class="code-form".*?</form>',
              '<p><a class="btn" href="bericht-beispiel.html">Beispielbericht öffnen (Vorschau)</a></p>', body, flags=re.S)
(out / "index.html").write_text(head.strip() + "\n" + body.strip() + "\n", encoding="utf-8")
print("wrote", out / "index.html")
