<?php
/**
 * Control container class file
 * Control container implementation
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
    namespace NETopes\Core\Controls;
	/**
	 * Control container class file
	 * Control container implementation for Native (table)
	 * @package  NETopes\Core\Controls
	 */
	class ContainerTable implements IControlContainer {
		/**
		 * @var object Control instance
		 */
		protected $control;
		/**
		 * Control container class constructor
		 * @param $control
		 */
		public function __construct($control) {
			$this->control = $control;
		}//END public function __construct
		/**
		 * description
		 * @param string $content
		 * @return string
		 */
		public function GetHtml($content) {
			$result = '';
			if($this->control->container) {
				$tdstyle = (is_numeric($this->control->width) && $this->control->width>0) ? ' style="width: '.$this->control->width.'px;"' : (strpos($this->control->width,'%')!==FALSE ? ' style="width: '.$this->control->width.';"' : '');
				$lcclass = strlen($this->control->container_class)>0 ? $this->control->container_class : 'clsControlContainer';
				$result .= "\t".'<table class="'.$lcclass.'">'."\n";
				if($this->control->no_label) {
					$result .= "\t\t".'<tr><td>'.$content.'</td></tr>'."\n";
				} else {
					$llabel = strlen($this->control->label) ? $this->control->label : '&nbsp;';
					$lrequired = $this->control->required===TRUE ? '<span class="clsRequired" id="req_'.$this->control->tag_id.'">&nbsp;*</span>' : '';
					$llabelwidth = (is_numeric($this->control->label_width) && $this->control->label_width>0) ? ' style="width: '.$this->control->label_width.'px;"' : (strpos($this->control->label_width,'%')!==FALSE ?' style="width: '.$this->control->label_width.';"' : '');
					$llabelclass = strlen($this->control->labelclass)>0 ? $this->control->labelclass : 'clsLabel';
					$llabelcontainerclass = strlen($this->control->labelcontainerclass) ? ' class="'.$this->control->labelcontainerclass.'"' : '';
					if($this->control->label_position=='left') {
						$result .= "\t\t".'<tr>'."\n";
						$result .= "\t\t\t".'<td'.$llabelcontainerclass.$llabelwidth.'><label class="'.$llabelclass.'">'.$llabel.$lrequired.'</label></td>'."\n";
						$result .= "\t\t\t".'<td'.$tdstyle.'>'.$content.'</td>'."\n";
						$result .= "\t\t".'</tr>'."\n";
					} elseif($this->control->label_position=='right') {
						$result .= "\t\t".'<tr>'."\n";
						$result .= "\t\t\t".'<td'.$tdstyle.'>'.$content.'</td>'."\n";
						$result .= "\t\t\t".'<td'.$llabelcontainerclass.$llabelwidth.'><label class="'.$llabelclass.'">'.$llabel.$lrequired.'</label></td>'."\n";
						$result .= "\t\t".'</tr>'."\n";
					} else {
						$result .= "\t\t".'<tr><td'.$llabelcontainerclass.'><label class="'.$llabelclass.' clsTopLabel">'.$llabel.$lrequired.'</label></td></tr>'."\n";
						$result .= "\t\t".'<tr><td'.$tdstyle.'>'.$content.'</td></tr>'."\n";
					}//if($this->control->label_position=='left')
				}//if($this->control->no_label)
				$result .= "\t".'</table>'."\n";
			} else {
				if($this->control->no_label) {
					$result .= $content;
				} else {
					$llabel = strlen($this->control->label) ? $this->control->label : '&nbsp;';
					$llabelclass = strlen($this->control->labelclass)>0 ? $this->control->labelclass : 'clsLabel';
					if($this->control->label_position=='right' || $this->control->label_position=='left') {
						$llwidth = (is_numeric($this->control->label_width) && $this->control->label_width>0) ? $this->control->label_width.'px' : (strpos($this->control->label_width,'%')!==FALSE ? $this->control->label_width : NULL);
						if($llwidth) {
							$llabelclass .= ' iblock';
							$llstyle = ' style="width: '.$llwidth.';"';
						} else {
							$llstyle = '';
						}//if($llwidth)
						if($this->control->label_position=='right') {
							$lrequired = $this->control->required===TRUE ? '<span class="clsRequired" id="req_'.$this->control->tag_id.'">*</span>' : '';
							$result .= "\t".$content."\n";
							$result .= "\t".'<label class="'.$llabelclass.'"'.$llstyle.'>'.$this->control->label.$lrequired.'</label>'."\n";
						} else {
							$lrequired = $this->control->required===TRUE ? '<span class="clsRequired" id="req_'.$this->control->tag_id.'">&nbsp;*</span>' : '';
							$result .= "\t".'<label class="'.$llabelclass.'"'.$llstyle.'>'.$this->control->label.$lrequired.'</label>'."\n";
							$result .= "\t".$content."\n";
						}//if($this->control->label_position=='right')
					} else {
						$lrequired = $this->control->required===TRUE ? '<span class="clsRequired" id="req_'.$this->control->tag_id.'">&nbsp;*</span>' : '';
						$result .= "\t".'<label class="'.$llabelclass.' fwidth">'.$this->control->label.$lrequired.'</label>'."\n";
						$result .= "\t".$content."\n";
					}//if($this->control->label_position=='right')
				}//if($this->control->no_label)
			}//if($this->control->container)
			return $result;
		}//END public function GetHtml
	}//END class ContainerTable implements IControlContainer
?>