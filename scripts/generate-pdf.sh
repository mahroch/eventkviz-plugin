#!/usr/bin/env bash
#
# generate-pdf.sh — generuje PDF z MD súboru s automatickým semver versioningom
#
# Použitie:
#   bash scripts/generate-pdf.sh path/to/FILE.md            # patch bump (1.0 → 1.1)
#   bash scripts/generate-pdf.sh path/to/FILE.md --minor    # minor bump (1.1 → 2.0)
#   bash scripts/generate-pdf.sh path/to/FILE.md --major    # major bump (2.0 → 3.0)
#   bash scripts/generate-pdf.sh --all <dir>                # všetky MD v dir
#
# Co robí:
#   1. Načíta pdf_version z YAML frontmatteru MD (alebo začne na 0.0)
#   2. Inkrementuje verziu podľa flagu (default patch)
#   3. Updatuje frontmatter v MD (pdf_version + pdf_updated_at + pdf_filename)
#   4. MD → HTML cez pandoc
#   5. HTML → PDF cez Google Chrome headless
#   6. Vytvorí <basename>-v<X.Y>.pdf v target dir
#   7. Vymaže STARÉ <basename>-v*.pdf (cleanup)
#
# Konvencie (Maros 2026-06-05, centralizované cross-project):
#   - ŽIADNE -latest.pdf symlinky (Foxit ich nevie otvoriť)
#   - macOS mktemp NIKDY s explicit extension v template (random suffix problém)
#
# Vyžaduje:
#   - pandoc (brew install pandoc)
#   - Google Chrome v /Applications/

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CSS_FILE="$SCRIPT_DIR/pdf-style.css"
CHROME="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"

if [[ ! -f "$CSS_FILE" ]]; then
  echo "❌ CSS file not found: $CSS_FILE"
  exit 1
fi

if [[ ! -x "$CHROME" ]]; then
  echo "❌ Google Chrome not found at: $CHROME"
  exit 1
fi

if ! command -v pandoc >/dev/null 2>&1; then
  echo "❌ pandoc not installed. Run: brew install pandoc"
  exit 1
fi

# --- Parse arguments ---
BUMP="patch"
TARGET=""

