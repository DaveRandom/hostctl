<?php

namespace Actions;
use Action, ServiceReloader;

class Provision extends Action
{
    /**
     * Status code constants
     */
    const SUCCESS = 0;
    const OBJECT_CREATED = 0x80;

    const OBJECT_EXISTS          = 1;
    const OBJECT_CREATION_FAILED = 2;
    const CHOWN_FAILED           = 3;
    const CHGRP_FAILED           = 4;

    /**
     * @var int[]
     */
    private $createDirectoriesResult = [];

    /**
     * @var int[]
     */
    private $createConfigFilesResult = [];

    /**
     * @var string[]
     */
    private $confTemplates = [];

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
        $this->registerOp("Creating directories", [$this, 'createDirectories'], [$this, 'removeDirectories']);
        $this->registerOp("Creating config files", [$this, 'createConfigFiles'], [$this, 'removeConfigFiles']);

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

        $env->HOSTNAME  = $args[0];
        $env->BASEDIR   = $this->config['hosts_dir'] . '/' . $env->HOSTNAME;
        $env->SOCKPATH  = $this->config['sock_dir'] . '/' . $env->HOSTNAME . '.sock';
        $env->PUBLICDIR = $env->BASEDIR . '/public';
        $env->CONFDIR   = $env->BASEDIR . '/conf';
        $env->LOGDIR    = $env->BASEDIR . '/logs';

        $this->confTemplates = [
            $this->config['fpm_conf_dir'] . '/' . $env->HOSTNAME . '.conf'   => 'fpm.conf',
            $this->config['nginx_conf_dir'] . '/' . $env->HOSTNAME . '.conf' => 'nginx.conf',
        ];
    }

    /**
     * Create the required directories
     *
     * @param object $env
     * @return bool|string
     */
    public function createDirectories($env)
    {
        $success = true;
        clearstatcache(true);
        $env->createDirectoriesResult = [];
        $dir = '';

        foreach ([$env->BASEDIR, $env->PUBLICDIR, $env->CONFDIR, $env->LOGDIR] as $dir) {
            if (file_exists($dir)) {
                $this->createDirectoriesResult[$dir] = self::OBJECT_EXISTS;
                $success = false;
                break;
            }

            if (!mkdir($dir, 0755, true)) {
                $this->createDirectoriesResult[$dir] = self::OBJECT_CREATION_FAILED;
                $success = false;
                break;
            }

            if (!chown($dir, 'nginx')) {
                $this->createDirectoriesResult[$dir] = self::CHOWN_FAILED | self::OBJECT_CREATED;
                $success = false;
                break;
            }

            if (!chgrp($dir, 'nginx')) {
                $this->createDirectoriesResult[$dir] = self::CHGRP_FAILED | self::OBJECT_CREATED;
                $success = false;
                break;
            }

            $this->createDirectoriesResult[$dir] = self::SUCCESS | self::OBJECT_CREATED;
        }

        return $success ? true : "Failed to create directory {$dir}!";
    }

    /**
     * Remove created directories during a rollback
     */
    public function removeDirectories()
    {
        clearstatcache(true);

        foreach ($this->createDirectoriesResult as $dir => $result) {
            if (($result & self::OBJECT_CREATED) && is_dir($dir)) {
                rmdir_plus($dir);
            }
        }
    }

    /**
     * Create the required config files from templates
     *
     * @param object $env
     * @return bool
     */
    public function createConfigFiles($env)
    {
        $success = true;
        clearstatcache(true);
        $templateName = '';

        foreach ($this->confTemplates as $linkFile => $templateName) {
            $inFile = sprintf('%s/%s.tpl', $this->config['templates_dir'], $templateName);
            $outFile = sprintf('%s/%s', $env->CONFDIR, $templateName);

            if (file_exists($outFile)) {
                $this->createConfigFilesResult[$outFile] = self::OBJECT_EXISTS;
                $success = false;
                break;
            }

            if (file_exists($linkFile)) {
                $this->createConfigFilesResult[$linkFile] = self::OBJECT_EXISTS;
                $success = false;
                break;
            }

            if (!render_template_file($inFile, $env, $outFile)) {
                $this->createConfigFilesResult[$outFile] = self::OBJECT_CREATION_FAILED;
                $success = false;
                break;
            }

            if (!symlink($outFile, $linkFile)) {
                $this->createConfigFilesResult[$linkFile] = self::OBJECT_CREATION_FAILED;
                $success = false;
                break;
            }

            $this->createConfigFilesResult[$outFile] = $this->createConfigFilesResult[$linkFile] = self::SUCCESS | self::OBJECT_CREATED;
        }

        return $success ? true : "Failed to generate config file {$templateName}!";
    }

    /**
     * Remove created config files during rollback
     */
    public function removeConfigFiles()
    {
        foreach ($this->createConfigFilesResult as $file => $result) {
            if (($result & self::OBJECT_CREATED) && is_file($file)) {
                unlink($file);
            }
        }
    }
}
