<?php


namespace EasyCorp\Bundle\EasyDeployBundle\System;


class WindowsSystem extends AbstractSystem
{
    public $commands = [
        'export' => 'set',
        'which' => 'where'
    ];
}