# PTAMesh Tasks

## Day-1 MVP — Acceptance criteria
- [ ] Extension injects “Add to PTAMesh Inventory” on Amazon product pages.
- [ ] Clicking button creates/updates a hidden WooCommerce product with ASIN, supplier URL, last purchase price.
- [ ] Quantity marked “On Order” appears in PTAMesh → Receiving.
- [ ] “Receive” action reduces “On Order” and increases stock quantity with a log entry.
- [ ] Job Cart CPT exists; can add line items and export CSV/printable HTML.
- [ ] Default markup of 40% applied to unit price.

## Commit checkpoints (Gripper-ready)
- [ ] feat(extension): MV3 scaffold, ASIN detect, button inject, REST POST
- [ ] feat(plugin): REST routes (/add-product, /receive)
- [ ] feat(plugin): product meta (ASIN, supplier URL, last purchase price)
- [ ] feat(admin): Receiving page, on-order listing, receive action
- [ ] feat(jobcart): CPT, line items, CSV/printable export
- [ ] docs: README, inline notes, architecture overview

## Next iteration — Acceptance criteria
- [ ] Security: app passwords or JWT for extension calls (no reliance on cookies).
- [ ] Job Cart: AJAX search by SKU/ASIN/name with autocomplete.
- [ ] Export: PDF via Dompdf template with ProTraxer branding.
- [ ] Barcode/QR: Generate per product; scanning via phone camera to add to cart.
- [ ] Sprout Invoices: “Export to Sprout Invoice” button creates/updates materials lines.
- [ ] Normalize: Strip Amazon branding in titles/descriptions automatically.
- [ ] Receiving: Attach delivery photo/PO; link via File Organizer Pro.

## Branches
- main: stable, tagged releases
- dev: active development
- feature/*: one module or enhancement per branch

## Gripper commands (examples)
- GitSync PTAMesh — commit/push dev
- GitSync PTAMesh release — merge dev → main, tag v0.1.0
