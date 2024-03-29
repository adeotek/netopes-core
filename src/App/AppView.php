<?php
/**
 * Application BaseView class file
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use NApp;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use NETopes\Core\Controls\BasicForm;
use NETopes\Core\Controls\ControlsHelpers;
use NETopes\Core\Controls\FilterBox;
use NETopes\Core\Controls\TabControl;
use NETopes\Core\Controls\TableView;
use NETopes\Core\Logging\Logger;

/**
 * Application BaseView class
 *
 * @package    NETopes\Core\App
 */
class AppView {
    /**
     * String content constant
     */
    const STRING_CONTENT='string';
    /**
     * Control content constant
     */
    const CONTROL_CONTENT='control';
    /**
     * Control builder constant
     */
    const CONTROL_BUILDER='builder';
    /**
     * File content constant
     */
    const FILE_CONTENT='file';
    /**
     * Object content constant
     */
    const OBJECT_CONTENT='object';
    /**
     * Module content content
     */
    const MODULE_CONTENT='module';
    /**
     * @var string|null View container type
     */
    protected $_containerType=NULL;
    /**
     * @var string|null View theme
     */
    protected $_theme=NULL;
    /**
     * @var bool Debug mode on/off
     */
    protected $_debug=FALSE;
    /**
     * @var string|null View container class
     */
    protected $_containerClass=NULL;
    /**
     * @var string|null View title
     */
    protected $_title=NULL;
    /**
     * @var string View dynamic title tag ID
     */
    protected $_titleTagId='';
    /**
     * @var string|integer|null Modal view width
     */
    protected $_modalWidth=300;
    /**
     * @var string Modal view dynamic target ID
     */
    protected $_targetId='';
    /**
     * @var bool Is modal view
     */
    protected $_isModal=FALSE;
    /**
     * @var bool Auto-generate js script for modal view
     */
    protected $_modalAutoJs=TRUE;
    /**
     * @var string|null Modal view custom close js
     */
    protected $_modalCustomClose=NULL;
    /**
     * @var array View pass trough params
     */
    protected $_params=[];
    /**
     * @var array View actions
     */
    protected $_actions=[];
    /**
     * @var array View content
     */
    protected $_content=[];
    /**
     * @var array Placeholders values
     */
    protected $_placeholders=[];
    /**
     * @var array View JS scripts to be executed on render
     */
    protected $_jsScripts=[];
    /**
     * @var array View pass trough properties
     */
    protected $_passTrough=[];
    /**
     * @var object Parent module object
     */
    public $module=NULL;

    /**
     * Pass trough parameters magic getter
     *
     * @param string $name The name of the property
     * @return mixed Returns the value of the property
     * @throws \NETopes\Core\AppException
     */
    public function __get($name) {
        if(!array_key_exists($name,$this->_passTrough)) {
            throw new AppException('Undefined property ['.$name.']!',E_ERROR,1);
        }
        return $this->_passTrough[$name];
    }//END public function __get

    /**
     * BaseView constructor.
     *
     * @param array       $params Pass trough variables array
     *                            Obtained by calling: get_defined_vars()
     * @param null|object $module
     * @param null|string $containerType
     * @throws \NETopes\Core\AppException
     */
    public function __construct(array $params,$module=NULL,?string $containerType=NULL) {
        $this->_debug=AppConfig::GetValue('debug');
        $this->_params=$params;
        if(is_object($module)) {
            $this->module=$module;
            foreach(get_object_vars($this->module) as $pn=>$pv) {
                $this->_passTrough[$pn]=$pv;
            }
        }//if(is_object($module))
        if(isset($containerType)) {
            $this->_containerType=$containerType;
        }
    }//END public function __construct

    /**
     * @param string|null $type
     * @return void
     */
    public function SetContainerType(?string $type): void {
        $this->_containerType=$type;
    }//END public function SetContainerType

    /**
     * @param string|null $class
     * @return void
     */
    public function SetContainerClass(?string $class): void {
        $this->_containerClass=$class;
    }//END public function SetContainerClass

    /**
     * @return bool
     */
    public function IsModalView(): bool {
        return $this->_isModal;
    }//END public function IsModalView

    /**
     * @param bool $isModal
     * @return void
     */
    public function SetIsModalView(bool $isModal): void {
        $this->_isModal=$isModal;
    }//END public function SetIsModalView

    /**
     * @return bool
     */
    public function IsModalAutoJs(): bool {
        return $this->_modalAutoJs;
    }//END public function IsModalAutoJs

    /**
     * @param bool $modalAutoJs
     * @return void
     */
    public function SetModalAutoJs(bool $modalAutoJs): void {
        $this->_modalAutoJs=$modalAutoJs;
    }//END public function SetModalAutoJs

