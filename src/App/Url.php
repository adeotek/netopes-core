<?php
/**
 * Class Url file.
 * Application URL interaction class.
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use NETopes\Core\AppConfig;
use NETopes\Core\DataHelpers;
use NETopes\Core\Helpers;

/**
 * Class Url
 * Application URL interaction class.
 *
 * @package NETopes\Core\App
 */
class Url {
    /**
     * URL format: friendly (original)
     */
    const URL_FORMAT_FRIENDLY_ORIGINAL=-1;
    /**
     * URL format: URI only
     */
    const URL_FORMAT_URI_ONLY=0;
    /**
     * URL format: friendly
     */
    const URL_FORMAT_FRIENDLY=1;
    /**
     * URL format: full
     */
    const URL_FORMAT_FULL=2;
    /**
     * URL format: short
     */
    const URL_FORMAT_SHORT=3;
    /**
     * @var        string The path included in the application URL
     */
    protected static $urlPath=NULL;
    /**
     * @var    string Application web protocol (http/https)
     */
    protected $appWebProtocol=NULL;
    /**
     * @var    string Application domain (auto-set on constructor)
     */
    protected $appDomain=NULL;
    /**
     * @var    string Application folder inside www root (auto-set on constructor)
     */
    protected $urlFolder=NULL;
    /**
     * @var    string Application base url: domain + path + url id (auto-set on constructor)
     */
    protected $urlBase=NULL;
    /**
     * @var    array GET (URL) data
     */
    public $data=[];
    /**
     * @var    array GET (URL) special parameters list
     */
    public $specialParams=['language','urlid'];
    /**
     * @var    string URL virtual path
     */
    public $urlVirtualPath=NULL;

    /**
     * Extracts the URL path of the application.
     *
     * @param string $startupPath Entry point file absolute path
     * @return     string Returns the URL path of the application.
     */
    public static function ExtractUrlPath($startupPath=NULL) {
        if(strlen($startupPath)) {
            self::$urlPath=str_replace('\\','/',(str_replace(_NAPP_ROOT_PATH._NAPP_PUBLIC_ROOT_PATH,'',$startupPath)));
            self::$urlPath=trim(str_replace(trim(self::$urlPath,'/'),'',trim(dirname($_SERVER['SCRIPT_NAME']),'/')),'/');
            self::$urlPath=trim(self::$urlPath.'/'.trim(_NAPP_PUBLIC_PATH,'\/'),'\/');
        } else {
            self::$urlPath=trim(dirname($_SERVER['SCRIPT_NAME']),'\/');
        }//if(strlen($startupPath))
        return (strlen(self::$urlPath) ? '/'.self::$urlPath : '');
    }//END public static function ExtractUrlPath

