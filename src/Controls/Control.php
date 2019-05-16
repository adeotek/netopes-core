<?php
/**
 * Control abstract class file
 * Base abstract class for controls
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\App\Module;
use NETopes\Core\AppSession;
use NApp;
use GibberishAES;
use Translate;

/**
 * Class Control
 *
 * @package NETopes\Core\Controls
 * @property mixed        custom_actions
 * @property int|null     tabindex
 * @property mixed        label_width
 * @property int          label_cols
 * @property mixed        width
 * @property int          cols
 * @property mixed        height
 * @property mixed        label
 * @property mixed        required
 * @property mixed        disabled
 * @property mixed        readonly
 * @property mixed        actions
 * @property mixed        clear_base_class
 * @property mixed        class
 * @property string       label_position
 * @property mixed        size
 * @property mixed        dynamic_target
 * @property mixed        tag_name
 * @property mixed        onenter
 * @property mixed        onenter_button
 * @property mixed        fixed_width
 * @property mixed        width_offset
 * @property mixed        style
 * @property mixed        align
 * @property mixed        placeholder
 * @property mixed        paf_property
 * @property mixed        extra_tag_params
 * @property mixed        loader
 * @property int|string   label_cwidth
 * @property int|string   cwidth
 * @property mixed        confirm_text
 * @property mixed        data_onclick
 * @property mixed        onkeypress_str
 * @property mixed        action_params
 * @property mixed        onchange_str
 * @property mixed        data_onkeypress
 * @property mixed        module
 * @property mixed        method
 * @property mixed|string onkeypress
 * @property mixed        data_onchange
 * @property mixed|string onchange
 * @property mixed        onclick_str
 * @property mixed        onclick
 * @property mixed        onclick_ajax_command
 * @property mixed        onclick_target_id
 * @property mixed        run_oninit_func
 * @property mixed        onchange_ajax_command
 * @property mixed        onchange_target_id
 * @property mixed        onkeypress_ajax_command
 * @property mixed        onkeypress_target_id
 */
abstract class Control {
    /**
     * @var    array Control dynamic properties array
     */
    protected $pdata=[];
    /**
     * @var    string Control instance hash
     */
    protected $chash=NULL;
    /**
     * @var    string Control instance UID
     */
    protected $uid=NULL;
    /**
     * @var    string Control base class
     */
    protected $base_class='';
    /**
     * @var    bool Page hash (window.name)
     */
    public $phash=NULL;
    /**
     * @var    string Main tag html id
     */
    public $tag_id=NULL;
    /**
     * @var    bool Postable in NETopes AJAX requests (default: TRUE)
     */
    public $postable=TRUE;
    /**
     * @var    string Theme type
     */
    public $theme_type=NULL;
    /**
     * @var    boolean Output container
     */
    public $container=TRUE;
    /**
     * @var    boolean Flag for no label
     */
    public $no_label=FALSE;
    /**
     * @var    string Tag style base value
     */
    protected $tag_base_style=''; // Old value: 'display: inline-block;';
    /**
     * @var    bool Output buffer on/off
     */
    protected $buffered=FALSE;
    /**
     * @var    string Output (resulting html) buffer
     */
    protected $output_buffer=NULL;
    /**
     * @var    array Control processed actions
     * @access private
     */
    private $ctrl_actions=NULL;

    /**
     * Control class dynamic getter method
     *
     * @param string $name The name o the property
     * @return mixed Returns the value of the property
     */
    public function __get($name) {
        return (is_array($this->pdata) && array_key_exists($name,$this->pdata)) ? $this->pdata[$name] : NULL;
    }//END public function __get

    /**
     * Control class dynamic setter method
     *
     * @param string $name  The name o the property
     * @param mixed  $value The value to be set
     * @return void
     */
    public function __set($name,$value) {
        if(!is_array($this->pdata)) {
            $this->pdata=[];
        }
        $this->pdata[$name]=$value;
    }//END public function __set

