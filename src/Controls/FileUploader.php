<?php
/**
 * Basic controls classes file
 * File containing basic controls classes
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.2.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use GibberishAES;
use NApp;
use NETopes\Core\AppSession;

/**
 * FileUploader class
 *
 * @property bool        require_login
 * @property string      status_target
 * @property string|null filter
 * @property string|null sub_folder
 * @property string|null dropzone_text
 * @property bool|null   droparea
 * @property string|null target_dir
 * @property string|null button_class
 * @property mixed       callback
 * @property string|null button_icon
 * @property string|null button_label
 * @package  NETopes\Controls
 */
class FileUploader extends Control {
    /**
     * FileUploader class constructor
     *
     * @param array $params An array of params
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        $this->status_target='ARequestStatus';
        $this->require_login=TRUE;
        parent::__construct($params);
        $this->container=FALSE;
        $this->no_label=TRUE;
        $this->buffered=FALSE;
    }//END public function __construct

    protected function SetControl(): ?string {
        $this->tag_id=$this->tag_id=='__auto' ? AppSession::GetNewUID() : $this->tag_id;
        switch(strtolower($this->filter)) {
            case 'images':
                $utype=1;
                break;
            case 'videos':
                $utype=2;
                break;
            case 'docs':
                $utype=3;
                break;
            case 'excel':
                $utype=4;
                break;
            case 'stdimp':
                $utype=40;
                break;
            case 'media':
                $utype=12;
                break;
            case 'apk':
                $utype=13;
                break;
            default:
                $utype=0;
                break;
        }//END switch
        $lstyle='';
        if((is_numeric($this->width) && $this->width>0)) {
            $lstyle.='width:'.$this->width.'px;';
        }
        if((is_numeric($this->height) && $this->height>0)) {
            $lstyle.='height:'.$this->height.'px;';
        }
        $lstyle=strlen($lstyle) ? ' style="'.$lstyle.'"' : '';
        $lalign=$this->align=='left' ? ' aleft' : '';
        $lClass=strlen($this->class) ? ' '.$this->class : '';
        $dclass=$this->droparea!==FALSE ? ' clsDropArea' : '';
        $lstatusid=strlen($this->status_target) ? ' data-statusid="'.$this->status_target.'"' : '';
        $lcallback=strlen($this->callback) && !$this->disabled ? ' data-callback="'.rawurlencode(GibberishAES::enc($this->callback,'HTML')).'"' : '';
        $this->target_dir=rawurlencode($this->target_dir);
        $this->sub_folder=rawurlencode($this->sub_folder);
        switch($this->theme_type) {
            case 'bootstrap2':
            case 'bootstrap3':
            case 'bootstrap4':
                $bclass=' class="'.(strlen($this->button_class) ? $this->button_class : 'btn btn-info btn-xs').'"';
                $result="\t".'<div class="'.$this->base_class.'Container'.$dclass.$lClass.$lalign.'"'.$lstyle.'>'."\n";
                if(!$this->disabled) {
                    $result.="\t\t".'<input type="file" id="'.$this->tag_id.'" class="'.$this->base_class.$lClass.'" data-url="'.NApp::$appBaseUrl.'/pipe/upload.php?rpa='.($this->require_login ? 1 : 0).'&namespace='.NApp::$currentNamespace.'&utype='.$utype.'" data-targetdir="'.$this->target_dir.'" data-subfolder="'.$this->sub_folder.'"'.$lstatusid.$lcallback.' name="files[]" multiple>'."\n";
                }
                break;
            default:
                $bclass=strlen($this->button_class) ? ' class="'.$this->button_class.'"' : '';
                $result="\t".'<div class="'.$this->base_class.'Container'.$dclass.$lClass.$lalign.'"'.$lstyle.'>'."\n";
                if(!$this->disabled) {
                    $result.="\t\t".'<input type="file" id="'.$this->tag_id.'" class="'.$this->base_class.$lClass.'" data-url="'.NApp::$appBaseUrl.'/pipe/upload.php?rpa='.($this->require_login ? 1 : 0).'&namespace='.NApp::$currentNamespace.'&utype='.$utype.'" data-targetdir="'.$this->target_dir.'" data-subfolder="'.$this->sub_folder.'"'.$lstatusid.$lcallback.' name="files[]" multiple>'."\n";
                }
                break;
        }//END switch
        $onclick=(!$this->disabled ? ' onclick="$(\'#'.$this->tag_id.'\').click()"' : ' disabled="disabled"');
        $result.="\t\t".'<button id="fu-button-'.$this->tag_id.'"'.$bclass.$onclick.'>'.$this->button_icon.$this->button_label.'</button>'."\n";
        if(strlen($this->dropzone_text)) {
            $result.="\t\t".'<span class="'.$this->base_class.'Text">'.$this->dropzone_text.'</span>'."\n";
        }//if(strlen($this->dropzone_text))
        $result.="\t".'</div>'."\n";
        if(!$this->disabled) {
            NApp::AddJsScript("CreateFileUploader('{$this->tag_id}',0);");
        }
        return $result;
    }//END protected function SetControl

    /**
     * Clears the base class of the control
     *
     * @return bool
     */
    public function ClearBaseClass(): bool {
        return FALSE;
    }//END public function ClearBaseClass
}//END class FileUploader extends Control