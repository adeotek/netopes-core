<?php
/**
 * NETopes application path class file containing helper methods for application paths
 *
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    4.0.0.0
 */

namespace NETopes\Core;
/**
 * NETopes path class
 * The NETopes path class contains helper methods for application paths.
 */
class AppPath {
    /**
     * Get NETopes path
     *
     * @return string
     */
    public static function GetPath(): string {
        return __DIR__;
    }//END public static function GetPath

    /**
     * Get NETopes boot file
     *
     * @return string
     */
    public static function GetBootFile(): string {
        return __DIR__.'/boot.php';
    }//END public static function GetBootFile
}//END class AppPath