    /**
     * Control class constructor
     *
     * @param array $params An array of params
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        $this->chash=AppSession::GetNewUID(get_class_basename($this));
        $this->uid=AppSession::GetNewUID(get_class_basename($this),'md5');
        $this->required=FALSE;
        $this->label_position='left';
        $this->label_width=0;
        $this->label_cols=3;
        $this->width=0;
        $this->cols=0;
        $this->height=0;
        // $this->size = 'xxs';
        $this->tabindex=NULL;
        $this->base_class='cls'.get_class_basename($this);
        $this->theme_type=is_object(NApp::$theme) ? NApp::$theme->GetThemeType() : 'bootstrap3';
        if(is_array($params) && count($params)) {
            foreach($params as $k=>$v) {
                $this->$k=$v;
            }
        }//if(is_array($params) && count($params))
        $this->tag_id=$this->tag_id=='__auto' ? AppSession::GetNewUID() : $this->tag_id;
        if($this->required===1 || $this->required==='1') {
            $this->required=TRUE;
        }
        if(!is_string($this->label)) {
            $this->no_label=TRUE;
        }
        switch($this->theme_type) {
            case 'bootstrap2':
            case 'bootstrap3':
            case 'bootstrap4':
                // For backwards compatibility
                switch(strtolower($this->container)) {
                    case 'bootstrap':
                    case 'bootstrap3':
                    case 'bootstrap4':
                        $this->container=TRUE;
                        $this->label_position='left';
                        break;
                    case 'bootstrap_horizontal':
                    case 'horizontalbootstrap3':
                    case 'horizontalbootstrap4':
                        $this->container=TRUE;
                        $this->label_position='left';
                        break;
                    case 'table':
                    case 'form':
                    case 'div':
                    case 'simpletable':
                        $this->container=TRUE;
                        break;
                    default:
                        if(is_numeric($this->container)) {
                            $this->container=$this->container==1;
                        } elseif(is_string($this->container)) {
                            $this->container=FALSE;
                        }
                        break;
                }//END switch
                $this->ProcessWidth(TRUE);
                break;
            default:
                $this->theme_type='table';
                // For backwards compatibility
                switch(strtolower($this->container)) {
                    case 'bootstrap':
                    case 'bootstrap3':
                    case 'bootstrap4':
                    case 'bootstrap_horizontal':
                    case 'horizontalbootstrap3':
                    case 'table':
                    case 'form':
                    case 'div':
                    case 'simpletable':
                        $this->container=TRUE;
                        break;
                    default:
                        $this->container=FALSE;
                        break;
                }//END switch
                $this->ProcessWidth(FALSE);
                break;
        }//END switch
        if($this->buffered) {
            $this->output_buffer=$this->SetContainer($this->SetControl());
        }
    }//END public function __construct

    /**
     * Gets this instance as a serialized string
     *
     * @param bool $encrypted Switch on/off encrypted result
     * @return string Return serialized control instance
     */
    protected function GetThis($encrypted=TRUE) {
        if($encrypted && strlen($this->chash)) {
            return GibberishAES::enc(serialize($this),$this->chash);
        }
        return serialize($this);
    }//END protected function GetThis

    /**
     * Process the control actions
     *
     * @return array|null
     */
    protected function ProcessActions(): ?array {
        if(!is_array($this->actions) || !count($this->actions)) {
            return $this->ctrl_actions;
        }
        $this->ctrl_actions=[];
        foreach($this->actions as $av) {
            if(($this->disabled || $this->readonly) && !get_array_value($av,'display_always',FALSE,'bool')) {
                continue;
            }
            $c_a_params=get_array_value($av,'params',NULL,'is_notempty_array');
            if(!$c_a_params) {
                continue;
            }
            $c_a_params['theme_type']=$this->theme_type;
            $c_a_params['size']=$this->size;
            $this->ctrl_actions[]=[
                'params'=>$c_a_params,
                'action_params'=>get_array_value($av,'action_params',NULL,'is_notempty_array'),
            ];
        }//END foreach
        return $this->ctrl_actions;
    }//END protected function ProcessActions

    /**
     * Get the actions total css width
     *
     * @return int Returns actions total width
     */
    protected function GetActionsWidth() {
        if(!is_array($this->ctrl_actions)) {
            return 0;
        }
        return (count($this->ctrl_actions) * 28);
    }//END protected function GetActionsWidth

