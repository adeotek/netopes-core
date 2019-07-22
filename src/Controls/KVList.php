<?php
/**
 * KVList class file
 * File containing KVList control class
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use Exception;
use NApp;
use NETopes\Core\AppSession;
use NETopes\Core\Data\DataSourceHelpers;
use NETopes\Core\Data\VirtualEntity;
use Translate;

/**
 * Class KVList
 *
 * @property mixed value
 * @package  Hinter\NETopes\Controls
 */
class KVList extends Control {
    /**
     * @var bool
     */
    protected $postable_elements=TRUE;
    /**
     * @var mixed|null
     */
    public $lang_items=NULL;
    /**
     * @var null
     */
    public $lang_ds=NULL;

    /**
     * KVList constructor.
     *
     * @param null $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        parent::__construct($params);
        if(!$this->postable) {
            $this->postable_elements=FALSE;
        } else {
            $this->postable=FALSE;
        }
        if(!strlen($this->tag_id)) {
            $this->tag_id=AppSession::GetNewUID('KVList');
        }
        if(!strlen($this->tag_name)) {
            $this->tag_name=strlen($this->tag_id) ? $this->tag_id : '';
        }
        if(is_array($this->lang_items)) {
            $this->lang_items=DataSourceHelpers::ConvertResultsToDataSet($this->lang_items,VirtualEntity::class);
        }
    }//END public function __construct

    /**
     * @param bool $with_translations
     * @param null $values
     * @param null $lang
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function SetControlInstance($with_translations=FALSE,$values=NULL,$lang=NULL) {
        $this->ProcessActions();
        $lvalues=(is_null($values) ? $this->value : $values);
        if(is_string($lvalues) && strlen($lvalues)) {
            try {
                $lvalues=@json_decode($lvalues,TRUE);
            } catch(Exception $e) {
                NApp::Elog($e);
                $lvalues=[];
            }//END try
        }//if(is_string($lvalues) && strlen($lvalues))
        if($with_translations) {
            if(is_object($lang)) {
                $tsufix='-'.$lang->getProperty('code');
                $pkey='['.$lang->getProperty('id').']';
            } else {
                $tsufix='-def';
                $pkey='[0]';
            }//if(is_object($lang))
        } else {
            $tsufix='';
            $pkey='';
        }//if($with_translations)
        $result='<div'.$this->GetTagId(TRUE,$tsufix).$this->GetTagClass('MainKVL').$this->GetTagAttributes().'>'."\n";
        $result.="\t".'<input type="text" class="KVLNewKey" data-name="'.$this->tag_name.$pkey.'" placeholder="[key]" value="">'."\n";
        $result.="\t".'<button class="KVLAddBtn"><i class="fa fa-plus-circle"></i></button>'."\n";
        $result.="\t".'<ul class="KVLList">'."\n";
        if(is_iterable($lvalues) && count($lvalues)) {
            $lpclass=$this->postable_elements ? ' postable' : '';
            foreach($lvalues as $k=>$v) {
                $result.="\t\t<li><label class=\"KVLILabel\">{$k}</label><input type=\"text\" class=\"KVLIValue{$lpclass}\" name=\"{$this->tag_name}{$pkey}[{$k}]\" placeholder=\"[value]\" value=\"{$v}\"><button class=\"KVLIDelBtn\"><i class=\"fa fa-minus-circle\"></i></button></li>\n";
            }//END foreach
        } else {
            $result.="\t\t<li><span class=\"KVLBlank\">".Translate::Get('label_empty')."</span></li>\n";
        }//if(is_iterable($lvalues) && count($lvalues))
        $result.="\t".'</ul>'."\n";
        $result.='</div>'."\n";
        $result.=$this->GetActions();
        return $result;
    }//END protected function SetControlInstance

    /**
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        $label=(is_string($this->label) && strlen($this->label) ? $this->label : NULL);
        if(is_iterable($this->lang_items) && count($this->lang_items)) {
            $result='<div id="'.$this->tag_id.'" class="clsAccordion clsControlContainer">'."\n";
            $result.="\t".'<h3>'.(strlen($label) ? $label.' - ' : '').Translate::Get('label_general').'</h3>'."\n";
            $result.="\t".'<div>'."\n";
            $result.=$this->SetControlInstance(TRUE);
            $result.="\t".'</div>'."\n";
            foreach($this->lang_items as $lang) {
                if(!is_object($lang)) {
                    continue;
                }
                $ds_field=$lang->getProperty('ds_field','value','is_notempty_string');
                $value=$lang->getProperty($ds_field,NULL,'is_string');
                if(is_null($value)) {
                    if(!is_array($this->lang_ds) || !count($this->lang_ds)) {
                        continue;
                    }
                    $rparams=$this->lang_ds;
                    $rparams['record_key']=$lang->getProperty('id');
                    $record=Control::GetTranslationData($rparams);
                    $value=get_array_value($record,[0,$ds_field],NULL,'is_string');
                }//if(is_null($value))
                $result.="\t".'<h3>'.(strlen($label) ? $label.' - ' : '').$lang->getProperty('name').'</h3>'."\n";
                $result.="\t".'<div>'."\n";
                $result.=$this->SetControlInstance(TRUE,$value,$lang);
                $result.="\t".'</div>'."\n";
            }//END foreach
            $result.='</div>'."\n";
            $js_script="$('#{$this->tag_id}').accordion();";
            NApp::AddJsScript($js_script);
        } else {
            $result=$this->SetControlInstance();
        }//if(is_iterable($this->lang_items) && count($this->lang_items))
        return $result;
    }//END protected function SetControl
}//END class KVList extends Control