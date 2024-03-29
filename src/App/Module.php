<?php
/**
 * Module class file
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use GibberishAES;
use NApp;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use NETopes\Core\Controls\ControlsHelpers;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Translate;

/**
 * Class Module
 * All applications modules extend this base class
 *
 * @package  NETopes\Core\App
 * @method static ViewDRights(?string $uid=NULL): ?bool
 * @method static ListDRights(?string $uid=NULL): ?bool
 * @method static SearchDRights(?string $uid=NULL): ?bool
 * @method static AddDRights(?string $uid=NULL): ?bool
 * @method static EditDRights(?string $uid=NULL): ?bool
 * @method static DeleteDRights(?string $uid=NULL): ?bool
 * @method static PrintDRights(?string $uid=NULL): ?bool
 * @method static ValidateDRights(?string $uid=NULL): ?bool
 * @method static CancelDRights(?string $uid=NULL): ?bool
 * @method static ExportDRights(?string $uid=NULL): ?bool
 * @method static ImportDRights(?string $uid=NULL): ?bool
 */
class Module {
    /**
     * Deny view right
     */
    const DRIGHT_VIEW='view';
    /**
     * Deny list right
     */
    const DRIGHT_LIST='list';
    /**
     * Deny search right
     */
    const DRIGHT_SEARCH='search';
    /**
     * Deny add right
     */
    const DRIGHT_ADD='add';
    /**
     * Deny edit right
     */
    const DRIGHT_EDIT='edit';
    /**
     * Deny delete right
     */
    const DRIGHT_DELETE='delete';
    /**
     * Deny print right
     */
    const DRIGHT_PRINT='print';
    /**
     * Deny validate right
     */
    const DRIGHT_VALIDATE='validate';
    /**
     * Deny cancel right
     */
    const DRIGHT_CANCEL='cancel';
    /**
     * Deny export right
     */
    const DRIGHT_EXPORT='export';
    /**
     * Deny import right
     */
    const DRIGHT_IMPORT='import';
    /**
     * @var    array Modules instances array
     * @access private
     */
    private static $ModuleInstances=[];
    /**
     * @var    array Module instance debug data
     */
    protected $debugData=NULL;
    /**
     * @var    string Views files extension
     */
    public $viewsExtension;
    /**
     * @var    string Short class name (called class without base prefix)
     */
    public $name;
    /**
     * @var    string Full qualified class name
     */
    public $class;
    /**
     * @var    bool Is custom class (NameCustom extended class)
     */
    public $custom=FALSE;
    /**
     * @var    bool Page hash (window.name)
     */
    public $phash=NULL;
    /**
     * @var    string|null Module menu GUID
     */
    public $dRightsUid=NULL;

    /**
     * Get class name with relative namespace
     *
     * @return string
     * @throws \NETopes\Core\AppException
     */
    public static final function getShortClassName(): string {
        return trim(str_replace(AppConfig::GetValue('app_root_namespace').'\\'.AppConfig::GetValue('app_modules_namespace_prefix'),'',static::class),'\\');
    }//END public static final function getShortClassName

    /**
     * Module class initializer
     *
     * @return void
     */
    protected function _Init() {
    }//END protected function _Init

    /**
     * @param \NETopes\Core\App\Params $params
     * @throws \NETopes\Core\AppException
     */
    protected function ProcessContext(Params $params) {
        $dRightsUid=$params->safeGet('_drights_uid',NULL,'?is_string');
        $callerClass=$params->safeGet('_caller_class',NULL,'?is_string');
        $targetId=$params->safeGet('_target_id',NULL,'?is_string');
        $routes=NApp::GetPageParam('ROUTES');
        if(!is_array($routes)) {
            $routes=[];
        }
        $sessionDRightsUid=get_array_value($routes,[$this->class,'drights_uid'],NULL,'?is_string');
        if(strlen($dRightsUid)) {
            $this->dRightsUid=$dRightsUid;
            $routes[$this->class]=['drights_uid'=>$dRightsUid,'drights_class'=>$this->class,'caller_class'=>$callerClass,'target_id'=>$targetId];
        } elseif(strlen($callerClass) && (!defined($this->class.'::DRIGHTS_UID') || !strlen(static::DRIGHTS_UID))) {
            $dRightsUid=get_array_value($routes,[$callerClass,'drights_uid'],NULL,'?is_string');
            $this->dRightsUid=$dRightsUid;
            $routes[$this->class]=['drights_uid'=>$dRightsUid,'drights_class'=>$callerClass,'caller_class'=>$callerClass,'target_id'=>$targetId];
        } elseif(strlen($sessionDRightsUid)) {
            $this->dRightsUid=$sessionDRightsUid;
            $routes[$this->class]=['drights_uid'=>$sessionDRightsUid,'drights_class'=>$this->class,'caller_class'=>$callerClass,'target_id'=>$targetId];
        } elseif(defined($this->class.'::DRIGHTS_UID')) {
            $this->dRightsUid=static::DRIGHTS_UID;
            $routes[$this->class]=['drights_uid'=>static::DRIGHTS_UID,'drights_class'=>$this->class,'caller_class'=>$callerClass,'target_id'=>$targetId];
        }
        NApp::SetPageParam('ROUTES',$routes);
    }//END protected function ProcessContext

