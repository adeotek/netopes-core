<?php
/**
 * HierarchicalTextArea class file
 * File containing HierarchicalTextArea control class
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
 * Class HierarchicalTextArea
 *
 * @property mixed value
 * @package  Hinter\NETopes\Controls
 */
class HierarchicalTexts extends Control {
    /**
     * @var bool
     */
    protected $postable_elements=TRUE;
    /**
     * @var array
     */
    public $sections=[];

    /**
     * @var mixed|null
     */
    public $text_area_height=NULL;

    /**
     * HierarchicalTextArea constructor.
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
            $this->tag_id=AppSession::GetNewUID(self::class);
        }
        if(!strlen($this->tag_name)) {
            $this->tag_name=$this->tag_id;
        }
    }//END public function __construct

    /**
     * Gets the html tag class string (' class="..."')
     *
     * @param string      $extra Other html classes to be included
     * @param string|null $sufix
     * @return string Returns the html tag class
     */
    protected function GetElementClass(?string $extra=NULL,?string $sufix=NULL) {
        $cssClass='';
        if(strlen($this->class)) {
            $cssClass.=$this->class.$sufix;
        }
        if(!$this->clear_base_class) {
            switch($this->theme_type) {
                case 'bootstrap2':
                case 'bootstrap3':
                case 'bootstrap4':
                    if($this->theme_type!='bootstrap2') {
                        $cssClass.=' form-control';
                    }
                    if(strlen($this->size)) {
                        $cssClass.=' input-'.$this->size;
                    }
                    break;
                default:
                    break;
            }//END switch
        }//if(!$this->clear_base_class)
        if(strlen($extra)) {
            $cssClass.=' '.$extra;
        }
        if(strlen(trim($cssClass))) {
            return ' class="'.trim($cssClass).'"';
        }
        return '';
    }//END protected function GetTagClass

    /**
     * @param mixed $value
     * @return array
     */
    protected function ProcessItems($value): array {
        $items=[];
        if(is_array($value)) {
            $items=$value;
        } elseif(is_string($value) && strlen($value)) {
            try {
                $items=json_decode($value,TRUE);
            } catch(Exception $e) {
                NApp::Elog($e);
            }
        }
        return $items;
    }//END protected function ProcessItems

    /**
     * @param array $data
     * @param       $id
     * @return string|null
     */
    protected function RenderData(array $data,$id): ?string {
        $result='';
        foreach($data as $i=>$item) {
            $result.="\t\t\t\t".'<li class="hItem">'."\n";
            $result.="\t\t\t\t\t".'<div class="hItemData" name="'.$this->tag_name.'['.$id.'][]">'.$item['text'].'</div>'."\n";
            $result.="\t\t\t\t\t".'<div class="hItemEditActions">'."\n";
            $result.="\t\t\t\t\t\t".'<button class="'.NApp::$theme->GetBtnPrimaryClass('btn-xxs io hTextsEditButton').'"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button>'."\n";
            $result.="\t\t\t\t\t".'</div>'."\n";
            $result.="\t\t\t\t\t".'<div class="hItemDeleteActions">'."\n";
            $result.="\t\t\t\t\t\t".'<button class="'.NApp::$theme->GetBtnDangerClass('btn-xxs io hTextsDeleteButton').'"><i class="fa fa-trash" aria-hidden="true"></i></button>'."\n";
            $result.="\t\t\t\t\t".'</div>'."\n";
            $result.="\t\t\t\t".'</li>'."\n";
        }//END foreach
        return $result;
    }//END protected function RenderData

    /**
     * @param array $items
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function RenderSections(array $items): ?string {
        $result="\t\t\t".'<ul class="hSections">'."\n";
        if(!count($items)) {
            $result.="\t\t\t\t".'<li class="hItemSection empty">'."\n";
            $result.="\t\t\t\t\t\t".'<span class="hItemText">'.Translate::GetLabel('no_data_available').'</span>'."\n";
            $result.="\t\t\t\t".'</li>'."\n";
        } else {
            foreach($items as $item) {
                $result.="\t\t\t\t".'<li class="hItemSection" data-id="'.$item['id'].'">'."\n";
                $result.="\t\t\t\t\t".'<span class="hItemTitle">'.$item['name'].'</span>'."\n";
                $result.="\t\t\t\t\t".'<ul class="hTexts sortable">'."\n";
                if(isset($item['data']) && is_array($item['data'])) {
                    $result.=$this->RenderData($item['data'],$item['id']);
                }
                $result.="\t\t\t\t\t".'</ul>'."\n";
                $result.="\t\t\t\t".'</li>'."\n";
            }//END foreach
        }//if(!count($items))
        $result.="\t\t\t".'</ul>'."\n";
        return $result;
    }//END protected function RenderSections

    /**
     * @param array $sections
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function GetSectionsActions(array $sections): ?string {
        $result='';
        foreach($sections as $item) {
            $color=get_array_value($item,'color',NULL,'is_string');
            $btn=new Button([
                'class'=>NApp::$theme->GetBtnDefaultClass('hTextsActionButton'),
                'value'=>get_array_value($item,'name',NULL,'is_string'),
                'style'=>(strlen($color) ? 'color: '.$color.';' : ''),
                'extra_tag_params'=>'data-id="'.$item['id'].'"',
            ]);
            $result.=$btn->Show();
        }//END foreach
        return $result;
    }//END protected function GetActions

    /**
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        $items=$this->ProcessItems($this->value);
        $result='<div'.$this->GetTagId(FALSE).$this->GetTagClass().$this->GetTagAttributes().'>'."\n";
        $result.="\t".'<div class="row hTextsData">'."\n";
        $result.="\t\t".'<div class="col-md-12 hContainer">'."\n";
        $result.=$this->RenderSections($items);
        $result.="\t\t".'</div>'."\n";
        $result.="\t".'</div>'."\n";
        $result.="\t".'<div class="row hTextsInput">'."\n";
        $result.="\t\t".'<div class="col-md-12 hContainer">'."\n";
        $result.="\t\t\t".'<textarea id="'.$this->tag_id.'_hValue" '.$this->GetElementClass().'></textarea>'."\n";
        $result.="\t\t\t".'<input type="hidden" id="'.$this->tag_id.'_hId" value="">'."\n";
        $result.="\t\t".'</div>'."\n";
        $result.="\t".'</div>'."\n";
        $result.="\t".'<div class="row hTextsActions">'."\n";
        $result.="\t\t".'<div class="col-md-12 hContainer">'."\n";
        $result.=$this->GetSectionsActions($this->sections);
        $result.="\t\t".'</div>'."\n";
        $result.="\t".'</div>'."\n";
        $result.='</div>'."\n";

        $jsScript="$('#{$this->tag_id}').NetopesHierarchicalTexts({
            tagId: '{$this->tag_id}',
            tagName: '{$this->tag_name}',
            sections: ".json_encode($this->sections).",
            fieldErrorClass: 'clsFieldError',
            editButtonClass: '".NApp::$theme->GetBtnPrimaryClass('btn-xxs io')."',
            deleteButtonClass: '".NApp::$theme->GetBtnDangerClass('btn-xxs io')."',
            deleteConfirmText: '".Translate::GetMessage('confirm_delete')."',
            deleteConfirmTitle: '".Translate::Get('title_confirm')."',
            deleteConfirmOkLabel: '".Translate::Get('button_ok')."',
            deleteConfirmCancelLabel: '".Translate::Get('button_cancel')."'
        });";
        NApp::AddJsScript($jsScript);

        return $result;
    }//END protected function SetControl
}//END class HierarchicalTexts.php extends Control