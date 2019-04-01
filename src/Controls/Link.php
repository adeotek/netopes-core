<?php
/**
 * Basic controls classes file
 * File containing basic controls classes
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\AppSession;
use NApp;
use GibberishAES;
/**
 * ClassName description
 * long_description
 * @package  NETopes\Controls
 */
class Link extends Control {
	protected $url_params = [];
	public function __construct($params = NULL) {
		$this->postable = FALSE;
		$this->no_label = TRUE;
		$this->container = FALSE;
		$this->encrypted = TRUE;
		parent::__construct($params);
		if(!strlen($this->hash_separator)) { $this->hash_separator = '|'; }
		if(!is_string($this->href) || !strlen($this->href)) {
			if(!is_string($this->domain) || !strlen($this->domain) || trim($this->domain)=='-') {
				$this->href = NApp::$appBaseUrl.'/';
			} else {
				$this->href = NApp::url()->GetAppWebProtocol().$this->domain.NApp::url()->GetUrlFolder().'/';
			}//if(!is_string($this->domain) || !strlen($this->domain) || trim($this->domain)=='-')
		}//if(!is_string($this->href) || !strlen($this->href))
	}//END public function __construct
	/**
	 * description
	 * @return void
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
		$lsufix = strlen($this->sufix) ? $this->sufix : '';
		$ltarget = (strlen($this->target) ? ' target="'.$this->target.'"' : '');
		$epass = is_string($this->encrypted) && strlen($this->encrypted) ? $this->encrypted : 'eUrlHash';
		$url_params = '';
		if(is_array($this->session_params) && count($this->session_params)) {
			$shash = rawurlencode(AppSession::GetNewUID($this->tag_id.serialize($this->session_params),'sha1',TRUE));
			$namespace = get_array_value($this->url_params,'namespace','','is_string');
			NApp::SetParam($shash,$this->session_params,FALSE,$namespace);
			$url_params = 'shash='.$shash;
		}//if(is_array($this->session_params) && count($this->session_params))
		if(is_array($this->url_params)) {
			foreach($this->url_params as $k=>$v) {
				if(is_array($v)) {
					$val = '';
					foreach($v as $hp) { $val .= (strlen($val) ? $this->hash_separator : '').$hp; }
					if(strlen($val) && $this->encrypted!==FALSE) { $val = GibberishAES::enc($val,$epass); }
				} else {
					$val = $v;
				}//if(is_array($v))
				$url_params .= (strlen($url_params) ? '&' : '').$k.'='.rawurlencode($val);
			}//END foreach
		}//if(is_array($this->url_params))
		$lhref = $this->href;
		if(strlen($url_params)) { $lhref .= (strpos($lhref,'?')===FALSE ? '?' : '&').$url_params; }
		if(strlen($this->anchor)) { $lhref = rtrim($lhref,'#').'#'.$this->anchor; }
		$result = "\t\t".'<a href="'.(strlen($lhref) ? $lhref : '#').'"'.$ltarget.$this->GetTagId().$this->GetTagClass($ttclass).$this->GetTagAttributes().$ltooltip.'>'.$licon.$this->value.'</a>'.$lsufix."\n";
		return $result;
	}//END protected function SetControl
}//END class Link extends Control