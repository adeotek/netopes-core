<?php
/**
 * FormValidator class file
 * Class for validating forms
 *
 * @package    NETopes\Core\Validators
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Validators;
use NETopes\Core\App\Params;
use NApp;
/**
 * Class FormValidator
 * @package NETopes\Core\Validators
 */
class FormValidator {
    /**
     * @var array Form elements array
     */
    protected $formElements = [];
    /**
     * @var \NETopes\Core\App\Params|null Form data Params collection instance
     */
    protected $formData = NULL;
    /**
     * @var array Form errors array
     */
    protected $errors = [];
    /**
     * FormValidator class constructor method
     * @param array|null                          $config Parameters array
     * @param \NETopes\Core\App\Params|array|null $data Form data array|Params collection
     * @throws \NETopes\Core\AppException
     */
	public function __construct($data = NULL,?array $config = NULL) {
		if(is_array($config) && count($config)) {
		    if(array_key_exists('content',$config)) {
		        $this->formElements = $config['content'];
		    } else {
		        $this->formElements = $config;
		    }//if(array_key_exists('content',$config))
		}//if(is_array($config) && count($config))
        if(is_array($data)) {
            $this->formData = new Params($data);
        } elseif(is_object($data) && $data instanceof Params) {
            $this->formData = $data;
        }//if(is_array($data))
	}//END public function __construct
    /**
     * @return array
     */
    public function GetFormElements(): array {
        return $this->formElements;
    }//END public function GetFormElements
    /**
     * @param array $formElements
     */
    public function SetFormElements(array $formElements): void {
        $this->formElements = $formElements;
    }//END public function SetFormElements
    /**
     * @return \NETopes\Core\App\Params
     * @throws \NETopes\Core\AppException
     */
    public function GetFormData(): Params {
        return $this->formData ?? new Params();
    }//END public function GetFormData
    /**
     * @param \NETopes\Core\App\Params|null $formData
     */
    public function SetFormData(?Params $formData): void {
        $this->formData = $formData;
    }
    /**
     * @return array
     */
    public function getErrors(): array {
        return $this->errors;
    }//END public function SetFormData
    public function Validate(): bool {
        if(!count($this->formElements)) { return FALSE; }
        $errors = [];
        foreach($this->formElements as $element) {
            $key = get_array_value($element,'validation_key',get_array_value($element,'tag_name','','is_string'),'is_string');
            if(!strlen($key)) {
                NApp::Dlog('Invalid form element key: '.print_r($element));
                continue;
            }//if(!strlen($key))
            $deafultValue = NULL;
            $required = get_array_value($element,'required',FALSE,'bool');
            $validationType = get_array_value($element,'validation_type','','?is_notempty_string');
            $sourceFormat = get_array_value($element,'source_format',NULL,'?is_notempty_string');
            $isValid = FALSE;
            $value = $this->formData->safeGet($key,$deafultValue,$validationType,$sourceFormat,$isValid);
            if(!$isValid) {
                $this->errors[] = [];
            }
        }//END foreach
        return (count($this->errors)==0);
    }//public function Validate
}//END class FormValidator