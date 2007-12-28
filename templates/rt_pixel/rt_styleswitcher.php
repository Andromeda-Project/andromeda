<?php
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );
$cookie_prefix = "pp-";
$cookie_time = time()+31536000;
if (isset($_GET['widthstyle'])) {
	 $width = $_GET['widthstyle'];
	$_SESSION[$cookie_prefix. 'widthstyle'] = $width;
	setcookie ($cookie_prefix. 'widthstyle', $width, $cookie_time, '/', false);
}
if (isset($_GET['fontstyle'])) {
	$font = $_GET['fontstyle'];
	$_SESSION[$cookie_prefix. 'fontstyle'] = $font;
	setcookie ($cookie_prefix. 'fontstyle', $font, $cookie_time, '/', false);
}
if (isset($_GET['colorstyle'])) {
	$clstyle = $_GET['colorstyle'];
	$_SESSION[$cookie_prefix. 'colorstyle'] = $clstyle;
	setcookie ($cookie_prefix. 'colorstyle', $clstyle, $cookie_time, '/', false);
}
if (isset($_GET['contraststyle'])) {
	$costyle = $_GET['contraststyle'];
	$_SESSION[$cookie_prefix. 'contraststyle'] = $costyle;
	setcookie ($cookie_prefix. 'contraststyle', $costyle, $cookie_time, '/', false);
}
?>
