<?php
/**
 * AssociationManager control class file
 * Control class for associations management
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
use NETopes\Core\AppException;
use NETopes\Core\AppSession;
use NETopes\Core\Data\DataSourceHelpers;
use NETopes\Core\Data\VirtualEntity;
use Translate;

/**
 * AssociationManager control class
 * Control class for associations management
 *
 * @package  NETopes\Controls
 */
abstract class AssociationManager {
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
     * @var bool
     */
    protected $jsInitialized=FALSE;
    /**
     * @var    bool Page hash (window.name)
     */
    public $phash=NULL;
    /**
     * @var    string Control base class
     */
    protected $base_class='';
    /**
     * @var    string Layout type: native(css grid)/bootstrap
     */
    public $layout_type=NULL;
    /**
     * @var    string Row container CSS class base
     */
    public $rowcls='';
    /**
     * @var    string Column container CSS class base
     */
    public $colcls='';
    /**
     * @var    int Associated items box width in CSS columns
     */
    public $associated_box_cols_no=5;
    /**
     * @var    string Associated items box title
     */
    public $associated_box_title='Associated items';
    /**
     * @var    string Name of ID field in the associated item list
     */
    public $associated_id_field=NULL;
    /**
     * @var    string Name of display name field in the associated item list
     */
    public $associated_name_field=NULL;
    /**
     * @var    string Name of state field in the associated item list
     */
    public $associated_state_field=NULL;
    /**
     * @var    int Assignable items box width in CSS columns
     */
    public $assignable_box_cols_no=5;
    /**
     * @var    string Assignable items box title
     */
    public $assignable_box_title='Assignable items';
    /**
     * @var    string Name of ID field in the assignable item list
     */
    public $assignable_id_field=NULL;
    /**
     * @var    string Name of display name field in the assignable item list
     */
    public $assignable_name_field=NULL;
    /**
     * @var    string Name of state field in the assignable item list
     */
    public $assignable_state_field=NULL;
    /**
     * @var    string Base tags id string
     */
    public $tag_id=NULL;
    /**
     * @var    bool Sortable associated items on/off
     */
    public $sortable=FALSE;
    /**
     * @var    bool Allow multiple associations for one element
     */
    public $allow_multi_assoc=TRUE;
    /**
     * @var    bool Display live version box on/off
     */
    public $show_live_version=FALSE;
    /**
     * @var    int Live version associated items box width in CSS columns
     */
    public $live_version_box_cols_no=2;
    /**
     * @var    string Live version associated items box title
     */
    public $live_version_box_title='Live version';
    /**
     * @var bool Sets if this control should have filters
     */
    public $with_filter=FALSE;

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
     * AssociationManager class constructor method
     *
     * @param array $params Parameters array
     * @return void
     */
    public function __construct($params=NULL) {
        $this->chash=AppSession::GetNewUID();
        $this->uid=AppSession::GetNewUID('','md5');
        $this->base_class='cls'.get_class_basename(__CLASS__);
        $this->theme_type=is_object(NApp::$theme) ? NApp::$theme->GetThemeType() : 'bootstrap3';
        $this->btn_size=NApp::$theme->GetButtonsDefaultSize() ? 'brn-'.NApp::$theme->GetButtonsDefaultSize() : '';
        if(is_array($params) && count($params)) {
            if(!is_array($this->pdata)) {
                $this->pdata=[];
            }
            foreach($params as $k=>$v) {
                if(property_exists($this,$k)) {
                    $this->$k=$v;
                } else {
                    $this->pdata[$k]=$v;
                }
            }//foreach ($params as $k=>$v)
        }//if(is_array($params) && count($params))
        if(!is_string($this->tag_id) || !strlen($this->tag_id)) {
            $this->tag_id=date('siHdmY');
        }
        $this->lis_box_tagid=$this->tag_id.'-lis-list';
        $this->sis_box_tagid=$this->tag_id.'-sis-list';
        $this->ais_box_tagid=$this->tag_id.'-ais-list';
        if(!is_string($this->associated_id_field) || !strlen($this->associated_id_field)) {
            $this->associated_id_field='id';
        }
        if(!is_string($this->associated_name_field) || !strlen($this->associated_name_field)) {
            $this->associated_name_field='name';
        }
        if(!is_string($this->associated_state_field) || !strlen($this->associated_state_field)) {
            $this->associated_state_field=NULL;
        }
        if(!is_string($this->assignable_id_field) || !strlen($this->assignable_id_field)) {
            $this->assignable_id_field='id';
        }
        if(!is_string($this->assignable_name_field) || !strlen($this->assignable_name_field)) {
            $this->assignable_name_field='name';
        }
        if(!is_string($this->assignable_state_field) || !strlen($this->assignable_state_field)) {
            $this->assignable_state_field=NULL;
        }
        switch(strtolower($this->layout_type)) {
            case 'bootstrap2':
            case 'bootstrap3':
            case 'bootstrap4':
                $this->rowcls='';
                $this->colcls='col-md-';
                break;
            default:
                $this->rowcls='row';
                $this->colcls='col-md-';
                break;
        }//END switch
        if(!strlen($this->sort_module) || !strlen($this->sort_method)) {
            $this->sortable=FALSE;
        }
    }//END public function __construct

