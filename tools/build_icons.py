"""Generate the site icons from one geometry.

Writes assets/favicon.svg, assets/apple-touch-icon.png, assets/icon-192.png,
assets/icon-512.png, favicon.ico (root, where browsers look by default) and the
logos assets/logo.svg (colour) and assets/logo-mono.svg (one colour).
Needs Pillow, fontTools and brotli. Shapes are on a 32-unit grid; PNGs are
drawn 4x and downsampled.
"""

from pathlib import Path

from PIL import Image, ImageDraw

ROOT = Path(__file__).resolve().parent.parent
NAVY = "#00274e"
TEAL = "#6cc6d9"
WHITE = "#ffffff"
SUPERSAMPLE = 4

CHECK = (
    '<path d="M9 18.6l4.8 4.8 9.2-9.2" fill="none" stroke="{c}" stroke-width="3.8" '
    'stroke-linecap="round" stroke-linejoin="round"/>'
)
TILE = (
    f'<rect width="32" height="32" rx="7" fill="{NAVY}"/>'
    f'<rect x="2" y="3.5" width="28" height="25" rx="4.5" fill="{WHITE}"/>'
    f'<path d="M6.5 3.5h19a4.5 4.5 0 0 1 4.5 4.5v1H2V8a4.5 4.5 0 0 1 4.5-4.5z" fill="{TEAL}"/>'
    + CHECK.format(c=NAVY)
)
# One colour: the window is cut out of the tile, the title bar merges into the frame.
TILE_MONO = (
    '<path fill-rule="evenodd" fill="{c}" d="M7 0h18a7 7 0 0 1 7 7v18a7 7 0 0 1-7 7H7a7 7 0 0 1-7-7V7a7 7 0 0 1 7-7z'
    'M2 9h28v15a4.5 4.5 0 0 1-4.5 4.5h-19A4.5 4.5 0 0 1 2 24z"/>'
    + CHECK
)
SVG = f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">\n{TILE}\n</svg>\n'

WORDMARK = "Webpickerl"
WORDMARK_FONT = ROOT / "assets" / "fonts" / "bricolage-grotesque-latin-400-normal.woff2"
CAP_HEIGHT = 18  # wordmark cap height in tile units (tile is 32)
GAP = 9  # space between tile and wordmark


def kerning(font):
    """Pair kerning from GPOS (format 1 and 2 PairPos), as {(left, right): x_advance}."""
    pairs, classes = {}, []
    for lookup in font["GPOS"].table.LookupList.Lookup:
        for st in lookup.SubTable:
            if lookup.LookupType == 9:
                st = st.ExtSubTable
            if getattr(st, "LookupType", lookup.LookupType) != 2:
                continue
            if st.Format == 1:
                for left, ps in zip(st.Coverage.glyphs, st.PairSet):
                    for pv in ps.PairValueRecord:
                        if pv.Value1 and getattr(pv.Value1, "XAdvance", 0):
                            pairs.setdefault((left, pv.SecondGlyph), pv.Value1.XAdvance)
            elif st.Format == 2:
                classes.append(st)
    return pairs, classes


def kern(pairs, classes, left, right):
    if (left, right) in pairs:
        return pairs[(left, right)]
    for st in classes:
        if left not in st.Coverage.glyphs:
            continue
        c1 = st.ClassDef1.classDefs.get(left, 0)
        c2 = st.ClassDef2.classDefs.get(right, 0)
        v = st.Class1Record[c1].Class2Record[c2].Value1
        if v and getattr(v, "XAdvance", 0):
            return v.XAdvance
    return 0


def wordmark(fill, x0, baseline):
    """The name as outlined paths, so the logo needs no installed font. Returns (svg, width)."""
    from fontTools.pens.svgPathPen import SVGPathPen
    from fontTools.ttLib import TTFont

    font = TTFont(WORDMARK_FONT)
    glyphs = font.getGlyphSet()
    cmap = font.getBestCmap()
    pairs, classes = kerning(font)
    k = CAP_HEIGHT / font["OS/2"].sCapHeight
    names = [cmap[ord(ch)] for ch in WORDMARK]
    parts, x = [], 0
    for i, name in enumerate(names):
        pen = SVGPathPen(glyphs)
        glyphs[name].draw(pen)
        parts.append(f'<path transform="translate({x:g} 0)" d="{pen.getCommands()}"/>')
        x += font["hmtx"][name][0]
        if i + 1 < len(names):
            x += kern(pairs, classes, name, names[i + 1])
    svg = (
        f'<g fill="{fill}" transform="translate({x0:g} {baseline:g}) scale({k:.6f} {-k:.6f})">'
        + "".join(parts)
        + "</g>"
    )
    return svg, x * k


def logo(tile, text_fill):
    baseline = 16 + CAP_HEIGHT / 2
    text, width = wordmark(text_fill, 32 + GAP, baseline)
    w = 32 + GAP + width
    return (
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w:.2f} 32">'
        f'<title>Webpickerl</title>\n{tile}\n{text}\n</svg>\n'
    )


def render(size, bleed=False):
    """Draw the tile at `size` px. `bleed` fills the square edge to edge
    (for iOS/Android, which round the corners themselves) and keeps a wider margin."""
    px = size * SUPERSAMPLE
    img = Image.new("RGBA", (px, px), (0, 0, 0, 0))
    d = ImageDraw.Draw(img)
    unit = px / 32
    offset, scale = (2.4, 0.85) if bleed else (0, 1)

    def p(v):
        return (offset + scale * v) * unit

    def box(x0, y0, x1, y1):
        return [p(x0), p(y0), p(x1), p(y1)]

    if bleed:
        d.rectangle([0, 0, px, px], fill=NAVY)
    else:
        d.rounded_rectangle([0, 0, px - 1, px - 1], radius=7 * unit, fill=NAVY)
    r = 4.5 * scale * unit
    d.rounded_rectangle(box(2, 3.5, 30, 28.5), radius=r, fill=WHITE)
    d.rounded_rectangle(box(2, 3.5, 30, 13.5), radius=r, fill=TEAL)
    d.rectangle(box(2, 9, 30, 13.5), fill=WHITE)

    width = 3.8 * scale * unit
    points = [(p(9), p(18.6)), (p(13.8), p(23.4)), (p(23), p(14.2))]
    d.line(points, fill=NAVY, width=round(width), joint="curve")
    for x, y in (points[0], points[-1]):
        d.ellipse([x - width / 2, y - width / 2, x + width / 2, y + width / 2], fill=NAVY)

    return img.resize((size, size), Image.LANCZOS)


def main():
    assets = ROOT / "assets"
    (assets / "favicon.svg").write_text(SVG, encoding="utf-8")
    render(180, bleed=True).convert("RGB").save(assets / "apple-touch-icon.png", optimize=True)
    render(192, bleed=True).save(assets / "icon-192.png", optimize=True)
    render(512, bleed=True).save(assets / "icon-512.png", optimize=True)
    icons = [render(s) for s in (16, 32, 48)]
    icons[-1].save(
        ROOT / "favicon.ico",
        format="ICO",
        sizes=[(16, 16), (32, 32), (48, 48)],
        append_images=icons[:-1],
    )
    (assets / "logo.svg").write_text(logo(TILE, NAVY), encoding="utf-8")
    (assets / "logo-mono.svg").write_text(logo(TILE_MONO.format(c=NAVY), NAVY), encoding="utf-8")
    print("wrote assets/favicon.svg, assets/logo*.svg, assets/*.png, favicon.ico")


if __name__ == "__main__":
    main()