    /**
     * Method to be invoked before a standard method call
     *
     * @param \NETopes\Core\App\Params $params Parameters
     * @return bool  Returns TRUE by default, if FALSE is return the call is canceled
     * @throws \NETopes\Core\AppException
     */
    protected function _BeforeExec(Params $params): bool {
        $this->ProcessContext($params);
        return TRUE;
    }//END protected function _BeforeExec

    /**
     * description
     *
     * @param string $name
     * @param string $class
     * @param bool   $custom
     * @return object
     */
    public static function GetInstance(string $name,string $class,bool $custom=FALSE) {
        if(!array_key_exists($class,self::$ModuleInstances) || !is_object(self::$ModuleInstances[$class])) {
            self::$ModuleInstances[$class]=new $class();
            self::$ModuleInstances[$class]->name=$name;
            self::$ModuleInstances[$class]->class=$class;
            self::$ModuleInstances[$class]->custom=$custom;
        }//if(!array_key_exists($name,self::$ModuleInstances) || !is_object(self::$ModuleInstances[$name]))
        return self::$ModuleInstances[$class];
    }//END public static function GetInstance

    /**
     * Module class constructor
     *
     * @return void
     * @throws \NETopes\Core\AppException
     */
    protected final function __construct() {
        $this->viewsExtension=AppConfig::GetValue('app_views_extension');
        $this->_Init();
    }//END protected final function __construct

    /**
     * @param array|null               $redirect
     * @param \NETopes\Core\App\Params $params
     * @return bool|mixed
     * @throws \NETopes\Core\AppException
     */
    protected function ExecRedirect(?array $redirect,Params $params) {
        $module=get_array_value($redirect,'module',NULL,'is_notempty_string');
        $method=get_array_value($redirect,'method',NULL,'is_notempty_string');
        if(!strlen($module) || !strlen($method) || !ModulesProvider::ModuleMethodExists($module,$method)) {
            NApp::Wlog($redirect,'Invalid redirect data!');
            return FALSE;
        }
        $rParams=get_array_value($redirect,'params',[],'is_array');
        if(count($rParams)) {
            $params->merge($rParams);
        }
        return ModulesProvider::Exec($module,$method,$params);
    }//END protected function ExecRedirect

    /**
     * @param \NETopes\Core\App\Params $params
     * @param array|null               $current
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function ProcessRedirects(Params $params,?array &$current=NULL): ?string {
        $redirects=$params->safeGet('redirects',[],'is_array');
        if(!count($redirects)) {
            return NULL;
        }
        $current=array_shift($redirects);
        if(!count($redirects)) {
            return NULL;
        }
        $params->set('redirects',$redirects);
        $cParams=clone $params;
        $rParams=get_array_value($redirects,[0,'params'],[],'is_array');
        if(count($rParams)) {
            $cParams->merge($rParams);
        }
        return NApp::Ajax()->PrepareAjaxRequest([
            'module'=>$this->name,
            'method'=>'ExecAndRedirect',
            'params'=>$cParams->toArray(),
        ],['target_id'=>get_array_value($redirects,[0,'target_id'],NULL,'is_notempty_string')]);
    }//END protected function ProcessRedirects

    /**
     * @param string|null $separator
     * @return string
     */
    public function GetName(?string $separator=NULL): string {
        return is_null($separator)
            ? $this->name
            : str_replace('\\',$separator,$this->name);
    }//END public function GetName

