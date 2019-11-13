<?php
/**
 * TabControlBuilder control class file
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.9.3
 * @filesource
 */
namespace NETopes\Core\Controls;

/**
 * Class TabControlBuilder
 *
 * @package NETopes\Core\Controls
 */
class TabControlBuilder extends ControlBuilder {

    /**
     * @var    array tabs descriptor array
     */
    public $tabs=[];

    /**
     * BasicForm class constructor method
     *
     * @param array $params Parameters array
     * @return void
     */
    public function __construct($params=NULL) {
        if(array_key_exists('tabs',$params)) {
            if(is_array($params['tabs'])) {
                $this->tabs=$params['tabs'];
            }
            unset($params['tabs']);
        }
        parent::__construct($params);
    }//END public function __construct

    /**
     * @param array $tab
     * @param bool  $first
     */
    public function AddTab(array $tab,bool $first=FALSE): void {
        if(!is_array($this->tabs)) {
            $this->tabs=[];
        }
        if($first) {
            array_unshift($this->tabs,$tab);
        } else {
            $this->tabs[]=$tab;
        }//if($first)
    }//END public function AddTab

    /**
     * @param array $tabs
     */
    public function SetTabs(array $tabs): void {
        $this->tabs=$tabs;
    }//END public function SetTabs

    /**
     * @return array
     */
    protected function GetTabs(): array {
        return $this->tabs;
    }//END private function GetTabs

    /**
     * @return int
     */
    public function GetTabsCount(): int {
        return count($this->tabs);
    }//END public function GetTabsCount

    /**
     * @return array
     */
    public function GetConfig(): array {
        $result=$this->params;
        $result['tabs']=$this->tabs;
        return $result;
    }//public function GetConfig
}//END class TabControlBuilder extends ControlBuilder