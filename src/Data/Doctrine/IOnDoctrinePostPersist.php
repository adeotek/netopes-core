<?php
/**
 * Doctrine entities lifecycle post-persist event interface
 * Implement for catching entities post-persist event interface
 *
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    4.0.0.0
 */

namespace NETopes\Core\Data\Doctrine;

/**
 * IOnDoctrinePostPersist interface
 */
interface IOnDoctrinePostPersist {
    /**
     * Post-persist event callback
     * Entity annotation must include [@]ORM\HasLifecycleCallbacks
     *
     * @ORM\PostPersist
     */
    public function ExecuteOnPostPersist();
}//END interface IOnDoctrinePostPersist