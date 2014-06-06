<?php

abstract class Action
{
    /**
     * @var Operation[]
     */
    private $ops = [];

    /**
     * @var Operation[]
     */
    private $executedOps = [];

    /**
     * Register an operation performed by this action
     *
     * @param string $name
     * @param callable $runFunc
     * @param callable $rollbackFunc
     */
    protected function registerOp($name, callable $runFunc, callable $rollbackFunc = null) {
        $this->ops[] = new Operation($name, $runFunc, $rollbackFunc);
    }

    /**
     * Execute this action
     *
     * @param Environment $env
     */
    public function run(Environment $env)
    {
        while ($this->ops) {
            if (!$this->ops[0]->run($env)) {
                $this->ops[0]->rollback($env);

                while ($this->executedOps) {
                    array_pop($this->executedOps)->rollback($env);
                }

                break;
            }

            $this->executedOps[] = array_shift($this->ops);
        }
    }
}