    /**
     * @return string|null
     */
    public function GetModalCustomClose(): ?string {
        return $this->_modalCustomClose;
    }//END public function GetModalCustomClose

    /**
     * @param string|null $modalCustomClose
     * @return void
     */
    public function SetModalCustomClose(?string $modalCustomClose): void {
        $this->_modalCustomClose=$modalCustomClose;
    }//END public function SetModalCustomClose

    /**
     * @param string $placeholder
     * @param string $value
     * @return void
     */
    public function SetPlaceholderValue(string $placeholder,string $value): void {
        $this->_placeholders[trim($placeholder,'{}')]=$value;
    }//END public function SetPlaceholderValue

    /**
     * @return array
     */
    public function GetPlaceholders(): array {
        return $this->_placeholders;
    }//END public function GetPlaceholdersValues

    /**
     * @return array
     */
    public function GetJsScripts(): array {
        return $this->_jsScripts;
    }//END public function GetJsScripts

    /**
     * @return string|null
     */
    public function GetJsScript(): ?string {
        if(!count($this->_jsScripts)) {
            return NULL;
        }
        $result='';
        foreach($this->_jsScripts as $js) {
            $js=trim($js);
            $result.=(strlen($result) ? "\n" : '').(substr($js,-1)=='}' ? $js : rtrim($js,';').';');
        }//END foreach
        return $result;
    }//END public function GetJsScript

    /**
     * @param string     $script
     * @param bool       $first
     * @param bool       $fromFile
     * @param array|null $jsParams
     * @return void
     */
    public function AddJsScript(string $script,bool $first=FALSE,bool $fromFile=FALSE,?array $jsParams=NULL): void {
        $script=trim($script);
        if($fromFile) {
            if(file_exists($script)) {
                $jsScript=file_get_contents($script);
            } else {
                $jsScript=NULL;
            }
        } else {
            $jsScript=$script;
        }
        if(!strlen($script)) {
            return;
        }
        $jsScript=AppHelpers::ProcessJsScriptParams($jsParams).$jsScript;
        if($first) {
            array_unshift($this->_jsScripts,$jsScript);
        } else {
            $this->_jsScripts[]=$jsScript;
        }//if($first)
    }//END public function AddJsScript

    /**
     * @return void
     */
    public function ClearJsScripts(): void {
        $this->_jsScripts=[];
    }//END public function GetJsScripts

    /**
     * Get module current method
     *
     * @param int|null $backSteps
     * @return string
     */
    public function GetCurrentMethod(?int $backSteps=NULL): string {
        return call_back_trace($backSteps ?? 5);
    }//END public function GetCurrentMethod

    /**
     * @param bool $debug
     * @return void
     */
    public function SetDebug(bool $debug): void {
        $this->_debug=$debug;
    }//END public function SetDebug

    /**
     * @param string|null $theme
     * @return void
     */
    public function SetTheme(?string $theme): void {
        $this->_theme=$theme;
    }//END public function SetTheme

    /**
     * @param string $title
     * @return void
     */
    public function SetTitle(string $title): void {
        $this->_title=$title;
    }//END public function SetTitle

    /**
     * @param string $title
     * @param string $tagId
     * @return void
     */
    public function SetDynamicTitle(string $title,string $tagId): void {
        $this->_title=$title;
        $this->_titleTagId=$tagId;
    }//END public function SetDynamicTitle

    /**
     * @param mixed $width
     * @return void
     */
    public function SetModalWidth($width): void {
        $this->_modalWidth=$width;
    }//END public function SetModalWidth

    /**
     * @param string $targetId
     * @return void
     */
    public function SetTargetId(string $targetId): void {
        $this->_targetId=$targetId;
        $this->_placeholders['TARGETID']=$targetId;
    }//END public function SetTargetId

    /**
     * @return array
     */
    public function GetActions(): array {
        return $this->_actions;
    }//END public function GetActions

    /**
     * @param array $actions
     * @return void
     */
    public function SetActions(array $actions): void {
        $this->_actions=$actions;
    }//END public function SetActions

    /**
     * @param string $action
     * @return void
     */
    public function AddAction(string $action): void {
        $this->_actions[]=$action;
    }//END public function AddAction

    /**
     * @return bool
     */
    public function HasActions(): bool {
        return count($this->_actions)>0;
    }//END public function HasActions

    /**
     * @return bool
     */
    public function HasTitle(): bool {
        return strlen($this->_title)>0;
    }//END public function HasTitle

    /**
     * Set pass-trough parameter value
     *
     * @param string $name
     * @param mixed  $value
     * @return void
     */
    public function SetParam(string $name,$value): void {
        $this->_params[$name]=$value;
    }//END public function SetParam

    /**
     * Remove pass-trough parameter value
     *
     * @param string $name
     * @return void
     */
    public function RemoveParam(string $name): void {
        unset($this->_params[$name]);
    }//END public function SetParam

