<?php
/**
 * Created by PhpStorm.
 * User: cwright
 * Date: 14/03/14
 * Time: 16:45
 */

namespace Actions;
use Action, ServiceReloader;

class ConfigTest extends Action
{
    /**
     * Constructor
     */
    public function __construct()
    {
        foreach ((new ServiceReloader)->getOps(ServiceReloader::OPS_TEST_CONFIG) as $op) {
            $this->registerOp($op[0], $op[1]);
        }
    }
}
