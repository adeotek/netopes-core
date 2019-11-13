<?php
/**
 * TableViewBuilder control class file
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
 * Class TableViewBuilder
 *
 * @package NETopes\Core\Controls
 */
class TableViewBuilder extends ControlBuilder {

    /**
     * @var    array Columns configuration params
     */
    public $columns=[];

    /**
     * @var    array Custom actions list
     */
    public $custom_actions=[];

    /**
     * BasicForm class constructor method
     *
     * @param array $params Parameters array
     * @return void
     */
    public function __construct($params=NULL) {
        if(array_key_exists('columns',$params)) {
            if(is_array($params['columns'])) {
                $this->columns=$params['columns'];
            }
            unset($params['columns']);
        }
        if(array_key_exists('custom_actions',$params)) {
            if(is_array($params['custom_actions'])) {
                $this->custom_actions=$params['custom_actions'];
            }
            unset($params['custom_actions']);
        }
        parent::__construct($params);
    }//END public function __construct

    /**
     * @param array $column
     * @param bool  $first
     */
    public function AddColumn(array $column,bool $first=FALSE): void {
        if(!is_array($this->columns)) {
            $this->columns=[];
        }
        if($first) {
            array_unshift($this->columns,$column);
        } else {
            $this->columns[]=$column;
        }//if($first)
    }//END public function AddColumn

    /**
     * @param array $columns
     */
    public function SetColumns(array $columns): void {
        $this->columns=$columns;
    }//END public function SetColumns

    /**
     * @return array
     */
    protected function GetColumns(): array {
        return $this->columns;
    }//END private function GetColumns

    /**
     * @return int
     */
    public function GetColumnsCount(): int {
        return count($this->columns);
    }//END public function GetColumnsCount

    /**
     * @param array $customAction
     * @param bool  $first
     */
    public function AddCustomAction(array $customAction,bool $first=FALSE): void {
        if(!is_array($this->custom_actions)) {
            $this->custom_actions=[];
        }
        if($first) {
            array_unshift($this->custom_actions,$customAction);
        } else {
            $this->custom_actions[]=$customAction;
        }//if($first)
    }//END public function AddCustomAction

    /**
     * @return array
     */
    protected function GetCustomActions(): array {
        return $this->custom_actions;
    }//END private function GetCustomActions

    /**
     * @param array $customActions
     */
    public function SetCustomActions(array $customActions): void {
        $this->custom_actions=$customActions;
    }//END public function SetCustomActions

    /**
     * @return int
     */
    public function GetCustomActionsCount(): int {
        return count($this->custom_actions);
    }//END public function GetCustomActionsCount

    /**
     * @return array
     */
    public function GetConfig(): array {
        $result=$this->params;
        $result['custom_actions']=$this->custom_actions;
        $result['columns']=$this->columns;
        return $result;
    }//public function GetConfig
}//END class TableViewBuilder extends ControlBuilder