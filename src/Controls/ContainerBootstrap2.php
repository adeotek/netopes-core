<?php
/**
 * Control container class file
 *
 * Control container implementation
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.0
 * @filesource
 */
    namespace NETopes\Core\Controls;
	/**
	 * Control container class file
	 *
	 * Control container implementation for Bootstrap2
	 *
	 * @package  NETopes\Core\Controls
	 * @access   public
	 */
	class ContainerBootstrap2 implements IControlContainer {
		/**
		 * @var object Control instance
		 */
		protected $control;
		/**
		 * Control container class constructor
		 *
		 * @param $control
		 * @access public
		 */
		public function __construct($control) {
			$this->control = $control;
		}//END public function __construct
		/**
		 * description
		 *
		 * @param string $content
		 * @return string
		 * @access public
		 */
		public function GetHtml($content) {
			if(!$this->control->container && $this->control->no_label) {
				$result = $content;
			} else {
				$result = '';
				$c_label = '';
				if(!$this->control->no_label) {
					$lrequired = $this->control->required===TRUE ? '<span style="color:#cf0000;">&nbsp;*</span>' : '';
					$llabelclass = strlen($this->control->labelclass) ? ' '.$this->control->labelclass : '';
					$c_label = "\t\t".'<label class="control-label'.$llabelclass.'" for="'.$this->control->tag_id.'">'.$this->control->label.$lrequired.'</label>'."\n";
				}//if(!$this->control->no_label)
				if($this->control->container) {
					$result .= "\t".'<div class="control-group">'."\n";
					$result .= $c_label;
					$result .= "\t\t".'<div class="controls">'.$content.'</div>'."\n";
					$result .= "\t".'</div>'."\n";
				} else {
					$result .= $c_label;
					$result .= "\t\t".'<div class="controls">'.$content.'</div>'."\n";
				}//if($this->control->container)
			}//if(!$this->control->container && $this->control->no_label)
			return $result;
		}//END public function GetHtml
	}//END class ContainerBootstrap2 implements IControlContainer
?>