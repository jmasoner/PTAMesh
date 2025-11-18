<?php
/**
 * Template: Branded Job Cart PDF-style export
 * Location: wordpress/templates/export-jobcart-pdf.php
 */

if (!defined('ABSPATH')) { exit; }

$logo_url = 'https://protraxer.com/wp-content/uploads/2025/09/ProTraxer-Logo.png';
$job    = get_post($job_id);
$items  = get_post_meta($job_id, '_ptamesh_items', true);
if (!is_array($items)) { $items = []; }
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Job Cart — <?php echo esc_html($job ? $job->post_title : 'Unknown'); ?></title>
<link rel="stylesheet" href="<?php echo plugins_url('assets/admin.css', __FILE__); ?>">
</head>
<body class="ptx-printable">

<div class="ptx-header">
  <div class="left">
    <img src="<?php echo esc_url($logo_url); ?>" alt="ProTraxer Logo">
    <div class="title">Job Cart</div>
  </div>
  <div class="right">
    <span class="stamp">Materials + Labor</span>
  </div>
</div>

<div class="ptx-meta">
  <div><span class="label">Job:</span> <?php echo esc_html($job ? $job->post_title : 'Unknown'); ?></div>
  <div><span class="label">Date:</span> <?php echo esc_html(date_i18n('Y-m-d')); ?></div>
  <div><span class="label">Job ID:</span> <?php echo esc_html($job_id); ?></div>
</div>

<table>
  <thead>
    <tr>
      <th>Product</th>
      <th>Qty</th>
      <th>Unit Price</th>
      <th>Total</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $grand = 0.0;
    foreach ($items as $row) {
      $p = wc_get_product(intval($row['product_id']));
      $name = $p ? $p->get_name() : 'Unknown';
      $qty = intval($row['qty']);
      $unit = floatval($row['unit_price']);
      $line = $qty * $unit;
      $grand += $line;
      echo '<tr>';
      echo '<td>'.esc_html($name).'</td>';
      echo '<td>'.esc_html($qty).'</td>';
      echo '<td>$'.number_format($unit,2).'</td>';
      echo '<td>$'.number_format($line,2).'</td>';
      echo '</tr>';
    }
    ?>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="3" class="ptx-total">Grand Total</td>
      <td>$<?php echo number_format($grand,2); ?></td>
    </tr>
  </tfoot>
</table>

<div class="no-print">
  <button onclick="window.print()" class="button ptx-primary">Print / Save as PDF</button>
</div>

</body>
</html>
