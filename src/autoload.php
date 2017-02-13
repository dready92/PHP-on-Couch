<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace PHPOnCouch;

class Autoload {

    public static function autoload($className) {
        //PSR4 autloader
        $splitName = explode('\\', $className);
        if ($splitName[0] == __NAMESPACE__) {
            array_shift($splitName);
            $ps4Format = join(DIRECTORY_SEPARATOR, $splitName);
            $file_name = join(DIRECTORY_SEPARATOR, [ROOT_DIR, $ps4Format . '.php']);


            //Default source files
            if (file_exists($file_name)) {
                require_once $file_name;
            }
        }
    }

}

define('ROOT_DIR', __DIR__);
spl_autoload_register(['PHPOnCouch\\Autoload', 'autoload']);
