<?php
/**
 * PTAMesh Help — complete, navigable, and content-rich
 * Registers a Help submenu, renders tabs, and includes inline docs.
 */

if (!defined('ABSPATH')) { exit; }

class PTAMesh_Help {

  /** @var string Menu slug */
  private $slug = 'ptamesh-help';

  public function __construct() {
    add_action('admin_menu', [$this, 'register_menu']);
  }

  /**
   * Register Help submenu under PTAMesh Settings (or under Tools if settings missing)
   */
  public function register_menu() {
    // Prefer nesting under PTAMesh Settings if it exists
    $parent_slug = 'ptamesh-settings';

    // If PTAMesh Settings menu hasn’t been registered, fallback to Tools
    global $submenu;
    $has_ptamesh_settings = is_array($submenu) && isset($submenu[$parent_slug]);

    if ($has_ptamesh_settings) {
      add_submenu_page(
        $parent_slug,
        'PTAMesh Help',
        'Help',
        'manage_options',
        $this->slug,
        [$this, 'render_page']
      );
    } else {
      add_submenu_page(
        'tools.php',
        'PTAMesh Help',
        'PTAMesh Help',
        'manage_options',
        $this->slug,
        [$this, 'render_page']
      );
    }
  }

  /**
   * Render the Help page with tabs and working links
   */
  public function render_page() {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Current tab
    $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';

    echo '<div class="wrap">';
    echo '<h1>PTAMesh Help</h1>';
    echo '<p class="description">Documentation for Job Carts, Pricing, Receiving, Exports, and integration with WooCommerce, Sprout Invoices, and Project Panorama.</p>';

    // Tabs
    echo '<h2 class="nav-tab-wrapper">';
    $this->tab_link('overview', 'Overview', $tab);
    $this->tab_link('jobcarts', 'Job Carts', $tab);
    $this->tab_link('pricing', 'Pricing', $tab);
    $this->tab_link('receiving', 'Receiving', $tab);
    $this->tab_link('exports', 'Exports', $tab);
    $this->tab_link('integration', 'Integrations', $tab);
    $this->tab_link('troubleshoot', 'Troubleshooting', $tab);
    echo '</h2>';

    // Content
    switch ($tab) {
      case 'jobcarts':
        $this->section_jobcarts();
        break;
      case 'pricing':
        $this->section_pricing();
        break;
      case 'receiving':
        $this->section_receiving();
        break;
      case 'exports':
        $this->section_exports();
        break;
      case 'integration':
        $this->section_integration();
        break;
      case 'troubleshoot':
        $this->section_troubleshoot();
        break;
      case 'overview':
      default:
        $this->section_overview();
        break;
    }

    echo '</div>';
  }

  /**
   * Render an individual tab link (active styling included)
   */
  private function tab_link($key, $label, $current) {
    $class = ($current === $key) ? 'nav-tab nav-tab-active' : 'nav-tab';
    $url = admin_url('admin.php?page='.$this->slug.'&tab='.$key);
    echo '<a class="'.$class.'" href="'.esc_url($url).'">'.esc_html($label).'</a>';
  }

  /* ------------------------ Sections ------------------------ */

  private function section_overview() {
    echo '<h2>Overview</h2>';
    echo '<p>PTAMesh centralizes materials, labor, and export workflows for field jobs. It adds:</p>';
    echo '<ul style="list-style: disc; margin-left: 20px;">';
    echo '<li><strong>Job Carts:</strong> Record materials and labor per job via a dedicated CPT.</li>';
    echo '<li><strong>Pricing:</strong> Apply default markup and compute line totals consistently.</li>';
    echo '<li><strong>Receiving:</strong> Track inbound product receipts and normalize IDs/SKUs.</li>';
    echo '<li><strong>Exports:</strong> Generate CSV, PDF, or Sprout Invoices from Job Carts.</li>';
    echo '</ul>';
    echo '<p>Use the tabs above to navigate features and step-by-step workflows.</p>';
  }

  private function section_jobcarts() {
    echo '<h2>Job Carts</h2>';
    echo '<p>Job Carts appear under <em>Job Carts</em> in the WordPress admin menu. Each Job Cart stores:</p>';
    echo '<ul style="list-style: disc; margin-left: 20px;">';
    echo '<li><strong>Materials:</strong> Product ID/SKU, Quantity, and Unit Price (base cost).</li>';
    echo '<li><strong>Labor:</strong> Technician name, Hours, and Rate.</li>';
    echo '</ul>';
    echo '<h3>Creating a Job Cart</h3>';
    echo '<ol style="list-style: decimal; margin-left: 20px;">';
    echo '<li>Go to <em>Job Carts → Add New</em>.</li>';
    echo '<li>Enter a title (e.g., “Install fiber drop — ACME HQ”).</li>';
    echo '<li>Fill in Materials and Labor tables; add new rows as needed.</li>';
    echo '<li>Click <em>Publish</em> or <em>Update</em>.</li>';
    echo '</ol>';
    echo '<h3>Best practices</h3>';
    echo '<ul style="list-style: disc; margin-left: 20px;">';
    echo '<li>Use WooCommerce product IDs in <em>Product ID</em> for automatic names on export.</li>';
    echo '<li>Enter <em>Unit Price</em> as base cost; markup is applied automatically if enabled.</li>';
    echo '</ul>';
  }