    /**
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    public function GetDRightsUid() {
        if(strlen($this->dRightsUid)) {
            return $this->dRightsUid;
        }
        $routes=NApp::GetPageParam('ROUTES');
        $sessionDRightsUid=get_array_value($routes,[$this->class,'drights_uid'],NULL,'?is_string');
        if(strlen($sessionDRightsUid)) {
            return $sessionDRightsUid;
        }
        if(defined($this->class.'::DRIGHTS_UID')) {
            return static::DRIGHTS_UID;
        }
        return NULL;
    }//END public function GetDRightsUid

    /**
     * Module class method call
     *
     * @param string $name
     * @param array  $arguments
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function __call(string $name,$arguments) {
        if(strpos($name,'DRights')===FALSE) {
            throw new AppException('Undefined module method ['.$name.']!',E_ERROR,1);
        }
        $uid=get_array_value($arguments,0,$this->dRightsUid,'is_notempty_string');
        // NApp::Dlog($uid,'$uid');
        return self::GetDRights($uid,str_replace('DRights','',$name));
    }//END public function __call

    /**
     * Module class static method call
     *
     * @param string $name
     * @param array  $arguments
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public static function __callStatic($name,$arguments) {
        if(strpos($name,'DRights')===FALSE) {
            throw new AppException('Undefined module method ['.$name.']!',E_ERROR,1);
        }
        $uid=get_array_value($arguments,0,NULL,'?is_string');
        // NApp::Dlog($uid,'$uid');
        return self::GetDRights($uid,str_replace('DRights','',$name));
    }//END public static function __callStatic

    /**
     * Gets the user rights
     *
     * @param string|null $uid
     * @param string      $type
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public static function GetDRights(?string $uid,string $type='All'): ?bool {
        $sAdmin=NApp::GetParam('sadmin')===1;
        // NApp::Dlog($uid,'$uid');
        // NApp::Dlog($type,'$type');
        if(!strlen($type)) {
            return ($sAdmin ? FALSE : NULL);
        }
        $rights=NApp::GetParam('user_rights_revoked');
        $rights=get_array_value($rights,$uid ?? '',NULL,'?is_array');
        // NApp::Dlog($rights,'$rights');
        if(is_null($rights)) {
            return ($sAdmin ? FALSE : NULL);
        }
        if(get_array_value($rights,'state',0,'is_integer')!=1) {
            return TRUE;
        } elseif($sAdmin) {
            return FALSE;
        }
        $userAccess=AppHelpers::GetBitFromString(get_array_value($rights,'access_type','','is_string'),NApp::GetParam('user_access_type'));
        // NApp::Dlog($userAccess,'$userAccess');
        if(!$userAccess && NApp::GetParam('sadmin')!=1) {
            return TRUE;
        }
        if(strtolower($type)=='all') {
            return $rights;
        }
        // NApp::Dlog(get_array_value($rights,strtolower('d'.$type),NULL,'bool'),'dright');
        return get_array_value($rights,strtolower('d'.$type),NULL,'bool');
    }//END public static function GetDRights

    /**
     * @param \NETopes\Core\App\Params $params
     * @return mixed|null
     * @throws \NETopes\Core\AppException
     */
    public function ExecAndRedirect(Params $params) {
        $redirect=$this->ProcessRedirects($params,$cAction);
        $module=get_array_value($cAction,'module',NULL,'is_notempty_string');
        $method=get_array_value($cAction,'method',NULL,'is_notempty_string');
        if(!strlen($module) || !strlen($method) || !ModulesProvider::ModuleMethodExists($module,$method)) {
            NApp::Wlog($cAction,'Invalid current redirect data!');
            return NULL;
        }
        $params->remove('redirects');
        $result=$this->ExecRedirect($cAction,$params);
        if(strlen($redirect)) {
            $this->AddJsScript($redirect);
        }
        return $result;
    }//END public function ExecAndRedirect

