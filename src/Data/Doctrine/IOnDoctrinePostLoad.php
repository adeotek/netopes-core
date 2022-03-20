<?php
/**
 * Doctrine entities lifecycle post-load event interface
 * Implement for catching entities post-load event interface
 *
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    4.0.0.0
 */

namespace NETopes\Core\Data\Doctrine;

/**
 * IOnDoctrinePostLoad interface
 */
interface IOnDoctrinePostLoad {
    /**
     * Post-load event callback
     * Entity annotation must include [@]ORM\HasLifecycleCallbacks
     *
     * @ORM\PrePersist
     */
    public function ExecuteOnPostLoad();
}//END interface IOnDoctrinePostLoad