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
     * @param string $columnKey
     * @param array  $column
     * @param bool   $first
     */
    public function SetColumn(string $columnKey,array $column,bool $first=FALSE): void {
        if(!is_array($this->columns)) {
            $this->columns=[];
        }
        if($first) {
            array_unshift($this->columns,[$columnKey=>$column]);
        } else {
            $this->columns[$columnKey]=$column;
        }//if($first)
    }//END public function SetColumn

    /**
     * @param string $key
     * @return array
     */
    protected function GetColumn(string $key): array {
        return isset($this->columns[$key]) ? $this->columns[$key] : NULL;
    }//END private function GetColumn

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
     * @param string $key
     * @param array  $action
     * @param bool   $incrementCount
     * @param bool   $first
     * @return void
     */
    public function AddAction(string $key,array $action,bool $incrementCount=TRUE,bool $first=FALSE): void {
        if(!is_array($this->columns)) {
            $this->columns=[];
        }
        if(!isset($this->columns[$key])) {
            $this->columns[$key]=['type'=>'actions','visual_count'=>0,'actions'=>[]];
        }
        if(!isset($this->columns[$key]['actions']) || !is_array($this->columns[$key]['actions'])) {
            $this->columns[$key]['actions']=[];
        }
        if($first) {
            array_unshift($this->columns[$key]['actions'],$action);
        } else {
            $this->columns[$key]['actions'][]=$action;
        }//if($first)
        if($incrementCount) {
            if(isset($this->columns[$key]['visual_count'])) {
                ++$this->columns[$key]['visual_count'];
            } else {
                $this->columns[$key]['visual_count']=1;
            }
        }//if($incrementCount)
    }//END public function AddAction

    /**
     * @param string $key
     * @param int    $count
     * @return void
     */
    public function SetActionsVisualCount(string $key,int $count): void {
        if(!is_array($this->columns)) {
            $this->columns=[];
        }
        if(!isset($this->columns[$key])) {
            $this->columns[$key]=['type'=>'actions','visual_count'=>$count,'actions'=>[]];
        } elseif(!isset($this->columns[$key]['visual_count'])) {
            $this->columns[$key]['visual_count']=$count;
        }
        if(!isset($this->columns[$key]['actions']) || !is_array($this->columns[$key]['actions'])) {
            $this->columns[$key]['actions']=[];
        }
    }//END public function SetActionsVisualCount

    /**
     * @param string $key
     * @return int
     */
    public function GetActionsCount(string $key): int {
        return isset($this->columns[$key]['actions']) ? count($this->columns[$key]['actions']) : 0;
    }//END public function GetActionsCount

    /**
     * @param string $key
     * @return int
     */
    public function GetActionsVisualCount(string $key): int {
        return isset($this->columns[$key]['visual_count']) ? $this->columns[$key]['visual_count'] : 0;
    }//END public function GetActionsVisualCount

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