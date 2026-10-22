# Modul 2 TDS dan TTM Template Contract

## Reference and verification

- Reference: `D:\KULIAH SEMESTER 7\PENJAMINAN KUALITAS PERANGKAT LUNAK\MODUL 2\PRELAB\Test Design Specification & TTM Template.docx`
- SHA-256: `FBDA5F27505D30FB7B4D9BF25DA38F0A301CB231C547EF49E64B33A63066D0E6`
- Rendered page count: 3. Word exported the reference to `tmp/module2-template-render/template.pdf`; bundled pypdfium2 produced `pages/page-1.png` through `page-3.png`. All three pages were visually inspected.
- The retained template remains the layout authority and must remain byte-for-byte unchanged.

## Page system and typography

- One portrait Letter section (8.50 x 11.00 in), margins L/R 1.25 in and T/B 1.00 in; header and footer distances 0.50 in. No header/footer content or section variation.
- Cambria is the embedded document font. Main title uses Heading 1 at 23 pt, black, with 6 pt after. Numbered section titles use Heading 2 at 17 pt, black, 18 pt before and 4 pt after, except section 5 which uses Heading 1 at 17 pt.
- Body paragraphs use the existing Normal style and direct formatting from the retained file. Preserve paragraph alignment, spacing, emphasis, numbering text, theme, and font data.
- Tables have no named table style. Keep the blue header fill, black grid, existing column geometry, and body-cell alignment. Current column widths (in): table 1 0.97/1.22/1.65/1.35/1.42; table 2 1.23/1.19/1.55/1.29/1.29; table 3 1.53/1.48/1.93/1.60.

## Content flow and editable slots

1. `TEST DESIGN SPECIFICATION (TDS)` title.
2. Section 1 Requirements: requirement paragraphs with `RQ-###` identifiers.
3. Section 2 Feature Sets: repeated four-paragraph blocks (feature-set title, objective, priority, traceability).
4. Section 3 Test Conditions: first table, columns ID / Feature Set / Description / Priority / Traceability.
5. Section 4 Coverage Items: second table, columns ID / Description / Related Test Condition / Priority / Traceability.
6. Section 5 Test Traceability Matrix: explanatory paragraph followed by third table, columns Requirement / Feature Set / Test Condition / Coverage Item.

## Slot and package map

- Editable: student identification lines (added before the title to identify the deliverable), title, requirements, feature-set blocks, all body rows in the three data tables, and the explanatory paragraph above the TTM.
- Preserve: page geometry, styles, theme, embedded fonts, table header styling, borders, column widths, relationships, settings, font table, document properties unless explicitly needed, and all other package parts.
- Package inventory from the reference: `[Content_Types].xml`, `_rels/.rels`, `word/document.xml` (editable), `word/_rels/document.xml.rels`, six `word/fonts/font*.odttf` parts, `word/theme/theme1.xml`, `word/settings.xml`, `word/styles.xml`, `word/webSettings.xml`, `word/fontTable.xml`, `docProps/core.xml`, `docProps/app.xml`, and `word/_rels/fontTable.xml.rels`. Only `word/document.xml` may differ in the completed copy.
- No images, content controls, comments, footnotes, fields, or relationship-backed editable slots were found. The document contains three tables and one section.

## Fidelity gates

- Build from a copy of the retained template and modify only supported content slots. Keep Sections 1 through 5; omit any later sections because the Module 2 Lab Session explicitly limits completion to Section 5.
- For any added rows or paragraphs, clone the corresponding existing body row or paragraph to retain its formatting.
- Confirm the source template SHA-256 remains unchanged and all preserve-only package-part hashes match.
- Render every page of the final DOCX using the available Word export and bundled pypdfium2 fallback because LibreOffice is not present in the runtime. Inspect every PNG at 100% for clipping, table split quality, page gaps, and readability.
