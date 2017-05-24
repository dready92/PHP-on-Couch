<?php

/*
 * Copyright (C) 2017 Alexis Côté
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace PHPOnCouch;

class Autoload {

    public static function autoload($className) {
        //PSR4 autoloader
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