    /**
     * Check if control has actions or not
     *
     * @return bool Returns TRUE if the control has actions or FALSE otherwise
     */
    public function HasActions(): bool {
        return (is_array($this->ctrl_actions) && count($this->ctrl_actions));
    }//END public function HasActions

    /**
     * Get the actions html string
     *
     * @return int Returns actions string
     * @throws \NETopes\Core\AppException
     */
    protected function GetActions() {
        if(!$this->HasActions()) {
            return NULL;
        }
        $result='';
        if($this->container) {
            $result.="\t\t\t\t".'<span class="input-group-btn">'."\n";
        }
        if(strlen($this->dynamic_target)) {
            if(NApp::ajax() && is_object(NApp::Ajax())) {
                NApp::Ajax()->ExecuteJs("AppendDynamicModal('{$this->dynamic_target}');");
            } else {
                $result.="\t"."<script type=\"text/javascript\">AppendDynamicModal('{$this->dynamic_target}');</script>"."\n";
            }//if(NApp::ajax() && is_object(NApp::Ajax()))
        }//if(strlen($this->dynamic_target))
        foreach($this->ctrl_actions as $act) {
            $actParams=$act['params'];
            $actParams['action_params']=get_array_value($act,'action_params',NULL,'is_notempty_array');
            $actParams['onclick']='var thisval = $(\'#'.$this->tag_id.'\').val(); '.get_array_value($actParams,'onclick','','is_string');
            $actParams['disabled']=get_array_value($actParams,'disabled',FALSE,'bool');
            $actParams['style']=get_array_value($act,'style','','is_string');
            $actButton=new Button($actParams);
            $actButton->ClearBaseClass();
            $result.=$actButton->Show();
        }//END foreach
        if($this->container) {
            $result.="\t\t\t\t".'</span>'."\n";
        }
        return $result;
    }//END protected function GetActions

    /**
     * Gets the html tag id string (' id="..."')
     *
     * @param bool $tagName Include the tag name in the result TRUE/FALSE (default FALSE)
     * @param null $sufix
     * @return string Returns the html tag id
     */
    protected function GetTagId($tagName=FALSE,$sufix=NULL) {
        if(!strlen($this->tag_id)) {
            return '';
        }
        if(!$tagName) {
            return ' id="'.$this->tag_id.(strlen($sufix) ? $sufix : '').'"';
        }
        if($tagName===2) {
            return ' id="'.$this->tag_id.(strlen($sufix) ? $sufix : '').'" data-name="'.(strlen($this->tag_name) ? $this->tag_name : $this->tag_id).(strlen($sufix) ? $sufix : '').'"';
        }
        return ' id="'.$this->tag_id.(strlen($sufix) ? $sufix : '').'" name="'.(strlen($this->tag_name) ? $this->tag_name : $this->tag_id).(strlen($sufix) ? $sufix : '').'"';
    }//END protected function GetTagId

