<?php
/**
 * PTAMesh Job Cart — hardened
 * CPT, meta boxes, save handlers, and export actions (CSV, PDF, Sprout)
 */

if (!defined('ABSPATH')) { exit; }

class PTAMesh_JobCart {

  /** @var self */
  protected static $instance = null;

  public static function instance() {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function __construct() {
    add_action('init', [$this, 'register_cpt']);
    add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
    add_action('save_post_jobcart', [$this, 'save_post']);
    add_action('admin_menu', [$this, 'register_export_page']);
  }

  public function register_cpt() {
    register_post_type('jobcart', [
      'labels' => [
        'name'               => 'Job Carts',
        'singular_name'      => 'Job Cart',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Job Cart',
        'edit_item'          => 'Edit Job Cart',
        'new_item'           => 'New Job Cart',
        'view_item'          => 'View Job Cart',
        'search_items'       => 'Search Job Carts',
        'not_found'          => 'No Job Carts found',
        'not_found_in_trash' => 'No Job Carts found in Trash',
        'menu_name'          => 'Job Carts',
      ],
      'public'          => false,
      'show_ui'         => true,
      'show_in_menu'    => true,
      'menu_icon'       => 'dashicons-clipboard',
      'supports'        => ['title'],
      'capability_type' => 'post',
      'map_meta_cap'    => true,
      'has_archive'     => false,
      'show_in_rest'    => false,
    ]);
  }

  public function register_meta_boxes() {
    add_meta_box(
      'ptamesh_items',
      'Materials Used',
      [$this, 'render_items_box'],
      'jobcart',
      'normal',
      'default'
    );

    add_meta_box(
      'ptamesh_labor',
      'Labor Entries',
      [$this, 'render_labor_box'],
      'jobcart',
      'normal',
      'default'
    );
  }

  public function render_items_box($post) {
    // Nonce for saving (recognized by both boxes)
    wp_nonce_field('ptamesh_save_jobcart', 'ptamesh_jobcart_nonce');

    $items = get_post_meta($post->ID, '_ptamesh_items', true);
    if (!is_array($items)) { $items = []; }

    echo '<table class="widefat striped">';
    echo '<thead><tr>';
    echo '<th style="width:40%;">Product ID</th>';
    echo '<th style="width:20%;">Qty</th>';
    echo '<th style="width:40%;">Unit Price</th>';
    echo '</tr></thead><tbody>';

    foreach ($items as $i => $row) {
      $pid  = isset($row['product_id']) ? $row['product_id'] : '';
      $qty  = isset($row['qty']) ? $row['qty'] : '';
      $unit = isset($row['unit_price']) ? $row['unit_price'] : '';
      echo '<tr>';
      echo '<td><input type="text" name="ptamesh_items['.$i.'][product_id]" value="'.esc_attr($pid).'" /></td>';
      echo '<td><input type="number" min="0" step="1" name="ptamesh_items['.$i.'][qty]" value="'.esc_attr($qty).'" /></td>';
      echo '<td><input type="text" name="ptamesh_items['.$i.'][unit_price]" value="'.esc_attr($unit).'" /></td>';
      echo '</tr>';
    }

    echo '<tr>';
    echo '<td><input type="text" name="ptamesh_items[new][product_id]" value="" placeholder="Product ID or SKU" /></td>';
    echo '<td><input type="number" min="0" step="1" name="ptamesh_items[new][qty]" value="" placeholder="Qty" /></td>';
    echo '<td><input type="text" name="ptamesh_items[new][unit_price]" value="" placeholder="Cost per unit" /></td>';
    echo '</tr>';

    echo '</tbody></table>';
    echo '<p class="description">Enter base cost in Unit Price; markup is applied on export if the pricing module is enabled.</p>';
  }

  public function render_labor_box($post) {
    // Duplicate nonce OK — ensures save safety
    wp_nonce_field('ptamesh_save_jobcart', 'ptamesh_jobcart_nonce');

    $labor = get_post_meta($post->ID, '_ptamesh_labor', true);
    if (!is_array($labor)) { $labor = []; }

    echo '<table class="widefat striped">';
    echo '<thead><tr>';
    echo '<th style="width:40%;">Technician</th>';
    echo '<th style="width:20%;">Hours</th>';
    echo '<th style="width:40%;">Rate</th>';
    echo '</tr></thead><tbody>';

    foreach ($labor as $i => $row) {
      $tech  = isset($row['tech']) ? $row['tech'] : '';
      $hours = isset($row['hours']) ? $row['hours'] : '';
      $rate  = isset($row['rate']) ? $row['rate'] : '';
      echo '<tr>';
      echo '<td><input type="text" name="ptamesh_labor['.$i.'][tech]" value="'.esc_attr($tech).'" /></td>';
      echo '<td><input type="number" min="0" step="0.1" name="ptamesh_labor['.$i.'][hours]" value="'.esc_attr($hours).'" /></td>';
      echo '<td><input type="text" name="ptamesh_labor['.$i.'][rate]" value="'.esc_attr($rate).'" /></td>';
      echo '</tr>';
    }

    echo '<tr>';
    echo '<td><input type="text" name="ptamesh_labor[new][tech]" value="" placeholder="Technician" /></td>';
    echo '<td><input type="number" min="0" step="0.1" name="ptamesh_labor[new][hours]" value="" placeholder="Hours" /></td>';
    echo '<td><input type="text" name="ptamesh_labor[new][rate]" value="" placeholder="Rate per hour" /></td>';
    echo '</tr>';

    echo '</tbody></table>';
    echo '<p class="description">Enter hours and rate; totals are calculated on export.</p>';
  }

  public function save_post($post_id) {
    // Nonce check
    if (!isset($_POST['ptamesh_jobcart_nonce']) ||
        !wp_verify_nonce($_POST['ptamesh_jobcart_nonce'], 'ptamesh_save_jobcart')) {
      return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if ('jobcart' !== get_post_type($post_id)) { return; }
    if (!current_user_can('edit_post', $post_id)) { return; }

    // Materials
    if (isset($_POST['ptamesh_items']) && is_array($_POST['ptamesh_items'])) {
      $raw_items = wp_unslash($_POST['ptamesh_items']);
      $items = [];

      foreach ($raw_items as $row) {
        $pid_raw = isset($row['product_id']) ? trim((string)$row['product_id']) : '';
        if ($pid_raw === '') { continue; }

        $pid  = sanitize_text_field($pid_raw);
        $qty  = isset($row['qty']) ? intval($row['qty']) : 0;
        $unit = isset($row['unit_price']) ? floatval($row['unit_price']) : 0.0;

        if ($qty < 0)  { $qty = 0; }
        if ($unit < 0) { $unit = 0.0; }

        $items[] = [
          'product_id' => $pid,
          'qty'        => $qty,
          'unit_price' => $unit,
        ];
      }
      update_post_meta($post_id, '_ptamesh_items', $items);
    }

    // Labor
    if (isset($_POST['ptamesh_labor']) && is_array($_POST['ptamesh_labor'])) {
      $raw_labor = wp_unslash($_POST['ptamesh_labor']);
      $labor = [];

      foreach ($raw_labor as $row) {
        $tech_raw = isset($row['tech']) ? trim((string)$row['tech']) : '';
        if ($tech_raw === '') { continue; }

        $tech  = sanitize_text_field($tech_raw);
        $hours = isset($row['hours']) ? floatval($row['hours']) : 0.0;
        $rate  = isset($row['rate']) ? floatval($row['rate']) : 0.0;

        if ($hours < 0) { $hours = 0.0; }
        if ($rate  < 0) { $rate  = 0.0; }

        $labor[] = [
          'tech'  => $tech,
          'hours' => $hours,
          'rate'  => $rate,
        ];
      }
      update_post_meta($post_id, '_ptamesh_labor', $labor);
    }
  }

  public function register_export_page() {
    add_submenu_page(
      'edit.php?post_type=jobcart',
      'Export Job Cart',
      'Export',
      'edit_posts',
      'ptamesh-export',
      [$this, 'render_export_page']
    );
  }

  /**
   * Render the Export page
   * - Handles actions FIRST (header-safe) with nonce + capability checks
   * - Then renders the UI
   */
  public function render_export_page() {
    // Handle action submissions before any output
    if (!empty($_POST)) {
      $post = wp_unslash($_POST);

      $has_nonce = isset($post['ptamesh_export_nonce']) &&
                   wp_verify_nonce($post['ptamesh_export_nonce'], 'ptamesh_export');

      if ($has_nonce && current_user_can('edit_posts') && !empty($post['job_id'])) {
        $job_id = intval($post['job_id']);

        // Validate job exists and is jobcart
        $job = get_post($job_id);
        if (!$job || 'jobcart' !== $job->post_type) {
          wp_die(__('Invalid Job Cart selected.'));
        }

        $items = get_post_meta($job_id, '_ptamesh_items', true);
        $labor = get_post_meta($job_id, '_ptamesh_labor', true);
        if (!is_array($items)) { $items = []; }
        if (!is_array($labor)) { $labor = []; }

        if (!empty($post['export_csv'])) {
          $this->export_csv($job_id, $items, $labor);
          return;
        }
        if (!empty($post['export_pdf'])) {
          $this->export_pdf($job_id, $items, $labor);
          return;
        }
        if (!empty($post['export_sprout'])) {
          $this->export_sprout($job_id, $items, $labor);
          return;
        }
      }
    }

    // Render UI
    echo '<div class="wrap">';
    echo '<h1>Export Job Cart</h1>';
    echo '<p>Select a Job Cart and export as CSV, PDF, or to Sprout Invoices.</p>';

    $jobs = get_posts([
      'post_type'   => 'jobcart',
      'numberposts' => -1,
      'orderby'     => 'date',
      'order'       => 'DESC',
    ]);

    echo '<form method="post" action="">';
    wp_nonce_field('ptamesh_export', 'ptamesh_export_nonce');

    echo '<p><label for="job_id">Job Cart:</label> ';
    echo '<select id="job_id" name="job_id" style="min-width:300px;">';
    foreach ($jobs as $job) {
      $title = $job->post_title ? $job->post_title : ('Job Cart #' . $job->ID);
      echo '<option value="'.esc_attr($job->ID).'">'.esc_html($title).' (ID '.intval($job->ID).')</option>';
    }
    echo '</select></p>';

    echo '<p>';
    echo '<button type="submit" name="export_csv" class="button">Export CSV</button> ';
    echo '<button type="submit" name="export_pdf" class="button button-primary">Export PDF</button> ';
    echo '<button type="submit" name="export_sprout" class="button button-secondary">Export to Sprout</button>';
    echo '</p>';

    echo '</form>';
    echo '</div>';
  }

  protected function export_csv($job_id, array $items, array $labor) {
    nocache_headers();
    $filename = 'jobcart-' . intval($job_id) . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Type', 'Description', 'Qty/Hours', 'Rate', 'Total']);

    // Items
    foreach ($items as $row) {
      $qty  = isset($row['qty']) ? intval($row['qty']) : 0;
      $unit = isset($row['unit_price']) ? floatval($row['unit_price']) : 0.0;
      $name = $this->resolve_product_name($row);

      list($rate, $total) = $this->price_line($unit, $qty);
      fputcsv($out, ['Material', $name, $qty, $rate, $total]);
    }

    // Labor
    foreach ($labor as $row) {
      $tech  = isset($row['tech']) ? $row['tech'] : 'Technician';
      $hours = isset($row['hours']) ? floatval($row['hours']) : 0.0;
      $rate  = isset($row['rate']) ? floatval($row['rate']) : 0.0;
      $total = round($hours * $rate, 2);

      fputcsv($out, ['Labor', $tech, $hours, $rate, $total]);
    }

    fclose($out);
    exit;
  }

  protected function export_pdf($job_id, array $items, array $labor) {
    // Template-based rendering (header-safe by template)
    $ptx_job_id = $job_id;
    $ptx_items  = $items;
    $ptx_labor  = $labor;

    $template_path = PTAMESH_DIR . 'templates/export-jobcart-pdf.php';
    if (file_exists($template_path)) {
      include $template_path;
      exit;
    } else {
      // Fallback HTML
      nocache_headers();
      header('Content-Type: text/html; charset=utf-8');
      header('Content-Disposition: attachment; filename="jobcart-'.intval($job_id).'.html"');

      echo '<h1>Job Cart PDF Fallback</h1>';
      echo '<h2>Materials</h2><ul>';
      foreach ($items as $row) {
        $desc = esc_html($this->resolve_product_name($row));
        $qty  = isset($row['qty']) ? intval($row['qty']) : 0;
        $unit = isset($row['unit_price']) ? floatval($row['unit_price']) : 0.0;
        echo '<li>'. $desc .' — Qty: '. $qty .' @ '. $unit .'</li>';
      }
      echo '</ul>';

      echo '<h2>Labor</h2><ul>';
      foreach ($labor as $row) {
        $tech  = isset($row['tech']) ? esc_html($row['tech']) : 'Technician';
        $hours = isset($row['hours']) ? floatval($row['hours']) : 0.0;
        $rate  = isset($row['rate']) ? floatval($row['rate']) : 0.0;
        echo '<li>'. $tech .' — Hours: '. $hours .' @ '. $rate .'</li>';
      }
      echo '</ul>';
      exit;
    }
  }

  protected function export_sprout($job_id, array $items, array $labor) {
    $invoice_id = wp_insert_post([
      'post_type'   => 'sa_invoice',
      'post_status' => 'draft',
      'post_title'  => 'Invoice for Job ' . intval($job_id),
    ]);

    if ($invoice_id && !is_wp_error($invoice_id)) {
      foreach ($items as $row) {
        $qty  = isset($row['qty']) ? intval($row['qty']) : 0;
        $unit = isset($row['unit_price']) ? floatval($row['unit_price']) : 0.0;
        $name = $this->resolve_product_name($row);

        list($rate, $total) = $this->price_line($unit, $qty);

        add_post_meta($invoice_id, '_line_item', [
          'desc'  => $name,
          'qty'   => $qty,
          'rate'  => $rate,
          'total' => $total,
          'type'  => 'material',
        ]);
      }

      foreach ($labor as $row) {
        $tech  = isset($row['tech']) ? sanitize_text_field($row['tech']) : 'Technician';
        $hours = isset($row['hours']) ? floatval($row['hours']) : 0.0;
        $rate  = isset($row['rate']) ? floatval($row['rate']) : 0.0;
        $total = round($hours * $rate, 2);

        add_post_meta($invoice_id, '_line_item', [
          'desc'  => 'Labor — ' . $tech,
          'qty'   => $hours,
          'rate'  => $rate,
          'total' => $total,
          'type'  => 'labor',
        ]);
      }

      wp_safe_redirect(admin_url('post.php?post='.intval($invoice_id).'&action=edit'));
      exit;
    } else {
      add_action('admin_notices', function() use ($invoice_id) {
        $msg = is_wp_error($invoice_id) ? $invoice_id->get_error_message() : 'Unknown error';
        echo '<div class="notice notice-error"><p>Failed to create Sprout invoice: '.esc_html($msg).'</p></div>';
      });
    }
  }

  /** Resolve product name via WooCommerce if available, else fallback to product_id */
  protected function resolve_product_name(array $row) {
    $name = 'Unknown';
    if (function_exists('wc_get_product') && !empty($row['product_id'])) {
      $p = wc_get_product(intval($row['product_id']));
      if ($p) { $name = $p->get_name(); }
    } else {
      if (!empty($row['product_id'])) {
        $name = (string)$row['product_id'];
      }
    }
    return $name;
  }

  /** Apply markup and compute line total consistently */
  protected function price_line(float $unit, int $qty) {
    if (class_exists('PTAMesh_Pricing')) {
      $rate  = PTAMesh_Pricing::apply_markup($unit);
      $total = PTAMesh_Pricing::line_total($unit, $qty);
    } else {
      $rate  = $unit;
      $total = round($unit * $qty, 2);
    }
    return [$rate, $total];
  }
}