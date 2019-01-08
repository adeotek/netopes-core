<?php
/**
 * PHP file target in case of brute force attacks.
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.0
 * @filesource
 */
define('_VALID_NAPP_REQ',TRUE);
/* Let browser know that response is utf-8 encoded */
header('Content-Type: text/html; charset=UTF-8');
header('Content-Language: en');
if(in_array('globals',array_keys(array_change_key_case($_REQUEST,CASE_LOWER)))) { exit(); }
if(in_array('_post',array_keys(array_change_key_case($_REQUEST,CASE_LOWER)))) { exit(); }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<title>-</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	</head>
	<body>
	</body>
</html>