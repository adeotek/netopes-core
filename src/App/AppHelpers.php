<?php
/**
 * NETopes application helpers class file
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use NApp;
use NETopes\Core\AppConfig;
use NETopes\Core\AppSession;

/**
 * Class AppHelpers
 *
 * @package NETopes\Core
 */
class AppHelpers {
    const JS_SCRIPT_INJECTION_TYPE_STRING='string';
    const JS_SCRIPT_INJECTION_TYPE_FUNCTION='function';
    const JS_SCRIPT_INJECTION_TYPE_OBJECT='object';
    const JS_SCRIPT_INJECTION_TYPE_NUMERIC='object';

    /**
     * @var array
     */
    protected static $_globals=[];

    /**
     * Get current namespace section relative path (with theme)
     *
     * @param string $themeDir Optional theme directory
     *                         For non-web namespaces overwrites configuration theme
     * @return string Returns the current namespace section relative path
     *                         For non-web namespaces includes theme directory
     * @throws \NETopes\Core\AppException
     */
    public static function GetSectionPath($themeDir=NULL) {
        $relativePath='/templates/'.NApp::$currentNamespace.NApp::$currentSectionFolder;
        if(NApp::$currentNamespace=='web') {
            $relativePath.=(is_string($themeDir) && strlen($themeDir) ? '/themes/'.$themeDir : '').'/';
        } else {
            $app_theme=AppConfig::GetValue('app_theme');
            $relativePath.='/themes/'.(is_string($themeDir) && strlen($themeDir) ? $themeDir : (is_string($app_theme) && strlen($app_theme) && $app_theme!='_default' ? $app_theme : 'default')).'/';
        }//if(NApp::$currentNamespace=='web')
        return $relativePath;
    }//END public static function GetSectionPath

    /**
     * Get application non-public repository path
     *
     * @return string
     * @throws \NETopes\Core\AppException
     */
    public static function GetRepositoryPath() {
        $repositoryPath=AppConfig::GetValue('repository_path');
        if(is_string($repositoryPath) && strlen($repositoryPath) && file_exists($repositoryPath)) {
            return rtrim($repositoryPath,'/\\').'/';
        }
        return NApp::$appPath.'/repository/';
    }//END public static function GetRepositoryPath

    /**
     * Get application cache path
     *
     * @return string
     * @throws \NETopes\Core\AppException
     */
    public static function GetCachePath() {
        $cachePath=AppConfig::GetValue('app_cache_path');
        if(is_string($cachePath) && strlen($cachePath) && file_exists($cachePath)) {
            return rtrim($cachePath,'/\\').'/';
        }
        if(!file_exists(NApp::$appPath.'/.cache')) {
            mkdir(NApp::$appPath.'/.cache',755);
        }//if(!file_exists(NApp::$appPath.'/.cache'))
        return NApp::$appPath.'/.cache/';
    }//END public static function GetCachePath

    /**
     * @param mixed     $input
     * @param mixed     $position
     * @param bool|null $defaultValue
     * @param bool      $zeroBasedPosition
     * @return bool|null
     */
    public static function GetBitFromString($input,$position,?bool $defaultValue=NULL,bool $zeroBasedPosition=TRUE): ?bool {
        if(!is_numeric($position) || !is_integer($position * 1) || $position<($zeroBasedPosition ? 0 : 1)) {
            return $defaultValue;
        }
        $position-=$zeroBasedPosition ? 0 : 1;
        if(!is_string($input) || strlen($input)<($position + 1)) {
            return $defaultValue;
        }
        $value=substr(str_pad($input,$position + 1,'0'),$position,1);
        return ($value==='1' ? TRUE : ($value==='0' ? FALSE : $defaultValue));
    }//END public static function GetBitFromString

    /**
     * Set global parameters data
     *
     * @param array|null $data
     * @return void
     */
    public static function SetGlobals(?array $data): void {
        static::$_globals=$data;
    }//END public static function SetGlobals

    /**
     * description
     *
     * @param      $key
     * @param null $defaultValue
     * @param null $validation
     * @return mixed
     */
    public static function GetGlobalVar($key,$defaultValue=NULL,$validation=NULL) {
        return get_array_value(static::$_globals,$key,$defaultValue,$validation);
    }//END public static function GetGlobalVar

