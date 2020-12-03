<?php
/**
 * ComboBox control class file
 * Standard ComboBox control
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NApp;
use NETopes\Core\AppSession;

/**
 * Class TreeComboBox
 *
 * @property mixed       dropdown_width
 * @property string|null please_select_text
 * @property mixed       hide_parents_checkbox
 * @property mixed       selected_value
 * @property bool|null   icon
 * @property string|null selected_text
 * @package  NETopes\Controls
 */
class TreeComboBox extends Control {
    /**
     * @var    array|null Data source configuration
     */
    public $data_source=NULL;
    /**
     * @var    bool Encrypt url parameters
     */
    public $encrypted=NULL;

    /**
     * Control class constructor
     *
     * @param array $params An array of params
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        parent::__construct($params);
    }//END public function __construct

    /**
     * Set control HTML string
     *
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        $this->ProcessActions();
        $textAlign=strlen($this->align)>0 ? ' text-align: '.$this->align.';' : '';
        $width=(is_numeric($this->width) && $this->width>0) ? $this->width - $this->GetActionsWidth() : NULL;
        $containerCssStyle=$width ? ' style="width: '.$width.'px;"' : '';
        if($this->dropdown_width) {
            $dropdownCssStyle=' style="display: none; width: '.$this->dropdown_width.(is_numeric($this->dropdown_width) ? 'px' : '').';"';
        } else {
            $dropdownCssStyle=' style="display: none;'.($width ? ' width: '.$width.'px;' : '').'"';
        }//if($this->dropdown_width)
        $cssStyle=strlen($this->style) ? ' style="'.$textAlign.' '.$this->style.'"' : '';
        $tabindex=(is_numeric($this->tabindex) && $this->tabindex>0) ? ' tabindex="'.$this->tabindex.'"' : '';
        $extraTagParams=strlen($this->extra_tag_params) ? ' '.$this->extra_tag_params : '';
        $onChange=strlen($this->onchange) ? ' data-onchange="'.$this->onchange.'"' : '';
        $placeholder='';
        if(strlen($this->please_select_text)>0) {
            $placeholder=' placeholder="'.$this->please_select_text.'"';
        }//if(strlen($this->please_select_text)>0)
        $requiredClass=$this->required===TRUE ? ' clsRequiredField' : '';
        $cssClass=$this->base_class.(strlen($this->class)>0 ? ' '.$this->class : '').$requiredClass;
        switch($this->theme_type) {
            case 'bootstrap2':
            case 'bootstrap3':
            case 'bootstrap4':
                $cssClass.=' form-control';
                break;
            default:
                break;
        }//END switch
        $containerClass=$this->base_class.' ctrl-container'.(strlen($this->class) ? ' '.$this->class : '');
        $dropdownBtnClass=$this->base_class.' ctrl-dd-i-btn'.(strlen($this->class) ? ' '.$this->class : '');
        if($this->disabled || $this->readonly) {
            $result='<div class="'.$containerClass.'"'.$containerCssStyle.'>'."\n";
            $result.="\t".'<input type="hidden"'.$this->GetTagId(TRUE).' value="'.$this->selected_value.'" class="'.$cssClass.($this->postable ? ' postable' : '').'">'."\n";
            $result.="\t".'<input type="text" id="'.$this->tag_id.'-cbo" value="'.$this->selected_text.'" class="'.$cssClass.'"'.$cssStyle.$placeholder.($this->disabled ? ' disabled="disabled"' : ' readonly="readonly"').$tabindex.$extraTagParams.'>'."\n";
            $result.="\t".'<div class="'.$dropdownBtnClass.'"><i class="fa fa-caret-down" aria-hidden="true"></i></div>'."\n";
            $result.='</div>'."\n";
            return $result;
        } else {
            $cssClass=trim($cssClass.' stdro');
        }//if($this->disabled || $this->readonly)
        $clearBtnClass=$this->base_class.' ctrl-clear'.(strlen($this->class) ? ' '.$this->class : '');
        $treeContainerClass=$this->base_class.' ctrl-ctree'.(strlen($this->class) ? ' '.$this->class : '');
        $dropdownContainerClass=$this->base_class.' ctrl-dropdown';
        $result='<div class="'.$containerClass.'"'.$containerCssStyle.'>'."\n";
        $result.="\t".'<input type="hidden"'.$this->GetTagId(TRUE).' value="'.$this->selected_value.'" class="'.$cssClass.($this->postable ? ' postable' : '').'"'.$onChange.'>'."\n";
        $result.="\t".'<input type="text" id="'.$this->tag_id.'-cbo" value="'.$this->selected_text.'" class="'.$cssClass.'"'.$cssStyle.$placeholder.' readonly="readonly"'.$tabindex.$extraTagParams.' data-value="'.$this->selected_value.'">'."\n";
        $result.="\t".'<div class="'.$dropdownBtnClass.'"><i class="fa fa-caret-down" aria-hidden="true"></i></div>'."\n";
        $result.="\t".'<div class="'.$clearBtnClass.'"></div>'."\n";
        $result.="\t".'<div class="'.$dropdownContainerClass.'"'.$dropdownCssStyle.'>';
        $result.="\t\t".'<div id="'.$this->tag_id.'-ctree" class="'.$treeContainerClass.'"></div>';
        $result.="\t".'</div>'."\n";
        $result.='</div>'."\n";
        $dsModule=get_array_value($this->data_source,'ds_class','','is_string');
        $dsMethod=get_array_value($this->data_source,'ds_method','','is_string');
        if(strlen($dsModule) && strlen($dsMethod)) {
            $dsModule=convert_from_camel_case($dsModule);
            $dsMethod=convert_from_camel_case($dsMethod);
            $urlParams='';
            $dsParams=get_array_value($this->data_source,'ds_params',[],'is_array');
            if(count($dsParams)) {
                foreach($dsParams as $pk=>$pv) {
                    $urlParams.='&'.$pk.'='.$pv;
                }//END foreach
            }//if(count($dsParams))
            $urlJsParams='';
            $dsJsParams=get_array_value($this->data_source,'ds_js_params',[],'is_array');
            if(count($dsJsParams)) {
                foreach($dsJsParams as $k=>$v) {
                    $urlJsParams.=(strlen($urlJsParams) ? ', ' : '').$k.": '".addcslashes($v,"'")."'";
                }//END foreach
            }//if(count($dsJsParams))
            $this->encrypted=$this->encrypted ? 1 : 0;
            $this->hide_parents_checkbox=$this->hide_parents_checkbox ? TRUE : FALSE;
            AppSession::SetSessionAcceptedRequest($this->uid,NApp::$currentNamespace);
            NApp::AddJsScript("$('#{$this->tag_id}').NetopesTreeComboBox({
                value: '{$this->selected_value}',
                ajaxUrl: nAppBaseUrl+'/aindex.php?namespace=".NApp::$currentNamespace."&uid={$this->uid}',
                module: '{$dsModule}',
                method: '{$dsMethod}',
                urlParams: '{$urlParams}',
                jsParams: { {$urlJsParams} },
                encrypt: ".($this->encrypted ? 'true' : 'false').",
                hideParentsCheckbox: ".($this->hide_parents_checkbox ? 'true' : 'false').",
                useIcons: ".($this->icon ? 'true' : 'false').",
                onChange: ".(strlen($this->onchange) ? '"'.$this->onchange.'"' : 'false').",
                disabled: ".($this->disabled_on_render ? 'true' : 'false')."
             });");
        }//if(strlen($dsModule) && strlen($dsMethod))
        $result.=$this->GetActions();
        return $result;
    }//END protected function SetControl
}//END class TreeComboBox extends Control