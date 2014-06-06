<?php

namespace Actions;
use Action, ServiceReloader;

class Disable extends Action
{
    /**
     * @var array
     */
    private $config;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $this->registerOp(null, [$this, 'setUpEnv']);
        $this->registerOp("Disabling config files", [$this, 'disableConfigFiles']);

        foreach ((new ServiceReloader)->getOps(ServiceReloader::OPS_TEST_CONFIG | ServiceReloader::OPS_RELOAD) as $op) {
            $this->registerOp($op[0], $op[1]);
        }
    }

    /**
     * Configure the environment based on the config array
     *
     * @param object $env
     */
    public function setUpEnv($env)
    {
        $args = fetch_args();

        if (!isset($args[0])) {
            echo "Y U NO SUPPLY HOSTNAME??!?!?!\n";
            exit(1);
        }

        $host = $args[0];
        $baseDir  = $this->config['hosts_dir'] . '/' . $host;
        if (!is_dir($baseDir)) {
            echo "Invalid hostname: {$host}\n";
            exit(1);
        }

        $env->confTemplates = [
            $this->config['fpm_conf_dir'] . '/' . $host . '.conf',
            $this->config['nginx_conf_dir'] . '/' . $host . '.conf',
        ];
    }

    /**
     * Disable the config files for the specified host
     *
     * @param object $env
     * @return string|null
     */
    public function disableConfigFiles($env)
    {
        foreach ($env->confTemplates as $linkFile) {
            if (!file_exists($linkFile)) {
                return "Config file {$linkFile} does not exist";
            }

            unlink($linkFile);
        }

        return null;
    }
}
