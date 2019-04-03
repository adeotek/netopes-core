<?php
/**
 * BasicForm control class file
 * Control class for generating basic forms
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\AppException;
use NETopes\Core\Data\DataSourceHelpers;
use NETopes\Core\Data\VirtualEntity;
use NETopes\Core\Validators\Validator;
use NApp;

/**
 * Class BasicForm
 *
 * @package NETopes\Core\Controls
 */
class BasicForm {
    /**
     * @var    string Theme (form) type: native(table)/bootstrap2/bootstrap3/bootstrap4
     */
    public $theme_type=NULL;
    /**
     * @var    string BasicForm table id
     */
    public $tag_id=NULL;
    /**
     * @var    string BasicForm response target id
     */
    public $response_target=NULL;
    /**
     * @var    string BasicForm width
     */
    public $width=NULL;
    /**
     * @var    string Form horizontal align (default "center")
     */
    public $align='center';
    /**
     * @var    string Separator column width
     */
    public $separator_width=NULL;
    /**
     * @var    string BasicForm additional class
     */
    public $class=NULL;
    /**
     * @var    int Columns number
     */
    public $cols_no=NULL;
    /**
     * @var    string Labels position type (horizontal/vertical)
     */
    public $positioning_type=NULL;
    /**
     * @var    int Labels width in grid columns number
     */
    public $label_cols=NULL;
    /**
     * @var    string Controls size CSS class
     */
    public $controls_size=NULL;
    /**
     * @var    string Actions controls size CSS class
     */
    public $actions_size=NULL;
    /**
     * @var    array fields descriptor array
     */
    public $content=[];
    /**
     * @var    array form actions descriptor array
     */
    public $actions=[];
    /**
     * @var    string Tags IDs sufix
     */
    public $tags_ids_sufix=NULL;
    /**
     * @var    string Tags names sufix
     */
    public $tags_names_sufix=NULL;
    /**
     * @var    string Sub-form tag ID
     */
    public $sub_form_tag_id=NULL;
    /**
     * @var    string Sub-form tag extra CSS class
     */
    public $sub_form_class=NULL;
    /**
     * @var    \NETopes\Core\Data\DataSet|null Fields conditions data
     */
    public $field_conditions=NULL;
    /**
     * @var    bool Filter for mandatory fields only
     */
    public $mandatory_fields_only=FALSE;
    /**
     * @var    string Fields conditions field name property
     */
    public $field_name_property='name';
    /**
     * @var    int|null Fields conditions field name case
     */
    public $field_name_property_case=CASE_LOWER;
    /**
     * @var    string Fields conditions visibility property
     */
    public $visible_field_property='is_visible';
    /**
     * @var    string Fields conditions mandatory property
     */
    public $mandatory_field_property='is_mandatory';
    /**
     * @var    string Fields conditions required property
     */
    public $required_field_property='is_required';
    /**
     * @var    string Sub-form tag extra attributes
     */
    public $sub_form_extra_tag_params=NULL;
    /**
     * @var    string Basic form base class
     */
    protected $base_class=NULL;
    /**
     * @var    string Output (resulting html) buffer
     */
    protected $output_buffer=NULL;
    /**
     * @var    array Javascript code execution queue
     */
    protected $js_scripts=[];

    /**
     * BasicForm class constructor method
     *
     * @param array $params Parameters array
     * @return void
     */
    public function __construct($params=NULL) {
        $this->base_class=get_array_value($params,'clear_base_class',FALSE,'bool') ? '' : 'cls'.get_class_basename($this);
        $this->theme_type=is_object(NApp::$theme) ? NApp::$theme->GetThemeType() : 'bootstrap3';
        $this->controls_size=is_object(NApp::$theme) ? NApp::$theme->GetControlsDefaultSize() : 'xs';
        $this->actions_size=is_object(NApp::$theme) ? NApp::$theme->GetButtonsDefaultSize() : 'xs';
        if(is_array($params) && count($params)) {
            foreach($params as $k=>$v) {
                if(property_exists($this,$k)) {
                    $this->$k=$v;
                }
            }//foreach ($params as $k=>$v)
        }//if(is_array($params) && count($params))
        if(!is_numeric($this->cols_no) || $this->cols_no<=0) {
            $this->cols_no=1;
        }
        $this->field_conditions=DataSourceHelpers::ConvertArrayToDataSet(is_iterable($this->field_conditions) ? $this->field_conditions : [],VirtualEntity::class,$this->field_name_property,$this->field_name_property_case);
    }//END public function __construct

