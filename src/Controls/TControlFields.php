<?php
/**
 * Control fields trait file
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\Data\VirtualEntity;
use NApp;
use Translate;

/**
 * Trait TControlFields
 *
 * @package NETopes\Core\Controls
 */
trait TControlFields {
    /**
	 * @param $item
	 * @return null|string
     * @throws \NETopes\Core\AppException
	 */
	protected function GetDisplayFieldValue($item): ?string {
	    if(!is_object($item) && !is_array($item)) { return NULL; }
	    if(!is_object($item)) { $item = new VirtualEntity($item); }
		$ldisplayvalue = '';
		$ldisplayfield = is_string($this->selected_text_field) && strlen($this->selected_text_field) ? $this->selected_text_field : $this->display_field;
		if(is_array($ldisplayfield)) {
			foreach($ldisplayfield as $dk=>$dv) {
				if(is_array($dv)) {
					$ov_items = get_array_value($dv,'items',[],'is_notempty_array');
					$ov_value = get_array_value($dv,'value','','is_string');
					$ov_mask = get_array_value($dv,'mask','','is_string');
					$ltext = $item->getProperty($dk,'N/A','is_string');
					$ldisplayvalue .= strlen($ov_mask)>0 ? str_replace('~',get_array_value($ov_items[$ltext],$ov_value,$ltext,'isset'),$ov_mask) : get_array_value($ov_items[$ltext],$ov_value,$ltext,'isset');
				} else {
				    $ltext = $item->getProperty($dk,'N/A','is_string');
					$ldisplayvalue .= strlen($dv) ? str_replace('~',$ltext,$dv) : $ltext;
				}//if(is_array($dv))
			}//foreach ($this->display_field as $dk=>$dv)
		} else {
		    $ltext = $item->getProperty($ldisplayfield,'N/A','is_string');
			$ldisplayvalue = $this->with_translate===TRUE ? Translate::Get($this->translate_prefix.$ltext) : $ltext;
		}//if(is_array($this->display_field))
		return html_entity_decode($ldisplayvalue);
	}//END protected function GetDisplayFieldValue
}//END trait TControlFields