    /**
     * description
     *
     * @param string                              $method
     * @param \NETopes\Core\App\Params|array|null $params Parameters
     * @param null|string                         $dynamicTargetId
     * @param bool                                $resetSessionParams
     * @param mixed                               $beforeCall
     * @param string|null                         $callerClass
     * @return mixed return description
     * @throws \NETopes\Core\AppException
     */
    public function Exec(string $method,$params=NULL,?string $dynamicTargetId=NULL,bool $resetSessionParams=FALSE,$beforeCall=NULL,?string $callerClass=NULL) {
        try {
            $reflection=new ReflectionMethod($this,$method);
            if(!$reflection->isPublic()) {
                throw new AppException('Non public module method: ['.$this->name.'::'.$method.']!');
            }
        } catch(ReflectionException $re) {
            throw AppException::GetInstance($re);
        }
        $oParams=($params instanceof Params) ? $params : new Params($params);
        if($callerClass && $this->class!==$callerClass) {
            $oParams->set('_caller_class',$callerClass);
        }
        $beforeCallParams=$beforeCall instanceof Params ? $beforeCall : new Params($beforeCall);
        $beforeCallParams->merge($oParams->toArray());
        if(!$this->_BeforeExec($beforeCallParams)) {
            return FALSE;
        }
        if(is_string($dynamicTargetId) && strlen(trim($dynamicTargetId))) {
            NApp::Ajax()->SetDynamicTarget($dynamicTargetId);
        }
        if($resetSessionParams) {
            $this->SetSessionParamValue(NULL,$method);
        }
        return $this->$method($oParams);
    }//END public function Exec

    /**
     * Add JavaScript code to execution queue
     *
     * @param string     $script
     * @param bool       $fromFile
     * @param array|null $jsParams
     * @param bool       $dynamic
     * @return void
     */
    public function AddJsScript(string $script,bool $fromFile=FALSE,?array $jsParams=NULL,bool $dynamic=FALSE): void {
        AppHelpers::AddJsScript($script,$fromFile,$jsParams,$dynamic);
    }//END public function AddJsScript

    /**
     * Get module current method
     *
     * @return string
     */
    public function GetCurrentMethod(): string {
        return call_back_trace(2);
    }//END public function GetCurrentMethod

    /**
     * description
     *
     * @param null        $defaultValue
     * @param string|null $method
     * @param string|null $pHash
     * @param null        $key
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function GetSessionParamValue($defaultValue=NULL,?string $method=NULL,?string $pHash=NULL,$key=NULL) {
        if($key) {
            $result=NApp::GetParam($key);
            return ($result ? $result : $defaultValue);
        }//if($key)
        $lMethod=$method ? $method : call_back_trace();
        $pageHash=$pHash ? $pHash : $this->phash;
        $result=NApp::GetParam($this->name.$lMethod.$pageHash);
        return ($result ? $result : $defaultValue);
    }//END public function GetSessionParamValue

    /**
     * description
     *
     * @param             $value
     * @param string|null $method
     * @param string|null $pHash
     * @param null        $key
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function SetSessionParamValue($value,?string $method=NULL,?string $pHash=NULL,$key=NULL) {
        if($key) {
            NApp::SetParam($key,$value);
            return;
        }//if($key)
        $lmethod=$method ? $method : call_back_trace();
        $pagehash=$pHash ? $pHash : $this->phash;
        NApp::SetParam($this->name.$lmethod.$pagehash,$value);
    }//END public function SetSessionParamValue

    /**
     * description
     *
     * @param \NETopes\Core\App\Params|array|null $params Parameters
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function SetFilter($params=NULL) {
        if(!is_object($params)) {
            $params=new Params($params);
        }
        $method=$params->safeGet('method','Listing','is_notempty_string');
        $pagehash=$params->safeGet('phash','','is_string');
        $target=$params->safeGet('target','','is_string');
        $lxparam=$this->GetSessionParamValue([],$method,$pagehash);
        $params->remove('method');
        $params->remove('target');
        $params->remove('phash');
        if(is_array($lxparam)) {
            foreach($params as $k=>$v) {
                switch($k) {
                    case 'qsearch':
                        $lxparam[$k]=$v==Translate::Get('qsearch_label') ? '' : $v;
                        break;
                    default:
                        $lxparam[$k]=$v;
                        break;
                }//END switch
            }//END foreach
            $lxparam['current_page']=1;
            $this->SetSessionParamValue($lxparam,$method,$pagehash);
        } else {
            $lparams['current_page']=1;
            $this->SetSessionParamValue($lparams,$method,$pagehash);
        }//if(is_array($lxparamp))
        $this->Exec($method,['target'=>$target,'phash'=>$pagehash]);
    }//END public function SetFilter

    /**
     * description
     *
     * @param null                                $firstRow
     * @param null                                $lastRow
     * @param null                                $currentPage
     * @param \NETopes\Core\App\Params|array|null $params Parameters
     * @return array
     * @throws \NETopes\Core\AppException
     */
    public function GetPaginationParams(&$firstRow=NULL,&$lastRow=NULL,$currentPage=NULL,$params=NULL) {
        if(!is_object($params)) {
            $params=new Params($params);
        }
        $cpage=is_numeric($currentPage) ? $currentPage : $params->safeGet('current_page',1,'is_integer');
        $firstRow=$params->safeGet('first_row',0,'is_integer');
        return ControlsHelpers::GetPaginationParams($firstRow,$lastRow,$cpage);
    }//END public function GetPaginationParams

