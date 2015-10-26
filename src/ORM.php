<?php

namespace Fastpress\Yaar;

use PDO;

class ORM
{
    const DEFAULT_CONNECTION_NAME = 'default';

    /**
     * @var PDO[]
     */
    protected $connections = array();
    /**
     * @var array
     */
    protected $connectionConfig = array();

    /**
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array  $options
     * @param string $connection
     *
     * @return string
     */
    public function connect($dsn, $username = '', $password = '', $options = array(), $connection = self::DEFAULT_CONNECTION_NAME)
    {
        $pdo = new PDO($dsn, $username, $password, $options);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connections[$connection] = $pdo;
        $this->connectionConfig[$connection]['quote_character'] = $this->detectQuoteCharacter($pdo);
    }

    /**
     * @param string $sql
     * @param array  $params
     * @param string $connection
     *
     * @return false|\PDOStatement
     */
    public function execute($sql, $params = array(), $connection = self::DEFAULT_CONNECTION_NAME)
    {
        $pdo = $this->getConnection($connection);
        $statement = $pdo->prepare($sql);
        if ($statement === false) {
            return false;
        }
        $statement->execute($params);
        return $statement;
    }

    /**
     * @param string $connection
     *
     * @return PDO
     */
    public function getConnection($connection = self::DEFAULT_CONNECTION_NAME)
    {
        return $this->connections[$connection];
    }

    public function quoteIdentifier($identifier, $connection = self::DEFAULT_CONNECTION_NAME)
    {
        if (is_array($identifier)) {
            return implode(',', array_map(array($this, 'quoteIdentifier'), $identifier));
        }
        $parts = explode('.', $identifier);
        $quotedParts = array();
        foreach ($parts as $part) {
            $quotedParts[] = $this->quoteSingleIdentifier($part, $connection);
        }
        return implode('.', $quotedParts);
    }

    /**
     * @param PDO $pdo
     *
     * @return string
     */
    protected function detectQuoteCharacter(PDO $pdo)
    {
        switch ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) {
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

    protected function quoteSingleIdentifier($identifier, $connection = self::DEFAULT_CONNECTION_NAME)
    {
        if ($identifier === '*') {
            return $identifier;
        }
        $quote = $this->connectionConfig[$connection]['quote_character'];
        return $quote . str_replace($quote, $quote . $quote, $identifier) . $quote;
    }
}