    /**
     * Clear pass-trough parameters
     *
     * @return void
     */
    public function ClearParams(): void {
        $this->_params=[];
    }//END public function SetParam

    /**
     * @return void
     */
    public function ClearContent(): void {
        $this->_placeholders=[];
        $this->_content=[];
    }//END public function ClearContent

    /**
     * @param array $content
     * @return void
     */
    public function AddContent(array $content): void {
        $tag=get_array_value($content,'tag','','is_string');
        if(strlen($tag)) {
            $this->_placeholders[$tag]=NULL;
        }
        $this->_content[]=$content;
    }//END public function AddContent

    /**
     * @param string     $content
     * @param array|null $extraParams
     * @return void
     */
    public function AddHtmlContent(string $content,?array $extraParams=NULL): void {
        $tag=get_array_value($extraParams,'tag','','is_string');
        if(strlen($tag)) {
            $this->_placeholders[$tag]=NULL;
        }
        $this->_content[]=array_merge($extraParams ?? [],['type'=>self::STRING_CONTENT,'value'=>$content]);
    }//END public function AddHtmlContent

    /**
     * @param string     $file
     * @param array|null $extraParams
     * @return void
     */
    public function AddFileContent(string $file,?array $extraParams=NULL): void {
        $tag=get_array_value($extraParams,'tag','','is_string');
        if(strlen($tag)) {
            $this->_placeholders[$tag]=NULL;
        }
        $this->_content[]=array_merge($extraParams ?? [],['type'=>self::FILE_CONTENT,'value'=>$file]);
    }//END public function AddFileContent

    /**
     * @param object     $object $object
     * @param string     $method
     * @param array|null $extraParams
     * @param array|null $args
     * @return void
     */
    public function AddObjectContent(object $object,string $method,?array $extraParams=NULL,?array $args=NULL): void {
        $tag=get_array_value($extraParams,'tag','','is_string');
        if(strlen($tag)) {
            $this->_placeholders[$tag]=NULL;
        }
        $this->_content[]=array_merge($extraParams ?? [],['type'=>self::OBJECT_CONTENT,'object'=>$object,'method'=>$method,'args'=>$args]);
    }//END public function AddObjectContent

    /**
     * @param string     $module
     * @param string     $method
     * @param null       $params
     * @param array|null $extraParams
     * @return void
     */
    public function AddModuleContent(string $module,string $method,$params=NULL,?array $extraParams=NULL): void {
        $tag=get_array_value($extraParams,'tag','','is_string');
        if(strlen($tag)) {
            $this->_placeholders[$tag]=NULL;
        }
        $this->_content[]=array_merge($extraParams ?? [],['type'=>self::MODULE_CONTENT,'module'=>$module,'method'=>$method,'params'=>$params]);
    }//END public function AddModuleContent

    /**
     * @param string|array $config
     * @param array|null   $extraParams
     * @param string|null  $varName
     * @param array|null   $args
     * @param string|null  $class
     * @return void
     */
    public function AddFilterBox($config,?array $extraParams=NULL,?string $varName=NULL,?array $args=NULL,?string $class=NULL): void {
        $tag=get_array_value($extraParams,'tag','','is_string');
        if(strlen($tag)) {
            $this->_placeholders[$tag]=NULL;
        }
        $this->_content[]=array_merge($extraParams ?? [],['type'=>self::CONTROL_CONTENT,'value'=>$config,'class'=>$class ?? FilterBox::class,'var_name'=>$varName,'args'=>$args]);
    }//END public function AddFilterBox

    /**
     * @param string|array $config
     * @param array|null   $extraParams
     * @param string|null  $varName
     * @param array|null   $args
     * @param string|null  $class
     * @return void
     */
    public function AddTableView($config,?array $extraParams=NULL,?string $varName=NULL,?array $args=NULL,?string $class=NULL): void {
        $tag=get_array_value($extraParams,'tag','','is_string');
        if(strlen($tag)) {
            $this->_placeholders[$tag]=NULL;
        }
        $this->_content[]=array_merge($extraParams ?? [],['type'=>self::CONTROL_CONTENT,'value'=>$config,'class'=>$class ?? TableView::class,'var_name'=>$varName,'args'=>$args]);
    }//END public function AddTableView

    /**
     * @param string|array $config
     * @param array|null   $extraParams
     * @param string|null  $varName
     * @param string|null  $class
     * @return void
     */
    public function AddBasicForm($config,?array $extraParams=NULL,?string $varName=NULL,?string $class=NULL): void {
        $tag=get_array_value($extraParams,'tag','','is_string');
        if(strlen($tag)) {
            $this->_placeholders[$tag]=NULL;
        }
        $this->_content[]=array_merge($extraParams ?? [],['type'=>self::CONTROL_CONTENT,'value'=>$config,'class'=>$class ?? BasicForm::class,'var_name'=>$varName]);
    }//END public function AddBasicForm