    /**
     * @param array $action
     * @param bool  $first
     */
    public function AddAction(array $action,bool $first=FALSE): void {
        if(!is_array($this->actions)) {
            $this->actions=[];
        }
        if($first) {
            array_unshift($this->actions,$action);
        } else {
            $this->actions[]=$action;
        }//if($first)
    }//END public function AddAction

    /**
     * @param array $row
     * @param bool  $first
     */
    public function AddRow(array $row,bool $first=FALSE): void {
        if(!is_array($this->content)) {
            $this->content=[];
        }
        if($first) {
            array_unshift($this->content,$row);
        } else {
            $this->content[]=$row;
        }//if($first)
    }//END public function AddRow

    /**
     * @param array    $control
     * @param int|null $row
     * @param int|null $column
     * @param bool     $overwrite
     * @throws \NETopes\Core\AppException
     */
    public function AddControl(array $control,?int $row=NULL,?int $column=NULL,bool $overwrite=FALSE): void {
        if(!is_array($this->content)) {
            $this->content=[];
        }
        if($row!==NULL) {
            if(!isset($this->content[$row]) || !is_array($this->content[$row])) {
                $this->content[$row]=[];
            }
            if($column!==NULL) {
                if(isset($this->content[$row][$column]) && is_array($this->content[$row][$column]) && !$overwrite) {
                    throw new AppException("Form element already set [{$row}:{$column}]!");
                }
                $this->content[$row][$column]=$control;
            } else {
                $this->content[$row][]=$control;
            }//if($column!==NULL)
        } else {
            $this->content[]=[$control];
        }//if($row!==NULL)
    }//END public function AddRow

    /**
     * @return array
     */
    public function GetJsScripts(): array {
        return $this->js_scripts;
    }//END public function GetJsScripts

    /**
     * @return null|string
     */
    public function GetJsScript(): ?string {
        if(!count($this->js_scripts)) {
            return NULL;
        }
        $result='';
        foreach($this->js_scripts as $js) {
            $js=trim($js);
            $result.=(strlen($result) ? "\n" : '').(substr($js,-1)=='}' ? $js : rtrim($js,';').';');
        }//END foreach
        return $result;
    }//END public function GetJsScript

