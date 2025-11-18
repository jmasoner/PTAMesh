# PTAMesh — ProTraxer Amazon Mesh

Internal inventory capture: click “Add to PTAMesh Inventory” on Amazon, auto-create/update WooCommerce products as “On Order,” receive into stock, build Job Carts, export to invoice.

## Modules
- Extension: Chrome/Edge MV3 (content + background).
- WordPress plugin: REST, Products, Receiving, Job Carts, Pricing, Normalize.

## Day-1 Setup
1. Install `wordpress/` plugin into `wp-content/plugins/ptamesh` and activate.
2. Build extension in `extension/`, load unpacked in Chrome.
3. Set WP_ENDPOINT in `background.js` to your domain.
4. Ensure WooCommerce active; visit PTAMesh > Receiving.
5. Create a Job Cart and add items; export CSV/printable.

## Auth
Day-1 uses logged-in session cookies. Next: application passwords or JWT for the extension.

## Pricing
Default markup 40%. Override via `ptamesh_markup_default` option.

## Notes
- Image download/attach is deferred.
- Search in Job Cart is manual Day-1; add AJAX later.
