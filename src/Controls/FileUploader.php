<?php
/**
 * Basic controls classes file
 *
 * File containing basic controls classes
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2017 Hinter Universal SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
namespace NETopes\Core\Classes\Controls;
use NApp;
use GibberishAES;
/**
 * ClassName description
 *
 * long_description
 *
 * @package  NETopes\Controls
 * @access   public
 */
class FileUploader extends Control {

	public function __construct($params = null) {
		$this->status_target = 'ARequestStatus';
		$this->width = 0;
		$this->height = 0;
		$this->require_login = TRUE;
		$this->theme_type = NApp::$x_app_theme_type;
		if(is_array($params) && count($params)) {
			foreach($params as $k=>$v) { $this->$k = $v; }
		}//if(is_array($params) && count($params))
		$this->baseclass = 'cls'.get_class_basename($this);
		$this->container = FALSE;
		$this->no_label = TRUE;
		$this->buffered = FALSE;
	}//END public function __construct

	protected function SetControl() {
		$this->tagid = $this->tagid=='__auto' ? NApp::GetNewUID() : $this->tagid;
		switch(strtolower($this->filter)) {
			case 'images':
				$utype = 1;
				break;
			case 'videos':
				$utype = 2;
				break;
			case 'docs':
				$utype = 3;
				break;
			case 'excel':
				$utype = 4;
			case 'stdimp':
				$utype = 40;
				break;
			case 'media':
				$utype = 12;
				break;
			case 'apk':
				$utype = 13;
				break;
			default:
				$utype = 0;
				break;
		}//END switch
		$lstyle = '';
		if((is_numeric($this->width) && $this->width>0)) { $lstyle .= 'width:'.$this->width.'px;'; }
		if((is_numeric($this->height) && $this->height>0)) { $lstyle .= 'height:'.$this->height.'px;'; }
		$lstyle = strlen($lstyle) ? ' style="'.$lstyle.'"' : '';
		$lalign = $this->align=='left' ? ' aleft' : '';
		$lclass = strlen($this->class) ? ' '.$this->class : '';
		$dclass = $this->droparea!==FALSE ? ' clsDropArea' : '';
		$lstatusid = strlen($this->status_target) ? ' data-statusid="'.$this->status_target.'"' : '';
		$lcallback = strlen($this->callback) && !$this->disabled ? ' data-callback="'.rawurlencode(GibberishAES::enc($this->callback,'HTML')).'"' : '';

		$this->targetdir = rawurlencode($this->targetdir);
		$this->subfolder = rawurlencode($this->subfolder);
		switch($this->theme_type) {
			case 'bootstrap2':
			case 'bootstrap3':
			case 'bootstrap4':
				$bclass = ' class="'.(strlen($this->button_class) ? $this->button_class : 'btn btn-info btn-xs').'"';
				$result = "\t".'<div class="'.$this->baseclass.'Container'.$dclass.$lclass.$lalign.'"'.$lstyle.'>'."\n";
				if(!$this->disabled) { $result .= "\t\t".'<input type="file" id="'.$this->tagid.'" class="'.$this->baseclass.$lclass.'" data-url="'.NApp::app_web_link().'/pipe/upload.php?rpa='.($this->require_login ? 1 : 0).'&namespace='.NApp::current_namespace().'&utype='.$utype.'" data-targetdir="'.$this->targetdir.'" data-subfolder="'.$this->subfolder.'"'.$lstatusid.$lcallback.' name="files[]" multiple>'."\n"; }
				break;
			default:
				$bclass = strlen($this->button_class) ? ' class="'.$this->button_class.'"' : '';
				$result = "\t".'<div class="'.$this->baseclass.'Container'.$dclass.$lclass.$lalign.'"'.$lstyle.'>'."\n";
				if(!$this->disabled) { $result .= "\t\t".'<input type="file" id="'.$this->tagid.'" class="'.$this->baseclass.$lclass.'" data-url="'.NApp::app_web_link().'/pipe/upload.php?rpa='.($this->require_login ? 1 : 0).'&namespace='.NApp::current_namespace().'&utype='.$utype.'" data-targetdir="'.$this->targetdir.'" data-subfolder="'.$this->subfolder.'"'.$lstatusid.$lcallback.' name="files[]" multiple>'."\n"; }
				break;
		}//END switch
		$onclick = (!$this->disabled ? ' onclick="$(\'#'.$this->tagid.'\').click()"' : ' disabled="disabled"');
		$result .= "\t\t".'<button id="fu-button-'.$this->tagid.'"'.$bclass.$onclick.'>'.$this->button_icon.$this->button_label.'</button>'."\n";
		if(strlen($this->dropzone_text)) {
			$result .= "\t\t".'<span class="'.$this->baseclass.'Text">'.$this->dropzone_text.'</span>'."\n";
		}//if(strlen($this->dropzone_text))
		$result .= "\t".'</div>'."\n";
		if(!$this->disabled) { NApp::_ExecJs("CreateFileUploader('{$this->tagid}',0);"); }
		return $result;
	}//END protected function SetControl
	/**
	 * Clears the base class of the control
	 *
	 * @return void
	 * @access public
	 */
	public function ClearBaseClass() {
		return FALSE;
	}//END public function ClearBaseClass
}//END class FileUploader extends Control
?>