<?php
/**
 * Doctrine entities lifecycle pre-remove event interface
 * Implement for catching entities pre-remove event interface
 *
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    4.0.0.0
 */

namespace NETopes\Core\Data\Doctrine;

/**
 * IOnDoctrinePreRemove interface
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