    /**
     * Gets the form actions string
     *
     * @param int $tabindex
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetActions($tabindex=0) {
        $result='';
        foreach($this->actions as $action) {
            $act_params=get_array_value($action,'params',[],'is_array');
            if(!count($act_params)) {
                continue;
            }
            $a_class=get_array_value($act_params,'class','','is_string');
            $act_type=get_array_value($action,'type','Button','is_notempty_string');
            if($act_type=='CloseModal') {
                $act_class='Button';
                $act_params['onclick']="CloseModalForm('".get_array_value($action,'custom_action','','is_string')."','".get_array_value($action,'targetid','','is_string')."',".intval(get_array_value($action,'dynamic',1,'bool')).")";
                $act_params['class']=strlen($a_class) ? $a_class : (is_object(NApp::$theme) ? NApp::$theme->GetBtnDefaultClass() : 'btn btn-default');
            } else {
                $act_class=$act_type;
                $act_params['class']=strlen($a_class) ? $a_class : (is_object(NApp::$theme) ? NApp::$theme->GetBtnPrimaryClass() : 'btn btn-primary');
            }//if($act_type=='CloseModal')
            if(!is_string($act_class) || !strlen($act_class)) {
                continue;
            }
            $act_class='\NETopes\Core\Controls\\'.$act_class;
            if(!class_exists($act_class)) {
                continue;
            }
            if(strlen($result)) {
                $ml_class=is_object(NApp::$theme) ? NApp::$theme->GetActionsSeparatorClass() : 'ml10';
                $act_params['class'].=(strlen(trim($ml_class)) ? ' '.trim($ml_class) : '');
            }//if(strlen($result))
            if(strlen($this->actions_size)) {
                $act_params['size']=$this->actions_size;
            }
            $act_instance=new $act_class($act_params);
            if(!Validator::IsValidValue($act_instance->tabindex,'is_not0_integer')) {
                $act_instance->tabindex=$tabindex++;
            }
            if(get_array_value($action,'clear_base_class',FALSE,'bool')) {
                $act_instance->ClearBaseClass();
            }
            $result.=$act_instance->Show();
        }//END foreach
        return $result;
    }//END protected function GetActions

    /**
     * @param array       $control
     * @param null|string $fieldName
     * @return bool
     */
    protected function CheckFieldConditions(array &$control,?string $fieldName=NULL): bool {
        if(!strlen($fieldName)) {
            $fieldName=get_array_value($control,'tag_name',NULL,'?is_notempty_string');
        }
        if(!strlen($fieldName) || !$this->field_conditions->containsKey($fieldName)) {
            return TRUE;
        }
        $condition=$this->field_conditions->get($fieldName);
        if($this->mandatory_fields_only && !$condition->getProperty($this->mandatory_field_property,FALSE,'bool')) {
            return FALSE;
        } elseif(!$condition->getProperty($this->visible_field_property,TRUE,'bool')) {
            if(!$condition->getProperty($this->mandatory_field_property,FALSE,'bool')) {
                return FALSE;
            }
            $control['hidden']=TRUE;
        }//if($this->mandatory_fields_only && !$condition->getProperty($this->mandatory_field_property,FALSE,'bool'))
        $control['required']=$condition->getProperty($this->required_field_property,isset($control['required']) ? $control['required'] : NULL,'bool');
        return TRUE;
    }//END protected function CheckFieldConditions

