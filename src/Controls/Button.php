<?php
/**
 * Basic controls classes file
 *
 * File containing basic controls classes
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
/**
 * ClassName description
 *
 * long_description
 *
 * @package  NETopes\Controls
 * @access   public
 */
class Button extends Control {
    /**
     * Button constructor.
     *
     * @param null $params
     */
    public function __construct($params = NULL) {
		$this->postable = FALSE;
		$this->no_label = TRUE;
		$this->container = FALSE;
		parent::__construct($params);
	}//END public function __construct
	/**
     * @return string
	 * @access public
	 */
    protected function SetControl(): ?string {
		$ltooltip = '';
		$ttclass = '';
		if(strlen($this->tooltip)) {
			$ltooltip = ' title="'.$this->tooltip.'"';
			$ttclass = 'clsTitleSToolTip';
		}//if(strlen($this->tooltip))
        $ttclass .= !strlen($this->value) ? (strlen($ttclass) ? ' ': '').'io' : '';
        $licon = is_string($this->icon) && strlen($this->icon) ? '<i class="'.$this->icon.'" aria-hidden="true"></i>' : '';
		$result = "\t\t".'<button'.$this->GetTagId().$this->GetTagClass($ttclass).$this->GetTagAttributes().$this->GetTagActions().$ltooltip.'>'.$licon.$this->value.'</button>'."\n";
		return $result;
	}//END protected function SetControl
}//END class Button extends Control