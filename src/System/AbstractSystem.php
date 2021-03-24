<?php


namespace EasyCorp\Bundle\EasyDeployBundle\System;


class AbstractSystem
{

    protected $commands;
    protected $sessionPrefix;

    public function getCommand(string $command)
    {
        if(isset($this->commands[$command])){
            return $this->commands[$command];
        }

        return $command;
    }

    public function getSessionPrefix(){

    }

}