    /**
     * Gets the html tag class string (' class="..."')
     *
     * @param string $extra Other html classes to be included
     * @param bool   $raw
     * @return string Returns the html tag class
     */
    protected function GetTagClass($extra=NULL,$raw=FALSE) {
        $lclass=(!$this->clear_base_class ? $this->base_class : '');
        if(strlen($this->class)) {
            $lclass.=' '.$this->class;
        }
        if(!$this->clear_base_class) {
            switch($this->theme_type) {
                case 'bootstrap2':
                case 'bootstrap3':
                case 'bootstrap4':
                    switch(get_class_basename($this)) {
                        case 'Button':
                        case 'DivButton':
                        case 'Link':
                        case 'Container':
                            if(strlen($this->size)) {
                                $lclass.=' btn-'.$this->size;
                            }
                            break;
                        case 'KVList':
                            break;
                        case 'CheckBox':
                            if(strlen($this->size)) {
                                $lclass.=' checkbox-'.$this->size;
                            }
                            break;
                        case 'SmartComboBox':
                            if($this->theme_type!='bootstrap2') {
                                $lclass.=' form-control';
                            }
                            if(strlen($this->size)) {
                                $lclass.=' input-'.$this->size;
                            }
                            if($raw===TRUE && (bool)$this->required) {
                                $lclass.=' clsRequiredField';
                            }
                            break;
                        default:
                            if($this->theme_type!='bootstrap2') {
                                $lclass.=' form-control';
                            }
                            if(strlen($this->size)) {
                                $lclass.=' input-'.$this->size;
                            }
                            break;
                    }//END switch
                    break;
                default:
                    switch(get_class_basename($this)) {
                        case 'SmartComboBox':
                            if($raw===TRUE && (bool)$this->required) {
                                $lclass.=' clsRequiredField';
                            }
                            break;
                        default:
                            break;
                    }//END switch
                    break;
            }//END switch
        }//if(!$this->clear_base_class)
        if(strlen($extra)) {
            $lclass.=' '.$extra;
        }
        if($raw===TRUE) {
            return trim($lclass);
        }
        if((bool)$this->required) {
            $lclass.=' clsRequiredField';
        }
        if($this->postable) {
            $lclass.=' postable';
        }
        if(strlen($this->onenter)) {
            $lclass.=' clsOnEnterAction';
        }
        if(strlen($this->onenter_button)) {
            $lclass.=' clsOnEnterActionButton';
        }
        if($this->HasActions() && !$this->container) {
            $lclass.=' w-act';
        }
        if(strlen(trim($lclass))) {
            return ' class="'.trim($lclass).'"';
        }
        return '';
    }//END protected function GetTagClass

    /**
     * Gets the html tag style attribute string (' style="...")
     *
     * @param mixed  $width  Custom css width
     * @param bool   $halign Include the tag text-align css attribute TRUE/FALSE (default TRUE)
     * @param string $extra  Other css attributes to be included
     * @return string Returns the html tag style attribute
     */
    protected function GetTagStyle($width=NULL,$halign=TRUE,$extra=NULL) {
        $lstyle='';
        $f_width=$this->fixed_width;
        switch($this->theme_type) {
            case 'bootstrap2':
            case 'bootstrap3':
            case 'bootstrap4':
                $fwidth=FALSE;
                if(isset($f_width) && strlen($f_width)) {
                    if(is_numeric($f_width)) {
                        if($f_width>0) {
                            $lstyle.=' '.$this->tag_base_style.' width: '.$f_width.'px;';
                            $fwidth=TRUE;
                        }//if($this->fixed_width>0)
                    } else {
                        $lstyle.=' '.$this->tag_base_style.' width: '.$f_width.';';
                        $fwidth=TRUE;
                    }//if(is_numeric($f_width))
                }//if(isset($f_width) && strlen($f_width))
                if(!$fwidth) {
                    if($this->container) {
                        $lstyle.=' '.$this->tag_base_style;
                    } else {
                        $wo=is_numeric($this->width_offset) ? $this->width_offset : 0;
                        $a_width=$this->GetActionsWidth();
                        if($a_width>0) {
                            $lstyle.=' '.$this->tag_base_style.' width: calc(100% - '.($a_width + $wo).'px);';
                        }
                    }//if($this->container)
                }//if(!$fwidth)
                break;
            default:
                if(isset($f_width) && strlen($f_width)) {
                    $this->width=$f_width;
                }
                $wo=is_numeric($this->width_offset) ? $this->width_offset : 0;
                if(strlen($width)) {
                    $lwidth=is_numeric($width) ? ($width - $this->GetActionsWidth() - $wo).'px' : $width;
                } else {
                    $lwidth=(is_numeric($this->width) && $this->width>0) ? ($this->width - $this->GetActionsWidth() - $wo).'px' : $this->width;
                }//if(strlen($width))
                if($lwidth) {
                    $lstyle.=' box-sizing: border-box; width: '.$lwidth.';';
                }
                break;
        }//END switch
        if($halign && strlen($this->align)) {
            $lstyle.=' text-align: '.$this->align.';';
        }
        if($this->height) {
            $lstyle.=' height: '.$this->height.(is_numeric($this->height) ? 'px' : '').';';
        }
        if(strlen($this->style)) {
            $lstyle.=' '.$this->style;
        }
        $lstyle=trim($lstyle).(strlen($extra) ? ' '.$extra : '');
        if(strlen($lstyle)) {
            return ' style="'.$lstyle.'"';
        }
        return '';
    }//END protected function GetTagStyle

