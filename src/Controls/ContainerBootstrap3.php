<?php
/**
 * Control container class file
 *
 * Control container implementation
 *
 * @package    NETopes\Core\Classes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2017 Hinter Universal SRL
 * @license    LICENSE.md
 * @version    2.2.0.0
 * @filesource
 */
    namespace NETopes\Core\Classes\Controls;
	/**
	 * Control container class file
	 *
	 * Control container implementation for Bootstrap3
	 *
	 * @package  NETopes\Core\Classes\Controls
	 * @access   public
	 */
	class ContainerBootstrap3 implements IControlContainer {
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
				$c_label_cols = 0;
				$c_label = '';
				if(!$this->control->no_label) {
					if($this->control->labelposition=='top') {
						$c_label_cols = 12;
					} else {
						$c_label_cols = is_numeric($this->control->label_cols) && $this->control->label_cols>0 && $this->control->label_cols<12 ? $this->control->label_cols : 2;
					}//if($this->control->labelposition=='top')
					$llabelclass = 'control-label col-md-'.$c_label_cols.(strlen($this->control->labelclass) ? ' '.$this->control->labelclass : '');
					$lrequired = $this->control->required ? '<span class="clsMarkerRequired"></span>' : '';
					if(strlen($this->control->size)) { $llabelclass .= ' label-'.$this->control->size; }
					$c_label = "\t\t".'<label class="'.$llabelclass.'" for="'.$this->control->tagid.'">'.$this->control->label.$lrequired.'</label>'."\n";
				}//if(!$this->control->no_label)
				if(!$this->control->no_label && $this->control->labelposition=='top') {
					$c_cols = is_numeric($this->control->cols) && $this->control->cols>0 && $this->control->cols<=12 ? $this->control->cols : 12;
				} else {
					$c_cols = is_numeric($this->control->cols) && $this->control->cols>0 && $this->control->cols<=(12-$c_label_cols) ? $this->control->cols : (12-$c_label_cols);
				}//if(!$this->control->no_label && $this->control->labelposition=='top')
				if($this->control->container) { $result .= "\t".'<div class="form-group">'."\n"; }
				$result .= $c_label;
				// if($this->control->labelposition!='top') { }
				$result .= "\t\t".'<div class="col-md-'.$c_cols.'">'."\n";
				$result .= "\t\t\t".$content."\n";
				$result .= "\t\t".'</div>'."\n";
				// if($this->control->labelposition!='top') { }
				if($this->control->container) { $result .= "\t".'</div>'."\n"; }
			}//if(!$this->control->container && $this->control->no_label)
			return $result;
		}//END public function GetHtml
	}//END class ContainerBootstrap3 implements IControlContainer
?>