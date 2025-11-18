<?php
class PTAMesh_Pricing {
  private static $instance;
  public static function instance() { return self::$instance ?? (self::$instance = new self()); }

  // Default markup: 40%
  public static function apply_markup($cost, $markup_percent = 40.0) {
    $cost = floatval($cost);
    return round($cost * (1.0 + ($markup_percent / 100.0)), 2);
  }
}
