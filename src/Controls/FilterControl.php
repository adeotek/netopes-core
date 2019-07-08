<?php
/**
 * FilterControl abstract class file
 * Base abstract class for filter controls
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.3.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use GibberishAES;
use NApp;
use NETopes\Core\App\Params;
use NETopes\Core\AppSession;
use Translate;

/**
 * Class FilterControl
 *
 * @package NETopes\Core\Controls
 */
abstract class FilterControl {
    /**
     * @var    string|null Control instance hash
     */
    protected $chash=NULL;
    /**
     * @var    array Filters values
     */
    protected $filters=[];
    /**
     * @var    string|null Control base CSS class
     */
    protected $base_class=NULL;
    /**
     * @var    string|null Main container id
     */
    public $tag_id=NULL;
    /**
     * @var    string|null Theme type
     */
    public $theme_type=NULL;
    /**
     * @var    string|null Control target
     */
    public $target=NULL;
    /**
     * @var    mixed Ajax calls loader: 1=default loader; 0=no loader;
     * [string]=html element id or javascript function
     */
    public $loader=1;
    /**
     * @var    string|null Control elements CSS class
     */
    public $class=NULL;

    /**
     * FilterBox class constructor method
     *
     * @param array $params Parameters array
     * @return void
     */
    public function __construct($params=NULL) {
        $this->chash=AppSession::GetNewUID();
        $this->base_class='cls'.get_class_basename($this);
        $this->theme_type=is_object(NApp::$theme) ? NApp::$theme->GetThemeType() : 'bootstrap3';
        if(is_array($params) && count($params)) {
            foreach($params as $k=>$v) {
                if(property_exists($this,$k)) {
                    $this->$k=$v;
                }
            }//foreach ($params as $k=>$v)
        }//if(is_array($params) && count($params))
        $this->tag_id=$this->tag_id ? $this->tag_id : $this->chash;
    }//END public function __construct

    /**
     * Gets this instance as a serialized string
     *
     * @param bool $encrypted Switch on/off encrypted result
     * @return string Return serialized control instance
     */
    protected function GetThis($encrypted=TRUE): string {
        if($encrypted) {
            return GibberishAES::enc(serialize($this),$this->chash);
        }
        return serialize($this);
    }//END protected function GetThis

    /**
     * Gets the filter box html
     *
     * @param string|int Key (type) of the filter to be checked
     * @return bool Returns TRUE if filter is used and FALSE otherwise
     */
    protected function CheckIfFilterIsActive($key): bool {
        if(!is_numeric($key) && (!is_string($key) || !strlen($key))) {
            return FALSE;
        }
        if(!is_array($this->filters) || !count($this->filters)) {
            return FALSE;
        }
        foreach($this->filters as $f) {
            if(get_array_value($f,'type','','is_string').''==$key.'') {
                return TRUE;
            }
        }//END foreach
        return FALSE;
    }//protected function CheckIfFilterIsActive

    /**
     * @param array $filtersGroups
     * @return string
     */
    protected function GetFiltersGroups(array $filtersGroups): ?string {
        if(!count($filtersGroups)) {
            return NULL;
        }
        $filtersString="\t\t\t".'<select id="'.$this->tag_id.'-f-group" class="f-group">'."\n";
        $filtersString.="\t\t\t\t".'<option value="'.uniqid().'">'.Translate::GetLabel('group_with_filter').'</option>'."\n";
        foreach($filtersGroups as $gid=>$group) {
            $filtersString.="\t\t\t\t".'<option value="'.$gid.'">'.($gid + 1).'</option>'."\n";
        }//END foreach
        $filtersString.="\t\t\t".'</select>'."\n";
        return $filtersString;
    }//END protected function GetFiltersGroups

