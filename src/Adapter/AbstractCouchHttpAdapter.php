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
 * Description of CouchHttpAdapterBase
 *
 * @author alexis
 */
abstract class AbstractCouchHttpAdapter implements CouchHttpAdapterInterface
{

    protected $dsn = null;

    /**
     * @var array database source name parsed
     */
    protected $dsnParsed = null;
    protected $options = null;

    /**
     *
     * @var array allowed HTTP methods for REST dialog
     */
    protected $httpMethods = [
        self::METHOD_PUT,
        self::METHOD_POST,
        self::METHOD_GET,
        self::METHOD_DELETE,
        self::METHOD_COPY
    ];

    /**
     *
     * @var string the session cookie
     */
    protected $sessioncookie = null;

    public function setDsn($dsn)
    {
        $this->dsn = $dsn;
    }

    public function getDsn()
    {
        return $this->dsn;
    }

    public function setOptions($options)
    {
        $this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function __construct($dsn, $options = [])
    {
        $this->setOptions($options);
        $this->dsn = preg_replace('@/+$@', '', $dsn);
        if (($parsed = parse_url($this->dsn)))
            $this->dsnParsed = $parsed;

        if (!isset($this->dsnParsed['port'])) {
            $this->dsnParsed['port'] = 80;
        }
    }

    /**
     * set the session cookie to send in the headers
     *
     * @param string $cookie
     *            the session cookie ( example : AuthSession=Y291Y2g6NENGNDgzNz )
     *
     * @return \PHPOnCouch\Adapter\AbstractCouchHttpAdapter
     */
    public function setSessionCookie($cookie)
    {
        $this->sessioncookie = $cookie;
        return $this;
    }

    /**
     * get the session cookie
     *
     * @return string cookie
     */
    public function getSessionCookie()
    {
        return $this->sessioncookie;
    }

    /**
     * get the session cookie
     *
     * @return string cookie
     */
    public function hasSessionCookie()
    {
        return (bool)$this->sessioncookie;
    }

    /**
     * return a part of the data source name
     *
     * if $part parameter is empty, returns dns array
     *
     * @param string $part part to return
     * @return string DSN part
     */
    protected function dsnPart($part = '')
    {
        if (strlen($part) <= 0) {
            return $this->getDsn();
        }
        if (isset($this->dsnParsed[$part])) {
            return $this->dsnParsed[$part];
        }
    }

    abstract public function query($method, $url, $parameters = [], $data = null, $contentType = null);

    abstract public function storeAsFile($url, $data, $contentType);

    abstract public function storeFile($url, $file, $contentType);

    abstract public function continuousQuery($callable, $method, $url, $parameters = [], $data = null, $caller = null);
}