    /**
     * description
     *
     * @param $key
     * @param $value
     * @return bool
     */
    public static function SetGlobalVar($key,$value) {
        if(!is_numeric($key) && (!is_string($key) || !strlen($key))) {
            return FALSE;
        }
        if(!is_array(static::$_globals)) {
            static::$_globals=[];
        }
        static::$_globals[$key]=$value;
        return TRUE;
    }//END public static function SetGlobalVar

    /**
     * description
     *
     * @param      $key
     * @param null $defaultValue
     * @param null $validation
     * @return mixed
     */
    public function GetRequestParamValue($key,$defaultValue=NULL,$validation=NULL) {
        return get_array_value(static::$_globals,['req_params',$key],$defaultValue,$validation);
    }//END public function GetRequestParamValue

    /**
     * description
     *
     * @return void
     */
    public static function ProcessRequestParams() {
        if(!is_array(static::$_globals)) {
            static::$_globals=[];
        }
        if(!array_key_exists('req_params',static::$_globals) || !is_array(static::$_globals['req_params'])) {
            static::$_globals['req_params']=[];
        }
        $uripage=NApp::Url()->GetParamElement('page');
        $uripag=NApp::Url()->GetParamElement('pag');
        static::$_globals['req_params']['id_page']=is_numeric($uripage) ? $uripage : NULL;
        static::$_globals['req_params']['pagination']=is_numeric($uripag) ? $uripag : NULL;
        $urlid=strtolower(trim(NApp::Url()->GetParamElement('urlid'),'/'));
        if(strpos($urlid,'/')===FALSE) {
            static::$_globals['req_params']['category']=NULL;
            static::$_globals['req_params']['subcategories']=NULL;
            static::$_globals['req_params']['page']=$urlid;
        } else {
            $urlid_arr=explode('/',$urlid);
            $e_page=array_pop($urlid_arr);
            $e_cat=array_shift($urlid_arr);
            $e_scat=is_array($urlid_arr) && count($urlid_arr) ? implode('/',$urlid_arr) : NULL;
            static::$_globals['req_params']['category']=$e_cat;
            static::$_globals['req_params']['subcategories']=$e_scat;
            static::$_globals['req_params']['page']=$e_page;
        }//if(strpos($urlid,'/')===FALSE)
        static::$_globals['req_params']['module']=NApp::Url()->GetParam('module');
        static::$_globals['req_params']['action']=NApp::Url()->GetParam('a');
    }//END public static function _ProcessRequestParams

    /**
     * @param array|null $params
     * @return string|null
     */
    public static function ProcessJsScriptParams(?array $params): ?string {
        if(!is_array($params)) {
            return NULL;
        }
        $result='';
        foreach($params as $k=>$v) {
            if(is_array($v)) {
                $type=get_array_value($v,'type',static::JS_SCRIPT_INJECTION_TYPE_STRING,'is_notempty_string');
                $jsVarType=get_array_value($v,'js_var_type','const','is_notempty_string');
                $value=get_array_value($v,'value',NULL,'isset');
                if(!is_scalar($value)) {
                    $value=json_encode($value);
                }
                if($type==static::JS_SCRIPT_INJECTION_TYPE_FUNCTION) {
                    $arguments=get_array_value($v,'arguments',[],'is_array');
                    $result.="{$jsVarType} {$k}=function(".implode(',',$arguments)."){ {$value} };\n";
                } elseif($type==static::JS_SCRIPT_INJECTION_TYPE_OBJECT) {
                    $result.=$jsVarType.' '.$k.'='.$value.';'."\n";
                } else {
                    $result.=" {$jsVarType} {$k}='{$value}';\n";
                }
            } else {
                $result.=' const '.$k.'='.(is_numeric($v) ? $v : (is_string($v) ? "'{$v}'" : json_encode($v))).';';
            }
        }//END foreach
        return $result;
    }//END public static function ProcessJsScriptParams

    /**
     * Add javascript code to the dynamic js queue (executed at the end of the current request)
     *
     * @param string     $value Javascript code
     * @param bool       $fromFile
     * @param array|null $jsParams
     * @param bool       $dynamic
     * @return void
     */
    public static function AddJsScript(string $value,bool $fromFile=FALSE,?array $jsParams=NULL,bool $dynamic=FALSE) {
        $value=trim($value);
        if($fromFile) {
            if(file_exists($value)) {
                $jsScript=file_get_contents($value);
            } else {
                $jsScript=NULL;
            }
        } else {
            $jsScript=$value;
        }
        if(!strlen($value)) {
            return;
        }
        $jsScript=static::ProcessJsScriptParams($jsParams).$jsScript;
        if(!$dynamic && NApp::IsAjax() && NApp::IsValidAjaxRequest()) {
            NApp::Ajax()->ExecuteJs($jsScript);
        } else {
            $dynamic_js_scripts=self::GetGlobalVar('dynamic_js_scripts',[],'is_array');
            $dynamic_js_scripts[]=['js'=>$jsScript];
            self::SetGlobalVar('dynamic_js_scripts',$dynamic_js_scripts);
        }//if(!$dynamic && NApp::IsAjax() && NApp::IsValidAjaxRequest())
    }//END public static function AddJsScript