    /**
     * Process the active filters (adds/removes filters)
     *
     * @param \NETopes\Core\App\Params $params Parameters for processing
     * @return array Returns the updated filters array
     * @throws \NETopes\Core\AppException
     */
    protected function ProcessActiveFilters(Params $params): array {
        $action=$params->safeGet('faction',NULL,'is_notempty_string');
        if(!$action || !in_array($action,['add','remove','clear'])) {
            return $this->filters;
        }
        if($action=='clear') {
            return [];
        }
        if($action=='remove') {
            $key=$params->safeGet('fkey',NULL,'is_string');
            return array_filter($this->filters,function($filter) use ($key) {
                return $filter['groupid']!==$key;
            });
        }//if($action=='remove')
        $lfilters=$this->filters;
        $multif=$params->safeGet('multif',[],'is_array');
        if(count($multif)) {
            foreach($multif as $fparams) {
                $op=get_array_value($fparams,'fop',NULL,'is_notempty_string');
                $type=get_array_value($fparams,'ftype',NULL,'is_notempty_string');
                $cond=get_array_value($fparams,'fcond',NULL,'is_notempty_string');
                $value=get_array_value($fparams,'fvalue',NULL,'isset');
                $svalue=get_array_value($fparams,'fsvalue',NULL,'isset');
                $dvalue=get_array_value($fparams,'fdvalue',NULL,'isset');
                $sdvalue=get_array_value($fparams,'fsdvalue',NULL,'isset');
                $fdtype=get_array_value($fparams,'data_type','','is_string');
                $isDSParam=get_array_value($fparams,'is_ds_param',0,'is_numeric');
                $groupid=get_array_value($fparams,'groupid',uniqid(),'is_string');
                if(!$op || !isset($type) || !$cond || !isset($value)) {
                    continue;
                }
                $lfilters[]=['operator'=>$op,'type'=>$type,'condition_type'=>$cond,'value'=>$value,'svalue'=>$svalue,'dvalue'=>$dvalue,'sdvalue'=>$sdvalue,'data_type'=>$fdtype,'is_ds_param'=>$isDSParam,'groupid'=>$groupid];
            }//END foreach
        } else {
            $op=$params->safeGet('fop',NULL,'is_notempty_string');
            $type=$params->safeGet('ftype',NULL,'is_notempty_string');
            $cond=$params->safeGet('fcond',NULL,'is_notempty_string');
            $value=$params->safeGet('fvalue',NULL,'isset');
            $svalue=$params->safeGet('fsvalue',NULL,'isset');
            $dvalue=$params->safeGet('fdvalue',NULL,'isset');
            $sdvalue=$params->safeGet('fsdvalue',NULL,'isset');
            $fdtype=$params->safeGet('data_type','','is_string');
            $isDSParam=$params->safeGet('is_ds_param',0,'is_numeric');
            $groupid=$params->safeGet('groupid',uniqid(),'is_string');
            if(!$op || !isset($type) || !$cond || !isset($value)) {
                return $this->filters;
            }
            $lfilters[]=['operator'=>$op,'type'=>$type,'condition_type'=>$cond,'value'=>$value,'svalue'=>$svalue,'dvalue'=>$dvalue,'sdvalue'=>$sdvalue,'data_type'=>$fdtype,'is_ds_param'=>$isDSParam,'groupid'=>$groupid];
        }//if(count($multif))
        return $lfilters;
    }//END protected function ProcessActiveFilters

    /**
     * Gets the control's content (html)
     *
     * @param Params|array|null $params An array of parameters
     *                                  * phash (string) = new page hash (window.name)
     *                                  * output (bool|numeric) = flag indicating direct (echo)
     *                                  or indirect (return) output (default FALSE - indirect (return) output)
     *                                  * other pass through params
     * @return string Returns the control's content (html)
     * @throws \NETopes\Core\AppException
     */
    public function Show($params=NULL): ?string {
        // NApp::Dlog($params,'FilterControl>>Show');
        $o_params=is_object($params) ? $params : new Params($params);
        $phash=$o_params->safeGet('phash',NULL,'?is_notempty_string');
        $output=$o_params->safeGet('output',FALSE,'bool');
        if($phash) {
            $this->phash=$phash;
        }
        if(!$output) {
            return $this->SetControl($o_params);
        }
        echo $this->SetControl($o_params);
        return NULL;
    }//END public function Show

    /**
     * Sets the output buffer value
     *
     * @param \NETopes\Core\App\Params|null $params
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    abstract protected function SetControl(Params $params=NULL): ?string;
}//END abstract class FilterControl