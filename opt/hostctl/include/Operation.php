<?php

class Operation
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var callable
     */
    private $runFunc;

    /**
     * @var callable
     */
    private $rollbackFunc;

    /**
     * @var Environment
     */
    private $oldEnv;

    /**
     * Constructor
     *
     * @param string $name
     * @param callable $runFunc
     * @param callable $rollbackFunc
     */
    public function __construct($name, callable $runFunc, callable $rollbackFunc = null)
    {
        $this->name = $name;
        $this->runFunc = $runFunc;
        $this->rollbackFunc = $rollbackFunc;
    }

    /**
     * Execute this operation
     *
     * @param Environment $env
     * @return bool
     */
    public function run(Environment $env)
    {
        $this->oldEnv = clone $env;

        if ($this->name !== null) {
            echo "{$this->name}...";
        }

        $ret = call_user_func($this->runFunc, $env);
        $result = $ret === null || $ret === true;

        if ($this->name !== null) {
            echo ' ' . ($result ? 'OK' : 'Failed') . "\n";

            if (!$result && $ret !== false) {
                foreach ((array) $ret as $line) {
                    echo " {$line}\n";
                }
            }
        }

        return $result;
    }

    /**
     * Roll back this operation
     *
     * @param Environment $env
     */
    public function rollback(Environment $env)
    {
        if ($this->rollbackFunc !== null) {
            if ($this->name !== null) {
                echo "Rolling back: {$this->name}...";
            }

            call_user_func($this->rollbackFunc, $env, $this->oldEnv);

            if ($this->name !== null) {
                echo " Done\n";
            }
        }
    }
}
