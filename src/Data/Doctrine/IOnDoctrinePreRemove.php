<?php
/**
 * Doctrine entities lifecycle pre-remove event interface
 * Implement for catching entities pre-remove event interface
 *
 * @package    NETopes\Core\Data
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Data\Doctrine;
/**
 * Interface IOnDoctrinePreRemove
 *
 * @package NETopes\Core\Data\Doctrine
 */
interface IOnDoctrinePreRemove {
    /**
     * Pre-remove event callback
     * Entity annotation must include [@]ORM\HasLifecycleCallbacks
     *
     * @ORM\PreRemove
     */
    public function ExecuteOnPreRemove();
}//END interface IOnDoctrinePreRemove