    /**
     * Gets the html tag attributes string (' placeholder="..." disabled="..." ...')
     *
     * @param bool   $style Include the tag style attribute TRUE/FALSE (default TRUE)
     * @param string $extra Other html attributes to be included
     * @return string Returns the html tag attributes
     */
    protected function GetTagAttributes($style=TRUE,$extra=NULL) {
        $lattr='';
        if($style) {
            $lattr.=$this->GetTagStyle();
        }
        if((bool)$this->disabled) {
            $lattr.=' disabled="disabled"';
        }
        if((bool)$this->readonly) {
            $lattr.=' readonly="readonly"';
        }
        if(strlen($this->placeholder)) {
            $lattr.=' placeholder="'.$this->placeholder.'"';
        }
        if(is_numeric($this->tabindex) && $this->tabindex>0) {
            $lattr.=' tabindex="'.$this->tabindex.'"';
        }
        if(strlen($this->onenter)) {
            $lattr.=' data-onenter="'.$this->onenter.'"';
        }
        if(strlen($this->onenter_button)) {
            $lattr.=' data-onenterbtn="'.$this->onenter_button.'"';
        }
        if(strlen($this->paf_property)) {
            $lattr.=' data-paf-prop="'.$this->paf_property.'"';
        }
        if(strlen($this->extra_tag_params)) {
            $lattr.=' '.$this->extra_tag_params;
        }
        $lattr=trim($lattr).(strlen($extra) ? ' '.$extra : '');
        return $lattr;
    }//END protected function GetTagAttributes

    /**
     * Gets the html tag action attributes string (' onclick="..." onchange="..." ...')
     *
     * @param array|null  $base
     * @param string|null $extra Other html attributes to be included
     * @return string Returns the html tag attributes
     * @throws \NETopes\Core\AppException
     */
    protected function GetTagActions(?array $base=NULL,?string $extra=NULL): string {
        if($this->readonly || $this->disabled) {
            return '';
        }
        $lActions=[];
        if(is_array($base)) {
            foreach($base as $ak=>$av) {
                if(strlen($av)) {
                    $lActions[$ak]=trim($av);
                }
            }
        }
        // OnClick
        $onClickBase=get_array_value($lActions,'onclick',NULL,'is_string');
        $lActions['onclick']=$this->GetOnClickAction($onClickBase);
        // OnChange
        $onChangeBase=get_array_value($lActions,'onchange',NULL,'is_string');
        $lActions['onchange']=$this->GetOnChangeAction($onChangeBase);
        // OnKeyPress
        $onKeyPress=get_array_value($lActions,'onkeypress',NULL,'is_string');
        $lActions['onkeypress']=$this->GetOnKeyPressAction($onKeyPress);
        $actionsString=implode(' ',$lActions).(strlen($extra) ? ' '.$extra : '');
        return $actionsString;
    }//END protected function GetTagActions

    /**
     * Gets the html tag onclick attributes string
     *
     * @param string|null $base
     * @param bool        $actOnly
     * @return string Returns the html tag attribute
     * @throws \NETopes\Core\AppException
     */
    protected function GetOnClickAction(?string $base=NULL,bool $actOnly=FALSE): string {
        $action='';
        if($base) {
            $action.=$base;
        }
        if(is_string($this->onclick) && strlen($this->onclick) && ($this->data_onclick!==TRUE || $this->disabled!==TRUE)) {
            $action.=' '.trim($this->onclick,' ;').';';
        }//if(is_string($this->onclick) && strlen($this->onclick) && ($this->data_onclick!==TRUE || $this->disabled!==TRUE))
        if($this->data_onclick!==TRUE || $this->disabled!==TRUE) {
            if(strlen($this->onclick_ajax_command)) {
                $action.=($action ? ' ' : '').ControlsHelpers::GetAjaxActionString($this->onclick_ajax_command,$this->onclick_target_id,$this->action_params,$this->run_oninit_func);
            } elseif(strlen($this->onclick_str)) {
                $action.=($action ? ' ' : '').ControlsHelpers::LegacyGetAjaxActionString($this->onclick_str,$this->action_params,$this->run_oninit_func);
            }//if(strlen($this->onclick_ajax_command))
        }//if($this->data_onclick!==TRUE || $this->disabled!==TRUE)
        if(!strlen(trim($action))) {
            return '';
        }
        if(strlen($this->confirm_text)) {
            $action='ShowConfirmDialog(\''.$this->confirm_text.'\',function(){'.trim($action).'},false,{title:\''.Translate::Get('title_confirm').'\',ok:\''.Translate::Get('button_ok').'\',cancel:\''.Translate::Get('button_cancel').'\'});';
        }//if(strlen($this->confirm_text))
        if($actOnly) {
            return $action;
        }
        return ($this->data_onclick===TRUE ? 'data-' : '').'onclick="'.trim($action).'"';
    }//END protected function GetOnClickAction

