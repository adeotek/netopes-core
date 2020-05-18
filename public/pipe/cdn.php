<?php
/**
 * CDN requests entry point file
 *
 * @package    NETopes\Core\CDN
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.10.4
 * @filesource
 */
use NETopes\Core\App\ModulesProvider;
use NETopes\Core\App\UserSession;
use NETopes\Core\AppException;
use NETopes\Core\Helpers;

define('_VALID_NAPP_REQ',TRUE);
ini_set('max_execution_time',900);
ini_set('max_input_time',-1);
require_once(realpath(dirname(__FILE__).'/../bootstrap.php'));
// NApp::StartTimeTrack('cdnTime');
$debug=(array_key_exists('dbg',$_GET) && $_GET['dbg']==1) ? TRUE : FALSE;
$cNamespace=array_key_exists('namespace',$_GET) ? $_GET['namespace'] : (array_key_exists('namespace',$_POST) ? $_POST['namespace'] : NULL);
NApp::Start(FALSE,['namespace'=>$cNamespace,'startup_path'=>realpath(dirname(__FILE__)),'debug'=>$debug,'silent_errors'=>TRUE]);
if(!NApp::GetAppState()) {
    end_request($debug,'Invalid request (1)!');
}
if($debug) {
    ob_start();
}
NApp::LoadAppSettings();
if(!UserSession::$loginStatus && (!array_key_exists('rpa',$_GET) || $_GET['rpa']!=1)) {
    end_request($debug,'Invalid request (2)!');
}
header('Content-Language: '.NApp::GetLanguageCode(),TRUE);

