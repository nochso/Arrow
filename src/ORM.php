<?php

namespace Fastpress\Yaar;

use PDO;

class ORM
{
    /**
     * @var ORM
     */
    public static $instance;

    /**
     * @var PDO
     */
    public $pdo;
    /**
     * @var array
     */
    protected $connectionConfig = array();

    /**
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array  $options
     *
     * @return string
     */
    public function connect($dsn, $username = '', $password = '', $options = array())
    {
        $this->pdo = new PDO($dsn, $username, $password, $options);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connectionConfig['quote_character'] = $this->detectQuoteCharacter();
    }

    public function __construct()
    {
        self::$instance = $this;
    }

    /**
     * @param string $sql
     * @param array  $params
     *
     * @return false|\PDOStatement
     */
    public function execute($sql, $params = array())
    {
        $statement = $this->pdo->prepare($sql);
        if ($statement === false) {
            return false;
        }
        $statement->execute($params);
        return $statement;
    }

    public function quoteIdentifier($identifier)
    {
        if (is_array($identifier)) {
            return implode(',', array_map(array($this, 'quoteIdentifier'), $identifier));
        }
        $parts = explode('.', $identifier);
        $quotedParts = array();
        foreach ($parts as $part) {
            $quotedParts[] = $this->quoteSingleIdentifier($part);
        }
        return implode('.', $quotedParts);
    }

    /**
     * @return string
     */
    protected function detectQuoteCharacter()
    {
        switch ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            case 'pgsql':
            case 'sqlsrv':
            case 'dblib':
            case 'mssql':
            case 'sybase':
            case 'firebird':
                return '"';
            case 'mysql':
            case 'sqlite':
            case 'sqlite2':
            default:
                return '`';
        }
    }

    protected function quoteSingleIdentifier($identifier)
    {
        if ($identifier === '*') {
            return $identifier;
        }
        $quote = $this->connectionConfig['quote_character'];
        return $quote . str_replace($quote, $quote . $quote, $identifier) . $quote;
    }
}
