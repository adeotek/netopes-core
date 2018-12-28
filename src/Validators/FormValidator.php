<?php
/**
 * FormValidator class file
 *
 * Class for validating forms
 *
 * @package    NETopes\Core\Validators
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.3.1.1
 * @filesource
 */
namespace NETopes\Core\Validators;
use NETopes\Core\App\Params;
use NApp;

/**
 * Class FormValidator
 *
 * @package NETopes\Core\Validators
 */
class FormValidator {
    /**
     * @var string Form element value validation class
     */
    protected $validationClass = Validator::class;
    /**
     * @var array Form elements array
     */
    protected $formElements = [];
    /**
     * @var \NETopes\Core\App\Params|null Form data Params collection instance
     */
    protected $formData = NULL;
    /**
     * FormValidator class constructor method
     *
     * @param array|null                          $params Parameters array
     * @param \NETopes\Core\App\Params|array|null $data Form data array|Params collection
     * @access public
     * @throws \PAF\AppException
     */
	public function __construct(?array $params = NULL,$data = NULL) {
		if(is_array($params) && count($params)) {
		    if(array_key_exists('content',$params)) {
		        $this->formElements = $params['content'];
		    } else {
		        $this->formElements = $params;
		    }//if(array_key_exists('content',$params))
		}//if(is_array($params) && count($params))
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
     * @throws \PAF\AppException
     */
    public function GetFormData(): Params {
        return $this->formData ?? new Params();
    }//END public function GetFormData
    /**
     * @param \NETopes\Core\App\Params|null $formData
     */
    public function SetFormData(?Params $formData): void {
        $this->formData = $formData;
    }//END public function SetFormData

    public function Validate() {
        if(!count($this->formElements)) { return FALSE; }
        $errors = [];
        foreach($this->formElements as $element) {
            $key = get_array_value($element,'validation_key',get_array_value($element,'tagname','','is_string'),'is_string');
            if(!strlen($key)) {
                NApp::_Dlog('Invalid form element key: '.print_r($element));
                continue;
            }//if(!strlen($key))
            $required = get_array_value($element,'required',FALSE,'bool');
            $validationType = get_array_value($element,'validation_type','','is_string');
            if(!call_user_func($this->validationClass.'::IsValidParam')) { // TODO: check IsValidParam
                $errors[] = [];
            }
        }//END foreach
        return (count($errors) ? $errors : TRUE);
    }//public function Validate
}//END class FormValidator