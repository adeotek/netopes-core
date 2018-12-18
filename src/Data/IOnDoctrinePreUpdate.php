<?php
/**
 * Doctrine entities lifecycle pre-update event interface
 *
 * Implement for catching entities pre-update event interface
 *
 * @package    NETopes\Core\Data
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.3.0.2
 * @filesource
 */
namespace NETopes\Core\Data;

/**
 * Interface IOnDoctrinePreUpdate
 *
 * @package NETopes\Core\Data
 */
interface IOnDoctrinePreUpdate {
    /**
     * Pre-update callback
     * Entity annotation must include [@]ORM\HasLifecycleCallbacks
     *
     * @ORM\PreUpdate
     */
    public function ExecuteOnPreUpdate();
}//END interface IOnDoctrinePreUpdate