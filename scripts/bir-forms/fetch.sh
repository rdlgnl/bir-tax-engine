#!/usr/bin/env bash
#
# Downloads the official BIR form PDFs and prepares them as fill templates.
#
# Developer-only: run it when BIR publishes a new edition of a form, then
# re-check the field map (php artisan forms:calibrate {form}) and commit.
#
# The originals are kept byte-for-byte in resources/forms/bir/originals.
# BIR's files use compressed object streams, which the free FPDI parser
# cannot read, so each one is rewritten with plain cross-reference tables.
#
# Needs: curl, shasum, qpdf (brew install qpdf).
#
# Source: the "BIR Forms" page of www.bir.gov.ph, which lists these files
# from https://bir-cms-ws.bir.gov.ph/api/pub/templates/{id}/datasets
# (returns under 165 and 174; application forms like 1900 and 1905 under 157).

set -euo pipefail

cd "$(dirname "$0")/../../resources/forms/bir"
mkdir -p originals

command -v qpdf >/dev/null || { echo "qpdf is required: brew install qpdf" >&2; exit 1; }

# template | edition | source URL
FORMS=(
    "1701Q-2018-01|January 2018 (ENCS)|https://bir-cdn.bir.gov.ph/local/pdf/1701Q%20Jan%202018%20final%20rev2_copy.pdf"
    "1701A-2018-01|January 2018 (ENCS)|https://bir-cdn.bir.gov.ph/local/pdf/1701A%20Jan%202018%20v5%20with%20rates.pdf"
    "1701-2018-01|January 2018 (ENCS)|https://bir-cdn.bir.gov.ph/local/pdf/1701%20Jan%202018%20final%20with%20rates.pdf"
    "2551Q-2018-01|January 2018 (ENCS)|https://bir-cdn.bir.gov.ph/local/pdf/2551Q%20Jan%202018%20ENCS%20final%20rev%203_copy.pdf"
    "1701-MS-2024-08|August 2024 (ENCS)|https://bir-cdn.bir.gov.ph/BIR/pdf/1701-MS%20August%202024%20Fillable_01.pdf"
    "2550Q-2024-04|April 2024 (ENCS)|https://bir-cdn.bir.gov.ph/BIR/pdf/2550Q%20%20April%202024%20ENCS_Final.pdf"
    "1905-2025-10|October 2025 (ENCS)|https://bir-cdn.bir.gov.ph/BIR/pdf/1905%20October%202025%20ENCS%20Final.pdf"
    "1900-2025-10|October 2025 (ENCS)|https://bir-cdn.bir.gov.ph/BIR/pdf/1900%20October%202025%20ENCS%20Final.pdf"
)

fetched=$(date +%Y-%m-%d)
entries=()

for entry in "${FORMS[@]}"; do
    IFS='|' read -r template edition url <<<"$entry"

    curl -fsSL -A "Mozilla/5.0" --max-time 120 -o "originals/$template.pdf" "$url"
    qpdf --object-streams=disable --compress-streams=y "originals/$template.pdf" "$template.pdf"

    sha=$(shasum -a 256 "originals/$template.pdf" | cut -d' ' -f1)
    entries+=("    \"$template\": {\"form\": \"${template%-*-*}\", \"edition\": \"$edition\", \"source\": \"$url\", \"sha256\": \"$sha\", \"fetched\": \"$fetched\"}")
    echo "$template  $sha"
done

{
    echo "{"
    for i in "${!entries[@]}"; do
        [[ $i -lt $((${#entries[@]} - 1)) ]] && echo "${entries[$i]}," || echo "${entries[$i]}"
    done
    echo "}"
} >manifest.json