    /**
     * Gets the base URL of the application.
     *
     * @param string $startupPath Startup absolute path
     * @return     string Returns the base URL of the application.
     */
    public static function GetRootUrl($startupPath=NULL) {
        $appWebProtocol=(isset($_SERVER["HTTPS"]) ? 'https' : 'http').'://';
        $appDomain=strtolower((array_key_exists('HTTP_HOST',$_SERVER) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
        $urlFolder=self::ExtractUrlPath($startupPath);
        return $appWebProtocol.$appDomain.$urlFolder;
    }//END public static function GetRootUrl

    /**
     * AppUrl constructor.
     *
     * @param string     $appDomain
     * @param string     $appWebProtocol
     * @param string     $urlFolder
     * @param array|null $removeFromPath
     */
    public function __construct(string $appDomain,string $appWebProtocol,?string $urlFolder=NULL,?array $removeFromPath=NULL) {
        $this->appDomain=$appDomain;
        $this->appWebProtocol=$appWebProtocol;
        $this->urlFolder=strlen(trim($urlFolder,'\/ ')) ? '/'.trim($urlFolder,'\/ ') : '';
        if(isset($_SERVER['REQUEST_URI'])) {
            $uri_len=strpos($_SERVER['REQUEST_URI'],'?')!==FALSE ? strpos($_SERVER['REQUEST_URI'],'?') : (strpos($_SERVER['REQUEST_URI'],'#')!==FALSE ? strpos($_SERVER['REQUEST_URI'],'#') : strlen($_SERVER['REQUEST_URI']));
            $this->urlVirtualPath=trim(substr($_SERVER['REQUEST_URI'],0,$uri_len),'/');
            if(is_array($removeFromPath) && count($removeFromPath)) {
                foreach($removeFromPath as $r) {
                    $this->urlVirtualPath=str_replace('/'.trim($r,'/ '),'',$this->urlVirtualPath);
                }//END foreach
            }
            $this->urlBase=$this->appWebProtocol.$this->appDomain.'/'.$this->urlVirtualPath;
        } else {
            $this->urlBase=$this->appWebProtocol.$this->appDomain;
        }//if(isset($_SERVER['REQUEST_URI']))
        $this->data=is_array($_GET) ? $this->SetParams($_GET) : [];
    }//END public function __construct

    /**
     * @return string
     */
    public function GetCurrentUrl(): string {
        return $this->appWebProtocol.$this->appDomain.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
    }//END public function GetCurrentUrl

    /**
     * @return string
     */
    public function GetWebLink(): string {
        return $this->appWebProtocol.$this->appDomain.$this->urlFolder;
    }//END public function GetWebLink

    /**
     * @return string
     */
    public function GetAppWebProtocol(): string {
        return $this->appWebProtocol;
    }//END public function GetAppWebProtocol

    /**
     * @return string
     */
    public function GetAppDomain(): string {
        return $this->appDomain;
    }//END public function GetAppDomain

    /**
     * @return string
     */
    public function GetUrlFolder(): string {
        return $this->urlFolder;
    }//END public function GetUrlFolder

    /**
     * description
     *
     * @param object|null $params Parameters object (instance of [Params])
     * @param bool        $keysonly
     * @return string
     */
    public function ParamToString($params,$keysonly=FALSE) {
        if(is_array($params)) {
            $keys='';
            $texts='';
            foreach($params as $k=>$v) {
                $keys.=(strlen($keys) ? ',' : '').$k;
                if($keysonly!==TRUE) {
                    $texts.=(strlen($texts) ? ',' : '').Helpers::stringToUrl($v);
                }
            }//foreach ($params as $k=>$v)
            if($keysonly===TRUE) {
                return $keys;
            }
            return $keys.(strlen($texts) ? '~'.$texts : '');
        } else {
            return (isset($params) ? $params : '');
        }//if(is_array($params))
    }//END public function ParamToString

    /**
     * description
     *
     * @param $param
     * @return array|null
     */
    public function GetParamElements($param) {
        $result=NULL;
        if(strlen($param)) {
            $param_keys=strpos($param,'~')===FALSE ? $param : substr($param,0,(strpos($param,'~')));
            $param_texts=strpos($param,'~')===FALSE ? '' : substr($param,(strpos($param,'~') + 1));
            $keys=explode(',',$param_keys);
            $texts=strlen($param_texts)>0 ? explode(',',$param_texts) : NULL;
            for($i=0; $i<count($keys); $i++) {
                if(strlen($keys[$i])>0) {
                    if(!is_array($result)) {
                        $result=[];
                    }//if(!is_array($result))
                    $result[$keys[$i]]=(is_array($texts) && array_key_exists($i,$texts)) ? $texts[$i] : '';
                }//if(strlen($keys[$i])>0)
            }//for($i=0; $i<count($keys); $i++)
        }//if(strlen($param))
        return $result;
    }//END public function GetParamElements

    /**
     * Get elements for a parameter from the url data array
     *
     * @param      $key
     * @param bool $string
     * @param bool $keysonly
     * @return string|null
     */
    public function GetComplexParam($key,$string=FALSE,$keysonly=FALSE) {
        $result=array_key_exists($key,$this->data) ? $this->data[$key] : NULL;
        if($string===TRUE && isset($result)) {
            return $this->ParamToString($result,$keysonly);
        }
        return $result;
    }//END public function GetComplexParam

    /**
     * Set a simple parameter into the url data array
     *
     * @param $key
     * @param $val
     * @return bool
     */
    public function SetComplexParam($key,$val) {
        if(!is_array($val) || !count($val)) {
            return FALSE;
        }
        $this->data[$key]=$val;
        return TRUE;
    }//END public function SetComplexParam

    /**
     * Unset a parameter from the url data array
     *
     * @param $key
     * @return void
     */
    public function UnsetComplexParam($key) {
        unset($this->data[$key]);
    }//END public function UnsetComplexParam

    /**
     * Get a simple parameter from the url data array
     *
     * @param      $key
     * @param bool $full
     * @return string|null
     */
    public function GetParam($key,$full=FALSE) {
        return $this->GetComplexParam($key,$full!==TRUE,TRUE);
    }//END public function GetParam

    /**
     * Set a simple parameter into the url data array
     *
     * @param $key
     * @param $val
     * @return bool
     */
    public function SetParam($key,$val) {
        return $this->SetComplexParam($key,[$val=>'']);
    }//END public function SetParam

    /**
     * Unset a parameter from the url data array
     *
     * @param $key
     * @return void
     */
    public function UnsetParam($key) {
        $this->UnsetComplexParam($key);
    }//END public function UnsetParam

    /**
     * Gets n-th element from a parameter in the url data array
     *
     * @param     $key
     * @param int $position
     * @return string|null
     */
    public function GetParamElement($key,$position=0) {
        if(strlen($key)>0 && array_key_exists($key,$this->data)) {
            if(is_array($this->data[$key])) {
                $i=0;
                foreach($this->data[$key] as $k=>$v) {
                    if($i==$position) {
                        return $k;
                    } else {
                        $i++;
                    }//if($i==$position)
                }//foreach ($this->data[$key] as $k=>$v)
            } else {
                return $this->data[$key];
            }//if(is_array($this->data[$key]))
        }//if(strlen($key)>0 && array_key_exists($key,$this->data))
        return NULL;
    }//END public function GetParamElement

    /**
     * Sets an element from a parameter in the url data array
     *
     * @param        $key
     * @param        $element
     * @param string $text
     * @return bool
     */
    public function SetParamElement($key,$element,$text='') {
        if(is_null($key) || is_null($element)) {
            return FALSE;
        }
        $this->data[$key]=is_array($this->data[$key]) ? $this->data[$key] : [];
        if(is_array($element)) {
            foreach($element as $k=>$v) {
                $this->data[$key][$k]=Helpers::stringToUrl($v);
            }//foreach ($element as $k=>$v)
        } else {
            $this->data[$key][$element]=Helpers::stringToUrl($text);
        }//if(is_array($element))
        return TRUE;
    }//END public function SetParamElement

    /**
     * Removes an element from a parameter in the url data array
     *
     * @param $key
     * @param $element
     * @return bool
     */
    public function UnsetParamElement($key,$element) {
        if(is_null($key) || is_null($element)) {
            return FALSE;
        }
        unset($this->data[$key][$element]);
        return TRUE;
    }//END public function UnsetParamElement

    /**
     * description
     *
     * @param $url
     * @return array
     */
    public function SetParams($url) {
        $result=[];
        if(is_array($url)) {
            foreach($url as $k=>$v) {
                $result[$k]=$this->GetParamElements($v);
            }
        } else {
            $param_str=explode('?',$url);
            $param_str=count($param_str)>1 ? $param_str[1] : '';
            if(strlen($param_str)>0) {
                $params=explode('&',$param_str);
                foreach($params as $param) {
                    $element=explode('=',$param);
                    if(count($element)>1) {
                        $result[$element[0]]=$this->GetParamElements($element[1]);
                    }
                }//foreach ($params as $k=>$v)
            }//if(strlen($param_str)>0)
        }//if(is_array($url))
        return $result;
    }//END public function SetParams

    /**
     * description
     *
     * @param int  $urlFormat
     * @param null $params
     * @return string
     * @throws \NETopes\Core\AppException
     */
    public function GetBase($urlFormat=self::URL_FORMAT_FRIENDLY,$params=NULL) {
        $lUrlFormat=AppConfig::GetValue('app_mod_rewrite') ? $urlFormat : self::URL_FORMAT_SHORT;
        switch($lUrlFormat) {
            case self::URL_FORMAT_FRIENDLY:
                $lang=NULL;
                $urlid=NULL;
                $urlpath=NULL;
                if(is_array($params) && count($params)) {
                    $lang=array_key_exists('language',$params) ? $this->ParamToString($params['language']) : NULL;
                    $urlid=array_key_exists('urlid',$params) ? $this->ParamToString($params['urlid']) : NULL;
                }//if(is_array($params) && count($params))
                if(is_null($lang)) {
                    $lang=array_key_exists('language',$this->data) ? $this->ParamToString($this->data['language']) : NULL;
                }//if(is_null($lang))
                if(is_null($urlid)) {
                    $urlid=array_key_exists('urlid',$this->data) ? $this->ParamToString($this->data['urlid']) : NULL;
                }//if(is_null($urlid))
                return $this->GetWebLink().'/'.(strlen($this->urlVirtualPath) ? $this->urlVirtualPath.'/' : '').(strlen($lang) ? $lang.'/' : '').(strlen(trim($urlid,'/')) ? trim($urlid,'/').'/' : '');
            case self::URL_FORMAT_FRIENDLY_ORIGINAL:
                return $this->urlBase;
            case self::URL_FORMAT_FULL:
                return $this->GetWebLink().'/index.php';
            case self::URL_FORMAT_SHORT:
                return $this->GetWebLink().'/';
            case self::URL_FORMAT_URI_ONLY:
            default:
                return '';
        }//END switch
    }//END public function GetBase

    /**
     * Create new application URL
     *
     * @param object|null $params Parameters object (instance of [Params])
     * @param int         $url_format
     * @return string
     * @throws \NETopes\Core\AppException
     */
    public function GetNewUrl($params=NULL,$url_format=self::URL_FORMAT_FRIENDLY) {
        $result='';
        $anchor='';
        $lurl_format=AppConfig::GetValue('app_mod_rewrite') ? $url_format : self::URL_FORMAT_SHORT;
        if(is_array($params) && count($params)) {
            $first=TRUE;
            foreach($params as $k=>$v) {
                if($k=='anchor') {
                    $anchor=$this->ParamToString($v);
                    continue;
                }//if($k=='anchor')
                if(($lurl_format==self::URL_FORMAT_FRIENDLY || $lurl_format==self::URL_FORMAT_FRIENDLY_ORIGINAL) && in_array($k,$this->specialParams)) {
                    continue;
                }
                $val=$this->ParamToString($v);
                if(in_array($k,$this->specialParams) && !$val) {
                    continue;
                }
                $prefix='&';
                if($first) {
                    $first=FALSE;
                    $prefix='?';
                }//if($first)
                $result.=$prefix.$k.'='.$val;
            }//END foreach
        }//if(is_array($params) && count($params))
        return $this->GetBase($lurl_format,$params).$result.(strlen($anchor) ? '#'.$anchor : '');
    }//END public function GetNewUrl

    /**
     * description
     *
     * @param null $params
     * @param null $rparams
     * @param int  $url_format
     * @return string
     * @throws \NETopes\Core\AppException
     */
    public function GetUrl($params=NULL,$rparams=NULL,$url_format=self::URL_FORMAT_FRIENDLY) {
        $data=$this->data;
        if(is_array($rparams) && count($rparams)) {
            foreach($rparams as $key=>$value) {
                if(is_array($value)) {
                    foreach($value as $rv) {
                        unset($data[$key][$rv]);
                    }
                    if(count($data[$key])==0) {
                        unset($data[$key]);
                    }
                } else {
                    unset($data[$value]);
                }//if(is_array($value))
            }//END foreach
        }//if(is_array($rparams) && count($rparams))
        if(is_array($params) && count($params)) {
            $data=DataHelpers::customArrayMerge($data,$params,TRUE);
        }
        return $this->GetNewUrl($data,$url_format);
    }//END public function GetUrl

    /**
     * description
     *
     * @param      $key
     * @param null $element
     * @return bool
     */
    public function ElementExists($key,$element=NULL) {
        if(is_null($element)) {
            if(array_key_exists($key,$this->data) && isset($this->data[$key])) {
                return TRUE;
            }//if(array_key_exists($key,$this->data) && isset($this->data[$key]))
        } else {
            if(array_key_exists($key,$this->data) && array_key_exists($element,$this->data[$key])) {
                return TRUE;
            }//if(array_key_exists($key,$this->data) && array_key_exists($element,$this->data[$key]))
        }//if(is_null($element))
        return FALSE;
    }//END public function ElementExists
}//END class AppUrl