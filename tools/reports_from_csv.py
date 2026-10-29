"""Creates one report file per letter from the mail-merge CSV, for the /check/CODE pages.

Usage: python3 tools/reports_from_csv.py PATH/TO/mailmerge.csv
Writes private/reports/<Audit_Code>.json. Upload that folder to the server together with the letters.
Each Befund text is matched to its explanation in private/befunde.json by the start of the sentence;
optional columns Befund_1_Code .. Befund_3_Code override the match. Reports expire 6 months after the letter date."""
import csv, json, re, sys
from datetime import date
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
LIB = json.loads((ROOT / "private" / "befunde.json").read_text(encoding="utf-8"))
OUT = ROOT / "private" / "reports"
MONTHS = {m: i for i, m in enumerate(["jänner", "februar", "märz", "april", "mai", "juni", "juli", "august",
                                      "september", "oktober", "november", "dezember"], 1)}
MONTHS["januar"] = 1; MONTHS["feber"] = 2


def parse_date(s):
    m = re.match(r"\s*(\d{1,2})\.\s*([A-Za-zäÄ]+)\s+(\d{4})", s or "")
    if not m:
        return date.today()
    return date(int(m.group(3)), MONTHS[m.group(2).lower()], int(m.group(1)))


def match_code(text):
    for code, info in LIB.items():
        if text.strip().startswith(info["start"]):
            return code
    return None


def main(csv_path):
    OUT.mkdir(parents=True, exist_ok=True)
    with open(csv_path, encoding="utf-8-sig", newline="") as f:
        rows = list(csv.DictReader(f, delimiter=";"))
    for row in rows:
        code = row["Audit_Code"].strip().upper()
        if not re.fullmatch(r"[A-Z0-9]{4}-[A-Z0-9]{4}", code):
            sys.exit(f"Invalid Audit_Code {code!r}: use 4 characters, a hyphen, 4 characters.")
        befunde = []
        for i in (1, 2, 3):
            text = (row.get(f"Befund_{i}") or "").strip()
            if not text:
                continue
            fcode = (row.get(f"Befund_{i}_Code") or "").strip() or match_code(text)
            if not fcode:
                print(f"  {code}: no explanation found for Befund_{i}, shown without one: {text[:60]}")
            befunde.append({"code": fcode, "text": text})
        letter = parse_date(row.get("Briefdatum", ""))
        expires = date(letter.year + (letter.month + 5) // 12, (letter.month + 5) % 12 + 1, min(letter.day, 28))
        report = {
            "code": code, "domain": row["Domain"].strip(), "firma": row["Firma"].strip(),
            "audit_datum": row["Audit_Datum"].strip(), "gueltig_bis": expires.isoformat(), "befunde": befunde,
        }
        (OUT / f"{code}.json").write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
        print("wrote", code, report["domain"], "valid until", report["gueltig_bis"])


if __name__ == "__main__":
    main(sys.argv[1])
