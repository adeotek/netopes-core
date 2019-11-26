<?php
/**
 * IPdfDocument interface file
 *
 * @package    NETopes\Reporting
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.9.15
 * @filesource
 */
namespace NETopes\Core\Reporting;

/**
 * Interface IPdfDocument
 *
 * @package NETopes\Core\Reporting
 */
interface IPdfDocument {
    /**
     * @return mixed
     */
    public function Render();
}//END interface IPdfDocument