    /**
     * @param string|array $config
     * @param array|null   $extraParams
     * @param string|null  $varName
     * @param string|null  $class
     * @return void
     */
    public function AddTabControl($config,?array $extraParams=NULL,?string $varName=NULL,?string $class=NULL): void {
        $tag=get_array_value($extraParams,'tag','','is_string');
        if(strlen($tag)) {
            $this->_placeholders[$tag]=NULL;
        }
        $this->_content[]=array_merge($extraParams ?? [],['type'=>self::CONTROL_CONTENT,'value'=>$config,'class'=>$class ?? TabControl::class,'var_name'=>$varName]);
    }//END public function AddTabControl

    /**
     * @param string|array $config
     * @param string       $controlClass
     * @param array|null   $extraParams
     * @param string|null  $varName
     * @param array|null   $args
     * @return void
     */
    public function AddControlContent($config,string $controlClass,?array $extraParams=NULL,?string $varName=NULL,?array $args=NULL): void {
        $tag=get_array_value($extraParams,'tag','','is_string');
        if(strlen($tag)) {
            $this->_placeholders[$tag]=NULL;
        }
        $this->_content[]=array_merge($extraParams ?? [],['type'=>self::CONTROL_CONTENT,'value'=>$config,'class'=>$controlClass,'var_name'=>$varName,'args'=>$args]);
    }//END public function AddControlContent

    /**
     * @param string|object $builder
     * @param string        $controlClass
     * @param array|null    $extraParams
     * @param string|null   $varName
     * @param array|null    $args
     * @return void
     */
    public function AddControlBuilderContent($builder,string $controlClass,?array $extraParams=NULL,?string $varName=NULL,?array $args=NULL): void {
        $tag=get_array_value($extraParams,'tag','','is_string');
        if(strlen($tag)) {
            $this->_placeholders[$tag]=NULL;
        }
        $this->_content[]=array_merge($extraParams ?? [],['type'=>self::CONTROL_BUILDER,'value'=>$builder,'class'=>$controlClass,'var_name'=>$varName,'args'=>$args]);
    }//END public function AddControlBuilderContent

    /**
     * @param string|null $viewFile
     * @param string|null $varName
     * @param array|null  $extraPasstroughParams
     * @return mixed|null
     */
    public function GetViewVariable(?string $viewFile,?string $varName=NULL,?array $extraPasstroughParams=NULL) {
        if(!strlen($viewFile) || !file_exists($viewFile) || $varName=='') {
            return NULL;
        }
        return $this->GetVariableFromFile($viewFile,$extraPasstroughParams,$varName);
    }//END public function GetViewVariable

    /**
     * @param array      $_c_config Control configuration array
     * @param string     $_c_class  Control class fully qualified name
     * @param array|null $args
     * @param array|null $extra_ctrl_params
     * @return string
     */
    protected function GetControlContent(array $_c_config,string $_c_class,?array $args=NULL,?array $extra_ctrl_params=NULL): string {
        if(is_array($extra_ctrl_params) && count($extra_ctrl_params)) {
            $_c_config=array_merge($_c_config,$extra_ctrl_params);
        }//if(is_array($extra_ctrl_params) && count($extra_ctrl_params))
        $_control=new $_c_class($_c_config);
        if(is_array($args) && count($args)) {
            $result=$_control->Show(...$args);
        } else {
            $result=$_control->Show();
        }//if(is_array($args) && count($args))
        if(method_exists($_control,'GetJsScript')) {
            $jsScript=$_control->GetJsScript();
            if(strlen(trim($jsScript))) {
                $this->AddJsScript($jsScript);
            }
        }
        if(is_null($result)) {
            NApp::Elog($_control,'Invalid AppView control');
            return '';
        }
        return $result;
    }//END protected function GetControlContentFromFile

    /**
     * @param string      $_v_file File full name (including absolute path)
     * @param array|null  $params
     * @param string|null $_var_name
     * @return mixed|null
     * @throws \NETopes\Core\AppException
     */
    protected function GetVariableFromFile(string $_v_file,?array $params=NULL,?string $_var_name=NULL) {
        $_var_name=$_var_name ?? 'ctrl_params';
        $passTroughParams=array_merge($this->_params,$params ?? []);
        if(count($passTroughParams)) {
            extract($passTroughParams);
        }
        require($_v_file);
        if(!isset($$_var_name)) {
            return NULL;
        }
        return $$_var_name;
    }//END protected function GetVariableFromFile