    /**
     * Gets the html tag onchange attributes string
     *
     * @param string|null $base
     * @param bool        $actOnly
     * @return string Returns the html tag attribute
     * @throws \NETopes\Core\AppException
     */
    protected function GetOnChangeAction(?string $base=NULL,bool $actOnly=FALSE): string {
        $action='';
        if($base) {
            $action.=$base;
        }
        if(is_string($this->onchange) && strlen($this->onchange) && ($this->data_onchange!==TRUE || $this->disabled!==TRUE)) {
            $action.=' '.trim($this->onchange,' ;').';';
        }//if(is_string($this->onchange) && strlen($this->onchange) && ($this->data_onchange!==TRUE || $this->disabled!==TRUE))
        if($this->data_onchange!==TRUE || $this->disabled!==TRUE) {
            if(strlen($this->onchange_ajax_command)) {
                $action.=($action ? ' ' : '').ControlsHelpers::GetAjaxActionString($this->onchange_ajax_command,$this->onchange_target_id,$this->action_params,$this->run_oninit_func);
            } elseif(strlen($this->onchange_str)) {
                $action.=($action ? ' ' : '').ControlsHelpers::LegacyGetAjaxActionString($this->onchange_str,$this->action_params,$this->run_oninit_func);
            }//if(strlen($this->onchange_ajax_command))
        }//if($this->data_onchange!==TRUE || $this->disabled!==TRUE)
        if(!strlen(trim($action))) {
            return '';
        }
        if(strlen($this->confirm_text)) {
            $action='ShowConfirmDialog(\''.$this->confirm_text.'\',function(){'.trim($action).'},false,{title:\''.Translate::Get('title_confirm').'\',ok:\''.Translate::Get('button_ok').'\',cancel:\''.Translate::Get('button_cancel').'\'});';
        }//if(strlen($this->confirm_text))
        if($actOnly) {
            return $action;
        }
        return ($this->data_onclick===TRUE ? 'data-' : '').'onchange="'.trim($action).'"';
    }//END protected function GetOnChangeAction

    /**
     * Gets the html tag onkeypress attributes string
     *
     * @param string|null $base
     * @param bool        $actOnly
     * @return string Returns the html tag attribute
     * @throws \NETopes\Core\AppException
     */
    protected function GetOnKeyPressAction(?string $base=NULL,bool $actOnly=FALSE): string {
        $action='';
        if($base) {
            $action.=$base;
        }
        if(is_string($this->onkeypress) && strlen($this->onkeypress) && ($this->data_onkeypress!==TRUE || $this->disabled!==TRUE)) {
            $action.=' '.trim($this->onkeypress,' ;').';';
        }//if(is_string($this->onkeypress) && strlen($this->onkeypress) && ($this->data_onkeypress!==TRUE || $this->disabled!==TRUE))
        if($this->data_onkeypress!==TRUE || $this->disabled!==TRUE) {
            if(strlen($this->onkeypress_ajax_command)) {
                $action.=($action ? ' ' : '').ControlsHelpers::GetAjaxActionString($this->onkeypress_ajax_command,$this->onkeypress_target_id,$this->action_params,$this->run_oninit_func);
            } elseif(strlen($this->onkeypress_str)) {
                $action.=($action ? ' ' : '').ControlsHelpers::LegacyGetAjaxActionString($this->onkeypress_str,$this->action_params,$this->run_oninit_func);
            }//if(strlen($this->onkeypress_ajax_command))
        }//if($this->data_onkeypress!==TRUE || $this->disabled!==TRUE)
        if(!strlen(trim($action))) {
            return '';
        }
        if(strlen($this->confirm_text)) {
            $action='ShowConfirmDialog(\''.$this->confirm_text.'\',function(){'.trim($action).'},false,{title:\''.Translate::Get('title_confirm').'\',ok:\''.Translate::Get('button_ok').'\',cancel:\''.Translate::Get('button_cancel').'\'});';
        }//if(strlen($this->confirm_text))
        if($actOnly) {
            return $action;
        }
        return ($this->data_onclick===TRUE ? 'data-' : '').'onkeypress="'.trim($action).'"';
    }//END protected function GetOnKeyPressAction