    /**
     * description
     *
     * @param \NETopes\Core\App\Params|array|null $params Parameters
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function CloseForm($params=NULL) {
        if(!is_object($params)) {
            $params=new Params($params);
        }
        $targetid=$params->safeGet('targetid','','is_string');
        if($params->safeGet('modal',TRUE,'bool')) {
            $callback=$params->safeGet('callback','','is_string');
            if($callback) {
                GibberishAES::enc($callback,'cmf');
            }
            $dynamic=intval($params->safeGet('dynamic',TRUE,'bool'));
            NApp::Ajax()->ExecuteJs("CloseModalForm('{$callback}','{$targetid}','{$dynamic}');");
        } elseif(strlen($targetid)) {
            NApp::Ajax()->ExecuteJs("$(document).off('keypress');");
            NApp::Ajax()->hide($targetid);
        }//if($params->safeGet('modal',TRUE,'bool'))
    }//END public function CloseForm

    /**
     * @param string $module Module full qualified name
     * @return array|null Returns and array containing parent class name, full class name, path
     *                       or NULL if invalid module is provided
     * @throws \ReflectionException
     */
    public static function GetParents($module) {
        if(!is_string($module) || !strlen($module) || !class_exists($module)) {
            return NULL;
        }
        $result=[];
        $mParentClass=get_parent_class($module);
        while($mParentClass) {
            $mParentArray=explode('\\',trim($mParentClass,'\\'));
            $mParent=array_pop($mParentArray);
            if($mParent=='Module') {
                break;
            }
            $rc=new ReflectionClass($mParentClass);
            $mPath=dirname($rc->getFileName());
            $result[]=['name'=>$mParent,'class'=>$mParentClass,'path'=>$mPath];
            $mParentClass=get_parent_class($mParentClass);
        }//END while
        return $result;
    }//END public static function GetParents

