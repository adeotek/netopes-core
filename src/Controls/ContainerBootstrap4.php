<?php
/**
 * Control container class file
 *
 * Control container implementation
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.4.0.3
 * @filesource
 */
namespace NETopes\Core\Controls;
/**
 * Control container class file
 *
 * Control container implementation for Bootstrap3
 *
 * @package  NETopes\Core\Controls
 * @access   public
 */
class ContainerBootstrap4 implements IControlContainer {
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
            $c_class = is_string($this->control->container_class) && strlen(trim($this->control->container_class)) ? $this->control->container_class : '';
            if(!$this->control->no_label) {
                if($this->control->label_position=='top') {
                    $c_label_cols = 12;
                } else {
                    $c_label_cols = is_numeric($this->control->label_cols) && $this->control->label_cols>0 && $this->control->label_cols<12 ? $this->control->label_cols : 2;
                }//if($this->control->label_position=='top')
                $llabelclass = 'control-label col-md-'.$c_label_cols.(strlen($this->control->labelclass) ? ' '.$this->control->labelclass : '');
                $lrequired = $this->control->required ? '<span class="clsMarkerRequired"></span>' : '';
                if(strlen($this->control->size)) { $llabelclass .= ' label-'.$this->control->size; }
                $c_label = "\t\t".'<label class="'.$llabelclass.'" for="'.$this->control->tag_id.'">'.$this->control->label.$lrequired.'</label>'."\n";
            }//if(!$this->control->no_label)
            if(!$this->control->no_label && $this->control->label_position=='top') {
                $c_cols = is_numeric($this->control->cols) && $this->control->cols>0 && $this->control->cols<=12 ? $this->control->cols : 12;
            } else {
                $c_cols = is_numeric($this->control->cols) && $this->control->cols>0 && $this->control->cols<=(12-$c_label_cols) ? $this->control->cols : (12-$c_label_cols);
            }//if(!$this->control->no_label && $this->control->label_position=='top')
            if($this->control->container) { $result .= "\t".'<div class="form-group'.(strlen($c_class) ? ' '.$c_class : '').'">'."\n"; }
            if($this->control->label_position!='right') { $result .= $c_label; }
            $result .= "\t\t".'<div class="col-md-'.$c_cols.'">'."\n";
            if($this->control->hasActions()) {
                $content = "\t\t\t".'<div class="input-group">'."\n\t\t\t\t".$content."\n\t\t\t".'</div>';
            } else {
                $content = "\t\t\t".$content."\n";
            }//if($this->control->hasActions())
            $result .= $content;
            if(is_string($this->control->field_hint) && strlen($this->control->field_hint)) {
                $result .= "\t\t\t".'<p'.(strlen($this->control->tag_id) ? ' id="'.$this->control->tag_id.'_hint"' : '').' class="help-block">'.$this->control->field_hint.'</p>'."\n";
            }//if(is_string($this->control->field_hint) && strlen($this->control->field_hint))
            $result .= "\t\t".'</div>'."\n";
            if($this->control->label_position=='right') { $result .= $c_label; }
            if($this->control->container) { $result .= "\t".'</div>'."\n"; }
        }//if(!$this->control->container && $this->control->no_label)
        return $result;
    }//END public function GetHtml
}//END class ContainerBootstrap4 implements IControlContainer