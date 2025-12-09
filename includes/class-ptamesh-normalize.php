<?php
class PTAMesh_Normalize {
  private static $instance;
  public static function instance() { return self::$instance ?? (self::$instance = new self()); }

  public static function clean_title($t) {
    $t = trim($t);
    // Remove obvious Amazon suffixes
    $t = preg_replace('/\|.*Amazon.*/i', '', $t);
    return $t;
  }

  public static function clean_description($d) {
    // Strip affiliate or “Sold by Amazon” lines
    $d = preg_replace('/Sold by Amazon.*$/mi', '', $d);
    $d = preg_replace('/Amazon Associates.*$/mi', '', $d);
    return wp_kses_post($d);
  }
}