    /**
     * @param string $tagId
     */
    protected function GetFilterJs(string $tagId) {
        $this->GetFilterHelperJs();
        $funcSufix='_'.str_replace('-','_',$tagId);
        $js="
            let assocManagerFilterElements{$funcSufix} = function(t) {
                let thisFilterValue = GetSlug($(t).val());
                if(!thisFilterValue) {
                    $('#{$tagId} li.am-element').show();
                } else {
                    $('#{$tagId} li.am-element').hide();
                    $('#{$tagId} li.am-element.is-filterable[data-search*=\"'+thisFilterValue+'\"]').show();
                }
            };
            $('#filter-{$tagId}').on('keyup',function(){
                assocManagerFilterElements{$funcSufix}(this);
            });
            $('#filter-{$tagId}').focusout(function(){
                assocManagerFilterElements{$funcSufix}(this);
            });
        ";
        NApp::AddJsScript($js);
    }//END protected function GetFilterJs

    /**
     * @param string $tagId
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetItemsFilter(string $tagId): string {
        $placeholder=Translate::Get('filter_items');
        $html=<<<HTML
        <div class="filter-input-holder">
            <input id="filter-{$tagId}" type="text" placeholder="{$placeholder}" value="" />
            <label alt="{$placeholder}" placeholder="{$placeholder}"></label>
        </div>
HTML;
        $this->GetFilterJs($tagId);
        return $html;
    }//END protected function GetItemsFilter

    /**
     * Get associated items actions HTML
     *
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetAssociatedItemsActions() {
        $result="\t\t\t".'<div class="subFormActions clearfix">'."\n";
        $btn_sel=new Button(['tag_id'=>$this->tag_id.'-sis-sel-all','class'=>(is_object(NApp::$theme) ? NApp::$theme->GetBtnInfoClass($this->btn_size) : 'btn btn-info btn-xxs'),'value'=>Translate::Get('button_select_all')]);
        $result.="\t\t\t\t".$btn_sel->Show()."\n";
        $btn_desel=new Button(['tag_id'=>$this->tag_id.'-sis-desel-all','class'=>(is_object(NApp::$theme) ? NApp::$theme->GetBtnDefaultClass($this->btn_size) : 'btn btn-default btn-xxs'),'value'=>Translate::Get('button_deselect_all')]);
        $result.="\t\t\t\t".$btn_desel->Show()."\n";
        $result.=$this->GetDeAssignItemsAction();
        $result.="\t\t\t".'</div>'."\n";
        return $result;
    }//END protected function GetAssociatedItemsActions

    /**
     * Sets associated items javascript actions
     *
     * @return void
     */
    protected function SetAssociatedItemsJs() {
        $sis_js=<<<JS
            $('#{$this->tag_id}-sis-sel-all').on('click',function() {
			    $('#{$this->sis_box_tagid}').find('li').each(function(){
				    $(this).find('input[type=image].clsCheckBox').val(0);
				});
				$('#{$this->sis_box_tagid}').find('li:visible').each(function(){
				    $(this).find('input[type=image].clsCheckBox').val(1);
				});
			});
			$('#{$this->tag_id}-sis-desel-all').on('click',function() {
				$('#{$this->sis_box_tagid}').find('li').each(function(){
				    $(this).find('input[type=image].clsCheckBox').val(0);
				});
			});
JS;
        if($this->sortable) {
            $sis_js.="
                $('#{$this->sis_box_tagid}').sortable({
                    placeholder: 'ui-state-highlight',
                    update: function(event,ui) {
                        var elid = $(ui.item).attr('id');
                        var previd = 0;
                        var newindex = $(ui.item).index();
                        if(newindex>0) { previd = $(ui.item).prev().attr('id'); }
                        ".NApp::Ajax()->Prepare("{ 'module': '{$this->sort_module}', 'method': '{$this->sort_method}', 'params': { 'id': elid, 'after_id': previd,'target': '{$this->sort_target}' !}",'errors',['elid','previd'])."
                    }
                });
                $('#{$this->sis_box_tagid}').disableSelection();
            ";
        }//if($this->sortable)
        NApp::AddJsScript($sis_js);
    }//END protected function SetAssociatedItemsJs

    /**
     * Get associated item display name
     *
     * @return string Returns associated item name
     */
    protected function GetAssociatedItemName($row) {
        return $row->getProperty($this->associated_name_field,'N/A','is_string');
    }//END protected function GetAssociatedItemName

    /**
     * Get associated item
     *
     * @return string Returns associated item HTML
     * @throws \NETopes\Core\AppException
     */
    protected function GetAssociatedItem($row) {
        $liclass='ui-state-default am-element';
        $item_id=$row->getProperty($this->associated_id_field,'','isset');
        $item_name=$this->GetAssociatedItemName($row);
        $itclass='';
        $filterData='';
        if(is_string($this->associated_state_field) && strlen($this->associated_state_field) && $row->getProperty($this->associated_state_field,0,'is_integer')<=0) {
            $itclass='inactive';
            $ckbTag="\t\t\t\t\t\t".'<span class="blank-checkbox"></span>'."\n";
        } else {
            $liclass.=(strlen($this->associated_item_class) ? (strlen($liclass) ? ' ' : '').$this->associated_item_class : '');
            if($this->with_filter) {
                $liclass.=(strlen($liclass) ? ' ' : '').'is-filterable';
                $filterData=$this->GetItemFilterData($item_name);
            }//if($this->with_filter)
            $ckb_sel=new CheckBox(['container'=>FALSE,'no_label'=>TRUE,'tag_id'=>$this->tag_id.'-sis-sel-'.$item_id,'tag_name'=>$item_id,'value'=>0,'class'=>'FInLine']);
            $ckbTag="\t\t\t\t\t\t".$ckb_sel->Show()."\n";
        }//if(is_string($this->associated_state_field) && strlen($this->associated_state_field) && $row->getProperty($this->associated_state_field,0,'is_integer')<=0)
        $result="\t\t\t\t\t".'<li class="'.$liclass.'" id="'.$item_id.'"'.$filterData.'>'."\n";
        $result.=$ckbTag;
        if($this->sortable) {
            $result.="\t\t\t\t\t".'<span class="ui-icon ui-icon-arrowthick-2-n-s"></span>'."\n";
        }//if($this->sortable)
        $result.="\t\t\t\t\t\t".'<span class="txt'.$itclass.'">'.$item_name.'</span>'."\n";
        $result.="\t\t\t\t\t".'</li>'."\n";
        return $result;
    }//END protected function GetAssociatedItem

    /**
     * Get associated items summary
     *
     * @param array $data Data item array
     * @return string Returns associated summary box HTML
     */
    protected function GetAssociatedItemsSummary($data,$extra=NULL) {
        $items_no=is_iterable($data) ? count($data) : 0;
        $result="\t\t\t\t".'<div class="subFormSummary">'."\n";
        $result.="\t\t\t\t\t".'<span class="count">'.$items_no.'</span>'."\n";
        $result.="\t\t\t\t\t".'<label>&nbsp;'.Translate::Get('label_items').'</label>'."\n";
        $result.="\t\t\t\t".'</div>'."\n";
        return $result;
    }//END protected function GetAssociatedItemsSummary

    /**
     * Get associated items box
     *
     * @return string Returns associated box HTML
     * @throws \NETopes\Core\AppException
     */
    protected function GetAssociatedItemsBox() {
        try {
            $items=$this->LoadAssociatedItems();
        } catch(AppException $e) {
            NApp::Elog($e);
            $items=[];
        }//END try
        $items=DataSourceHelpers::ConvertResultsToDataSet($items,VirtualEntity::class);
        $result="\t\t\t".'<div class="clsBlock clsAssociatedItems">'."\n";
        $result.="\t\t\t\t".'<span class="clsBoxTitle">'.$this->associated_box_title.'</span>'."\n";
        $result.=$this->GetAssociatedItemsSummary($items);
        $result.=$this->GetAssociatedItemsActions();
        if($this->with_filter) {
            $result.=$this->GetItemsFilter($this->sis_box_tagid);
        }
        $result.="\t\t\t\t".'<div class="subFormMsg msgErrors" id="'.$this->tag_id.'-sis-errors">&nbsp;</div>'."\n";
        $result.="\t\t\t\t".'<ul id="'.$this->sis_box_tagid.'" class="items '.($this->sortable ? ' sortable' : '').'">'."\n";
        if(is_iterable($items) && count($items)) {
            foreach($items as $v) {
                $result.=$this->GetAssociatedItem($v);
            }
            $this->SetAssociatedItemsJs();
        } else {
            $result.="\t\t".'<li class="bold ErrorMsg">'.Translate::Get('label_empty_list').'</li>'."\n";
        }//if(is_iterable($items) && count($items))
        $result.="\t\t\t\t".'</ul>'."\n";
        $result.="\t\t\t".'</div>'."\n";
        return $result;
    }//END public function GetAssociatedItemsBox

    /**
     * Get assignable items actions HTML
     *
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetAssignableItemsActions() {
        $result="\t\t\t".'<div class="subFormActions clearfix">'."\n";
        $btn_sel=new Button(['tag_id'=>$this->tag_id.'-ais-sel-all','class'=>(is_object(NApp::$theme) ? NApp::$theme->GetBtnInfoClass($this->btn_size) : 'btn btn-info btn-xxs'),'value'=>Translate::Get('button_select_all')]);
        $result.="\t\t\t\t".$btn_sel->Show()."\n";
        $btn_desel=new Button(['tag_id'=>$this->tag_id.'-ais-desel-all','class'=>(is_object(NApp::$theme) ? NApp::$theme->GetBtnDefaultClass($this->btn_size) : 'btn btn-default btn-xxs'),'value'=>Translate::Get('button_deselect_all')]);
        $result.="\t\t\t\t".$btn_desel->Show()."\n";
        $result.=$this->GetAssignItemsAction();
        $result.="\t\t\t".'</div>'."\n";
        return $result;
    }//END protected function GetAssignableItemsActions

    /**
     * Sets assignable items javascript actions
     *
     * @return void
     */
    protected function SetAssignableItemsJs() {
        $ais_js=<<<JS
			$('#{$this->tag_id}-ais-sel-all').on('click',function() {
				$('#{$this->ais_box_tagid}').find('li').each(function(){
					$(this).find('input[type=image].clsCheckBox').val(0);
				});
				$('#{$this->ais_box_tagid}').find('li:visible').each(function(){
					$(this).find('input[type=image].clsCheckBox').val(1);
				});
			});
			$('#{$this->tag_id}-ais-desel-all').on('click',function() {
				$('#{$this->ais_box_tagid}').find('li').each(function(){
					$(this).find('input[type=image].clsCheckBox').val(0);
				});
			});
JS;
        NApp::AddJsScript($ais_js);
    }//END protected function SetAssignableItemsJs

    /**
     * Get associated item display name
     *
     * @param $row
     * @return string Returns associated item name
     */
    protected function GetAssignableItemName($row) {
        return $row->getProperty($this->assignable_name_field,'N/A','is_string');
    }//END protected function GetAssignableItemName

    /**
     * Get assignable item
     *
     * @param $row
     * @return string Returns assignable item HTML
     */
    protected function GetAssignableItem($row) {
        $liclass='ui-state-default am-element';
        $item_id=$row->getProperty($this->assignable_id_field,'','isset');
        $is_associated=$row->getProperty('assoc',0,'is_numeric')==1;
        if($this->allow_multi_assoc===FALSE && $is_associated) {
            return '';
        }
        $item_name=$this->GetAssignableItemName($row);
        $itclass='txt'.($is_associated ? ' associated' : '');
        $filterData='';
        if(is_string($this->assignable_state_field) && strlen($this->assignable_state_field) && $row->getProperty($this->assignable_state_field,0,'is_integer')<=0) {
            $itclass.=(strlen($itclass) ? ' ' : '').'inactive';
            $ckbTag="\t\t\t\t\t\t".'<span class="blank-checkbox"></span>'."\n";
        } else {
            $liclass.=(strlen($this->assignable_item_class) ? (strlen($liclass) ? ' ' : '').$this->assignable_item_class : '');
            if($this->with_filter) {
                $liclass.=(strlen($liclass) ? ' ' : '').'is-filterable';
                $filterData=$this->GetItemFilterData($item_name);
            }//if($this->with_filter)
            $ckb_sel=new CheckBox(['container'=>FALSE,'no_label'=>TRUE,'tag_id'=>$this->tag_id.'-ais-sel-'.$item_id,'tag_name'=>$item_id,'value'=>0,'class'=>'FInLine']);
            $ckbTag="\t\t\t\t\t\t".$ckb_sel->Show()."\n";
        }//if(is_string($this->assignable_state_field) && strlen($this->assignable_state_field) && $row->getProperty($this->assignable_state_field,0,'is_integer')<=0)
        $result="\t\t\t\t\t".'<li class="'.$liclass.'" id="'.$item_id.'"'.$filterData.'>'."\n";
        $result.=$ckbTag;
        $result.="\t\t\t\t\t\t".'<span class="'.$itclass.'">'.$item_name.'</span>'."\n";
        $result.="\t\t\t\t\t".'</li>'."\n";
        return $result;
    }//END protected function GetAssignableItem

    /**
     * Get assignable items summary
     *
     * @param array $data Data item array
     * @return string Returns assignable summary box HTML
     */
    protected function GetAssignableItemsSummary($data,$extra=NULL) {
        $items_no=is_iterable($data) ? count($data) : 0;
        $result="\t\t\t\t".'<div class="subFormSummary">'."\n";
        $result.="\t\t\t\t\t".'<span class="count">'.$items_no.'</span>'."\n";
        $result.="\t\t\t\t\t".'<label>&nbsp;'.Translate::Get('label_items').'</label>'."\n";
        $result.="\t\t\t\t".'</div>'."\n";
        return $result;
    }//END protected function GetAssignableItemsSummary

    /**
     * Get assignable items box
     *
     * @return string Returns assignable box HTML
     * @throws \NETopes\Core\AppException
     */
    protected function GetAssignableItemsBox() {
        try {
            $items=$this->LoadAssignableItems();
        } catch(AppException $e) {
            NApp::Elog($e);
            $items=[];
        }//END try
        $items=DataSourceHelpers::ConvertResultsToDataSet($items,VirtualEntity::class);
        $result="\t\t\t".'<div class="clsBlock clsAssignableItems">'."\n";
        $result.="\t\t\t\t".'<span class="clsBoxTitle">'.$this->assignable_box_title.'</span>'."\n";
        $result.=$this->GetAssignableItemsSummary($items);
        $result.=$this->GetAssignableItemsActions();
        if($this->with_filter) {
            $result.=$this->GetItemsFilter($this->ais_box_tagid);
        }
        $result.="\t\t\t\t".'<div class="subFormMsg msgErrors clearfix" id="'.$this->tag_id.'-ais-errors">&nbsp;</div>'."\n";
        $result.="\t\t\t\t".'<ul id="'.$this->ais_box_tagid.'" class="items">'."\n";
        if(is_iterable($items) && count($items)) {
            foreach($items as $v) {
                $result.=$this->GetAssignableItem($v);
            }
            $this->SetAssignableItemsJs();
        } else {
            $result.="\t\t\t\t".'<li class="bold ErrorMsg">'.Translate::Get('label_empty_list').'</li>'."\n";
        }//if(is_iterable($items) && count($items))
        $result.="\t\t\t\t".'</ul>'."\n";
        $result.="\t\t\t".'</div>'."\n";
        return $result;
    }//END public function GetAssignableItemsBox

    /**
     * Get live version associated item
     *
     * @return string Returns live version associated item HTML
     */
    protected function GetLiveVersionItem($row) {
        $item_id=$row->getProperty('id','','is_numeric');
        $item_name=$this->GetAssociatedItemName($row);
        $liclass=strlen($this->associated_item_class) ? ' '.$this->associated_item_class : '';
        $itclass=$row->getProperty($this->associated_state_field,0,'is_numeric')<=0 ? ' inactive' : '';
        $result="\t\t\t\t\t".'<li class="ui-state-default'.$liclass.'" id="'.$item_id.'">'."\n";
        $result.="\t\t\t\t\t\t".'<span class="txt'.$itclass.'">'.$item_name.'</span>'."\n";
        $result.="\t\t\t\t\t".'</li>'."\n";
        return $result;
    }//END protected function GetLiveVersionItem

    /**
     * Get live version associated items summary
     *
     * @param array $data Data item array
     * @return string Returns live version associated summary box HTML
     */
    protected function GetLiveVersionItemsSummary($data,$extra=NULL) {
        return $this->GetAssociatedItemsSummary($data,$extra);
    }//END protected function GetLiveVersionItemsSummary

    /**
     * Get live version associated items box
     *
     * @return string Returns associated box HTML
     */
    protected function GetLiveVersionItemsBox() {
        try {
            $items=$this->LoadLiveVersionItems();
        } catch(AppException $e) {
            NApp::Elog($e);
            $items=[];
        }//END try
        if($items===FALSE) {
            return NULL;
        }
        $items=DataSourceHelpers::ConvertResultsToDataSet($items,VirtualEntity::class);
        $result="\t\t\t".'<div class="clsBlock clsLiveVersionItems">'."\n";
        $result.="\t\t\t\t".'<span class="clsBoxTitle">'.$this->live_version_box_title.'</span>'."\n";
        $result.=$this->GetLiveVersionItemsSummary($items);
        $result.="\t\t\t\t".'<div class="subFormActions empty"></div>'."\n";
        $result.="\t\t\t\t".'<div class="subFormMsg msgErrors" id="'.$this->tag_id.'-lis-errors">&nbsp;</div>'."\n";
        $result.="\t\t\t\t".'<ul id="'.$this->lis_box_tagid.'" class="items">'."\n";
        if(is_iterable($items) && count($items)) {
            foreach($items as $v) {
                $result.=$this->GetLiveVersionItem($v);
            }
        } else {
            $result.="\t\t\t\t\t".'<li class="bold ErrorMsg">'.Translate::Get('label_empty_list').'</li>'."\n";
        }//if(is_iterable($items) && count($items))
        $result.="\t\t\t\t".'</ul>'."\n";
        $result.="\t\t\t".'</div>'."\n";
        return $result;
    }//END public function GetLiveVersionItemsBox

    /**
     * Sets the output buffer value
     *
     * @return string Returns the complete HTML for the control
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        $live_box=$this->GetLiveVersionItemsBox();
        $result='<div class="'.$this->rowcls.' '.$this->base_class.' clsPanel">'."\n";
        $result.="\t".'<div class="clsDivTable">'."\n";
        if($this->show_live_version && strlen($live_box)) {
            $sis_cols=$ais_cols=$lv_cols=4;
            if(is_numeric($this->live_version_box_cols_no) && $this->live_version_box_cols_no>0 && $this->live_version_box_cols_no<=10) {
                $lv_cols=$this->live_version_box_cols_no;
                $sis_cols=$ais_cols=ceil((12 - $lv_cols) / 2);
            }//if(is_numeric($this->live_version_box_cols_no) && $this->live_version_box_cols_no>0  && $this->live_version_box_cols_no<=10)
            $fcol_class=' clsMiddlePanel';
            $result.="\t\t".'<div class="'.$this->colcls.$lv_cols.' clsDivTableCell clsLeftPanel" id="'.$this->tag_id.'-lis">'."\n";
            $result.=$live_box;
            $result.="\t\t".'</div>'."\n";
        } else {
            $lv_cols=0;
            $sis_cols=$ais_cols=6;
            $fcol_class=' clsLeftPanel';
        }//if($this->show_live_version && strlen($live_box))
        $fixed_sis_cols=FALSE;
        if(is_numeric($this->associated_box_cols_no) && $this->associated_box_cols_no>0 && $this->associated_box_cols_no<12) {
            $sis_cols=$this->associated_box_cols_no;
            $ais_cols=12 - $lv_cols - $sis_cols;
            $fixed_sis_cols=TRUE;
        }//if(is_numeric($this->associated_box_cols_no) && $this->associated_box_cols_no>0  && $this->associated_box_cols_no<=10)
        if(is_numeric($this->assignable_box_cols_no) && $this->assignable_box_cols_no>0 && $this->assignable_box_cols_no<12) {
            $ais_cols=$this->assignable_box_cols_no;
            if(!$fixed_sis_cols) {
                $sis_cols=12 - $lv_cols - $sis_cols;
            }
        }//if(is_numeric($this->assignable_box_cols_no) && $this->assignable_box_cols_no>0  && $this->assignable_box_cols_no<=10)
        $result.="\t\t".'<div class="'.$this->colcls.$sis_cols.' clsDivTableCell'.$fcol_class.'" id="'.$this->tag_id.'-sis">'."\n";
        $result.=$this->GetAssociatedItemsBox();
        $result.="\t\t".'</div>'."\n";
        $result.="\t\t".'<div class="'.$this->colcls.$ais_cols.' clsDivTableCell clsRightPanel" id="'.$this->tag_id.'-ais">'."\n";
        $result.=$this->GetAssignableItemsBox();
        $result.="\t\t".'</div>'."\n";
        $result.="\t".'</div>'."\n";
        $result.='</div>'."\n";
        return $result;
    }//END private function SetControl

    /**
     * Gets the output buffer content
     *
     * @param bool $output
     * @return string|null Returns or outputs the content (html)
     * @throws \NETopes\Core\AppException
     */
    public function Show($output=FALSE) {
        if(!$output) {
            return $this->SetControl();
        }
        echo $this->SetControl();
        return NULL;
    }//END public function Show

    /**
     * @param string $itemName
     * @return string
     */
    protected function GetItemFilterData(string $itemName): string {
        if(!$this->with_filter) {
            return '';
        }
        return ' data-search="'.self::GetSlugForString($itemName).'"';
    }//END protected function GetItemFilterData

    /**
     * Method used to gather the helper js function used by filters
     *
     * @param bool $return
     * @return string|null
     */
    protected function GetFilterHelperJs(bool $return=FALSE): ?string {
        if($this->jsInitialized) {
            return NULL;
        }
        $js=<<<JS
        function GetSlug(text) {
          return text.toString().toLowerCase()
            .replace(/\s+/g, '-')           // Replace spaces with -
            .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
            .replace(/\-\-+/g, '-')         // Replace multiple - with single -
            .replace(/^-+/, '')             // Trim - from start of text
            .replace(/-+$/, '');            // Trim - from end of text
        }
JS;
        if($return) {
            return $return;
        }
        NApp::AddJsScript($js);
        $this->jsInitialized=TRUE;
        return NULL;
    }//END protected function GetFilterHelperJs

    /**
     * Helper method used to generate valid url slug
     *
     * @param string $text
     * @return string
     */
    protected static function GetSlugForString(string $text): string {
        // remove html tags
        $text=strip_tags($text);
        // replace non letter or digits by -
        $text=preg_replace('~[^\pL\d]+~u','-',$text);
        // transliterate
        $text=mb_convert_encoding($text,'us-ascii');
        // remove unwanted characters
        $text=preg_replace('~[^-\w]+~','',$text);
        // trim
        $text=trim($text,'-');
        // remove duplicate -
        $text=preg_replace('~-+~','-',$text);
        // lowercase
        $text=strtolower($text);
        if(!is_string($text) || !strlen($text)) {
            return 'n-a';
        }
        return $text;
    }//END protected function GetSlugForString

    /**
     * Load live version associated items
     *
     * @return array|bool Returns live version associated items array
     */
    protected function LoadLiveVersionItems() {
        return FALSE;
    }//END protected function LoadLiveVersionItems

    /**
     * Load associated items
     *
     * @return array Returns associated items array
     */
    abstract protected function LoadAssociatedItems();

    /**
     * Load assignable items
     *
     * @return array Returns associated items array
     */
    abstract protected function LoadAssignableItems();

    /**
     * Get assign item(s) action button
     *
     * @return string Assign item(s) action button HTML
     */
    abstract protected function GetAssignItemsAction();

    /**
     * Get de-assign item(s) action button
     *
     * @return string De-assign item(s) action button HTML
     */
    abstract protected function GetDeAssignItemsAction();
}//END abstract class AssociationManager