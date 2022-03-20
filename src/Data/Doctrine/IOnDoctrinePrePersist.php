<?php
/**
 * Doctrine entities lifecycle pre-persist event interface
 * Implement for catching entities pre-persist event interface
 *
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    4.0.0.0
 */

namespace NETopes\Core\Data\Doctrine;

/**
 * IOnDoctrinePrePersist interface
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