    /**
     * Get dynamic javascript to be executed
     *
     * @param bool $asArray
     * @param bool $raw
     * @return string|array Returns scripts to be executed
     */
    public static function GetDynamicJs(bool $asArray=FALSE,bool $raw=FALSE) {
        $scripts=self::GetGlobalVar('dynamic_js_scripts',[],'is_array');
        if($asArray || !count($scripts)) {
            return $scripts;
        }
        $html_data='';
        $data='';
        foreach($scripts as $s) {
            if(is_array($s)) {
                $d_js=get_array_value($s,'js','','is_string');
                $d_html=get_array_value($s,'html','','is_string');
                if(strlen($d_js)) {
                    $data.=$d_js."\n";
                }
                if(strlen($d_html)) {
                    $html_data.=$d_html."\n";
                }
            } elseif(is_string($s) && strlen($s)) {
                $data.=$s."\n\n";
            }//if(is_array($s))
        }//END foreach
        if($raw) {
            return [
                'html'=>$html_data,
                'js'=>$data,
            ];
        }
        return $html_data.'<script type="text/javascript">'."\n".$data."\n".'</script>'."\n";
    }//END public static function GetDynamicJs

    /**
     * @param int $startFrom
     * @return string|null
     */
    public static function GetParentCallerModule(int $startFrom=1): ?string {
        foreach(debug_backtrace() as $k=>$btItem) {
            if($k<$startFrom) {
                continue;
            }
            $class=get_array_value($btItem,'class',NULL,'is_notempty_string');
            if(is_subclass_of($class,Module::class) && !in_array(ISystemModule::class,class_implements($class))) {
                return '\\'.ltrim($class,'\\');
            }
        }//END foreach
        return NULL;
    }//END public static function GetParentCallerModule

    /**
     * Converts the rights revoked database array to an nested array
     * (on 3 levels - module=>method=>rights_revoked)
     *
     * @param $array
     * @return array
     */
    public static function ConvertRightsRevokedArray($array) {
        if(!is_array($array) || !count($array)) {
            return [];
        }
        $result=[];
        foreach($array as $line) {
            if(!strlen($line['module']) || !strlen($line['method'])) {
                continue;
            }
            $result[$line['module']][$line['method']]=$line;
        }//END foreach
        return $result;
    }//END public static function ConvertRightsRevokedArray

    /**
     * @param string|null $type
     * @param mixed       $data
     * @param string|null $content
     */
    public static function OutputResponse(?string $type=NULL,$data=NULL,?string $content=NULL): void {
        switch(strtolower($type)) {
            case 'json':
                header('Content-type: application/json');
                echo json_encode($data);
                break;
            case 'jsonp':
                header('Content-type: application/jsonp');
                echo json_encode($data);
                break;
            case 'php':
                echo serialize($data);
                break;
            case 'html':
            default:
                echo utf8_encode($content.(is_string($data) ? $data : ''));
                break;
        }//END switch
    }//END public static function OutputResponse

    /**
     * Gets the application copyright
     *
     * @return string Returns the application copyright
     * @throws \NETopes\Core\AppException
     */
    public static function GetAppCopyright() {
        $copyright=AppConfig::GetValue('app_copyright');
        return ($copyright ? $copyright : '&copy; ').date('Y');
    }//END public static function GetAppCopyright