  private function section_pricing() {
    echo '<h2>Pricing</h2>';
    echo '<p>Pricing rules apply markup and compute totals on export. Configure the default markup under <em>PTAMesh Settings</em>.</p>';
    echo '<h3>Markup configuration</h3>';
    echo '<ul style="list-style: disc; margin-left: 20px;">';
    echo '<li>Default markup is stored as a decimal (e.g., 0.15 for 15%).</li>';
    echo '<li>On CSV/PDF/Sprout export, unit rates are marked up and totals computed.</li>';
    echo '</ul>';
    echo '<p>If the <code>PTAMesh_Pricing</code> module is present, exports will use <code>apply_markup()</code> and <code>line_total()</code>. Otherwise, totals are <code>unit_price × qty</code>.</p>';
  }

  private function section_receiving() {
    echo '<h2>Receiving</h2>';
    echo '<p>Receiving tracks inbound shipments and normalizes product identifiers for consistency.</p>';
    echo '<ul style="list-style: disc; margin-left: 20px;">';
    echo '<li><strong>Normalize:</strong> Convert vendor SKUs into an internal canonical ID.</li>';
    echo '<li><strong>Record:</strong> Save receipts with quantities and cost basis.</li>';
    echo '</ul>';
    echo '<p>Use Receiving to keep your Job Cart product IDs aligned with WooCommerce products.</p>';
  }

  private function section_exports() {
    echo '<h2>Exports</h2>';
    echo '<p>Export Job Carts as CSV, PDF, or Sprout invoices via <em>Job Carts → Export</em>.</p>';
    echo '<h3>CSV</h3>';
    echo '<ul style="list-style: disc; margin-left: 20px;">';
    echo '<li>Header-safe streaming with no prior output.</li>';
    echo '<li>Columns: Type, Description, Qty/Hours, Rate, Total.</li>';
    echo '</ul>';
    echo '<h3>PDF</h3>';
    echo '<ul style="list-style: disc; margin-left: 20px;">';
    echo '<li>Uses template <code>templates/export-jobcart-pdf.php</code> if present.</li>';
    echo '<li>Fallbacks to HTML download if the template is missing.</li>';
    echo '</ul>';
    echo '<h3>Sprout Invoices</h3>';
    echo '<ul style="list-style: disc; margin-left: 20px;">';
    echo '<li>Creates a draft <code>sa_invoice</code> and adds materials/labor as line items.</li>';
    echo '<li>Redirects to the invoice editor on success.</li>';
    echo '</ul>';
  }

  private function section_integration() {
    echo '<h2>Integrations</h2>';
    echo '<p>PTAMesh works alongside your existing stack:</p>';
    echo '<ul style="list-style: disc; margin-left: 20px;">';
    echo '<li><strong>WooCommerce:</strong> Use product IDs to auto-resolve names on export.</li>';
    echo '<li><strong>Sprout Invoices:</strong> Export Job Carts into invoices for billing.</li>';
    echo '<li><strong>Project Panorama:</strong> Link Job Carts to project milestones (manual or via custom glue).</li>';
    echo '</ul>';
    echo '<p>Amazon product ingestion is handled via a separate plugin/bridge that creates WooCommerce products, which you can then reference in Job Carts.</p>';
  }

  private function section_troubleshoot() {
    echo '<h2>Troubleshooting</h2>';
    echo '<ul style="list-style: disc; margin-left: 20px;">';
    echo '<li><strong>Help links don’t work:</strong> This Help page uses admin URLs like <code>admin.php?page=ptamesh-help&tab=...</code>. Ensure you’re logged in with sufficient privileges.</li>';
    echo '<li><strong>Export 404 or blank page:</strong> Check that no output occurs before CSV/PDF headers. Our export methods exit after streaming.</li>';
    echo '<li><strong>Missing product names on export:</strong> Confirm WooCommerce is active and Product IDs match <code>wc_get_product()</code> IDs.</li>';
    echo '<li><strong>Sprout invoice errors:</strong> Verify Sprout Invoices plugin is active and <code>sa_invoice</code> post type exists.</li>';
    echo '<li><strong>Settings not saving:</strong> Ensure you use the <em>PTAMesh Settings</em> page and that your user has <code>manage_options</code>.</li>';
    echo '</ul>';
  }
}