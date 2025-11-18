<?php
/**
 * PTAMesh Pricing Module
 * Applies markup rules to product line items.
 */

if (!defined('ABSPATH')) { exit; }

class PTAMesh_Pricing {

  /**
   * Get default markup percentage (stored as decimal, e.g. 0.15 = 15%)
   */
  public static function get_default_markup() {
    $markup = get_option('ptamesh_default_markup', 0.15);
    return floatval($markup);
  }

  /**
   * Apply markup to a base unit price
   */
  public static function apply_markup($base_price) {
    $markup = self::get_default_markup();
    return round($base_price * (1 + $markup), 2);
  }

  /**
   * Calculate line total with markup
   */
  public static function line_total($base_price, $qty) {
    $unit = self::apply_markup($base_price);
    return round($unit * $qty, 2);
  }
}
