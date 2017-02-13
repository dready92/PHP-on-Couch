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

namespace PhpOnCouch\Adapter;

/**
 * Description of CouchHttpAdapterBase
 *
 * @author alexis
 */
abstract class AbstractCouchHttpAdapter implements CouchHttpAdapterInterface {

    protected $dsn = null;
    protected $options = null;

    /**
     *
     * @var array allowed HTTP methods for REST dialog
     */
    protected $httpMethods = [
        SELF::METHOD_PUT,
        SELF::METHOD_POST,
        SELF::METHOD_GET,
        SELF::METHOD_DELETE,
        SELF::METHOD_COPY
    ];

    /**
     *
     * @var string the session cookie
     */
    protected $sessioncookie = null;

    public function setDsn($dsn) {
        $this->dsn = $dsn;
    }

    public function getDsn() {
        return $this->dsn;
    }

    public function setOptions($options) {
        $this->options = $options;
    }

    public function getOptions() {
        return $this->options;
    }

    public function __construct($options) {
        $this->setOptions($options);
    }

    /**
     * set the session cookie to send in the headers
     *
     * @param string $cookie
     *            the session cookie ( example : AuthSession=Y291Y2g6NENGNDgzNz )
     *
     * @return \PHPOnCouch\Couch
     */
    public function setSessionCookie($cookie) {
        $this->sessioncookie = $cookie;
        return $this;
    }

    /**
     * get the session cookie
     *
     * @return string cookie
     */
    public function getSessionCookie() {
        return $this->sessioncookie;
    }

    /**
     * get the session cookie
     *
     * @return string cookie
     */
    public function hasSessionCookie() {
        return (bool) $this->sessioncookie;
    }

    abstract public function query($method, $url, $parameters = [], $data = null, $contentType = null);

    abstract public function storeAsFile($url, $data, $contentType);

    abstract public function storeFile($url, $file, $contentType);

    abstract public function continuousQuery($callable, $method, $url, $parameters = [], $data = null);
}
