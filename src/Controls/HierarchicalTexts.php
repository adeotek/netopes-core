<?php
/**
 * HierarchicalTextArea class file
 * File containing HierarchicalTextArea control class
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.2.1.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use DKMed\Extra\ControlsConfig;
use Exception;
use NApp;
use NETopes\Core\AppException;
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
     * Text input type Textarea
     */
    const TEXT_INPUT_EDITOR_TEXTAREA='textarea';
    /**
     * Text input type CkEditor
     */
    const TEXT_INPUT_EDITOR_CKEDITOR='ckeditor';
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
    public $text_input_height=NULL;

    /**
     * @var string
     */
    public $text_input_type=self::TEXT_INPUT_EDITOR_TEXTAREA;

    /**
     * @var array|string|null
     */
    public $ckeditor_extra_config=NULL;

    /**
     * HierarchicalTextArea constructor.
     *
     * @param null $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        $this->ckeditor_extra_config=ControlsConfig::GetCkEditorConfig(ControlsConfig::TYPE_DEFAULT);
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
        if(!in_array(strtolower($this->text_input_type),[self::TEXT_INPUT_EDITOR_TEXTAREA,self::TEXT_INPUT_EDITOR_CKEDITOR])) {
            $this->text_input_type=self::TEXT_INPUT_EDITOR_TEXTAREA;
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
            $result.="\t\t\t\t".'<li class="hItem" data-id="'.$id.'">'."\n";
            // $result.="\t\t\t\t\t".'<input type="hidden" class="postable"
            $result.="\t\t\t\t\t".'<div class="hItemData postable" name="'.$this->tag_name.'['.$id.'][data][]">'.$item['text'].'</div>'."\n";
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
                $result.="\t\t\t\t\t".'<input type="hidden" class="postable" name="'.$this->tag_name.'['.$item['id'].'][id]" value="'.$item['id'].'">'."\n";
                $result.="\t\t\t\t\t".'<input type="hidden" class="postable" name="'.$this->tag_name.'['.$item['id'].'][code]" value="'.get_array_value($item,'code','','is_string').'">'."\n";
                $result.="\t\t\t\t\t".'<input type="hidden" class="postable" name="'.$this->tag_name.'['.$item['id'].'][name]" value="'.$item['name'].'">'."\n";
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
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function GetEditActions(): ?string {
        $btn=new Button([
            'class'=>NApp::$theme->GetBtnPrimaryClass('hTextsSaveButton'),
            'value'=>Translate::GetButton('save'),
            'icon'=>'fa fa-save',
        ]);
        $result=$btn->Show();
        $btn=new Button([
            'class'=>NApp::$theme->GetBtnDefaultClass('hTextsCancelButton'),
            'value'=>Translate::GetButton('cancel'),
            'icon'=>'fa fa-ban',
        ]);
        $result.=$btn->Show();
        return $result;
    }//END protected function GetEditActions

    /**
     * @param array $sections
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function GetSectionsActions(array &$sections): ?string {
        $result='';
        foreach($sections as $item) {
            $color=get_array_value($item,'color',NULL,'is_string');
            $btn=new Button([
                'class'=>NApp::$theme->GetBtnSpecialDarkClass('hTextsActionButton'),
                'value'=>get_array_value($item,'name',NULL,'is_string'),
                'style'=>(strlen($color) ? 'color: '.$color.';' : ''),
                'extra_tag_params'=>'data-id="'.$item['id'].'" data-code="'.get_array_value($item,'code','','is_string').'"',
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
        if(!is_array($this->sections) || !count($this->sections)) {
            throw new AppException('Invalid HierarchicalTexts sections array!');
        }
        $result='<div'.$this->GetTagId(FALSE).$this->GetTagClass().$this->GetTagAttributes().'>'."\n";
        $result.="\t".'<div class="row hTextsData">'."\n";
        $result.="\t\t".'<div class="col-md-12 hContainer">'."\n";
        $result.=$this->RenderSections($this->ProcessItems($this->value));
        $result.="\t\t".'</div>'."\n";
        $result.="\t".'</div>'."\n";
        $result.="\t".'<div class="row hTextsInput">'."\n";
        $result.="\t\t".'<div class="col-md-12 hContainer">'."\n";
        $style=strlen($this->text_input_height) && $this->text_input_type!==self::TEXT_INPUT_EDITOR_CKEDITOR ? 'style="'.$this->text_input_height.'"' : '';
        $result.="\t\t\t".'<textarea id="'.$this->tag_id.'_hValue" '.$this->GetElementClass().$style.'></textarea>'."\n";
        $result.="\t\t\t".'<input type="hidden" id="'.$this->tag_id.'_hId" value="">'."\n";
        $result.="\t\t".'</div>'."\n";
        $result.="\t".'</div>'."\n";
        $result.="\t".'<div class="row hTextsEditActions">'."\n";
        $result.="\t\t".'<div class="col-md-12 hContainer">'."\n";
        $result.=$this->GetEditActions();
        $result.="\t\t".'</div>'."\n";
        $result.="\t".'</div>'."\n";
        $result.="\t".'<div class="row hTextsActions">'."\n";
        $result.="\t\t".'<div class="col-md-12 hContainer">'."\n";
        $result.=$this->GetSectionsActions($this->sections);
        $result.="\t\t".'</div>'."\n";
        $result.="\t".'</div>'."\n";
        $result.='</div>'."\n";

        if($this->text_input_type===self::TEXT_INPUT_EDITOR_CKEDITOR) {
            $height=$this->text_input_height ? (is_numeric($this->text_input_height) ? ',undefined,'.$this->text_input_height : ",undefined,'".$this->text_input_height."'") : '';
            $extraConfig='undefined';
            if(is_array($this->ckeditor_extra_config)) {
                try {
                    $extraConfig=json_encode($this->ckeditor_extra_config);
                } catch(Exception $je) {
                    NApp::Elog($je);
                    $extraConfig='undefined';
                }//END try
            } elseif(is_string($this->ckeditor_extra_config) && strlen($this->ckeditor_extra_config)) {
                $extraConfig='{'.trim($this->ckeditor_extra_config,'}{').'}';
            }//if(is_array($this->extra_config))
            NApp::AddJsScript("CreateCkEditor('{$this->phash}','{$this->tag_id}_hValue',false,".$extraConfig.$height.");");
        }//if($this->text_input_type===self::TEXT_INPUT_EDITOR_CKEDITOR)

        $jsScript="$('#{$this->tag_id}').NetopesHierarchicalTexts({
            tagId: '{$this->tag_id}',
            tagName: '{$this->tag_name}',
            textEditorType: '{$this->text_input_type}',
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