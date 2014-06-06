<?php

class ServiceReloader
{
    /**
     * Operation class constants
     */
    const OPS_TEST_CONFIG = 1;
    const OPS_RELOAD = 2;

    /**
     * Test the config files for the named service
     *
     * @param string $serviceName
     * @return bool
     */
    private function testServiceConfig($serviceName)
    {
        list($code, $stdOut, $stdErr) = exec_plus("service {$serviceName} configtest");
        return $code === 0 ? true : preg_split('/\s*[\r\n]+\s*/', trim($stdErr));
    }

    /**
     * Reload the named service
     *
     * @param string $serviceName
     * @return bool
     */
    private function reloadService($serviceName)
    {
        return exec_plus("service {$serviceName} reload")[0] === 0;
    }

    /**
     * Test the config files for the php-fpm service
     *
     * @return bool
     */
    public function testFPMConfig()
    {
        return $this->testServiceConfig('php-fpm');
    }

    /**
     * Test the config files for the nginx service
     *
     * @return bool
     */
    public function testNginxConfig()
    {
        return $this->testServiceConfig('nginx');
    }

    /**
     * Reload the php-fpm service
     *
     * @return bool
     */
    public function reloadFPM()
    {
        return $this->reloadService('php-fpm');
    }

    /**
     * Reload the nginx service
     *
     * @return bool
     */
    public function reloadNginx()
    {
        return $this->reloadService('nginx');
    }

    /**
     * Get the operation classes defined by the bit supplied mask
     *
     * @param int $ops
     * @return array
     */
    public function getOps($ops)
    {
        $result = [];

        if ($ops & self::OPS_TEST_CONFIG) {
            $result[] = ["Testing FPM config", [$this, 'testFPMConfig']];
            $result[] = ["Testing Nginx config", [$this, 'testNginxConfig']];
        }

        if ($ops & self::OPS_RELOAD) {
            $result[] = ["Reloading FPM", [$this, 'reloadFPM']];
            $result[] = ["Reloading Nginx", [$this, 'reloadNginx']];
        }

        return $result;
    }
}