if [[ "$1" == "--all" ]]; then
  ALL_DIR="${2:-$(pwd)}"
  if [[ ! -d "$ALL_DIR" ]]; then
    echo "❌ --all dir not found: $ALL_DIR"
    exit 1
  fi
  for md in "$ALL_DIR"/*.md; do
    [[ -f "$md" ]] || continue
    bash "$0" "$md"
  done
  exit 0
fi

if [[ -z "$1" ]]; then
  echo "Použitie: $0 <path/to/file.md> [--patch|--minor|--major]"
  echo "         $0 --all <dir>"
  exit 1
fi

TARGET="$1"
[[ "$2" == "--minor" ]] && BUMP="minor"
[[ "$2" == "--major" ]] && BUMP="major"

if [[ ! -f "$TARGET" ]]; then
  echo "❌ File not found: $TARGET"
  exit 1
fi

BASENAME=$(basename "$TARGET" .md)
DIR=$(cd "$(dirname "$TARGET")" && pwd)
PARENT_DIRNAME="$(basename "$DIR")"

# --- Read current version from frontmatter ---
CURRENT_VERSION=$(awk '/^---$/{ if(++n==2) exit } n==1 && /^pdf_version:/ { gsub(/^pdf_version: +/, ""); gsub(/"/, ""); print; exit }' "$TARGET" || echo "")
[[ -z "$CURRENT_VERSION" ]] && CURRENT_VERSION="0.0"

MAJOR=$(echo "$CURRENT_VERSION" | cut -d. -f1)
MINOR=$(echo "$CURRENT_VERSION" | cut -d. -f2)
[[ -z "$MAJOR" ]] && MAJOR=0
[[ -z "$MINOR" ]] && MINOR=0

case "$BUMP" in
  major) MAJOR=$((MAJOR + 1)); MINOR=0 ;;
  minor) MINOR=$((MINOR + 1)) ;;
  patch) MINOR=$((MINOR + 1)) ;;
esac

NEW_VERSION="${MAJOR}.${MINOR}"
PDF_FILENAME="${BASENAME}-v${NEW_VERSION}.pdf"
PDF_PATH="${DIR}/${PDF_FILENAME}"
UPDATED_AT=$(date -u +"%Y-%m-%d %H:%M UTC")

echo "📝 $BASENAME.md"
echo "   verzia: $CURRENT_VERSION → $NEW_VERSION"
echo "   PDF:    $PDF_FILENAME"

# Cleanup starých verzií (vymaže VŠETKY <basename>-v*.pdf okrem práve generovanej).
for old in "${DIR}/${BASENAME}-v"*.pdf; do
  if [[ -f "$old" && "$old" != "$PDF_PATH" ]]; then
    rm -f "$old"
    echo "   🗑️  cleanup: $(basename "$old")"
  fi
done

# --- Update frontmatter v MD ---
# Maros 2026-06-05: mktemp BEZ extension v template (macOS random suffix problém).
TMP="$(mktemp -t mdfm-XXXXXX)"
if head -1 "$TARGET" | grep -q "^---$"; then
  awk -v ver="$NEW_VERSION" -v ts="$UPDATED_AT" -v fname="$PDF_FILENAME" '
    BEGIN { in_fm = 0; fm_done = 0; seen_ver = 0; seen_ts = 0; seen_fn = 0 }
    /^---$/ {
      if (in_fm == 0 && fm_done == 0) {
        print; in_fm = 1; next
      } else if (in_fm == 1) {
        if (!seen_ver) print "pdf_version: " ver
        if (!seen_ts) print "pdf_updated_at: " ts
        if (!seen_fn) print "pdf_filename: " fname
        print; in_fm = 0; fm_done = 1; next
      }
    }
    in_fm == 1 {
      if (/^pdf_version:/) { print "pdf_version: " ver; seen_ver = 1; next }
      if (/^pdf_updated_at:/) { print "pdf_updated_at: " ts; seen_ts = 1; next }
      if (/^pdf_filename:/) { print "pdf_filename: " fname; seen_fn = 1; next }
    }
    { print }
  ' "$TARGET" > "$TMP"
else
  {
    echo "---"
    echo "pdf_version: $NEW_VERSION"
    echo "pdf_updated_at: $UPDATED_AT"
    echo "pdf_filename: $PDF_FILENAME"
    echo "---"
    echo ""
    cat "$TARGET"
  } > "$TMP"
fi
mv "$TMP" "$TARGET"

# --- Pripravím MD bez frontmatteru + s PDF headerom ---
# Maros 2026-06-05: mktemp BEZ .md v template, explicit mv na .md.
TMP_MD_RAW="$(mktemp -t pdf-md-XXXXXX)"
TMP_MD="${TMP_MD_RAW}.md"
mv "$TMP_MD_RAW" "$TMP_MD"
{
  cat <<EOF
<div class="pdf-header">
<strong>📄 ${BASENAME}</strong> — verzia <strong>${NEW_VERSION}</strong> · vygenerované ${UPDATED_AT}<br>
<em>Master MD súbor: <code>${BASENAME}.md</code> v ${PARENT_DIRNAME}/</em>
</div>

EOF
  # Skip YAML frontmatter (first --- block)
  awk 'BEGIN{n=0; printing=0} /^---$/{n++; if(n==2){printing=1; next}; if(n==1) next} printing || n==0 {print}' "$TARGET"
} > "$TMP_MD"

# --- MD → HTML cez pandoc ---
# Maros 2026-06-05: mktemp BEZ .html v template (macOS random suffix → Chrome
# renderuje ako plain text → PDF obsahuje HTML zdrojový kód). Riešenie: mv na .html.
echo "   🔄 pandoc: MD → HTML"
TMP_HTML_RAW="$(mktemp -t pdf-html-XXXXXX)"
TMP_HTML="${TMP_HTML_RAW}.html"
mv "$TMP_HTML_RAW" "$TMP_HTML"
pandoc "$TMP_MD" \
  -f markdown+pipe_tables+yaml_metadata_block \
  -t html5 \
  -s \
  --metadata title="${BASENAME} v${NEW_VERSION}" \
  --css "file://${CSS_FILE}" \
  -o "$TMP_HTML"

# --- HTML → PDF cez Chrome headless ---
echo "   🚀 Chrome headless: HTML → PDF"
"$CHROME" \
  --headless=new \
  --no-sandbox \
  --disable-gpu \
  --no-pdf-header-footer \
  --virtual-time-budget=30000 \
  --print-to-pdf="$PDF_PATH" \
  "file://${TMP_HTML}" 2>/dev/null

if [[ ! -f "$PDF_PATH" ]]; then
  echo "   ❌ PDF generation failed"
  rm -f "$TMP_MD" "$TMP_HTML"
  exit 1
fi

# --- Cleanup tmp ---
rm -f "$TMP_MD" "$TMP_HTML"

SIZE=$(stat -f %z "$PDF_PATH" 2>/dev/null | awk '{ printf "%.1f KB\n", $1/1024 }')
echo "   ✅ $PDF_FILENAME ($SIZE)"
echo ""
