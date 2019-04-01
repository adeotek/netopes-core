<?php
/**
 * Grid combo box control class file
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use GibberishAES;
use NApp;
use NETopes\Core\AppException;
use Translate;

/**
 * Class GridComboBox
 *
 * @package NETopes\Core\Controls
 */
class GridComboBox extends Control {
    /**
     * @var    string Data source class
     */
    public $ds_class=NULL;
    /**
     * @var    string Data adapter method name
     */
    public $ds_method=NULL;
    /**
     * @var    array Data adapter method parameters array
     */
    public $ds_params=NULL;
    /**
     * @var    array Data adapter method extra parameters array
     */
    public $ds_extra_params=NULL;
    /**
     * @var    array Grid columns array
     */
    public $columns=NULL;
    /**
     * @var    array Data grid control parameters array
     */
    public $grid_params=NULL;
    /**
     * @var    string Target for ajax calls
     */
    protected $target=NULL;
    /**
     * @var    bool Drop down state (loaded or not)
     */
    protected $loaded=FALSE;

    /**
     * GridComboBox class constructor
     *
     * @param array $params An array of params
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        $this->visible_tooltip=TRUE;
        $this->autoload=FALSE;
        parent::__construct($params);
        if(!strlen($this->data_source) || !strlen($this->ds_method) || !is_array($this->columns) || !count($this->columns)) {
            throw new AppException('Wrong GridComboBox control parameters !',E_ERROR,1);
        }//if(!strlen($this->data_source) || !strlen($this->ds_method) || !is_array($this->columns) || !count($this->columns))
        $this->target=$this->tag_id.'-gcbo-target';
    }//END public function __construct

    protected function SetControl(): ?string {
        $this->ProcessActions();
        $ar_class='';
        if($this->required===TRUE) {
            $ar_class.=(strlen($ar_class) ? ' ' : '').'clsRequiredField';
        }
        if($this->visible_tooltip===TRUE) {
            $ar_class.=(strlen($ar_class) ? ' ' : '').'clsGCBToolTip';
        }
        $lclass=$this->GetTagClass($ar_class,TRUE);
        $lalign=strlen($this->align) ? ' text-align: '.$this->align.';' : '';
        $lwidth=(is_numeric($this->width) && $this->width>0) ? ($this->width - $this->GetActionsWidth()).'px' : $this->width;
        $ccstyle=$lwidth ? ' style="width: '.$lwidth.';"' : '';
        if($this->dropdown_width) {
            $ddstyle=' style="display: none; width: '.$this->dropdown_width.(is_numeric($this->dropdown_width) ? 'px' : '').';"';
        } else {
            $ddstyle=' style="display: none;'.($lwidth ? ' width: '.$lwidth.'px;' : '').'"';
        }//if($this->dropdown_width)
        $lstyle=(strlen($this->style) || strlen($lalign)) ? ' style="'.trim($lalign.' '.$this->style).'"' : '';
        $ltabindex=(is_numeric($this->tabindex) && $this->tabindex>0) ? ' tabindex="'.$this->tabindex.'"' : '';
        $lextratagparam=strlen($this->extra_tag_params)>0 ? ' '.$this->extra_tag_params : '';
        $lonchange=strlen($this->onchange)>0 ? ' data-onchange="'.$this->onchange.'"' : '';
        $lplaceholder='';
        if(strlen($this->please_select_text)>0) {
            $lplaceholder=' placeholder="'.$this->please_select_text.'"';
        }//if(strlen($this->please_select_text)>0)
        $cclass=$this->base_class.' ctrl-container'.(strlen($this->class)>0 ? ' '.$this->class : '');
        $ddbtnclass=$this->base_class.' ctrl-dd-i-btn'.(strlen($this->class)>0 ? ' '.$this->class : '');
        if($this->disabled || $this->readonly) {
            $result='<div id="'.$this->tag_id.'-container" class="'.$cclass.'"'.$ccstyle.'>'."\n";
            $result.="\t".'<input type="hidden"'.$this->GetTagId(TRUE).' value="'.$this->selected_value.'" class="'.$lclass.($this->postable ? ' postable' : '').'">'."\n";
            $result.="\t".'<input type="text" id="'.$this->tag_id.'-cbo" value="'.$this->selected_text.'" data-value="'.$this->selected_value.'" class="'.$lclass.'"'.$lstyle.$lplaceholder.($this->disabled ? ' disabled="disabled"' : ' readonly="readonly"').$ltabindex.$lextratagparam.'>'."\n";
            $result.="\t".'<div id="'.$this->tag_id.'-ddbtn" class="'.$ddbtnclass.'"><i class="fa fa-caret-down" aria-hidden="true"></i></div>'."\n";
            $result.='</div>'."\n";
            return $result;
        }//if($this->disabled || $this->readonly)
        $cbtnclass=$this->base_class.' ctrl-clear'.(strlen($this->class) ? ' '.$this->class : '');
        $ldivclass=$this->base_class.' ctrl-dropdown';
        $dparams='';
        if(is_array($this->dynamic_params) && count($this->dynamic_params)) {
            foreach($this->dynamic_params as $dk=>$dv) {
                $dparams.="~'dynf[{$dk}]'|$dv";
            }
        }//if(is_array($this->dynamic_params) && count($this->dynamic_params))
        $dd_action=NApp::Ajax()->Prepare("{ 'control_hash': '{$this->chash}', 'method': 'ShowDropDown', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'selected_value': '{nGet:{$this->tag_id}:value}', 'qsearch': '{nGet:{$this->tag_id}-cbo:value}', 'text': '{nGet:{$this->tag_id}-cbo:attr:data-text{$dparams}}' } }",$this->target,NULL,TRUE,NULL,TRUE,"function(s){ GCBOLoader(s,'{$this->tag_id}'); }",NULL,TRUE,'ControlAjaxRequest');
        $isvalue=strlen($this->selected_value) ? $this->selected_value : NULL;
        $demptyval=strlen($this->empty_value) ? ' data-eval="'.$this->empty_value.'"' : '';
        $result='<div id="'.$this->tag_id.'-container" class="'.$cclass.'"'.$ccstyle.'>'."\n";
        $result.="\t".'<input type="hidden"'.$this->GetTagId(TRUE).' value="'.$this->selected_value.'" class="'.$lclass.($this->postable ? ' postable' : '').'"'.$lonchange.' data-text="'.($isvalue ? $this->selected_text : '').'"'.$demptyval.'>'."\n";
        $result.="\t".'<input type="text" id="'.$this->tag_id.'-cbo" value="'.($isvalue ? $this->selected_text : '').'" class="'.$lclass.'"'.$lstyle.$lplaceholder.$ltabindex.$lextratagparam.' data-value="'.$this->selected_value.'" data-ajax="'.GibberishAES::enc($dd_action,$this->tag_id).'" data-id="'.$this->tag_id.'">'."\n";
        $result.="\t".'<div id="'.$this->tag_id.'-ddbtn" class="'.$ddbtnclass.'" onclick="GCBODDBtnClick(\''.$this->tag_id.'\');"><i class="fa fa-caret-down" aria-hidden="true"></i></div>'."\n";
        $result.="\t".'<div id="'.$this->tag_id.'-clear" class="'.$cbtnclass.'" onclick="GCBOSetValue(\''.$this->tag_id.'\',null,\'\',false);"></div>'."\n";
        if($this->autoload || $isvalue) {
            $result.="\t".'<div id="'.$this->tag_id.'-dropdown" class="'.$ldivclass.'"'.$ddstyle.' data-reload="0">';
            $result.="\t\t".'<div class="gcbo-loader" style="display: none;"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i></div>'."\n";
            $result.="\t\t".'<div id="'.$this->tag_id.'-gcbo-target" class="gcbo-target">'."\n";
            if(strlen($dparams)) {
                if(NApp::ajax() && is_object(NApp::Ajax())) {
                    NApp::Ajax()->ExecuteJs($dd_action);
                } else {
                    $result.="\t"."<script type=\"text/javascript\">{$dd_action}</script>"."\n";
                }//if(NApp::ajax() && is_object(NApp::Ajax()))
            } else {
                $result.=$this->ShowDropDown(['return'=>TRUE,'selected_value'=>$isvalue]);
            }//if(strlen($dparams))
            $result.="\t\t".'</div>'."\n";
            $result.="\t".'</div>'."\n";
        } else {
            $result.="\t".'<div id="'.$this->tag_id.'-dropdown" class="'.$ldivclass.'"'.$ddstyle.' data-reload="1">'."\n";
            $result.="\t\t".'<div class="gcbo-loader" style="display: none;"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i></div>'."\n";
            $result.="\t\t".'<div id="'.$this->tag_id.'-gcbo-target" class="gcbo-target"></div>'."\n";
            $result.="\t".'</div>'."\n";
        }//if($this->autoload || $isvalue)
        $result.='</div>'."\n";
        $result.=$this->GetActions();
        return $result;
    }//END protected function SetControl

    public function ShowDropDown($params=NULL) {
        //NApp::Dlog($params,'ShowDropDown');
        if(!is_object(NApp::Ajax())) {
            throw new AppException('Wrong ajax object for GridComboBox control !',E_ERROR,1);
        }
        if(array_key_exists('params',$params)) {
            $lparams=$params['params'];
        } else {
            $lparams=$params;
        }//if(array_key_exists('params',$params))
        $qsearch_field=strlen($this->qsearch_da_param) ? $this->qsearch_da_param : 'for_text';
        $value_filter=strlen($this->value_da_param) ? $this->value_da_param : 'for_id';
        $ifilters=[];
        $s_params=[];
        $selectedvalue=get_array_value($lparams,'selected_value',NULL,'is_notempty_string');
        $qsearch=get_array_value($lparams,'qsearch','','is_string');
        $selectedtext=get_array_value($lparams,'text','','is_string');
        $dynf=get_array_value($lparams,'dynf',[],'is_array');
        foreach($dynf as $dk=>$dv) {
            if(!is_array($this->ds_params) || !array_key_exists($dk,$this->ds_params)) {
                continue;
            }
            $this->ds_params[$dk]=$dv;
        }//END foreach
        if($qsearch && $qsearch!=$selectedtext) {
            $s_params=['faction'=>'add','sessact'=>'filters','fop'=>'and','ftype'=>0,'fcond'=>'like','fvalue'=>$qsearch,'fsvalue'=>'','fdvalue'=>$qsearch,'data_type'=>''];
        } else {
            if($selectedvalue) {
                $ifilters[$value_filter]=$selectedvalue;
            }
        }//if($qsearch && $qsearch!=$selectedtext)
        $ctrl_params=[
            'module'=>$this->module,
            'method'=>$this->method,
            'persistent_state'=>get_array_value($this->grid_params,'persistent_state',FALSE,'bool'),
            'exportable'=>get_array_value($this->grid_params,'exportable',FALSE,'bool'),
            'target'=>$this->target,
            'loader'=>"function(s){ GCBOLoader(s,'{$this->tag_id}'); }",
            'alternate_row_color'=>get_array_value($this->grid_params,'alternate_row_color',TRUE,'bool'),
            'compact_mode'=>get_array_value($this->grid_params,'compact_mode',TRUE,'bool'),
            'scrollable'=>get_array_value($this->grid_params,'scrollable',FALSE,'bool'),
            'with_filter'=>get_array_value($this->grid_params,'with_filter',TRUE,'bool'),
            'with_pagination'=>get_array_value($this->grid_params,'with_pagination',TRUE,'bool'),
            'sortby'=>['column'=>$this->display_field,'direction'=>'asc'],
            'initial_filters'=>$ifilters,
            'qsearch'=>$qsearch_field,
            'ds_class'=>$this->ds_class,
            'ds_method'=>$this->ds_method,
            'ds_params'=>$this->ds_params,
            'ds_extra_params'=>$this->ds_extra_params,
            'auto_load_data'=>get_array_value($this->grid_params,'auto_load_data',TRUE,'bool'),
            'columns'=>[
                'actions'=>[
                    'type'=>'actions',
                    'width'=>'18',
                    'actions'=>[
                        [
                            'type'=>'CheckBox',
                            'params'=>['container'=>FALSE,'no_label'=>TRUE,'tag_id'=>$this->tag_id.'-{!'.$this->value_field.'!}','tooltip'=>Translate::Get('button_select'),'class'=>$this->base_class.' gcbo-selector','postable'=>FALSE,'onclick'=>"GCBOSetValue('{$this->tag_id}','{!{$this->value_field}!}','{!{$this->display_field}!}',true)",'value'=>['type'=>'eval','arg'=>"return ({!{$this->value_field}!}=='{$selectedvalue}' ? 1 : 0);"]],
                        ],
                    ],
                ],
            ],
        ];
        $ctrl_params['columns']=array_merge($ctrl_params['columns'],$this->columns);
        $datagrid=new TableView($ctrl_params);
        if(get_array_value($lparams,'return',FALSE,'bool')) {
            return $datagrid->Show($s_params);
        }
        echo $datagrid->Show($s_params);
        if(!get_array_value($lparams,'open',FALSE,'bool')) {
            return;
        }
        NApp::AddJsScript("GCBODDBtnClick('{$this->tag_id}',1);");
    }//END public function ShowDropDown
}//END class GridComboBox extends Control