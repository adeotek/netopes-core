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
use GibberishAES;
use NApp;
use NETopes\Core\AppSession;

/**
 * Link control class
 *
 * @property string|null type
 * @property string      href
 * @property bool        encrypted
 * @property string      hash_separator
 * @property string|null domain
 * @property string|null tooltip
 * @property string|null anchor
 * @property string|null value
 * @property string|null icon
 * @property array|null  session_params
 * @property array|null  payload
 * @property string|null sufix
 * @property string|null target
 * @package  NETopes\Controls
 */
class Link extends Control {
    /**
     * @var array|null
     */
    protected $url_params=[];

    /**
     * Link constructor.
     *
     * @param array|null $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct(?array $params=NULL) {
        $this->postable=FALSE;
        $this->no_label=TRUE;
        $this->container=FALSE;
        $this->encrypted=TRUE;
        parent::__construct($params);
        if(!strlen($this->hash_separator)) {
            $this->hash_separator='|';
        }
        if(!is_string($this->href) || !strlen($this->href)) {
            if(!is_string($this->domain) || !strlen($this->domain) || trim($this->domain)=='-') {
                $this->href=NApp::$appBaseUrl.'/';
            } else {
                $this->href=NApp::url()->GetAppWebProtocol().$this->domain.NApp::url()->GetUrlFolder().'/';
            }//if(!is_string($this->domain) || !strlen($this->domain) || trim($this->domain)=='-')
        }//if(!is_string($this->href) || !strlen($this->href))
    }//END public function __construct

    /**
     * description
     *
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        $ePass=is_string($this->encrypted) && strlen($this->encrypted) ? $this->encrypted : 'eUrlHash';
        $urlParams='';
        if(is_array($this->url_params)) {
            foreach($this->url_params as $k=>$v) {
                if(is_array($v)) {
                    $val='';
                    foreach($v as $hp) {
                        $val.=(strlen($val) ? $this->hash_separator : '').$hp;
                    }
                    if(strlen($val) && $this->encrypted!==FALSE) {
                        $val=GibberishAES::enc($val,$ePass);
                    }
                } else {
                    $val=$v;
                }//if(is_array($v))
                $urlParams.=(strlen($urlParams) ? '&' : '').$k.'='.rawurlencode($val);
            }//END foreach
        }//if(is_array($this->url_params))
        $href=$this->href;
        switch($this->type) {
            case 'ehash':
                $payload=rawurlencode(GibberishAES::enc(json_encode($this->payload ?? []),$ePass));
                $urlParams.=(strlen($urlParams) ? '&' : '').'ehash='.$payload;
                break;
            default:
                if(is_array($this->session_params) && count($this->session_params)) {
                    $sHash=rawurlencode(AppSession::GetNewUID($this->tag_id.serialize($this->session_params),'sha1',TRUE));
                    $namespace=get_array_value($this->url_params,'namespace','','is_string');
                    NApp::SetParam($sHash,$this->session_params,FALSE,$namespace);
                    $urlParams.=(strlen($urlParams) ? '&' : '').'shash='.$sHash;
                }//if(is_array($this->session_params) && count($this->session_params))
                break;
        }//END switch
        if(strlen($urlParams)) {
            $href.=(strpos($href,'?')===FALSE ? '?' : '&').$urlParams;
        }
        if(strlen($this->anchor)) {
            $href=rtrim($href,'#').'#'.$this->anchor;
        }
        $lTooltip='';
        $ttClass='';
        if(strlen($this->tooltip)) {
            $lTooltip=' title="'.$this->tooltip.'"';
            $ttClass='clsTitleSToolTip';
        }//if(strlen($this->tooltip))
        $ttClass.=!strlen($this->value) ? (strlen($ttClass) ? ' ' : '').'io' : '';
        $lIcon=is_string($this->icon) && strlen($this->icon) ? '<i class="'.$this->icon.'" aria-hidden="true"></i>' : '';
        $lSufix=strlen($this->sufix) ? $this->sufix : '';
        $lTarget=(strlen($this->target) ? ' target="'.$this->target.'"' : '');
        $result="\t\t".'<a href="'.(strlen($href) ? $href : '#').'"'.$lTarget.$this->GetTagId().$this->GetTagClass($ttClass).$this->GetTagAttributes().$lTooltip.'>'.$lIcon.$this->value.'</a>'.$lSufix."\n";
        return $result;
    }//END protected function SetControl
}//END class Link extends Control