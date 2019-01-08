<?php
/**
 * NETopes Main entry point file
 *
 * All requests begins here (except ajax, uploads, downloads, crons, api and newsletter).
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
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
$napp = NApp::GetInstance();
if(array_key_exists('phpnfo',$_GET) && $_GET['phpnfo']==1) { phpinfo(); die(); }
if(array_key_exists('do',$_GET) && strtolower($_GET['do'])=='logout') {
    $napp->Logout();
    $rns = get_array_param($_GET,'rns',$napp->current_namespace,'is_notempty_string');
    if($rns!=$napp->current_namespace) {
        header('Location:'.$napp->GetAppWebLink(NULL,$rns));
    } else {
    header('Location:'.$napp->url->GetUrl(NULL,array('do','uhash')));
    }//if($rns!=$napp->current_namespace)
    exit();
}//if(array_key_exists('do',$_GET) && strtolower($_GET['do'])=='logout')
$napp->LoadAppSettings();
// NApp::_Dlog(NApp::ShowTimeTrack('MainScriptDuration',FALSE).' sec','MainScriptDuration:1');
if(array_key_exists('uhash',$_GET)) {
    $napp->NamespaceSessionCommit();
    header('Location:'.$napp->url->GetUrl(NULL,array('uhash')));
    exit();
}//if(array_key_exists('uhash',$_GET))
header('Content-Language: '.$napp->GetLanguageCode(),TRUE);
switch($napp->login_status) {
    case TRUE:
        require_once($napp->app_public_path.$napp->GetSectionPath().$napp->loggedin_start_page);
        break;
    case FALSE:
    default:
        require_once($napp->app_public_path.$napp->GetSectionPath().$napp->start_page);
        break;
}//switch($logged_in)
// NApp::_Dlog(NApp::ShowTimeTrack('MainScriptDuration').' sec','MainScriptDuration');
$napp->NamespaceSessionCommit();