    /**
     * Gets the current application version
     *
     * @param string $type Specifies the return type:
     *                     - NULL or empty string (default) for return as string
     *                     - 'array' for return as array (key-value)
     *                     - 'major' for return only the major version as int
     *                     - 'minor' for return only the minor version as int
     *                     - 'build' for return only the build version as int
     * @return mixed Returns the application version as a string or an array or a specific part of the version
     * @throws \NETopes\Core\AppException
     */
    public static function GetVersion($type=NULL) {
        switch($type) {
            case 'array':
                $ver_arr=explode('.',AppConfig::GetValue('app_version'));
                return ['major'=>$ver_arr[0],'minor'=>$ver_arr[1],'build'=>$ver_arr[2]];
                break;
            case 'major':
                $ver_arr=explode('.',AppConfig::GetValue('app_version'));
                return intval($ver_arr[0]);
                break;
            case 'minor':
                $ver_arr=explode('.',AppConfig::GetValue('app_version'));
                return intval($ver_arr[1]);
                break;
            case 'buid':
                $ver_arr=explode('.',AppConfig::GetValue('app_version'));
                return intval($ver_arr[2]);
                break;
            default:
                return AppConfig::GetValue('app_version');
                break;
        }//END switch
    }//END public static function GetVersion

    /**
     * Gets the current application framework version
     *
     * @param string $type Specifies the return type:
     *                     - NULL or empty string (default) for return as string
     *                     - 'array' for return as array (key-value)
     *                     - 'major' for return only the major version as int
     *                     - 'minor' for return only the minor version as int
     *                     - 'build' for return only the build version as int
     * @return mixed Returns the application framework version
     * @throws \NETopes\Core\AppException
     */
    public static function GetFrameworkVersion($type=NULL) {
        switch($type) {
            case 'array':
                $ver_arr=explode('.',AppConfig::GetValue('framework_version'));
                return ['major'=>$ver_arr[0],'minor'=>$ver_arr[1],'build'=>$ver_arr[2]];
                break;
            case 'major':
                $ver_arr=explode('.',AppConfig::GetValue('framework_version'));
                return intval($ver_arr[0]);
                break;
            case 'minor':
                $ver_arr=explode('.',AppConfig::GetValue('framework_version'));
                return intval($ver_arr[1]);
                break;
            case 'buid':
                $ver_arr=explode('.',AppConfig::GetValue('framework_version'));
                return intval($ver_arr[2]);
                break;
            default:
                return AppConfig::GetValue('framework_version');
                break;
        }//END switch
    }//END public static function GetFrameworkVersion

    /**
     * Initializes KCFinder session parameters
     *
     * @param array $params
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public static function InitializeKCFinder($params=NULL) {
        if(!AppSession::WithSession() || !AppConfig::GetValue('use_kc_finder')) {
            return;
        }
        $type=get_array_value($params,'type',AppConfig::GetValue('kc_finder_default_type'),'is_string');
        switch(strtolower($type)) {
            case 'public':
                AppSession::SetGlobalParam('disabled',FALSE,'__KCFINDER',NULL,FALSE);
                AppSession::SetGlobalParam('uploadURL',NApp::$appBaseUrl.'/repository/public','__KCFINDER',NULL,FALSE);
                AppSession::SetGlobalParam('uploadDir',NApp::$appPublicPath.'/repository/public','__KCFINDER',NULL,FALSE);
                break;
            case 'app':
                AppSession::SetGlobalParam('disabled',(UserSession::$loginStatus && NApp::GetParam('user_hash')) ? FALSE : TRUE,'__KCFINDER',NULL,FALSE);
                AppSession::SetGlobalParam('uploadURL',NApp::$appBaseUrl.'/repository/app','__KCFINDER',NULL,FALSE);
                AppSession::SetGlobalParam('uploadDir',NApp::$appPublicPath.'/repository/app','__KCFINDER',NULL,FALSE);
                break;
            case 'cms':
            default:
                $section_folder=get_array_value($params,'section_folder',NApp::GetParam('section_folder'),'is_string');
                $zone_code=get_array_value($params,'zone_code',NApp::GetParam('zone_code'),'is_string');
                // TODO: fix multi instance
                AppSession::SetGlobalParam('disabled',(UserSession::$loginStatus && NApp::GetParam('user_hash')) ? FALSE : TRUE,'__KCFINDER',NULL,FALSE);
                AppSession::SetGlobalParam('uploadURL',NApp::$appBaseUrl.'/repository/'.$section_folder.'/'.$zone_code,'__KCFINDER',NULL,FALSE);
                AppSession::SetGlobalParam('uploadDir',NApp::$appPublicPath.'/repository/'.$section_folder.'/'.$zone_code,'__KCFINDER',NULL,FALSE);
                break;
        }//END switch
        NApp::SessionCommit(FALSE,TRUE,TRUE,NULL,'__KCFINDER',FALSE);
    }//END public static function InitializeKCFinder
}//END class AppHelpers