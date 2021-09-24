<?php
/**
 * Control container class file
 * Control container implementation
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
/**
 * Control container class file
 * Control container implementation for Bootstrap3
 *
 * @package  NETopes\Core\Controls
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
     */
    public function __construct($control) {
        $this->control=$control;
    }//END public function __construct

    /**
     * @param string      $content
     * @param string|null $secondaryContent
     * @return string
     */
    public function GetHtml(string $content,?string $secondaryContent=NULL) {
        if(!$this->control->container && $this->control->no_label && !$this->control->input_group) {
            $result=$content.$secondaryContent;
        } else {
            $result='';
            $labelCols=0;
            $labelTag='';
            $containerClass=is_string($this->control->container_class) && strlen(trim($this->control->container_class)) ? $this->control->container_class : '';
            if(!$this->control->no_label) {
                $labelClass='control-label';
                if($this->control->inline!==TRUE) {
                    if($this->control->label_position=='top') {
                        $labelCols=12;
                    } else {
                        $labelCols=is_numeric($this->control->label_cols) && $this->control->label_cols>0 && $this->control->label_cols<12 ? $this->control->label_cols : 2;
                    }//if($this->control->label_position=='top')
                    $labelClass.=' col-md-'.$labelCols;
                }
                $labelClass.=(strlen($this->control->labelclass) ? ' '.$this->control->labelclass : '');
                if($this->control->required) {
                    $isRequired=$this->control->multi_field_require ? '<span class="clsDblMarkerRequired"></span>' : '<span class="clsMarkerRequired"></span>';
                } else {
                    $isRequired='';
                }
                if(strlen($this->control->size)) {
                    $labelClass.=' label-'.$this->control->size;
                }
                $labelTag="\t\t".'<label class="'.$labelClass.'" for="'.$this->control->tag_id.'">'.$this->control->label.$isRequired.'</label>'."\n";
            }//if(!$this->control->no_label)
            if(!$this->control->no_label && $this->control->label_position=='top') {
                $c_cols=is_numeric($this->control->cols) && $this->control->cols>0 && $this->control->cols<=12 ? $this->control->cols : 12;
            } else {
                $c_cols=is_numeric($this->control->cols) && $this->control->cols>0 && $this->control->cols<=(12 - $labelCols) ? $this->control->cols : (12 - $labelCols);
            }//if(!$this->control->no_label && $this->control->label_position=='top')
            if($this->control->container) {
                $result.="\t".'<div class="form-group'.(strlen($containerClass) ? ' '.$containerClass : '').'">'."\n";
            }
            if($this->control->label_position!='right') {
                $result.=$labelTag;
            }
            if($this->control->inline!==TRUE) {
                if(get_class($this->control)==CheckBox::class && strlen($this->control->align)) {
                    $result.="\t\t".'<div class="col-md-'.$c_cols.'" style="text-align: '.$this->control->align.';">'."\n";
                } else {
                    $result.="\t\t".'<div class="col-md-'.$c_cols.'">'."\n";
                }
            }
            if($this->control->hasActions()) {
                $content="\t\t\t".'<div class="input-group">'."\n\t\t\t\t".$content."\n\t\t\t".'</div>';
            } else {
                $content="\t\t\t".$content."\n";
            }//if($this->control->hasActions())
            $result.=$content.$secondaryContent;
            if(is_string($this->control->field_hint) && strlen($this->control->field_hint)) {
                $result.="\t\t\t".'<p'.(strlen($this->control->tag_id) ? ' id="'.$this->control->tag_id.'_hint"' : '').' class="help-block">'.$this->control->field_hint.'</p>'."\n";
            }//if(is_string($this->control->field_hint) && strlen($this->control->field_hint))
            if($this->control->inline!==TRUE) {
                $result.="\t\t".'</div>'."\n";
            }
            if($this->control->label_position=='right') {
                $result.=$labelTag;
            }
            if($this->control->container) {
                $result.="\t".'</div>'."\n";
            }
        }//if(!$this->control->container && $this->control->no_label)
        return $result;
    }//END public function GetHtml
}//END class ContainerBootstrap3 implements IControlContainer