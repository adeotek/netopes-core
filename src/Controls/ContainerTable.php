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
	 * Control container implementation for Native (table)
	 *
	 * @package  NETopes\Core\Classes\Controls
	 * @access   public
	 */
	class ContainerTable implements IControlContainer {
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
			$result = '';
			if($this->control->container) {
				$tdstyle = (is_numeric($this->control->width) && $this->control->width>0) ? ' style="width: '.$this->control->width.'px;"' : (strpos($this->control->width,'%')!==FALSE ? ' style="width: '.$this->control->width.';"' : '');
				$lcclass = strlen($this->control->containerclass)>0 ? $this->control->containerclass : 'clsControlContainer';
				$result .= "\t".'<table class="'.$lcclass.'">'."\n";
				if($this->control->no_label) {
					$result .= "\t\t".'<tr><td>'.$content.'</td></tr>'."\n";
				} else {
					$llabel = strlen($this->control->label) ? $this->control->label : '&nbsp;';
					$lrequired = $this->control->required===TRUE ? '<span class="clsRequired" id="req_'.$this->control->tagid.'">&nbsp;*</span>' : '';
					$llabelwidth = (is_numeric($this->control->labelwidth) && $this->control->labelwidth>0) ? ' style="width: '.$this->control->labelwidth.'px;"' : (strpos($this->control->labelwidth,'%')!==FALSE ?' style="width: '.$this->control->labelwidth.';"' : '');
					$llabelclass = strlen($this->control->labelclass)>0 ? $this->control->labelclass : 'clsLabel';
					$llabelcontainerclass = strlen($this->control->labelcontainerclass) ? ' class="'.$this->control->labelcontainerclass.'"' : '';
					if($this->control->labelposition=='left') {
						$result .= "\t\t".'<tr>'."\n";
						$result .= "\t\t\t".'<td'.$llabelcontainerclass.$llabelwidth.'><label class="'.$llabelclass.'">'.$llabel.$lrequired.'</label></td>'."\n";
						$result .= "\t\t\t".'<td'.$tdstyle.'>'.$content.'</td>'."\n";
						$result .= "\t\t".'</tr>'."\n";
					} elseif($this->control->labelposition=='right') {
						$result .= "\t\t".'<tr>'."\n";
						$result .= "\t\t\t".'<td'.$tdstyle.'>'.$content.'</td>'."\n";
						$result .= "\t\t\t".'<td'.$llabelcontainerclass.$llabelwidth.'><label class="'.$llabelclass.'">'.$llabel.$lrequired.'</label></td>'."\n";
						$result .= "\t\t".'</tr>'."\n";
					} else {
						$result .= "\t\t".'<tr><td'.$llabelcontainerclass.'><label class="'.$llabelclass.' clsTopLabel">'.$llabel.$lrequired.'</label></td></tr>'."\n";
						$result .= "\t\t".'<tr><td'.$tdstyle.'>'.$content.'</td></tr>'."\n";
					}//if($this->control->labelposition=='left')
				}//if($this->control->no_label)
				$result .= "\t".'</table>'."\n";
			} else {
				if($this->control->no_label) {
					$result .= $content;
				} else {
					$llabel = strlen($this->control->label) ? $this->control->label : '&nbsp;';
					$llabelclass = strlen($this->control->labelclass)>0 ? $this->control->labelclass : 'clsLabel';
					if($this->control->labelposition=='right' || $this->control->labelposition=='left') {
						$llwidth = (is_numeric($this->control->labelwidth) && $this->control->labelwidth>0) ? $this->control->labelwidth.'px' : (strpos($this->control->labelwidth,'%')!==FALSE ? $this->control->labelwidth : NULL);
						if($llwidth) {
							$llabelclass .= ' iblock';
							$llstyle = ' style="width: '.$llwidth.';"';
						} else {
							$llstyle = '';
						}//if($llwidth)
						if($this->control->labelposition=='right') {
							$lrequired = $this->control->required===TRUE ? '<span class="clsRequired" id="req_'.$this->control->tagid.'">*</span>' : '';
							$result .= "\t".$content."\n";
							$result .= "\t".'<label class="'.$llabelclass.'"'.$llstyle.'>'.$this->control->label.$lrequired.'</label>'."\n";
						} else {
							$lrequired = $this->control->required===TRUE ? '<span class="clsRequired" id="req_'.$this->control->tagid.'">&nbsp;*</span>' : '';
							$result .= "\t".'<label class="'.$llabelclass.'"'.$llstyle.'>'.$this->control->label.$lrequired.'</label>'."\n";
							$result .= "\t".$content."\n";
						}//if($this->control->labelposition=='right')
					} else {
						$lrequired = $this->control->required===TRUE ? '<span class="clsRequired" id="req_'.$this->control->tagid.'">&nbsp;*</span>' : '';
						$result .= "\t".'<label class="'.$llabelclass.' fwidth">'.$this->control->label.$lrequired.'</label>'."\n";
						$result .= "\t".$content."\n";
					}//if($this->control->labelposition=='right')
				}//if($this->control->no_label)
			}//if($this->control->container)
			return $result;
		}//END public function GetHtml
	}//END class ContainerTable implements IControlContainer
?>