    /**
     * @param string      $_v_file  File full name (including absolute path)
     * @param string      $_c_class Control class fully qualified name
     * @param array|null  $args
     * @param array|null  $params
     * @param string|null $_var_name
     * @param array|null  $extra_ctrl_params
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetControlContentFromFile(string $_v_file,string $_c_class,?array $args=NULL,?array $params=NULL,?string $_var_name=NULL,?array $extra_ctrl_params=NULL): string {
        $ctrl_params=$this->GetVariableFromFile($_v_file,$params,$_var_name);
        if(!is_array($ctrl_params)) {
            throw new AppException('Undefined control parameters variable [$ctrl_params:'.$_v_file.']!');
        }
        return $this->GetControlContent($ctrl_params,$_c_class,$args,$extra_ctrl_params);
    }//END protected function GetControlContentFromFile

    /**
     * @param string      $_v_file  File full name (including absolute path)
     * @param string      $_c_class Control class fully qualified name
     * @param array|null  $args
     * @param array|null  $params
     * @param string|null $_var_name
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetControlBuilderFromFile(string $_v_file,string $_c_class,?array $args=NULL,?array $params=NULL,?string $_var_name=NULL): string {
        $_var_name=$_var_name ?? 'ctrl_builder';
        $passTroughParams=array_merge($this->_params,$params ?? []);
        if(count($passTroughParams)) {
            extract($passTroughParams);
        }
        require($_v_file);
        if(!isset($$_var_name) || !is_object($$_var_name)) {
            throw new AppException('Undefined control builder variable [$ctrl_builder:'.$_v_file.']!');
        }
        return $this->GetControlContent($$_var_name->GetConfig(),$_c_class,$args);
    }//END protected function GetControlBuilderFromFile

    /**
     * @param string     $_v_file
     * @param array|null $params
     * @return string
     */
    protected function GetFileContent(string $_v_file,?array $params=NULL): string {
        $passTroughParams=array_merge($this->_params,$params ?? []);
        if(count($passTroughParams)) {
            extract($passTroughParams);
        }
        ob_start();
        require($_v_file);
        $result=ob_get_clean();
        return $result;
    }//END protected function GetFileContent

