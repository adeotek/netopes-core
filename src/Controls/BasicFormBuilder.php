<?php
/**
 * BasicFormBuilder control class file
 * Control class for generating basic forms configuration array
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\AppException;

/**
 * Class BasicFormBuilder
 *
 * @package NETopes\Core\Controls
 */
class BasicFormBuilder extends ControlBuilder {

    /**
     * @var    array fields descriptor array
     */
    protected $content=[];

    /**
     * @var    array form actions descriptor array
     */
    protected $actions=[];

    /**
     * BasicForm class constructor method
     *
     * @param array $params Parameters array
     * @return void
     */
    public function __construct($params=NULL) {
        if(array_key_exists('content',$params)) {
            if(is_array($params['content'])) {
                $this->content=$params['content'];
            }
            unset($params['content']);
        }
        if(array_key_exists('actions',$params)) {
            if(is_array($params['actions'])) {
                $this->actions=$params['actions'];
            }
            unset($params['actions']);
        }
        parent::__construct($params);
    }//END public function __construct

    /**
     * @return array
     */
    public function GetContent(): array {
        return $this->content;
    }//END public function GetContent

    /**
     * @param array $content
     */
    public function SetContent(array $content): void {
        $this->content=$content;
    }//END public function SetContent

    /**
     * @return array
     */
    public function GetActions(): array {
        return $this->actions;
    }//END public function GetActions

    /**
     * @param array $actions
     */
    public function SetActions(array $actions): void {
        $this->actions=$actions;
    }//END public function SetActions

    /**
     * @param array $action
     * @param bool  $first
     */
    public function AddAction(array $action,bool $first=FALSE): void {
        if(!is_array($this->actions)) {
            $this->actions=[];
        }
        if($first) {
            array_unshift($this->actions,$action);
        } else {
            $this->actions[]=$action;
        }//if($first)
    }//END public function AddAction

    /**
     * @param array $row
     * @param bool  $first
     */
    public function AddRow(array $row,bool $first=FALSE): void {
        if(!is_array($this->content)) {
            $this->content=[];
        }
        if($first) {
            array_unshift($this->content,$row);
        } else {
            $this->content[]=$row;
        }//if($first)
    }//END public function AddRow

    /**
     * @param array    $control
     * @param int|null $row
     * @param int|null $column
     * @param bool     $overwrite
     * @throws \NETopes\Core\AppException
     */
    public function AddControl(array $control,?int $row=NULL,?int $column=NULL,bool $overwrite=FALSE): void {
        if(!is_array($this->content)) {
            $this->content=[];
        }
        if($row!==NULL) {
            if(!isset($this->content[$row]) || !is_array($this->content[$row])) {
                $this->content[$row]=[];
            }
            if($column!==NULL) {
                if(isset($this->content[$row][$column]) && is_array($this->content[$row][$column]) && !$overwrite) {
                    throw new AppException("Form element already set [{$row}:{$column}]!");
                }
                $this->content[$row][$column]=$control;
            } else {
                $this->content[$row][]=$control;
            }//if($column!==NULL)
        } else {
            $this->content[]=[$control];
        }//if($row!==NULL)
    }//END public function AddControl

    /**
     * @return int
     */
    public function GetRowsCount(): int {
        return count($this->content);
    }//END public function GetRowsCount

    /**
     * @param int $row
     * @return int
     */
    public function GetColumnsCount(int $row): int {
        return count(get_array_value($this->content,$row,[],'is_array'));
    }//END public function GetColumnsCount

    /**
     * @return int
     */
    public function GetActionsCount(): int {
        return count($this->actions);
    }//END public function GetActionsCount

    /**
     * @return array
     */
    public function GetConfig(): array {
        $result=$this->params;
        $result['content']=$this->content;
        $result['actions']=$this->actions;
        return $result;
    }//public function GetConfig
}//END class BasicFormBuilder extends ControlBuilder