    /**
     * Convert Ncol width to standard
     *
     * @param bool $bootstrap Flag indicating use of bootstrap grid (default FALSE)
     * @return void
     */
    protected function ProcessWidth(bool $bootstrap=FALSE) {
        if($bootstrap) {
            if(strpos($this->label_width,'col')!==FALSE && strpos($this->width,'col')!==FALSE) {
                $llw=str_replace('col','',$this->label_width);
                $lw=str_replace('col','',$this->width);
                if(is_numeric($lw) && $lw>0 && $lw<=12) {
                    $this->label_width=NULL;
                    $this->width=NULL;
                    $this->label_cwidth=(12 - $lw);
                    $this->cwidth=$lw;
                } elseif(is_numeric($llw) && $llw>0 && $llw<12) {
                    $this->label_width=NULL;
                    $this->width=NULL;
                    $this->cwidth=(12 - $llw);
                    $this->label_cwidth=$llw;
                } else {
                    $this->width=$lw;
                    $this->label_width=$llw;
                }//if(is_numeric($lw) && $lw>0 && $lw<=12)
            } elseif(strpos($this->label_width,'col')!==FALSE) {
                $llw=str_replace('col','',$this->label_width);
                if(is_numeric($llw) && $llw>0 && $llw<12) {
                    $this->label_width=NULL;
                    $this->width=NULL;
                    $this->cwidth=(12 - $llw);
                    $this->label_cwidth=$llw;
                } else {
                    $this->label_width=$llw;
                }//if(is_numeric($llw) && $llw>0 && $llw<12)
            } elseif(strpos($this->width,'col')!==FALSE) {
                $lw=str_replace('col','',$this->width);
                if(is_numeric($lw) && $lw>0 && $lw<=12) {
                    $this->label_width=NULL;
                    $this->width=NULL;
                    $this->label_cwidth=(12 - $lw);
                    $this->cwidth=$lw;
                } else {
                    $this->width=$lw;
                }//if(is_numeric($lw) && $lw>0 && $lw<=12)
            }//if(strpos($this->label_width,'col')!==FALSE && strpos($this->width,'col')!==FALSE)
        } else {
            if(strpos($this->label_width,'col')!==FALSE && strpos($this->width,'col')!==FALSE) {
                $llw=str_replace('col','',$this->label_width);
                $lw=str_replace('col','',$this->width);
                if(is_numeric($lw) && $lw>0 && $lw<=12) {
                    $this->label_width=round((12 - $lw) / 12 * 100,0).'%';
                    $this->width=round($lw / 12 * 100,0).'%';
                } elseif(is_numeric($llw) && $llw>0 && $llw<12) {
                    $this->width=round((12 - $llw) / 12 * 100,0).'%';
                    $this->label_width=round($llw / 12 * 100,0).'%';
                } else {
                    $this->width=$lw;
                    $this->label_width=$llw;
                }//if(is_numeric($lw) && $lw>0 && $lw<=12)
            } elseif(strpos($this->label_width,'col')!==FALSE) {
                $llw=str_replace('col','',$this->label_width);
                if(is_numeric($llw) && $llw>0 && $llw<12) {
                    $this->width=round((12 - $llw) / 12 * 100,0).'%';
                    $this->label_width=round($llw / 12 * 100,0).'%';
                } else {
                    $this->label_width=$llw;
                }//if(is_numeric($llw) && $llw>0 && $llw<12)
            } elseif(strpos($this->width,'col')!==FALSE) {
                $lw=str_replace('col','',$this->width);
                if(is_numeric($lw) && $lw>0 && $lw<=12) {
                    $this->label_width=round((12 - $lw) / 12 * 100,0).'%';
                    $this->width=round($lw / 12 * 100,0).'%';
                } else {
                    $this->width=$lw;
                }//if(is_numeric($lw) && $lw>0 && $lw<=12)
            }//if(strpos($this->label_width,'col')!==FALSE && strpos($this->width,'col')!==FALSE)
        }//if($bootstrap)
    }//END protected function ProcessWidth

