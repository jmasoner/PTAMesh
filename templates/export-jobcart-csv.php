<?php
/**
 * Template: Job Cart CSV Export
 * Location: wordpress/templates/export-jobcart-csv.php
 */

if (!defined('ABSPATH')) { exit; }

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="jobcart-'.$job_id.'.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Product', 'Qty', 'Unit Price', 'Line Total']);

$items = get_post_meta($job_id, '_ptamesh_items', true);
if (!is_array($items)) { $items = []; }

foreach ($items as $row) {
  $p = wc_get_product(intval($row['product_id']));
  $name = $p ? $p->get_name() : 'Unknown';
  $qty = intval($row['qty']);
  $unit = floatval($row['unit_price']);
  fputcsv($out, [$name, $qty, $unit, $qty*$unit]);
}
fclose($out);
exit;