    /**
     * Gets the view full file name (including absolute path)
     *
     * @param string      $name      View name (without extension)
     * @param string|null $subDir    View sub-directory or empty/NULL for none
     * @param string|null $themeDir  View theme sub-directory
     *                               (if empty/NULL) application configuration will be used
     * @param string|null $ext       File extension
     * @return string Returns view full name (including absolute path and extension)
     * @throws \NETopes\Core\AppException
     * @throws \ReflectionException
     */
    private function ViewFileProvider(string $name,?string $subDir=NULL,?string $themeDir=NULL,?string $ext=NULL) {
        $fName=(strlen($subDir) ? '/'.trim($subDir,'/') : '').'/'.$name.(strlen($ext) ? '.'.trim($ext,'.') : $this->viewsExtension);
        // Get theme directory and theme views base directory
        $appTheme=strtolower(AppConfig::GetValue('app_theme'));
        $viewsDefDir=AppConfig::GetValue('app_default_views_dir');
        $themeModulesViewsPath=AppConfig::GetValue('app_theme_modules_views_path');
        $defDir=(is_string($viewsDefDir) ? (strlen(trim($viewsDefDir,'/')) ? '/'.trim($viewsDefDir,'/') : '') : '/_default');
        $themeDir=strlen($themeDir) ? $themeDir : (is_string($appTheme) && strlen($appTheme) ? $appTheme : NULL);
        // NApp::Dlog($fName,'$fName');
        // NApp::Dlog($themeDir,'$themeDir');
        if(isset($themeDir) && is_string($themeModulesViewsPath) && strlen($themeModulesViewsPath)) {
            if(!file_exists(NApp::$appPath.'/'.trim($themeModulesViewsPath,'/\\'))) {
                throw new AppException('Invalid views theme path!');
            }
            $baseDir=NApp::$appPath.'/'.trim($themeModulesViewsPath,'/\\').DIRECTORY_SEPARATOR;
            // NApp::Dlog($baseDir,'$baseDir');
            // NApp::Dlog($this->class,'$this->class');
            $mPathArr=explode('\\',trim($this->class,'\\'));
            array_shift($mPathArr);
            array_shift($mPathArr);
            array_pop($mPathArr);
            $mPath=implode(DIRECTORY_SEPARATOR,$mPathArr);
            // NApp::Dlog($mPath,'$mPath');
            // NApp::Dlog($this->class,'$this->class');
            $parents=self::GetParents($this->class);
            // NApp::Dlog($parents,'$parents');
            // For themed views stored outside "modules" directory (with fallback on "modules" directory)
            if($baseDir) {
                if(file_exists($baseDir.$mPath.$fName)) {
                    return $baseDir.$mPath.$fName;
                }
                if($parents) {
                    foreach($parents as $parent) {
                        $p_path=get_array_value($parent,'path','','is_string');
                        if(file_exists($baseDir.$p_path.$fName)) {
                            return $baseDir.$parent.$fName;
                        }
                    }//END foreach
                }//if($parents)
            }//if($baseDir)
            // Get theme view file
            $mFullPath=NApp::$appPath.DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.$mPath;
            if($themeDir && !$baseDir) {
                // NApp::Dlog($mFullPath.'/'.$themeDir.$fName,'Check[T.C]');
                if(file_exists($mFullPath.'/'.$themeDir.$fName)) {
                    return $mFullPath.'/'.$themeDir.$fName;
                }
            }//if($themeDir && !$baseDir)
            // Get default theme view file
            // NApp::Dlog($mFullPath.$defDir.$fName,'Check[D.C]');
            if(file_exists($mFullPath.$defDir.$fName)) {
                return $mFullPath.$defDir.$fName;
            }
            // Get view from parent classes hierarchy
            if($parents) {
                foreach($parents as $parent) {
                    // NApp::Dlog($parent,'$parent');
                    $pPath=get_array_value($parent,'path','','is_string');
                    $pFullPath=NApp::$appPath.DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.$pPath;
                    // Get from parent theme dir
                    if($themeDir && !$baseDir) {
                        // NApp::Dlog($pFullPath.'/'.$themeDir.$fName,'Check[T.P]');
                        if(file_exists($pFullPath.'/'.$themeDir.$fName)) {
                            return $pFullPath.'/'.$themeDir.$fName;
                        }
                    }//if($themeDir && !$baseDir)
                    // Get view from current parent class path
                    // NApp::Dlog($pFullPath.$defDir.$fName,'Check[D.P]');
                    if(file_exists($pFullPath.$defDir.$fName)) {
                        return $pFullPath.$defDir.$fName;
                    }
                }//END foreach
            }//if($parents)
        }//if(isset($themeDir) && is_string($themeModulesViewsPath) && strlen($themeModulesViewsPath))
        $rc=new ReflectionClass($this);
        $mFullPath=dirname($rc->getFileName());
        // NApp::Dlog($mFullPath,'$mFullPath');
        if($themeDir) {
            // NApp::Dlog($mFullPath.'/'.$themeDir.$fName,'Check[T.C]');
            if(file_exists($mFullPath.'/'.$themeDir.$fName)) {
                return $mFullPath.'/'.$themeDir.$fName;
            }
        }//if($themeDir)
        // Get default theme view file
        // NApp::Dlog($mFullPath.$defDir.$fName,'Check[D.C]');
        if(file_exists($mFullPath.$defDir.$fName)) {
            return $mFullPath.$defDir.$fName;
        }
        $parents=self::GetParents($this->class);
        // NApp::Dlog($parents,'$parents');
        // Get view from parent classes hierarchy
        if($parents) {
            foreach($parents as $parent) {
                // NApp::Dlog($parent,'$parent');
                $pFullPath=get_array_value($parent,'path','','is_string');
                // Get from parent theme dir
                if($themeDir) {
                    // NApp::Dlog($pFullPath.'/'.$themeDir.$fName,'Check[T.P]');
                    if(file_exists($pFullPath.'/'.$themeDir.$fName)) {
                        return $pFullPath.'/'.$themeDir.$fName;
                    }
                }//if($themeDir)
                // Get view from current parent class path
                // NApp::Dlog($pFullPath.$defDir.$fName,'Check[D.P]');
                if(file_exists($pFullPath.$defDir.$fName)) {
                    return $pFullPath.$defDir.$fName;
                }
            }//END foreach
        }//if($parents)
        throw new AppException('View file ['.$fName.'] for module ['.$this->class.'] not found!');
    }//private function ViewFileProvider