    /**
     * Convert Ncol width to standard
     *
     * @return string Custom actions HTML string
     * @throws \NETopes\Core\AppException
     */
    protected function ProcessCustomActions() {
        // NApp::Dlog($this->custom_actions,'$this->custom_actions');
        if(!is_array($this->custom_actions) || !count($this->custom_actions)) {
            return NULL;
        }
        $result='';
        foreach($this->custom_actions as $ca) {
            if(!is_array($ca) || !count($ca)) {
                continue;
            }
            $caParams=get_array_value($ca,'params',[],'is_array');
            $caParams['theme_type']=$this->theme_type;
            $caParams['size']=$this->size;
            $caType=get_array_value($ca,'type','DivButton','is_notempty_string');
            $caClass='\NETopes\Core\Controls\\'.$caType;
            if(!class_exists($caClass)) {
                continue;
            }
            $caDRight=get_array_value($ca,'dright','','is_string');
            if(strlen($caDRight)) {
                $dright=Module::GetDRights($this->module,$this->method,$caDRight);
                if($dright) {
                    continue;
                }
            }//if(strlen($caDRight))
            $ajaxCommand=get_array_value($ca,'ajax_command',NULL,'is_notempty_string');
            $targetId=get_array_value($ca,'ajax_target_id',NULL,'is_notempty_string');
            if(!$ajaxCommand) {
                $aCommand=get_array_value($ca,'command_string',NULL,'?is_string');
                if(strlen($aCommand)) {
                    $ajaxCommand=NApp::Ajax()->LegacyProcessParamsString($aCommand,$targetId);
                }
            }//if(!$ajaxCommand)
            if($ajaxCommand) {
                $ajaxCommand=ControlsHelpers::ReplaceDynamicParams($ajaxCommand,$ca,TRUE,NULL,'is_scalar');
                $caParams['onclick']=NApp::Ajax()->Prepare($ajaxCommand,$targetId,NULL,$this->loader);
            }//if($ajaxCommand)
            $caCtrl=new $caClass($caParams);
            if(get_array_value($ca,'clear_base_class',FALSE,'bool')) {
                $caCtrl->ClearBaseClass();
            }
            $result.=$caCtrl->Show();
        }//END foreach
        // NApp::Dlog($result,'custom_actions>$result');
        return $result;
    }//END protected function ProcessCustomActions

    /**
     * description
     *
     * @param $tag
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetContainer($tag): ?string {
        $cActions=$this->ProcessCustomActions();
        $containerClass='NETopes\Core\Controls\Container'.ucfirst($this->theme_type);
        /** @var \NETopes\Core\Controls\IControlContainer $ctrlContainer */
        $ctrlContainer=new $containerClass($this);
        $result=$ctrlContainer->GetHtml($tag,$cActions);
        return $result;
    }//END protected function SetContainer

    /**
     * description
     *
     * @return string|null
     */
    abstract protected function SetControl(): ?string;

    /**
     * Process control to generate HTML
     *
     * @return string
     * @throws \NETopes\Core\AppException
     */
    public function Show() {
        if($this->buffered) {
            return $this->output_buffer;
        }
        return $this->SetContainer($this->SetControl());
    }//END public function Show

    /**
     * Clears the base class of the control
     *
     * @return void
     */
    public function ClearBaseClass() {
        $this->base_class='';
    }//END public function ClearBaseClass
}//END abstract class Control