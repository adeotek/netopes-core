<?php
namespace NETopes\Core\Controls;
use NETopes\Core\AppSession;
use NETopes\Core\Data\DataSourceHelpers;
use NETopes\Core\Data\IEntity;
use NETopes\Core\Data\VirtualEntity;

/**
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
     * @param $key  int|string
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function GetItemActions(IEntity $item,$key): ?string {
        $result='';
        // if(is_string($this->sub_title_field) && strlen($this->sub_title_field)) {
        //     $result.='<div class="clsListBoxItemSubTitle">'."\n";
        //     if(is_string($this->sub_title_label) && strlen($this->sub_title_label)) {
        //         $result.='<span class="lb-label">'.$this->sub_title_label.':</span>'."\n";
        //     }
        //     $result.=$item->GetProperty($this->sub_title_field,'','is_string')."\n";
        //     $result.='</div>'."\n";
        // }
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
        $cssClass='';
        if(is_string($this->css_class_field) && strlen($this->css_class_field)) {
            $cssClass=$item->getProperty($this->css_class_field,'','is_string');
        }
        $header=$this->GetItemTitle($item,$key);
        $header.=$this->GetItemSubTitle($item,$key);
        $header.=$this->GetItemActions($item,$key);
        $result='<div class="clsListBoxItem'.(strlen($cssClass) ? ' '.$cssClass : '').'">'."\n";
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