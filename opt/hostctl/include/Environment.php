<?php

class Environment
{
    /**
     * Ensure a deep copy happens when cloning environment objects
     *
     * I have no idea why this is necessary but I assume it is so I'm leaving it for now
     */
    public function __clone()
    {
        foreach (get_object_vars($this) as $name => $value) {
            if (is_object($value) && !($value instanceof \Closure)) {
                $this->$name = clone $this->$name;
            }
        }
    }
}
