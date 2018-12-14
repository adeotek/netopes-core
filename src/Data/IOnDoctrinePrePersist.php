<?php
/**
 * Doctrine entities lifecycle pre-persist event interface
 *
 * Implement for catching entities pre-persist event interface
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
 * Interface IOnDoctrinePrePersist
 *
 * @package NETopes\Core\Data
 */
interface IOnDoctrinePrePersist {
    /**
     * Pre-persist event callback
     * Entity annotation must include [@]ORM\HasLifecycleCallbacks
     *
     * @ORM\PrePersist
     */
    public function ExecuteOnPrePersist();
}//END interface IOnDoctrinePrePersist