    /**
     * @param string                              $module
     * @param string                              $method
     * @param \NETopes\Core\App\Params|array|null $params
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetModuleContent(string $module,string $method,$params=NULL): string {
        ob_start();
        ModulesProvider::Exec($module,$method,$params);
        $result=ob_get_clean();
        return $result;
    }//END protected function GetModuleContent

    /**
     * @param string $containerType
     * @param array  $tags
     * @param bool   $hasActions
     * @param bool   $hasTitle
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function GetContainer(?string $containerType,array $tags=[],bool $hasActions=FALSE,bool $hasTitle=FALSE): ?string {
        if($containerType===NULL) {
            return NULL;
        }
        $containerMethod='Get'.(strlen($containerType) ? ucfirst($containerType) : 'Default').'Container';
        if(strlen($this->_theme)) {
            $themeObj=NApp::GetTheme($this->_theme);
        } else {
            $themeObj=NApp::$theme;
            if(is_null($themeObj)) {
                $themeObj=NApp::GetTheme();
            }
        }//if(strlen($this->theme))
        if(!is_object($themeObj)) {
            if($this->_debug) {
                NApp::Wlog('Invalid view object ['.$this->_theme.']!');
            }
            return NULL;
        }//if(!is_object($themeObj))
        if(!method_exists($themeObj,$containerMethod)) {
            if($this->_debug) {
                NApp::Wlog('View container method ['.$containerMethod.'] not found!');
            }
            return NULL;
        }//if(!method_exists($themeObj,$containerMethod))
        ob_start();
        $themeObj->$containerMethod(new Params($tags),$hasActions,$hasTitle);
        $container=ob_get_clean();
        return $container;
    }//END protected function GetContainer

    /**
     * @param string      $content
     * @param string      $containerType
     * @param null|string $targetId
     * @param string|null $containerClass
     * @param null|string $tag
     * @param string|null $title
     * @param array|null  $actions
     * @return bool
     * @throws \NETopes\Core\AppException
     */
    protected function ProcessSubContainer(?string &$content,string $containerType,?string $targetId=NULL,?string $containerClass=NULL,?string $tag=NULL,?string $title=NULL,?array $actions=NULL): bool {
        $tags=[];
        if(strlen($targetId)) {
            $tags['TARGETID']=$targetId;
        }
        if(strlen($containerClass)) {
            $tags['CSSCLASS']=$containerClass;
        }
        if(strlen($tag)) {
            $tags[$tag]=NULL;
        }
        $container=$this->GetContainer($containerType,$tags,(is_array($actions) && count($actions)),(bool)strlen($title));
        if(!strlen($container)) {
            return FALSE;
        }
        if(strlen($targetId)) {
            if(strpos($container,'{!TARGETID!}')===FALSE) {
                if($this->_debug) {
                    NApp::Dlog('{!TARGETID!} placeholder is missing for view container ['.$containerType.']!',NULL,[],[Logger::WEB_CONSOLE_ADAPTER]);
                }
            } else {
                $container=str_replace('{!TARGETID!}',$targetId,$container);
            }//if(strpos($container,'{!TARGETID!}')===FALSE)
        }//if(strlen($targetId))
        if(strlen(trim($containerClass))) {
            if(strpos($container,'{!CSSCLASS!}')===FALSE) {
                if($this->_debug) {
                    NApp::Dlog('{!CSSCLASS!} placeholder is missing for view container ['.$containerType.']!',NULL,[],[Logger::WEB_CONSOLE_ADAPTER]);
                }
            } else {
                $container=str_replace('{!CSSCLASS!}',' '.trim($containerClass),$container);
            }//if(strpos($container,'{!CSSCLASS!}')===FALSE)
        }//if(strlen(trim($containerClass)))
        if(strlen($title)) {
            if(strpos($container,'{!TITLE!}')===FALSE) {
                if($this->_debug) {
                    NApp::Dlog('{!TITLE!} placeholder is missing for view container ['.$containerType.']!',NULL,[],[Logger::WEB_CONSOLE_ADAPTER]);
                }
            } else {
                $container=str_replace('{!TITLE!}',' '.$title,$container);
            }//if(strpos($container,'{!TITLE!}')===FALSE)
        }//if(strlen($title))
        if(is_array($actions) && count($actions)) {
            if(strpos($container,'{!ACTIONS!}')===FALSE) {
                if($this->_debug) {
                    NApp::Dlog('{!ACTIONS!} placeholder is missing for view container ['.$containerType.']!',NULL,[],[Logger::WEB_CONSOLE_ADAPTER]);
                }
            } else {
                $container=str_replace('{!ACTIONS!}',implode("\n",$actions),$container);
            }//if(strpos($container,'{!TITLE!}')===FALSE)
        }//if(is_array($actions) && count($actions))
        if(strlen($tag)) {
            $placeholder='{!'.trim($tag,'{}').'!}';
            if(strpos($container,$placeholder)===FALSE) {
                if($this->_debug) {
                    NApp::Dlog($placeholder.' placeholder is missing for view container ['.$containerType.']!',NULL,[],[Logger::WEB_CONSOLE_ADAPTER]);
                }
                return FALSE;
            }//if(strpos($container,$placeholder)===FALSE)
            $content=str_replace($placeholder,$content,$container);
            $content=$this->ReplaceEmptyPlaceholders($content);
            return TRUE;
        }//if(strlen($tag))
        if(strpos($container,'{!CONTENT!}')===FALSE) {
            if($this->_debug) {
                NApp::Dlog('{!CONTENT!} placeholder is missing for view container ['.$containerType.']!',NULL,[],[Logger::WEB_CONSOLE_ADAPTER]);
            }
            return FALSE;
        }//if(strpos($container,'{!CONTENT!}')===FALSE)
        $content=str_replace('{!CONTENT!}',$content,$container);
        $content=$this->ReplaceEmptyPlaceholders($content);
        return FALSE;
    }//END protected function ProcessSubContainer