    /**
     * Gets the form content as table
     *
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetTableControl(): string {
        $ltabindex=101;
        $lclass=trim($this->base_class.' '.$this->class);
        $lstyle=strlen($this->width)>0 ? ' style="width: '.$this->width.(strpos($this->width,'%')===FALSE ? 'px' : '').';"' : '';
        $sc_style=strlen($this->separator_width)>0 ? ' style="width: '.$this->separator_width.(strpos($this->separator_width,'%')===FALSE ? 'px' : '').';"' : '';
        if(!$this->width || $this->width='100%') {
            switch(strtolower($this->align)) {
                case 'left':
                    $lsidecolumn='';
                    $rsidecolumn="\t".'<td>&nbsp;</td>'."\n";
                    break;
                case 'right':
                    $lsidecolumn="\t".'<td>&nbsp;</td>'."\n";
                    $rsidecolumn='';
                    break;
                case 'center':
                default:
                    $lsidecolumn=$rsidecolumn="\t".'<td>&nbsp;</td>'."\n";
                    break;
            }//END switch
        } else {
            $lsidecolumn=$rsidecolumn='';
        }//if(!$this->width || $this->width='100%')
        $result='';
        if(strlen($this->sub_form_tag_id)) {
            $sfclass='clsSubForm'.(strlen($this->sub_form_class) ? ' '.$this->sub_form_class : '');
            $sfextratagparam=(strlen($this->sub_form_extra_tag_params) ? ' '.$this->sub_form_extra_tag_params : '');
            $result.='<div class="'.$sfclass.'" id="'.$this->sub_form_tag_id.'"'.$sfextratagparam.'>'."\n";
        }//if(strlen($this->sub_form_tag_id))
        $result.='<table'.($this->tag_id ? ' id="'.$this->tag_id.'"' : '').' class="'.$lclass.'"'.$lstyle.'>'."\n";
        foreach($this->content as $row) {
            if(!is_array($row) || !count($row)) {
                continue;
            }
            $sr_type=get_array_value($row,'separator',NULL,'is_notempty_string');
            if($sr_type) {
                $result.="\t".'<tr>'."\n";
                $result.=$lsidecolumn;
                switch(strtolower($sr_type)) {
                    case 'empty':
                        $sr_class=' class="clsTRS"';
                        $sr_hr='&nbsp;';
                        break;
                    case 'title':
                        $sr_hr=get_array_value($row,'value','&nbsp;','is_notempty_string');
                        $sr_c_class=get_array_value($row,'class','','is_string');
                        $sr_class=' class="clsTRS form-title'.($sr_c_class ? ' '.$sr_c_class : '').'"';
                        break;
                    case 'subtitle':
                        $sr_hr=get_array_value($row,'value','&nbsp;','is_notempty_string');
                        $sr_c_class=get_array_value($row,'class','','is_string');
                        $sr_class=' class="clsTRS sub-title'.($sr_c_class ? ' '.$sr_c_class : '').'"';
                        break;
                    case 'line':
                    default:
                        $sr_class=' class="clsTRS"';
                        $sr_hr='<hr>';
                        break;
                }//END switch
                $sr_span=' colspan="'.($this->cols_no * 2 - 1).'"';
                $result.="\t\t".'<td'.$sr_span.$sr_class.'>'.$sr_hr.'</td>'."\n";
                $result.=$rsidecolumn;
                $result.="\t".'</tr>'."\n";
                continue;
            }//if($sr_type)
            $hidden=get_array_value($row,[0,'hidden_row'],FALSE,'bool');
            $result.="\t".'<tr'.($hidden ? ' class="hidden"' : '').'>'."\n";
            $result.=$lsidecolumn;
            $first=TRUE;
            foreach($row as $col) {
                if($first) {
                    $first=FALSE;
                } else {
                    $result.="\t\t".'<td'.$sc_style.'>&nbsp;</td>'."\n";
                }//if($first)
                $c_type=get_array_value($col,'control_type',NULL,'is_notempty_string');
                $c_width=get_array_value($col,'width',NULL,'is_notempty_string');
                $cstyle=strlen($c_width)>0 ? ' style="width: '.$c_width.(strpos($c_width,'%')===FALSE ? 'px' : '').';"' : '';
                $c_span=get_array_value($col,'colspan',1,'is_numeric');
                $cspan=$c_span>1 ? ' colspan="'.($c_span + ($c_span - 1)).'"' : '';
                $c_type=$c_type ? '\NETopes\Core\Controls\\'.$c_type : $c_type;
                if(!$c_type || !class_exists($c_type)) {
                    NApp::Elog('Control class ['.$c_type.'] not found!');
                    $result.="\t\t".'<td'.$cspan.$cstyle.'>&nbsp;</td>'."\n";
                    continue;
                }//if(!$c_type || !class_exists($c_type))
                $result.="\t\t".'<td'.$cspan.$cstyle.'>'."\n";
                $ctrl_params=get_array_value($col,'control_params',[],'is_array');
                if(strlen($this->tags_ids_sufix) && isset($ctrl_params['tag_id'])) {
                    $ctrl_params['tag_id'].=$this->tags_ids_sufix;
                }
                if(strlen($this->tags_names_sufix) && isset($ctrl_params['tag_name'])) {
                    $ctrl_params['tag_name'].=$this->tags_names_sufix;
                }
                $control=new $c_type($ctrl_params);
                if(property_exists($c_type,'tabindex') && !Validator::IsValidValue($control->tabindex,'is_not0_integer')) {
                    $control->tabindex=$ltabindex++;
                }
                if(get_array_value($col,'clear_base_class',FALSE,'bool')) {
                    $control->ClearBaseClass();
                }
                $result.=$control->Show();
                $result.="\t\t".'</td>'."\n";
            }//END foreach
            $result.=$rsidecolumn;
            $result.="\t".'</tr>'."\n";
        }//END foreach
        $lcolspan=(2 * $this->cols_no - 1)>1 ? ' colspan="'.(2 * $this->cols_no - 1).'"' : '';
        if(is_string($this->response_target) && strlen($this->response_target)) {
            $result.="\t".'<tr>'."\n";
            $result.=$lsidecolumn;
            $result.="\t\t".'<td'.$lcolspan.' id="'.$this->response_target.'" class="'.($this->base_class ? $this->base_class : 'cls').'ErrMsg'.'"></td>'."\n";
            $result.=$rsidecolumn;
            $result.="\t".'</tr>'."\n";
        }//if(is_string($this->response_target) && strlen($this->response_target))
        if(is_array($this->actions) && count($this->actions)) {
            $result.="\t".'<tr>'."\n";
            $result.=$lsidecolumn;
            $result.="\t\t".'<td'.$lcolspan.'>'."\n";
            $result.=$this->GetActions($ltabindex);
            $result.="\t\t".'</td>'."\n";
            $result.=$rsidecolumn;
            $result.="\t".'</tr>'."\n";
        }//if(is_array($this->actions) && count($this->actions))
        $result.='</table>';
        if(strlen($this->sub_form_tag_id)) {
            $result.='</div>'."\n";
        }
        return $result;
    }//END protected function GetTableControl

    /**
     * Gets the form content as bootstrap3 form
     *
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetBootstrap3Control(): string {
        $ltabindex=101;
        $lclass=trim($this->base_class.' '.$this->class);
        if($this->positioning_type!='vertical') {
            $lclass.=' form-horizontal';
        }
        if(strlen($this->controls_size)) {
            $lclass.=' form-'.$this->controls_size;
        }
        if($this->cols_no>1) {
            $lclass.=' multi';
        }
        if(strlen($this->sub_form_tag_id)) {
            $sfclass='clsSubForm'.(strlen($this->sub_form_class) ? ' '.$this->sub_form_class : '');
            $sfextratagparam=(strlen($this->sub_form_extra_tag_params) ? ' '.$this->sub_form_extra_tag_params : '');
            $result='<div class="'.$sfclass.'" id="'.$this->sub_form_tag_id.'"'.$sfextratagparam.'>'."\n";
            $result.='<div'.($this->tag_id ? ' id="'.$this->tag_id.'"' : '').' class="clsFormContainer '.$lclass.'">'."\n";
        } else {
            $result='<div class="row"><div class="col-md-12 '.$lclass.'"'.($this->tag_id ? ' id="'.$this->tag_id.'"' : '').'>'."\n";
        }//if(strlen($this->sub_form_tag_id))
        foreach($this->content as $row) {
            if(!is_array($row) || !count($row)) {
                continue;
            }
            $sr_type=get_array_value($row,'separator',NULL,'is_notempty_string');
            if($sr_type) {
                $result.="\t".'<div class="row">'."\n";
                switch(strtolower($sr_type)) {
                    case 'empty':
                        $sr_class=' clsTRS';
                        $sr_hr='&nbsp;';
                        break;
                    case 'title':
                        $sr_hr=get_array_value($row,'value','&nbsp;','is_notempty_string');
                        $sr_c_class=get_array_value($row,'class','','is_string');
                        $sr_class=' clsTRS form-title'.($sr_c_class ? ' '.$sr_c_class : '');
                        break;
                    case 'subtitle':
                        $sr_hr=get_array_value($row,'value','&nbsp;','is_notempty_string');
                        $sr_c_class=get_array_value($row,'class','','is_string');
                        $sr_class=' clsTRS sub-title'.($sr_c_class ? ' '.$sr_c_class : '');
                        break;
                    case 'line':
                    default:
                        $sr_class=' clsTRS';
                        $sr_hr='<hr>';
                        break;
                }//END switch
                $result.="\t\t".'<div class="col-md-12'.$sr_class.'">'.$sr_hr.'</div>'."\n";
                $result.="\t".'</div>'."\n";
                continue;
            }//if($sr_type)
            $lgs=12;
            $hidden=get_array_value($row,[0,'hidden_row'],FALSE,'bool');
            if($this->cols_no>1) {
                $lgs=round(12 / $this->cols_no,0,PHP_ROUND_HALF_DOWN);
                $result.="\t".'<div class="row'.($hidden ? ' hidden' : '').'">'."\n";
            }//if($this->cols_no>1)
            $ci=0;
            foreach($row as $col) {
                $ctrl_params=get_array_value($col,'control_params',[],'is_array');
                if(strlen($this->tags_names_sufix) && isset($ctrl_params['tag_name'])) {
                    $ctrl_params['tag_name'].=$this->tags_names_sufix;
                }
                if(!$this->CheckFieldConditions($ctrl_params,get_array_value($col,'conditions_field_name',NULL,'?is_notempty_string'))) {
                    continue;
                }
                $hiddenControl=get_array_value($ctrl_params,'hidden',get_array_value($col,'hidden',FALSE,'bool'),'bool');
                $c_type=get_array_value($col,'control_type',NULL,'?is_notempty_string');
                $csi=0;
                if($this->cols_no>1) {
                    $c_span=get_array_value($col,'colspan',1,'is_numeric');
                    if($c_span==$this->cols_no) {
                        $c_class=get_array_value($col,'class','col-md-12','is_notempty_string');
                        $csi=$this->cols_no;
                    } else {
                        $c_cols=get_array_value($col,'cols',0,'is_integer');
                        if($c_cols>0 && $c_cols<=12) {
                            $c_gs=$c_cols;
                        } else {
                            $c_gs=$c_span>1 ? $lgs * $c_span : $lgs;
                        }//if($c_cols>0 && $c_cols<=12)
                        $c_class=get_array_value($col,'class','col-md-'.$c_gs,'is_notempty_string');
                        $csi+=$c_span;
                    }//if($c_span==$this->cols_no)
                } else {
                    $c_class=get_array_value($col,'class','form-group','is_notempty_string');
                    if($hidden || $hiddenControl) {
                        $c_class.=' hidden';
                    }
                }//if($this->cols_no>1)
                $ci+=$csi;
                if(strlen($c_type)) {
                    $c_type='\NETopes\Core\Controls\\'.$c_type;
                    if(!class_exists($c_type)) {
                        NApp::Elog('Control class ['.$c_type.'] not found!');
                        $result.="\t\t".'<div class="'.$c_class.'">&nbsp;</div>'."\n";
                        continue;
                    }//if(!class_exists($c_type))
                } else {
                    $result.="\t\t".'<div class="'.$c_class.'">&nbsp;</div>'."\n";
                    continue;
                }//if(strlen($c_type))
                $ctrl_params['theme_type']=$this->theme_type;
                if(strlen($this->tags_ids_sufix) && isset($ctrl_params['tag_id'])) {
                    $ctrl_params['tag_id'].=$this->tags_ids_sufix;
                }
                // Label and input CSS columns calculation
                $ctrl_label_cols=get_array_value($ctrl_params,'label_cols',0,'is_integer');
                if($ctrl_label_cols<1 || $ctrl_label_cols>11) {
                    $c_c_lcols=get_array_value($col,'label_cols',0,'is_integer');
                    $c_lcols=$c_c_lcols>0 ? $c_c_lcols : $this->label_cols;
                    if(is_numeric($c_lcols) && $c_lcols>0) {
                        if($this->cols_no>1 && $c_span!=$this->cols_no && $c_c_lcols==0) {
                            $c_lcols=$c_lcols * $this->cols_no / ($c_span>0 ? $c_span : 1);
                            $ctrl_params['label_cols']=$c_lcols;
                        } else {
                            $ctrl_params['label_cols']=$c_lcols;
                        }//if($this->cols_no>1 && $c_span!=$this->cols_no && $c_c_lcols==0)
                    }//if(is_numeric($c_lcols) && $c_lcols>0)
                }//if($ctrl_label_cols<1 || $ctrl_label_cols>11)
                $c_cols=get_array_value($col,'control_cols',0,'is_integer');
                if($c_cols>0) {
                    $ctrl_params['cols']=$c_cols;
                }
                //$this->controls_size
                $c_size=get_array_value($col,'control_size','','is_string');
                if(strlen($c_size)) {
                    $ctrl_params['size']=$c_size;
                }
                $c_label_pos=get_array_value($ctrl_params,'labelposition',($this->positioning_type=='vertical' ? 'top' : 'left'),'is_notempty_string');
                $ctrl_params['labelposition']=$c_label_pos;
                $jsScript=trim(get_array_value($col,'js_script','','is_string'));
                if(strlen($jsScript)) {
                    $this->js_scripts[]=$jsScript;
                }
                $control=new $c_type($ctrl_params);
                if(property_exists($c_type,'tabindex') && !Validator::IsValidValue($control->tabindex,'is_not0_integer')) {
                    $control->tabindex=$ltabindex++;
                }
                if(get_array_value($col,'clear_base_class',FALSE,'bool')) {
                    $control->ClearBaseClass();
                }
                if($this->cols_no>1) {
                    $ctrl_params['container']=FALSE;
                    //form-group
                    $result.="\t\t".'<div class="'.$c_class.'">'."\n";
                    $result.=$control->Show();
                    $result.="\t\t".'</div>'."\n";
                } else {
                    $ctrl_params['class']=trim(get_array_value($ctrl_params,'class','','is_string').($hidden ? ' hidden' : ''));
                    $ctrl_params['container']=TRUE;
                    $result.=$control->Show();
                }//if($this->cols_no>1)
            }//END foreach
            if($this->cols_no>1) {
                if($ci<$this->cols_no) {
                    for($i=0; $i<($this->cols_no - $ci); $i++) {
                        $result.="\t".'<div class="col-md-'.$lgs.'"></div>'."\n";
                    }
                }
                $result.="\t".'</div>'."\n";
            }//if($this->cols_no>1)
        }//END foreach
        if(is_string($this->response_target) && strlen($this->response_target)) {
            $result.="\t".'<div class="row">'."\n";
            $result.="\t\t".'<div class="col-md-12 '.($this->base_class ? $this->base_class : 'cls').'ErrMsg" id="'.$this->response_target.'">&nbsp;</div>'."\n";
            $result.="\t".'</div>'."\n";
        }//if(is_string($this->response_target) && strlen($this->response_target))
        if(is_array($this->actions) && count($this->actions)) {
            $result.="\t".'<div class="row '.($this->base_class ? $this->base_class : 'clsForm').'Footer">'."\n";
            $result.="\t\t".'<div class="col-md-12">'."\n";
            $result.=$this->GetActions($ltabindex);
            $result.="\t\t".'</div>'."\n";
            $result.="\t".'</div>'."\n";
        }//if(is_array($this->actions) && count($this->actions))
        $result.='</div></div>'."\n";
        return $result;
    }//END protected function GetBootstrap3Control

    /**
     * Sets the output buffer value
     *
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        if(!is_array($this->content) || !count($this->content)) {
            return NULL;
        }
        switch($this->theme_type) {
            case 'bootstrap3':
            case 'bootstrap4':
                return $this->GetBootstrap3Control();
            case 'standard':
            case 'native':
            case 'table':
            default:
                return $this->GetTableControl();
        }//END switch
    }//END private function SetControl

    /**
     * Gets the output buffer content
     *
     * @return string Returns the output buffer content (html)
     * @throws \NETopes\Core\AppException
     */
    public function Show() {
        return $this->SetControl();
    }//END public function Show
}//END class BasicForm