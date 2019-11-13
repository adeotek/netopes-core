<?php
namespace NETopes\Core\Controls;
use NApp;
use NETopes\Core\AppSession;
use NETopes\Core\Data\DataSourceHelpers;
use NETopes\Core\Data\IEntity;
use NETopes\Core\Data\VirtualEntity;

/**
 * @property string|null item_id_field
 * @property string|null content_field
 * @property string|null title_field
 * @property string|null title_label
 * @property string|null sub_title_label
 * @property string|null sub_title_field
 * @property string|null css_class_field
 */
class ListBox extends Control {
    use TControlDataSource;

    /**
     * @var    string Iterator type (array/DataSource/Module)
     */
    public $iterator_type='list';
    /**
     * @var    array Elements data source
     */
    public $data_source=[];
    /**
     * @var    mixed Iterator items array
     */
    public $items;
    /**
     * @var    array Items actions array
     */
    public $actions=[];

    /**
     * ListBox constructor.
     *
     * @param null $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        $this->postable=FALSE;
        $this->container=FALSE;
        $this->no_label=TRUE;
        parent::__construct($params);
        if(!strlen($this->tag_id)) {
            $this->tag_id=AppSession::GetNewUID('ListBox','md5');
        }
    }//END public function __construct

    /**
     * @return array|mixed|\NETopes\Core\Data\DataSet|null
     * @throws \NETopes\Core\AppException
     */
    protected function GetItems() {
        $items=NULL;
        switch(strtolower($this->iterator_type)) {
            case 'module':
                $items=$this->LoadData($this->data_source,TRUE);
                break;
            case 'datasource':
                $items=$this->LoadData($this->data_source);
                break;
            case 'list':
            default:
                $items=$this->items;
                break;
        }//END switch
        if(is_object($this->items)) {
            return $this->items;
        }
        return DataSourceHelpers::ConvertArrayToDataSet($items,VirtualEntity::class);
    }//END protected function GetItems

    /**
     * @param $item IEntity
     * @param $key  int|string
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function GetItemTitle(IEntity $item,$key): ?string {
        $result='';
        if(is_string($this->title_field) && strlen($this->title_field)) {
            $result.='<div class="clsListBoxItemTitle">'."\n";
            if(is_string($this->title_label) && strlen($this->title_label)) {
                $result.='<span class="lb-label">'.$this->title_label.':</span>'."\n";
            }
            $result.=$item->GetProperty($this->title_field,'','is_string')."\n";
            $result.='</div>'."\n";
        }
        return $result;
    }//END protected function GetItemTitle

    /**
     * @param $item IEntity
     * @param $key  int|string
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function GetItemSubTitle(IEntity $item,$key): ?string {
        $result='';
        if(is_string($this->sub_title_field) && strlen($this->sub_title_field)) {
            $result.='<div class="clsListBoxItemSubTitle">'."\n";
            if(is_string($this->sub_title_label) && strlen($this->sub_title_label)) {
                $result.='<span class="lb-label">'.$this->sub_title_label.':</span>'."\n";
            }
            $result.=$item->GetProperty($this->sub_title_field,'','is_string')."\n";
            $result.='</div>'."\n";
        }
        return $result;
    }//END protected function GetItemSubTitle

    /**
     * @param $item IEntity
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function ProcessItemActions(IEntity $item): ?string {
        $actions=NULL;
        if(is_array($this->actions) && count($this->actions)) {
            foreach($this->actions as $act) {
                $act=ControlsHelpers::ReplaceDynamicParams($act,$item);
                $actParams=get_array_value($act,'params',[],'is_array');
                // Check conditions for displaying action
                $conditions=get_array_value($actParams,'conditions',NULL,'is_array');
                if(is_array($conditions) && !ControlsHelpers::CheckRowConditions($item,$conditions)) {
                    continue;
                }
                $actType=get_array_value($act,'type','DivButton','is_notempty_string');
                $actType='\NETopes\Core\Controls\\'.$actType;
                if(!class_exists($actType)) {
                    NApp::Elog('Control class ['.$actType.'] not found!');
                    continue;
                }//if(!class_exists($actType))
                $ajaxCommand=get_array_value($act,'ajax_command',NULL,'is_notempty_string');
                $targetId=get_array_value($act,'ajax_target_id',NULL,'is_notempty_string');
                if(!$ajaxCommand) {
                    $aCommand=get_array_value($act,'command_string',NULL,'?is_string');
                }//if(!$ajaxCommand)
                if($ajaxCommand) {
                    $actParams['onclick']=NApp::Ajax()->Prepare($ajaxCommand,$targetId,NULL,$this->loader);
                }//if($ajaxCommand)
                $actControl=new $actType($actParams);
                if(get_array_value($act,'clear_base_class',FALSE,'bool')) {
                    $actControl->ClearBaseClass();
                }//if(get_array_value($act,'clear_base_class',FALSE,'bool'))
                $actions.=$actControl->Show()."\n";
            }//END foreach
        }//if(is_array($this->actions) && count($this->actions))
        return $actions;
    }//END protected function ProcessItemActions

    /**
     * @param $item IEntity
     * @param $key  int|string
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function GetItemActions(IEntity $item,$key): ?string {
        $actions=$this->ProcessItemActions($item);
        if(!strlen($actions)) {
            return NULL;
        }
        $result='<div class="clsListBoxItemActions">'."\n";
        $result.=$actions;
        $result.='</div>'."\n";
        return $result;
    }//END protected function GetItemActions

    /**
     * @param $item IEntity
     * @param $key  int|string
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function GetItemContent(IEntity $item,$key): ?string {
        $result='<div class="clsListBoxItemBody">'."\n";
        $result.=html_entity_decode($item->GetProperty($this->content_field,'','is_string'))."\n";
        $result.='</div>'."\n";
        return $result;
    }//END protected function GetItemContent

    /**
     * @param $item IEntity
     * @param $key  int|string
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function GetItem(IEntity $item,$key): ?string {
        $itemId='';
        if(is_string($this->item_id_field) && strlen($this->item_id_field)) {
            $itemId=$item->getProperty($this->item_id_field,'','is_string');
        }
        $cssClass='';
        if(is_string($this->css_class_field) && strlen($this->css_class_field)) {
            $cssClass=$item->getProperty($this->css_class_field,'','is_string');
        }
        $header=$this->GetItemActions($item,$key);
        $header.=$this->GetItemTitle($item,$key);
        $header.=$this->GetItemSubTitle($item,$key);
        $result='<div class="clsListBoxItem'.(strlen($cssClass) ? ' '.$cssClass : '').'"'.(strlen($itemId) ? ' id="'.$itemId.'"' : '').'>'."\n";
        if(strlen($header)) {
            $result.='<div class="clsListBoxItemHeader">'."\n";
            $result.=$header;
            $result.='</div>'."\n";
        }
        $result.=$this->GetItemContent($item,$key);
        $result.='</div>'."\n";
        return $result;
    }//END protected function GetItemContent

    /**
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        $this->items=$this->GetItems();
        $lContent='';
        if(is_object($this->items) && $this->items->count()) {
            foreach($this->items as $k=>$v) {
                $lContent.=$this->GetItem($v,$k);
            }//END foreach
        }//if(is_object($this->items) && $this->items->count())
        $result='<div'.$this->GetTagId().$this->GetTagClass().$this->GetTagAttributes().'>'."\n";
        $result.=$lContent;
        $result.='</div>'."\n";
        return $result;
    }//END protected function SetControl
}//END class ListBox extends Control