<?php
/**
 * PHP file that serves the robots.txt after setting 'robot' flag in session data.
 *
 * To detect crawlers, the robots.txt is served by this php file
 * (after setting the 'robot' session flag to 1), with the help of a .htaccess rewrite rule.
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2012 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.0
 * @filesource
 */
define('_VALID_NAPP_REQ',TRUE);
require_once('pathinit.php');
if(defined('_NAPP_OFFLINE') && _NAPP_OFFLINE) { die('OFFLINE!'); }
/* Let browser know that response is utf-8 encoded */
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.
header('X-Frame-Options: GOFORIT');
header('Content-Language: en');
if(in_array('globals',array_keys(array_change_key_case($_REQUEST,CASE_LOWER)))) { exit(); }
if(in_array('_post',array_keys(array_change_key_case($_REQUEST,CASE_LOWER)))) { exit(); }
require_once(_NAPP_ROOT_PATH._NAPP_APPLICATION_PATH._NAPP_CONFIG_PATH.'/Configuration.php');
require_once(_NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.'/vendor/autoload.php');
require_once(NETopes\Core\AppPath::GetBootFile());
NETopes\Core\AppSession::SessionStart();
$_SESSION['robot'] = 1;
NETopes\Core\AppSession::SessionClose();
switch(strtolower((array_key_exists('HTTP_HOST',$_SERVER) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost')) {
    default:
        echo file_get_contents('robots.txt');
        break;
}//END switch