    /**
     * Gets the view full file name (including absolute path)
     *
     * @param string      $name      View name (without extension)
     * @param string|null $subDir    View sub-directory or empty/NULL for none
     * @param string|null $themeDir  View theme sub-directory
     *                               (if empty/NULL) application configuration will be used
     * @return string Returns view full name (including absolute path and extension)
     * @throws \NETopes\Core\AppException
     */
    public function GetViewFile(string $name,?string $subDir=NULL,?string $themeDir=NULL) {
        // \NETopes\Core\Logging\Logger::StartTimeTrack('MGetViewFile');
        try {
            $result=$this->ViewFileProvider($name,$subDir,$themeDir);
        } catch(ReflectionException $re) {
            throw AppException::GetInstance($re,'reflection',0);
        }//END try
        // NApp::Dlog(number_format(\NETopes\Core\Logging\Logger::ShowTimeTrack('MGetViewFile'),3,'.','').' sec.','GetViewFile::'.$name);
        // NApp::Dlog($result,'GetViewFile::'.$name);
        return $result;
    }//END public function GetViewFile

    /**
     * Gets a resource full file name (including absolute path)
     *
     * @param string      $name      View name (without extension)
     * @param string      $ext       File extension
     * @param string|null $subDir    View sub-directory or empty/NULL for none
     * @param string|null $themeDir  View theme sub-directory
     *                               (if empty/NULL) application configuration will be used
     * @return string Returns view full name (including absolute path and extension)
     * @throws \NETopes\Core\AppException
     */
    public function GetResourceFile(string $name,string $ext,?string $subDir=NULL,?string $themeDir=NULL) {
        // \NETopes\Core\Logging\Logger::StartTimeTrack('MGetResourceFile');
        try {
            $result=$this->ViewFileProvider($name,$subDir,$themeDir,$ext);
        } catch(ReflectionException $re) {
            throw AppException::GetInstance($re,'reflection',0);
        }//END try
        // NApp::Dlog(number_format(\NETopes\Core\Logging\Logger::ShowTimeTrack('MGetResourceFile'),3,'.','').' sec.','GetResourceFile::'.$name);
        // NApp::Dlog($result,'GetResourceFile::'.$name);
        return $result;
    }//END public function GetResourceFile

    /**
     * @param      $data
     * @param null $label
     * @param bool $reset
     * @param null $method
     */
    protected function SetDebugData($data,$label=NULL,$reset=FALSE,$method=NULL) {
        $current_method=$method ? $method : call_back_trace();
        if($reset || !is_array($this->debugData)) {
            $this->debugData=[];
        }
        if(!isset($this->debugData[$current_method]) || !is_array($this->debugData[$current_method])) {
            $this->debugData[$current_method]=[];
        }
        if(is_string($label) && strlen($label)) {
            $this->debugData[$current_method][$label]=$data;
        } else {
            $this->debugData[$current_method][]=$data;
        }//if(is_string($label) && strlen($label))
    }//END protected function SetDebugData

    /**
     * @param      $label
     * @param null $method
     * @return null
     */
    protected function GetDebugData($label,$method=NULL) {
        if(!is_string($label) && !is_numeric($label)) {
            return NULL;
        }
        $current_method=$method ? $method : call_back_trace();
        if(!is_array($this->debugData) || !isset($this->debugData[$current_method][$label])) {
            return NULL;
        }
        return $this->debugData[$current_method][$label];
    }//END protected function GetDebugData

    /**
     * @param \NETopes\Core\App\Params|array|null $params Parameters
     * @return array|mixed|null
     * @throws \NETopes\Core\AppException
     */
    public function GetCallDebugData($params=NULL) {
        $clear=$params->safeGet('clear',FALSE,'bool');
        $method=$params->safeGet('method',NULL,'is_string');
        $result=NULL;
        if(is_null($method)) {
            $result=$this->debugData;
            if($clear) {
                $this->debugData=NULL;
            }
        } elseif(is_string($method) && strlen($method) && isset($this->debugData[$method])) {
            $result=$this->debugData[$method];
            if($clear) {
                $this->debugData[$method]=NULL;
            }
        }//if(is_null($method))
        return $result;
    }//END public function GetCallDebugData
}//END class Module