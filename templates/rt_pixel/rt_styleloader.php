<?php
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );
$fontstyle = "f-" . $default_font;
$widthstyle = "w-" . $default_width;
$colorstyle = $default_color;
$contraststyle = "co-" . $default_contrast;
$cookie_prefix = "pp-";

//load font style
if (isset($_SESSION[$cookie_prefix. 'fontstyle'])) {
	$fontstyle = $_SESSION[$cookie_prefix. 'fontstyle'];
} elseif (isset($_COOKIE[$cookie_prefix. 'fontstyle'])) {
	$fontstyle = $_COOKIE[$cookie_prefix. 'fontstyle'];
}

//load width style
if (isset($_SESSION[$cookie_prefix. 'widthstyle'])) {
	$widthstyle = $_SESSION[$cookie_prefix. 'widthstyle'];
} elseif (isset($_COOKIE[$cookie_prefix. 'widthstyle'])) {
	$widthstyle = $_COOKIE[$cookie_prefix. 'widthstyle'];
}

//load color style
if (isset($_SESSION[$cookie_prefix. 'colorstyle'])) {
	$colorstyle = $_SESSION[$cookie_prefix. 'colorstyle'];
} elseif (isset($_COOKIE[$cookie_prefix. 'colorstyle'])) {
	$colorstyle = $_COOKIE[$cookie_prefix. 'colorstyle'];
}

//load contrast style
if (isset($_SESSION[$cookie_prefix. 'contraststyle'])) {
	$contraststyle = $_SESSION[$cookie_prefix. 'contraststyle'];
} elseif (isset($_COOKIE[$cookie_prefix. 'contraststyle'])) {
	$contraststyle = $_COOKIE[$cookie_prefix. 'contraststyle'];
}
//echo "widthstyle:" . $widthstyle ."<br />";
//echo "tstyle:" . $tstyle ."<br />";
//exit();

$thisurl = $_SERVER['PHP_SELF'] . rebuildQueryString();

function rebuildQueryString() {
  $ignores = array("colorstyle","contraststyle","fontstyle","widthstyle");
  if (!empty($_SERVER['QUERY_STRING'])) {
      $parts = explode("&", $_SERVER['QUERY_STRING']);
      $newParts = array();
      foreach ($parts as $val) {
          $val_parts = explode("=", $val);
          if (!in_array($val_parts[0], $ignores)) {
            array_push($newParts, $val);
          }
      }
      if (count($newParts) != 0) {
          $qs = implode("&amp;", $newParts);
      } else {
          return "?";
      }
      return "?" . $qs . "&amp;"; // this is your new created query string
  } else {
      return "?";
  } 
}
?>