$outputResult=TRUE;
$result=$fileName=$mimeType=NULL;
if(isset($_GET['hash'])) {
    $rHash=get_array_value($_GET,'hash','','is_string');
    if(strlen($rHash)) {
        $rHash=GibberishAES::dec(rawurldecode($rHash),'eUrlHash');
        if(strpos($rHash,'|')===FALSE) {
            end_request($debug,'Invalid request (hash)!');
        }
        $rHashArr=explode('|',$rHash);
        //file_name(0)|path(1)|download_name(2)
        $fileName=get_array_value($rHashArr,0,'','is_string');
        $path=get_array_value($rHashArr,1,'','is_string');
        $downloadName=get_array_value($rHashArr,2,'','is_string');
    } else {
        $fileName=get_array_value($_POST,'file_name','','is_string');
        $path=get_array_value($_POST,'path','','is_string');
        $downloadName=get_array_value($_POST,'download_name','','is_string');
    }//if(strlen($rHash))
    // vprint(['$rHash'=>$rHash,'$fileName'=>$fileName,'$path'=>$path,'$downloadName'=>$downloadName]);
    if(!$fileName || !$path) {
        end_request($debug,'Invalid request (3)!');
    }
    $sourceFileName=rtrim($path,'/').'/'.$fileName;
    // vprint(['$sourceFileName'=>$sourceFileName,'is_file'=>is_file($sourceFileName)]);
    if(!is_file($sourceFileName)) {
        end_request($debug,'File not found!');
    }
    $fileName=strlen($downloadName) ? $downloadName : $sourceFileName;
    $fInfo=finfo_open(FILEINFO_MIME_TYPE);
    $mimeType=finfo_file($fInfo,$sourceFileName);
    finfo_close($fInfo);
    if(!$mimeType) {
        $mimeType=Helpers::getFileMimeTypeByExtension($fileName);
    }
    $result=file_get_contents($sourceFileName);
} elseif(isset($_GET['shash'])) {
    $rHash=get_array_value($_GET,'shash','','is_string');
    if(!strlen($rHash)) {
        end_request($debug,'File not found!');
    }
    $moduleResultType=get_array_value($_GET,'mrt',0,'is_integer');
    $data=NApp::GetParam($rHash);
    $module=get_array_value($data,'module','','is_string');
    $method=get_array_value($data,'method','','is_string');
    if(!strlen($module) || !strlen($method) || !ModulesProvider::ModuleMethodExists($module,$method)) {
        end_request($debug,($debug ? 'Invalid request: missing module ['.$module.'] / method ['.$method.']!' : 'Invalid request: missing module/method!'));
    }
    $params=get_array_value($data,'params',[],'is_array');
    $uid=get_array_value($_GET,'uid','','is_string');
    if(strlen($uid)) {
        $params['uid']=$uid;
    }
    try {
        $result=ModulesProvider::ExecUnsafe($module,$method,$params);
    } catch(AppException $e) {
        NApp::Elog($e);
        end_request($debug,$e->getMessage());
    }//END try
    switch($moduleResultType) {
        case 1:
            $sourceFileName=get_array_value($result,'file_name','','is_string');
            $path=get_array_value($result,'path','','is_string');
            $downloadName=get_array_value($result,'download_name','','is_string');
            if(!$sourceFileName || !$path) {
                end_request($debug,'Invalid request (3)!');
            }
            $sourceFileName=rtrim($path,'/').'/'.$sourceFileName;
            if(!is_file($sourceFileName)) {
                end_request($debug,'File not found!');
            }
            $fileName=strlen($downloadName) ? $downloadName : $sourceFileName;
            $fInfo=finfo_open(FILEINFO_MIME_TYPE);
            $mimeType=finfo_file($fInfo,$sourceFileName);
            finfo_close($fInfo);
            if(!$mimeType) {
                $mimeType=Helpers::getFileMimeTypeByExtension($fileName);
            }
            $result=file_get_contents($sourceFileName);
            break;
        case 0;
            if(!is_string($result) || !strlen($result)) {
                end_request($debug,'Invalid file content!');
            }
            $fileName=date('YmdHis');
            $fInfo=finfo_open();
            $mimeType=finfo_buffer($fInfo,$result,FILEINFO_MIME_TYPE);
            finfo_close($fInfo);
            if(!$mimeType) {
                $fInfo=new finfo(FILEINFO_MIME_TYPE);
                $ext=$fInfo->buffer($result);
            } else {
                $ext=Helpers::getFileExtensionByMimeType($mimeType);
            }//if(!$mime_type)
            $fileName.=(strlen($ext) ? '.' : '').$ext;
            break;
        default:
            end_request($debug,'Invalid request (4)!');
            break;
    }//END switch
} elseif(isset($_GET['ehash'])) {
    $rHash=get_array_value($_GET,'ehash','','is_string');
    if(!strlen($rHash)) {
        end_request($debug,'Invalid request (hash)!');
    }
    $rHash=GibberishAES::dec(rawurldecode($rHash),'eUrlHash');
    if(!strlen($rHash)) {
        end_request($debug,'Invalid request (hash)!');
    }
    try {
        $payload=json_decode($rHash,TRUE);
    } catch(Exception $e) {
        NApp::Elog($e);
        end_request($debug,$e->getMessage());
    }
    $module=convert_to_camel_case(get_array_value($payload,'module',NULL,'is_string'),FALSE,TRUE);
    $method=convert_to_camel_case(get_array_value($payload,'method',NULL,'is_string'));
    $params=get_array_value($payload,'params',[],'is_array');
    if(!ModulesProvider::ModuleMethodExists($module,$method)) {
        end_request($debug,'Invalid request (3)!');
    }
    try {
        $result=ModulesProvider::Exec($module,$method,$params);
    } catch(AppException $e) {
        NApp::Elog($e);
        end_request($debug,$e->getMessage());
    }//END try
    $outputResult=is_string($result) && strlen($result);
} else {
    $module=get_array_value($_GET,'module',get_array_value($_POST,'module',NULL,'is_string'),'is_string');
    $method=get_array_value($_GET,'method',get_array_value($_POST,'method',NULL,'is_string'),'is_string');
    if(isset($module) || isset($method)) {
        $params=array_merge($_POST,$_GET);
        $module=convert_to_camel_case($module,FALSE,TRUE);
        $method=convert_to_camel_case($method);
        if(!ModulesProvider::ModuleMethodExists($module,$method)) {
            end_request($debug,'Invalid request (3)!');
        }
        try {
            $result=ModulesProvider::Exec($module,$method,$params);
        } catch(AppException $e) {
            NApp::Elog($e);
            end_request($debug,$e->getMessage());
        }//END try
        $outputResult=is_string($result) && strlen($result);
    } else {
        $file=get_array_value($_GET,'file',get_array_value($_POST,'file',NULL,'is_notempty_string'),'is_notempty_string');
        $file=rawurldecode($file);
        if(!file_exists(NApp::GetRepositoryPath().$file)) {
            end_request($debug,'File not found!');
        }
        $fInfo=finfo_open(FILEINFO_MIME_TYPE);
        $mimeType=finfo_file($fInfo,NApp::GetRepositoryPath().$file);
        finfo_close($fInfo);
        if(!$mimeType) {
            $mimeType=Helpers::getFileMimeTypeByExtension(basename($file));
        }
        $result=file_get_contents(NApp::GetRepositoryPath().$file);
        $fileName=basename($file);
    }//if(isset($module) || isset($method))
}//if(isset($_GET['hash']))

if($outputResult) {
    download_string($result,$fileName,$mimeType,$debug);
}

function download_string(&$data,$filename,$mime_type,$debug=FALSE,$content_dis='attachment') {
    // NApp::Dlog(number_format(NApp::ShowTimeTrack('cdnTime'),3,'.',',').' sec.','cdnTime');
    if(ErrorHandler::HasErrors()) {
        end_request($debug);
    }
    header('Content-Description: File Transfer');
    header('Content-Type: '.$mime_type.'; charset=UTF-8');
    header('Content-Disposition: '.$content_dis.'; filename='.$filename);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: max-age=0, must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: '.strlen($data));
    echo $data;
}//END function download_string

function end_request($debug=FALSE,$message=NULL) {
    if($debug) {
        if($message) {
            print_r($message);
        }
        ErrorHandler::ShowErrors();
        ob_flush();
    } else {
        header("HTTP/1.0 404 Not Found");
    }//if($debug)
    die();
}//END function end_request