    /**
     * Render view content
     *
     * @param bool $return If TRUE view content is returned as string, else is outputted
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    public function Render(bool $return=FALSE): ?string {
        $content='';
        $mainContainer=$this->GetContainer($this->_containerType,$this->_placeholders,$this->HasActions(),$this->HasTitle());
        foreach($this->_content as $k=>$c) {
            $type=get_array_value($c,'type','','is_string');
            $cContainerType=get_array_value($c,'container_type',NULL,'?is_string');
            $cContainerId=get_array_value($c,'container_id',NULL,'?is_string');
            $cContainerClass=get_array_value($c,'container_class',NULL,'?is_string');
            $cContainerTitle=get_array_value($c,'title',NULL,'?is_string');
            $cContainerActions=get_array_value($c,'actions',NULL,'?is_array');
            $cTag=get_array_value($c,'tag',NULL,'?is_string');
            $cContent='';
            switch($type) {
                case self::CONTROL_CONTENT:
                    $class=get_array_value($c,'class','','is_string');
                    if(!strlen($class)) {
                        if($this->_debug) {
                            NApp::Wlog('Invalid content class [control:index:'.$k.']!');
                        }
                        continue 2;
                    }//if(!strlen($class))
                    $args=get_array_value($c,'args',NULL,'?is_array');
                    $config=get_array_value($c,'value',NULL,'isset');

                    if(is_array($config)) {
                        $extraCtrlParams=get_array_value($c,'extra_ctrl_params',NULL,'?is_array');
                        $cContent=$this->GetControlContent($config,$class,$args,$extraCtrlParams);
                    } elseif(is_string($config) && strlen($config)) {
                        $customParams=get_array_value($c,'params',NULL,'?is_array');
                        $varName=get_array_value($c,'var_name',NULL,'?is_string');
                        $extraCtrlParams=get_array_value($c,'extra_ctrl_params',NULL,'?is_array');
                        $cContent=$this->GetControlContentFromFile($config,$class,$args,$customParams,$varName,$extraCtrlParams);
                    } else {
                        if($this->_debug) {
                            NApp::Wlog('Invalid content value [control:index:'.$k.']!');
                        }
                        continue 2;
                    }//if(is_array($config))
                    break;
                case self::CONTROL_BUILDER:
                    $class=get_array_value($c,'class','','is_string');
                    if(!strlen($class)) {
                        if($this->_debug) {
                            NApp::Wlog('Invalid content class [control:index:'.$k.']!');
                        }
                        continue 2;
                    }//if(!strlen($class))
                    $args=get_array_value($c,'args',NULL,'?is_array');
                    $builder=get_array_value($c,'value',NULL,'isset');
                    if(is_object($builder)) {
                        $cContent=$this->GetControlContent($builder->GetConfig(),$class,$args);
                    } elseif(is_string($builder) && strlen($builder)) {
                        $customParams=get_array_value($c,'params',NULL,'?is_array');
                        $varName=get_array_value($c,'var_name',NULL,'?is_string');
                        $cContent=$this->GetControlBuilderFromFile($builder,$class,$args,$customParams,$varName);
                    } else {
                        if($this->_debug) {
                            NApp::Wlog('Invalid content value [control:index:'.$k.']!');
                        }
                        continue 2;
                    }//if(is_array($config))
                    break;
                case self::FILE_CONTENT:
                    $value=get_array_value($c,'value','','is_string');
                    if(!strlen($value)) {
                        if($this->_debug) {
                            NApp::Wlog('Invalid content value [file:index:'.$k.']!');
                        }
                        continue 2;
                    }//if(!strlen($value))
                    $customParams=get_array_value($c,'params',NULL,'?is_array');
                    $cContent=$this->GetFileContent($value,$customParams);
                    break;
                case self::MODULE_CONTENT:
                    $module=get_array_value($c,'module','','is_string');
                    $method=get_array_value($c,'method','','is_string');
                    if(!strlen($module) || !strlen($method) || !ModulesProvider::ModuleMethodExists($module,$method)) {
                        if($this->_debug) {
                            NApp::Wlog('Invalid module content parameters [index:'.$k.':'.print_r($c,1).']!');
                        }
                        continue 2;
                    }//if(!strlen($module) || !strlen($method) || !ModulesProvider::ModuleMethodExists($module,$method))
                    $customParams=get_array_value($c,'params',NULL,'isset');
                    $cContent=$this->GetModuleContent($module,$method,$customParams);
                    break;
                case self::OBJECT_CONTENT:
                    $object=get_array_value($c,'object',NULL,'?is_object');
                    $method=get_array_value($c,'method','','is_string');
                    if(!$object || !strlen($method) || !method_exists($object,$method)) {
                        if($this->_debug) {
                            NApp::Wlog('Invalid content class/value [object:method:'.$method.']!');
                        }
                        continue 2;
                    }//if(!$object || !strlen($method) || !method_exists($object,$method))
                    $args=get_array_value($c,'args',NULL,'?is_array');
                    if(is_array($args) && count($args)) {
                        $cContent=$object->$method(...$args);
                    } else {
                        $cContent=$object->$method();
                    }//if(is_array($args) && count($args))
                    break;
                case self::STRING_CONTENT:
                    $cContent=get_array_value($c,'value','','is_string');
                    break;
                default:
                    if($this->_debug) {
                        NApp::Wlog('Invalid content type [index:'.$k.']!');
                    }
                    break;
            }//END switch
            $processed=FALSE;
            if(strlen($cContainerType)) {
                $processed=$this->ProcessSubContainer($cContent,$cContainerType,$cContainerId,$cContainerClass,$cTag,$cContainerTitle,$cContainerActions);
            }
            if(!$processed && strlen($cTag)) {
                $placeholder='{!'.trim($cTag,'{}').'!}';
                if(strpos($mainContainer,$placeholder)===FALSE) {
                    if($this->_debug) {
                        NApp::Dlog($placeholder.' placeholder is missing for view container ['.$cContainerType.']!',NULL,[],[Logger::WEB_CONSOLE_ADAPTER]);
                    }
                } else {
                    $mainContainer=str_replace($placeholder,$cContent,$mainContainer);
                    $placeholder='{!'.trim($cTag,'{}').'_ID!}';
                    $mainContainer=str_replace($placeholder,$cContainerId,$mainContainer);
                    $placeholder='{!'.trim($cTag,'{}').'_TITLE!}';
                    $mainContainer=str_replace($placeholder,$cContainerTitle,$mainContainer);
                    $processed=TRUE;
                }//if(strpos($mainContainer,$placeholder)===FALSE)
            }//if(!$processed && strlen($cTag))
            if(!$processed) {
                $content.=(strlen($content) ? "\n" : '').$cContent;
            }
        }//END foreach
        if($this->_isModal && $this->_modalAutoJs) {
            $mJsScript=strlen($this->_targetId) ? "ShowDynamicModalForm('{$this->_targetId}'," : "ShowModalForm(";
            $mJsScript.=is_numeric($this->_modalWidth) && $this->_modalWidth>0 ? $this->_modalWidth : (is_string($this->_modalWidth) && strlen($this->_modalWidth) ? "'{$this->_modalWidth}'" : 300);
            if(strlen($this->_titleTagId) || strlen($this->_title)) {
                if(strlen($this->_titleTagId)) {
                    $mJsScript.=",($('#{$this->_titleTagId}').html()".(strlen($this->_title) ? "+': {$this->_title}'" : '').")";
                } elseif(strlen($this->_title)) {
                    $mJsScript.=",'{$this->_title}'";
                }//if(strlen($this->_titleTagId))
            } else {
                $mJsScript.=",''";
            }//if(strlen($this->_titleTagId) || strlen($this->_title))
            if(is_string($this->_modalCustomClose) && strlen($this->_modalCustomClose)) {
                $mJsScript.=','.$this->_modalCustomClose;
            }
            $mJsScript.=');';
            $this->AddJsScript($mJsScript,TRUE);
        }//if($this->_isModal && $this->_modalAutoJs)
        if(strlen($mainContainer)) {
            if($this->_debug && strpos($mainContainer,'{!CONTENT!}')===FALSE) {
                NApp::Dlog('{!CONTENT!} placeholder is missing for view container ['.$this->_containerType.']!',NULL,[],[Logger::WEB_CONSOLE_ADAPTER]);
            }
            if($this->_debug && strlen($this->_targetId) && strpos($mainContainer,'{!TARGETID!}')===FALSE) {
                NApp::Dlog('{!TARGETID!} placeholder is missing for view container ['.$this->_containerType.']!',NULL,[],[Logger::WEB_CONSOLE_ADAPTER]);
            }
            if($this->_debug && count($this->_actions) && strpos($mainContainer,'{!ACTIONS!}')===FALSE) {
                NApp::Dlog('{!ACTIONS!} placeholder is missing for view container ['.$this->_containerType.']!',NULL,[],[Logger::WEB_CONSOLE_ADAPTER]);
            }
            if($this->_debug && strlen($this->_title) && strpos($mainContainer,'{!TITLE!}')===FALSE) {
                NApp::Dlog('{!TITLE!} placeholder is missing for view container ['.$this->_containerType.']!',NULL,[],[Logger::WEB_CONSOLE_ADAPTER]);
            }
            $mainContainer=str_replace('{!CSSCLASS!}',' '.trim($this->_containerClass),$mainContainer);
            $mainContainer=str_replace('{!TITLE!}',$this->_title,$mainContainer);
            $mainContainer=str_replace('{!TARGETID!}',$this->_targetId,$mainContainer);
            $mainContainer=str_replace('{!ACTIONS!}',implode("\n",$this->_actions),$mainContainer);
            $content=str_replace('{!CONTENT!}',$content,$mainContainer);
            $content=$this->ReplacePlaceholders($content);
        } else {
            $content=implode("\n",$this->_actions)."\n".$content;
        }//if(strlen($mainContainer))
        if($return) {
            return $content;
        }
        echo $content;
        if(count($this->_jsScripts)) {
            NApp::AddJsScript($this->GetJsScript());
        }
        return NULL;
    }//END public function Render

    /**
     * @param string $content
     * @return string
     */
    protected function ReplaceEmptyPlaceholders(string $content): string {
        return preg_replace(ControlsHelpers::PLACEHOLDERS_REG_EXP,'',$content);
    }//END protected function ReplaceEmptyPlaceholders

    /**
     * @param string $content
     * @return string
     */
    protected function ReplacePlaceholders(string $content): string {
        $placeholders=[];
        if(preg_match_all(ControlsHelpers::PLACEHOLDERS_REG_EXP,$content,$placeholders)) {
            foreach($placeholders[0] as $placeholder) {
                $content=str_replace($placeholder,get_array_value($this->_placeholders,trim($placeholder,ControlsHelpers::PLACEHOLDERS_TRIM_CHARS),'','is_string'),$content);
            }//END foreach
        }//if(preg_match_all(ControlsHelpers::PLACEHOLDERS_REG_EXP,$content,$placeholders))
        return $content;
    }//END protected function ReplacePlaceholders
}//END class AppView