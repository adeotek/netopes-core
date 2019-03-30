<?php
/**
 * Module class file
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use NETopes\Core\Controls\ControlsHelpers;
use GibberishAES;
use NApp;
use ReflectionClass;
use ReflectionException;
use Translate;

/**
 * Class Module
 * All applications modules extend this base class
 * @package  NETopes\Core\App
 * @method ViewDRights()
 * @method ListDRights()
 * @method SearchDRights()
 * @method AddDRights()
 * @method EditDRights()
 * @method DeleteDRights()
 * @method PrintDRights()
 * @method ValidateDRights()
 * @method ExportDRights()
 * @method ImportDRights()
 */
class Module {
	/**
     * Deny view right
     */
    const DRIGHT_VIEW = 'view';
    /**
     * Deny list right
     */
    const DRIGHT_LIST = 'list';
    /**
     * Deny search right
     */
    const DRIGHT_SEARCH = 'search';
    /**
     * Deny add right
     */
    const DRIGHT_ADD = 'add';
    /**
     * Deny edit right
     */
    const DRIGHT_EDIT = 'edit';
    /**
     * Deny delete right
     */
    const DRIGHT_DELETE = 'delete';
    /**
     * Deny print right
     */
    const DRIGHT_PRINT = 'print';
    /**
     * Deny validate right
     */
    const DRIGHT_VALIDATE = 'validate';
    /**
     * Deny export right
     */
    const DRIGHT_EXPORT = 'export';
    /**
     * Deny import right
     */
    const DRIGHT_IMPORT = 'import';
	/**
	 * @var    array Modules instances array
	 * @access private
	 */
	private static $ModuleInstances = [];
	/**
	 * @var    array Module instance debug data
	 */
	protected $debugData = NULL;
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
	public $custom = FALSE;
	/**
	 * @var    bool Page hash (window.name)
	 */
	public $phash = NULL;
    /**
     * Get class name with relative namespace
     * @return string
     * @throws \NETopes\Core\AppException
     */
    public static final function getShortClassName(): string {
	    return trim(str_replace(AppConfig::GetValue('app_root_namespace').'\\'.AppConfig::GetValue('app_modules_namespace_prefix'),'',static::class),'\\');
	}//END public static final function getShortClassName
	/**
	 * Module class initializer
	 * @return void
	 */
	protected function _Init() {
	}//END protected function _Init
    /**
     * Method to be invoked before a standard method call
     * @param \NETopes\Core\App\Params|array|null $params Parameters
     * @return bool  Returns TRUE by default
     * If FALSE is return the call is canceled
     */
	protected function _BeforeExec($params = NULL) {
		return TRUE;
	}//END protected function _BeforeExec
    /**
     * Module class constructor
     * @throws \NETopes\Core\AppException
     * @return void
     */
	protected final function __construct() {
	    $this->viewsExtension = AppConfig::GetValue('app_views_extension');
		$this->_Init();
	}//END protected final function __construct
	/**
	 * Module class method call
	 * @param string $name
	 * @param array  $arguments
	 * @return mixed
	 * @throws \NETopes\Core\AppException
	 */
	public function __call(string $name,$arguments) {
		if(strpos($name,'DRights')===FALSE) { throw new AppException('Undefined module method ['.$name.']!',E_ERROR,1); }
		$method = get_array_value($arguments,0,$this->name,'is_notempty_string');
		$module = get_array_value($arguments,1,get_called_class(),'is_notempty_string');
		return self::GetDRights($module,$method,str_replace('DRights','',$name));
	}//END public function __call
	/**
	 * Module class static method call
	 * @param string $name
	 * @param array  $arguments
	 * @return mixed
	 * @throws \NETopes\Core\AppException
	 */
	public static function __callStatic($name,$arguments) {
		if(strpos($name,'DRights')===FALSE) { throw new AppException('Undefined module method ['.$name.']!',E_ERROR,1); }
		$method = get_array_value($arguments,0,'','is_notempty_string');
		$module = get_array_value($arguments,1,get_called_class(),'is_notempty_string');
		return self::GetDRights($module,$method,str_replace('DRights','',$name));
	}//END public static function __callStatic
	/**
	 * Gets the user rights
	 * @param string $module
	 * @param string $method
	 * @param string $type
	 * @return mixed
     */
	public static function GetDRights(string $module,string $method = '',string $type = 'All') {
		if(NApp::GetParam('sadmin')==1) { return FALSE; }
		// NApp::Dlog($module,'$module');
		// NApp::Dlog($method,'$method');
		// NApp::Dlog($type,'$type');
		if(!strlen($type)) { return NULL; }
		$module = $module==='Module' ? '' : $module;
		$rights = NApp::GetParam('user_rights_revoked');
		$rights = get_array_value($rights,[$module,$method],NULL,'is_array');
		// NApp::Dlog($rights,'$rights');
		if(is_null($rights)) { return NULL; }
		if(get_array_value($rights,'state',0,'is_integer')!=1 || (get_array_value($rights,'sadmin',0,'is_integer')==1 && NApp::GetParam('sadmin')!=1)) { return TRUE; }
		if(strtolower($type)=='all') { return $rights; }
		// NApp::Dlog(get_array_value($rights,strtolower('d'.$type),NULL,'bool'),'dright');
		return get_array_value($rights,strtolower('d'.$type),NULL,'bool');
	}//END public static function GetDRights
	/**
	 * description
	 * @param string $name
	 * @param string $class
	 * @param bool   $custom
	 * @return object
	 */
	public static function GetInstance(string $name,string $class,bool $custom = FALSE) {
		if(!array_key_exists($class,self::$ModuleInstances) || !is_object(self::$ModuleInstances[$class])) {
			self::$ModuleInstances[$class] = new $class();
			self::$ModuleInstances[$class]->name = $name;
			self::$ModuleInstances[$class]->class = $class;
			self::$ModuleInstances[$class]->custom = $custom;
		}//if(!array_key_exists($name,self::$ModuleInstances) || !is_object(self::$ModuleInstances[$name]))
		return self::$ModuleInstances[$class];
	}//END public static function GetInstance
	/**
	 * description
	 * @param string $method
	 * @param \NETopes\Core\App\Params|array|null $params Parameters
     * @param null|string $dynamicTargetId
	 * @param bool   $resetSessionParams
	 * @param mixed  $beforeCall
	 * @return mixed return description
	 * @throws \NETopes\Core\AppException
	 */
	public function Exec(string $method,$params = NULL,?string $dynamicTargetId = NULL,bool $resetSessionParams = FALSE,$beforeCall = NULL) {
		$o_before_call = is_object($beforeCall) ? $beforeCall : new Params($beforeCall);
		if($o_before_call->count() && !$this->_BeforeExec($beforeCall)) { return FALSE; }
		$o_params = is_object($params) ? $params : new Params($params);
		if(is_string($dynamicTargetId) && strlen(trim($dynamicTargetId))) { NApp::Ajax()->SetDynamicTarget($dynamicTargetId); }
		if($resetSessionParams) { $this->SetSessionParamValue(NULL,$method); }
		return $this->$method($o_params);
	}//END public function Exec
    /**
     * Add JavaScript code to execution queue
     * @param string $script
     * @return void
     */
	public function AddJsScript(string $script): void {
		AppHelpers::AddJsScript($script);
	}//END public function AddJsScript
	/**
     * Get module current method
     * @return string
     */
	public function GetCurrentMethod(): string {
		return call_back_trace(2);
	}//END public function GetCurrentMethod
    /**
     * description
     * @param null        $defaultValue
     * @param string|null $method
     * @param string|null $pHash
     * @param null        $key
     * @return mixed
     */
	public function GetSessionParamValue($defaultValue = NULL,?string $method = NULL,?string $pHash = NULL,$key = NULL) {
		if($key) {
			$result = NApp::GetParam($key);
			return ($result ? $result : $defaultValue);
		}//if($key)
		$lmethod = $method ? $method : call_back_trace();
		$pagehash = $pHash ? $pHash : $this->phash;
		$result = NApp::GetParam($this->name.$lmethod.$pagehash);
		return ($result ? $result : $defaultValue);
	}//END public function GetSessionParamValue
    /**
     * description
     * @param             $value
     * @param string|null $method
     * @param string|null $pHash
     * @param null        $key
     * @return void
     */
	public function SetSessionParamValue($value,?string $method = NULL,?string $pHash = NULL,$key = NULL) {
		if($key) {
			NApp::SetParam($key,$value);
			return;
		}//if($key)
		$lmethod = $method ? $method : call_back_trace();
		$pagehash = $pHash ? $pHash : $this->phash;
		NApp::SetParam($this->name.$lmethod.$pagehash,$value);
	}//END public function SetSessionParamValue
    /**
     * description
     * @param \NETopes\Core\App\Params|array|null $params Parameters
     * @return void
     * @throws \NETopes\Core\AppException
     */
	public function SetFilter($params = NULL) {
	    if(!is_object($params)) { $params = new Params($params); }
		$method = $params->safeGet('method','Listing','is_notempty_string');
		$pagehash = $params->safeGet('phash','','is_string');
		$target = $params->safeGet('target','','is_string');
		$lxparam = $this->GetSessionParamValue([],$method,$pagehash);
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
						$lxparam[$k] = $v;
						break;
				}//END switch
			}//END foreach
			$lxparam['current_page'] = 1;
			$this->SetSessionParamValue($lxparam,$method,$pagehash);
		} else {
			$lparams['current_page'] = 1;
			$this->SetSessionParamValue($lparams,$method,$pagehash);
		}//if(is_array($lxparamp))
		$this->Exec($method,array('target'=>$target,'phash'=>$pagehash));
	}//END public function SetFilter
    /**
     * description
     * @param null                     $firstRow
     * @param null                     $lastRow
     * @param null                     $currentPage
     * @param \NETopes\Core\App\Params|array|null $params Parameters
     * @return array
     * @throws \NETopes\Core\AppException
     */
	public function GetPaginationParams(&$firstRow = NULL,&$lastRow = NULL,$currentPage = NULL,$params = NULL) {
	    if(!is_object($params)) { $params = new Params($params); }
		$cpage = is_numeric($currentPage) ? $currentPage : $params->safeGet('current_page',1,'is_integer');
		$firstRow = $params->safeGet('first_row',0,'is_integer');
		return ControlsHelpers::GetPaginationParams($firstRow,$lastRow,$cpage);
	}//END public function GetPaginationParams
	/**
	 * description
	 * @param \NETopes\Core\App\Params|array|null $params Parameters
	 * @return void
	 * @throws \NETopes\Core\AppException
	 */
	public function CloseForm($params = NULL) {
		if(!is_object($params)) { $params = new Params($params); }
		$targetid = $params->safeGet('targetid','','is_string');
		if($params->safeGet('modal',TRUE,'bool')) {
			$callback = $params->safeGet('callback','','is_string');
            if($callback) {
                GibberishAES::enc($callback,'cmf');
            }
			$dynamic = intval($params->safeGet('dynamic',TRUE,'bool'));
			NApp::Ajax()->ExecuteJs("CloseModalForm('{$callback}','{$targetid}','{$dynamic}');");
		} elseif(strlen($targetid)) {
			NApp::Ajax()->ExecuteJs("$(document).off('keypress');");
			NApp::Ajax()->hide($targetid);
		}//if($params->safeGet('modal',TRUE,'bool'))
	}//END public function CloseForm
	/**
     * @param string $module Module full qualified name
     * @return array|null Returns and array containing parent class name, full class name, path
     * or NULL if invalid module is provided
     * @throws \ReflectionException
     */
	public static function GetParents($module) {
		if(!is_string($module) || !strlen($module) || !class_exists($module)) { return NULL; }
		$result = [];
		$mParentClass = get_parent_class($module);
		while($mParentClass) {
		    $mParentArray = explode('\\',trim($mParentClass,'\\'));
			$mParent = array_pop($mParentArray);
			if($mParent=='Module') { break; }
            $rc=new ReflectionClass($mParentClass);
            $mPath = dirname($rc->getFileName());
			$result[] = ['name'=>$mParent,'class'=>$mParentClass,'path'=>$mPath];
			$mParentClass = get_parent_class($mParentClass);
		}//END while
		return $result;
	}//END public static function GetParents
    /**
     * Gets the view full file name (including absolute path)
     * @param  string      $name View name (without extension)
     * @param  string|null $sub_dir View sub-directory or empty/NULL for none
     * @param  string|null $theme_dir View theme sub-directory
     * (if empty/NULL) application configuration will be used
     * @return string Returns view full name (including absolute path and extension)
     * @throws \NETopes\Core\AppException
     * @throws \ReflectionException
     */
	private function ViewFileProvider(string $name,?string $sub_dir = NULL,?string $theme_dir = NULL) {
        $fName = (is_string($sub_dir) && strlen($sub_dir) ? '/'.trim($sub_dir,'/') : '').'/'.$name.$this->viewsExtension;
        // Get theme directory and theme views base directory
        $appTheme = strtolower(AppConfig::GetValue('app_theme'));
		$viewsDefDir = AppConfig::GetValue('app_default_views_dir');
		$themeModulesViewsPath = AppConfig::GetValue('app_theme_modules_views_path');
		$defDir = (is_string($viewsDefDir) ? (strlen(trim($viewsDefDir,'/')) ? '/'.trim($viewsDefDir,'/') : '') : '/_default');
        $themeDir = (is_string($theme_dir) && strlen($theme_dir)) ? $theme_dir : (is_string($appTheme) && strlen($appTheme) ? $appTheme : NULL);
        // NApp::Dlog($fName,'$fName');
		// NApp::Dlog($themeDir,'$themeDir');
        if(isset($themeDir) && is_string($themeModulesViewsPath) && strlen($themeModulesViewsPath)) {
            if(!file_exists(NApp::$appPath.'/'.trim($themeModulesViewsPath,'/\\'))) { throw new AppException('Invalid views theme path!'); }
            $baseDir = NApp::$appPath.'/'.trim($themeModulesViewsPath,'/\\').DIRECTORY_SEPARATOR;
            // NApp::Dlog($baseDir,'$baseDir');
            // NApp::Dlog($this->class,'$this->class');
            $mPathArr = explode('\\',trim($this->class,'\\'));
            array_shift($mPathArr);
            array_shift($mPathArr);
            array_pop($mPathArr);
            $mPath = implode(DIRECTORY_SEPARATOR,$mPathArr);
            // NApp::Dlog($mPath,'$mPath');
            // NApp::Dlog($this->class,'$this->class');
            $parents = self::GetParents($this->class);
            // NApp::Dlog($parents,'$parents');
            // For themed views stored outside "modules" directory (with fallback on "modules" directory)
            if($baseDir) {
                if(file_exists($baseDir.$mPath.$fName)) { return $baseDir.$mPath.$fName; }
                if($parents) {
                    foreach($parents as $parent) {
                        $p_path = get_array_value($parent,'path','','is_string');
                        if(file_exists($baseDir.$p_path.$fName)) { return $baseDir.$parent.$fName; }
                    }//END foreach
                }//if($parents)
            }//if($baseDir)
            // Get theme view file
            $mFullPath = NApp::$appPath.DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.$mPath;
            if($themeDir && !$baseDir) {
                // NApp::Dlog($mFullPath.'/'.$themeDir.$fName,'Check[T.C]');
                if(file_exists($mFullPath.'/'.$themeDir.$fName)) { return $mFullPath.'/'.$themeDir.$fName; }
            }//if($themeDir && !$baseDir)
            // Get default theme view file
            // NApp::Dlog($mFullPath.$defDir.$fName,'Check[D.C]');
            if(file_exists($mFullPath.$defDir.$fName)) { return $mFullPath.$defDir.$fName; }
            // Get view from parent classes hierarchy
            if($parents) {
                foreach($parents as $parent) {
                    // NApp::Dlog($parent,'$parent');
                    $pPath = get_array_value($parent,'path','','is_string');
                    $pFullPath = NApp::$appPath.DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.$pPath;
                    // Get from parent theme dir
                    if($themeDir && !$baseDir) {
                        // NApp::Dlog($pFullPath.'/'.$themeDir.$fName,'Check[T.P]');
                        if(file_exists($pFullPath.'/'.$themeDir.$fName)) { return $pFullPath.'/'.$themeDir.$fName; }
                    }//if($themeDir && !$baseDir)
                    // Get view from current parent class path
                    // NApp::Dlog($pFullPath.$defDir.$fName,'Check[D.P]');
                    if(file_exists($pFullPath.$defDir.$fName)) { return $pFullPath.$defDir.$fName; }
                }//END foreach
            }//if($parents)
        }//if(isset($themeDir) && is_string($themeModulesViewsPath) && strlen($themeModulesViewsPath))
        $rc=new ReflectionClass($this);
        $mFullPath = dirname($rc->getFileName());
        // NApp::Dlog($mFullPath,'$mFullPath');
		if($themeDir) {
			// NApp::Dlog($mFullPath.'/'.$themeDir.$fName,'Check[T.C]');
			if(file_exists($mFullPath.'/'.$themeDir.$fName)) { return $mFullPath.'/'.$themeDir.$fName; }
		}//if($themeDir)
		// Get default theme view file
		// NApp::Dlog($mFullPath.$defDir.$fName,'Check[D.C]');
		if(file_exists($mFullPath.$defDir.$fName)) { return $mFullPath.$defDir.$fName; }
		$parents = self::GetParents($this->class);
        // NApp::Dlog($parents,'$parents');
        // Get view from parent classes hierarchy
		if($parents) {
			foreach($parents as $parent) {
				// NApp::Dlog($parent,'$parent');
				$pFullPath = get_array_value($parent,'path','','is_string');
				// Get from parent theme dir
				if($themeDir) {
					// NApp::Dlog($pFullPath.'/'.$themeDir.$fName,'Check[T.P]');
					if(file_exists($pFullPath.'/'.$themeDir.$fName)) { return $pFullPath.'/'.$themeDir.$fName; }
				}//if($themeDir)
				// Get view from current parent class path
				// NApp::Dlog($pFullPath.$defDir.$fName,'Check[D.P]');
				if(file_exists($pFullPath.$defDir.$fName)) { return $pFullPath.$defDir.$fName; }
			}//END foreach
		}//if($parents)
		throw new AppException('View file ['.$fName.'] for module ['.$this->class.'] not found!');
	}//private function ViewFileProvider
    /**
     * Gets the view full file name (including absolute path)
     * @param  string      $name View name (without extension)
     * @param  string|null $sub_dir View sub-directory or empty/NULL for none
     * @param  string|null $theme_dir View theme sub-directory
     * (if empty/NULL) application configuration will be used
     * @return string Returns view full name (including absolute path and extension)
     * @throws \NETopes\Core\AppException
     */
	public function GetViewFile(string $name,?string $sub_dir = NULL,?string $theme_dir = NULL) {
		// \NETopes\Core\App\Debugger::StartTimeTrack('MGetViewFile');
		try {
		    $result = $this->ViewFileProvider($name,$sub_dir,$theme_dir);
        } catch(ReflectionException $re) {
		    throw AppException::GetInstance($re,'reflection',0);
		}//END try
		// NApp::Dlog(number_format(\NETopes\Core\App\Debugger::ShowTimeTrack('MGetViewFile'),3,'.','').' sec.','GetViewFile::'.$name);
		// NApp::Dlog($result,'GetViewFile::'.$name);
		return $result;
	}//END public function GetViewFile
    /**
     * @param      $data
     * @param null $label
     * @param bool $reset
     * @param null $method
     */
    protected function SetDebugData($data,$label = NULL,$reset = FALSE,$method = NULL) {
		$current_method = $method ? $method : call_back_trace();
		if($reset || !is_array($this->debugData)) { $this->debugData = []; }
		if(!isset($this->debugData[$current_method]) || !is_array($this->debugData[$current_method])) { $this->debugData[$current_method] = []; }
		if(is_string($label) && strlen($label)) {
			$this->debugData[$current_method][$label] = $data;
		} else {
			$this->debugData[$current_method][] = $data;
		}//if(is_string($label) && strlen($label))
	}//END protected function SetDebugData
    /**
     * @param      $label
     * @param null $method
     * @return null
     */
    protected function GetDebugData($label,$method = NULL) {
		if(!is_string($label) && !is_numeric($label)) { return NULL; }
		$current_method = $method ? $method : call_back_trace();
		if(!is_array($this->debugData) || !isset($this->debugData[$current_method][$label])) { return NULL; }
		return $this->debugData[$current_method][$label];
	}//END protected function GetDebugData
    /**
     * @param \NETopes\Core\App\Params|array|null $params Parameters
     * @return array|mixed|null
     * @throws \NETopes\Core\AppException
     */
    public function GetCallDebugData($params = NULL) {
		$clear = $params->safeGet('clear',FALSE,'bool');
		$method = $params->safeGet('method',NULL,'is_string');
		$result = NULL;
		if(is_null($method)) {
			$result = $this->debugData;
			if($clear) { $this->debugData = NULL; }
		} elseif(is_string($method) && strlen($method) && isset($this->debugData[$method])) {
			$result = $this->debugData[$method];
			if($clear) { $this->debugData[$method] = NULL; }
		}//if(is_null($method))
		return $result;
	}//END public function GetCallDebugData
}//END class Module