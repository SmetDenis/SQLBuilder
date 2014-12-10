<?php
namespace SQLBuilder\Query\MySQLQuery;
use Exception;
use SQLBuilder\RawValue;
use SQLBuilder\Driver\BaseDriver;
use SQLBuilder\Driver\MySQLDriver;
use SQLBuilder\Driver\PgSQLDriver;
use SQLBuilder\Driver\SQLiteDriver;
use SQLBuilder\ToSqlInterface;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Bind;
use SQLBuilder\ParamMarker;

/**

MYSQL CREATE USER SYNTAX
=========================

CREATE USER user_specification [, user_specification] ...

user_specification:
    user
    [
      | IDENTIFIED WITH auth_plugin [AS 'auth_string']
        IDENTIFIED BY [PASSWORD] 'password'
    ]


When using auth plugin, we need to specify the password later.

The 'old_passwords' global variable is for the hash algorithm.

There are two mysql auth plugin:
    mysql_native_password
    mysql_old_password

CREATE USER 'jeffrey'@'localhost' IDENTIFIED WITH mysql_native_password;
SET old_passwords = 0;
SET PASSWORD FOR 'jeffrey'@'localhost' = PASSWORD('mypass');

*/
class UserSpecification { 

    public $account;

    public $host = 'localhost';

    public $password;

    public $parent;

    public $authPlugin;

    public function __construct($parent, $account) {
        $this->parent = $parent;
        $this->account = $account;
    }

    public function account($account)
    {
        $this->account = $account;
        return $this;
    }

    public function host($host) {
        $this->host = $host;
        return $this;
    }

    public function identifiedBy($pass) {
        $this->password = $pass;
        return $this;
    }

    public function identifiedWith($authPlugin) {
        $this->authPlugin = $authPlugin;
        return $this;
    }

    public function getAccount() {
        return $this->account;
    }

    public function getPassword() {
        return $this->password;
    }

    public function getHost() {
        return $this->host;
    }

    public function getAuthPlugin() {
        return $this->authPlugin;
    }

    public function __call($m , $args) {
        return call_user_func_array(array($this->parent, $m), $args);
    }
}

class CreateUserQuery implements ToSqlInterface
{
    public $userSpecifications = array();

    public function user($account) {
        $user = new UserSpecification($this, $account);
        $this->userSpecifications[] = $user;
        return $user;
    }

    public function toSql(BaseDriver $driver, ArgumentArray $args) {
        $specSql = array();
        foreach($this->userSpecifications as $spec) {
            $sql = $driver->quoteIdentifier($spec->getAccount()) . '@' . $driver->quoteIdentifier($spec->getHost());
            if ($pass = $spec->getPassword()) {
                $sql .= ' IDENTIFIED BY ' . $driver->quote($pass);
            }
            elseif ($authPlugin = $spec->getAuthPlugin()) {
                $sql .= ' IDENTIFIED WITH ' . $driver->quoteIdentifier($authPlugin);
            }
            $specSql[] = $sql;
        }
        return 'CREATE USER ' . join(', ', $specSql);
    }
}

