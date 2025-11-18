Purpose
=======

This file gives concise, actionable guidance for AI coding agents working on PTAMesh.
Focus on immediate productivity: architecture, developer workflows, code conventions, and integration points.

**Big Picture**
- **Architecture:** A Chrome/Edge extension (in `extension/`) captures product data from Amazon and posts to a WordPress plugin (in `wordpress/`). The plugin exposes REST endpoints and manages WooCommerce products.
- **Major components:** `extension/` (client side), `wordpress/ptamesh.php` (module bootstrap), `wordpress/includes/*` (module implementations: `class-ptamesh-products.php`, `class-ptamesh-rest.php`, `class-ptamesh-receiving.php`, `class-ptamesh-jobcart.php`, `class-ptamesh-pricing.php`, `class-ptamesh-normalize.php`, `class-ptamesh-admin.php`).
- **Data flow:** Extension -> POST `ptamesh/v1/add-product` (ASIN, title, url, image, price, qty, sku, bin, barcode) -> `PTAMesh_Products::create_or_update()` -> WooCommerce product meta updates -> `mark_on_order` meta increments. Receiving uses `ptamesh/v1/receive` to move on-order to stock.

**Day-1 Developer Workflows**
- **Install plugin:** copy `wordpress/` into `wp-content/plugins/ptamesh` and activate (see `README.md`).
- **Load extension:** open `extension/` as an unpacked extension in Chrome/Edge; set `WP_ENDPOINT` in `extension/background.js` to your WordPress domain.
- **Auth for Day-1:** extension uses browser session cookies. REST permission is `current_user_can('manage_woocommerce')` (see `wordpress/includes/class-ptamesh-rest.php`).
- **Debugging:** enable `WP_DEBUG` to see `PTAMesh` logs (logger uses `error_log` when `WP_DEBUG` is true). Use browser devtools for extension debugging and REST inspector for requests.

**Project Conventions & Patterns**
- **Singleton modules:** each module exposes an `instance()` method and is initialized from `ptamesh.php`.
- **Traits for cross-cutting concerns:** logging and security utilities live in `wordpress/includes/traits/trait-ptamesh-logger.php` and `trait-ptamesh-security.php`. Prefer these helpers (`$this->log()`, `$this->require_cap()`, sanitizer wrappers) rather than reinventing checks.
- **Meta keys & constants:** product metadata uses `_ptamesh_` prefix. See `PTAMesh_Products` constants (e.g. `PTAMesh_Products::META_ASIN`, `META_BIN`, `META_BARCODE`). Use these constants when reading/updating meta.
- **On-order & logs:** on-order quantity stored in post meta `_ptamesh_on_order`; event logs in `_ptamesh_logs` (array). Prefer these over creating new storage formats for Day-1.
- **Visibility and stock:** new products are created as `WC_Product_Simple`, `catalog_visibility` set to `'hidden'`, `manage_stock` enabled, and stock adjusted with WooCommerce API (`set_stock_quantity`, `save`). Follow these same methods when modifying stock or creating products.
- **Pragmatic shortcuts:** Day-1 intentionally uses post meta increments and defers image downloading/attaching; preserve these pragmatic choices unless explicitly refactoring.

**Integration Points & External Dependencies**
- **WooCommerce:** plugin expects WooCommerce active; many operations use `wc_get_products`, `WC_Product_Simple`, and stock APIs.
- **REST endpoints:** `ptamesh/v1/add-product` and `ptamesh/v1/receive` are the primary integration points. See `wordpress/includes/class-ptamesh-rest.php` for allowed args and permission callback.
- **Extension <> WordPress:** extension posts JSON form data from the page context to the REST endpoints using browser session cookies today. Future plans mention application passwords or JWT — avoid changing auth model without updating `background.js` and REST permission callbacks.

**Developer Notes & Quick Examples**
- **Add-product payload example:** POST `.../wp-json/ptamesh/v1/add-product` with fields: `asin`, `title`, `url`, `image`, `price`, `qty`, `sku`, `bin`, `barcode`.
- **Receive payload example:** POST `.../wp-json/ptamesh/v1/receive` with `product_id` and `qty`.
- **Enable logging:** set `WP_DEBUG` true in `wp-config.php` to surface `$this->log()` messages from `trait-ptamesh-logger.php`.

**Files To Reference (fast)**
- `README.md` — day-1 setup checklist and notes.
- `extension/background.js` — set `WP_ENDPOINT` and see the extension-to-WP wiring.
- `wordpress/ptamesh.php` — module bootstrap and asset enqueue.
- `wordpress/includes/class-ptamesh-rest.php` — REST routes and permission behavior.
- `wordpress/includes/class-ptamesh-products.php` — product create/update, meta keys, receive logic.
- `wordpress/includes/traits/trait-ptamesh-logger.php` and `trait-ptamesh-security.php` — shared helpers.

**What Not To Change (unless refactoring plan exists)**
- Don't replace meta-based on-order/log storage with a new DB table unless you also update migration and all places that read those metas.
- Don't change REST permission to unauthenticated or public — Day-1 expects `manage_woocommerce` capability.

Feedback
- If any of the above assumptions are incomplete or you'd like a different focus (tests, CI, or auth refactor), tell me which area to expand and I'll iterate.
