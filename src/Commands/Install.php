<?php

namespace Tualo\Office\SystemFiles\Commands;

use Tualo\Office\Basic\ICommandline;
use Tualo\Office\Basic\CommandLineInstallSQL;

class Install extends CommandLineInstallSQL  implements ICommandline
{
    public static function getDir(): string
    {
        return dirname(__DIR__, 1);
    }
    public static $shortName  = 'systemfile';
    public static $files = [
        'install/system_file' => 'setup system_file',
    ];
}
