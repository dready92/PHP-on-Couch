<?php

/*
  Copyright (C) 2017  Alexis Cote

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Lesser General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Lesser General Public License for more details.

  You should have received a copy of the GNU Lesser General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace PHPOnCouch\Adapter;


/**
 *
 * @author alexis
 */
interface CouchHttpAdapterInterface
{

    const METHOD_PUT = 'PUT';
    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';
    const METHOD_DELETE = 'DELETE';
    const METHOD_COPY = 'COPY';

    public function setDsn($dsn);

    public function getDsn();

    public function setOptions($options);

    public function getOptions();

    public function query($method, $url, $parameters = [], $data = null, $contentType = null);

    public function storeAsFile($url, $data, $contentType);

    public function storeFile($url, $file, $contentType);

    public function continuousQuery($callable, $method, $url, $parameters = [], $data = null, $caller = null);

    public function setSessionCookie($cookie);

    public function getSessionCookie();
}
