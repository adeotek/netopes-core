<?php
/**
 * Doctrine entities lifecycle pre-update event interface
 * Implement for catching entities pre-update event interface
 *
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    4.0.0.0
 */

namespace NETopes\Core\Data\Doctrine;

/**
 * IOnDoctrinePreUpdate interface
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