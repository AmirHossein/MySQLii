<?php
/**
 * MySQLi Improvement
 * Author: Amir Hossein Hodjati Pour <me@amir-hossein.com>
 * Date: 13/1/12
 * Time: 19:19
 */
class MySQLii {
    /**
     * @var MySQLi connection
     */
    public $mysqli;

    /**
     * @access protected
     * @var string MySQLi last error
     */
    protected $_error = '';
    /**
     * @access protected
     * @var int MySQLi last error number
     */
    protected $_errno = 0;
    /**
     * @access protected
     * @var string MySQLi last error SQL statement
     */
    protected $_sqlstate = '00000';
    /**
     * @access protected
     * @var string MySQLii last error
     */
    protected $_internal_error = '';
    /**
     * @access protected
     * @var string MySQL default delimiter used by multiple queries
     */
    protected $query_delimiter = ';';
    /**
     * @access protected
     * @var bool Check transaction status
     */
    protected $inTransaction = false;


    public function __construct(MySQLi &$mysqli = null) {
        if ($mysqli instanceof MySQLi) {
            $this->mysqli = &$mysqli;
        }
    }

    /**
     * Connect to mysql database
     *
     * @param string $host MySQLi host
     * @param string $username MySQL username
     * @param string $password MySQL password
     * @param string $dbname MySQL database name
     * @param int    $port MySQL port
     * @param string $socket MySQL socket
     * @return bool
     */
    public function connect($host = null, $username = null, $password = null, $dbname = null, $port = null, $socket = null) {
        $this->mysqli = new MySQLi($host, $username, $password, $dbname, $port, $socket);
        $this->_checkErrors($this->mysqli->connect_error, $this->mysqli->connect_errno, null);
        return empty($this->mysqli->connect_error);
    }

    /**
     * Close Connection
     *
     * @return bool
     */
    public function close() {
        if (isset($this->mysqli)) {
            if ($this->mysqli->close()) {
                $this->mysqli = null;
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * Alias of self::close
     */
    public function disconnect() {
        return $this->close();
    }

    /**
     * Quote string for MySQL
     *
     * @param string|NULL $str Value to quote
     * @param bool $checkNull Set TRUE to check for NULL value and use MySQL NULL instead of '' string
     * @return string quoted string
     */
    public function quote($str, $checkNull = false) {
        if ($checkNull === true && is_null($str)) {
            return 'NULL';
        }
        return isset($this->mysqli) ? "'" . $this->mysqli->real_escape_string((string)$str) . "'" : "''";
    }

    /**
     * Execute SQL statement
     *
     * @param string SQL to execute
     * @return bool TRUE on successful and FALSE on failure.
     */
    public function exec($sql) {
        if (isset($this->mysqli)) {
            if ($this->mysqli->query($sql) === true) {
                return true;
            }
            $this->_checkErrors();
        }
        return false;
    }

    /**
     * Execute Multiple SQL statements
     *
     * @params string SQL queries. They will be separated by default mysql delimiter
     * @return int Total number of affected rows. 0 means no row changed. -1 means error and no affected rows.
     */
    public function exec_multiple() {
        if (func_num_args() > 0) {
            $args = func_get_args();
            $sql = join($this->query_delimiter, $args);
            $this->mysqli->multi_query($sql);
            do {
                if ($result = $this->mysqli->use_result()) {
                    $result->close();
                }
            } while ($this->mysqli->more_results && $this->mysqli->next_result());

            $this->_checkErrors();
            if ($this->hasError()) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Alias of self::exec_multiple()
     */
    public function multiple_exec() {
        $args = func_get_args();
        return call_user_func_array(array($this, 'exec_multiple'), $args);
    }

    /**
     * Run a query
     *
     * @param string $sql SQL Statement
     * @return MySQLiiStatement instance
     */
    public function query($sql) {
        if (isset($this->mysqli)) {
            //$result = $this->mysqli->query($sql);
        }
    }

    /**
     * Run multiple queries
     *
     * @params string SQL queries. They will be separated by default mysql delimiter
     * @return MySQLiiStatement instance
     */
    public function query_multiple() {
        if (isset($this->mysqli)) {

        }
    }

    /**
     * Alias of self::query_multiple()
     */
    public function multiple_query() {
        $args = func_get_args();
        return call_user_func_array(array($this, 'query_multiple'), $args);
    }

    /**
     * Create a new prepared statement
     *
     * @param string sql query
     * @params mixed bindings
     * @return MySQLiiStatement instance
     */
    public function prepare() {

    }

    /**
     * Run query and return first row of result
     *
     * @param $sql SQL statement
     * @param array $bindings Binding for prepared statement
     * @return First row of result or NULL
     */
    public function fetch($sql, $bindings=array()) {

    }

    /**
     * Run query and return all rows of result
     *
     * @param $sql SQL statement
     * @param array $bindings Binding for prepared statement
     * @return all rows of result or NULL
     */
    public function fetchAll($sql, $bindings=array()) {

    }

    /**
     * Run query and return first column of first row of result
     *
     * @param $sql SQL statement
     * @param array $bindings Binding for prepared statement
     * @return First column of first row of result
     */
    public function fetchOne($sql, $bindings=array()) {

    }

    /**
     * Start Transaction and set autocommit to off
     *
     * @return bool
     */
    public function beginTransaction() {
        if (isset($this->mysqli)) {
            $this->inTransaction = true;
            return $this->mysqli->autocommit(false);
        }
        return false;
    }

    /**
     * Commit command
     *
     * @param bool $finishTransaction Finish transaction after commit
     * @return bool
     */
    public function commit($finishTransaction = true) {
        if (isset($this->mysqli)) {
            $result = $this->mysqli->commit();
            if ($finishTransaction === true) {
                $this->finishTransaction();
            }
            return $result;
        }
        return false;
    }

    /**
     * Rollback command
     *
     * @param bool $finishTransaction Finish transaction after rollback
     * @return bool
     */
    public function rollBack($finishTransaction = true) {
        if (isset($this->mysqli)) {
            $result = $this->mysqli->rollback();
            if ($finishTransaction === true) {
                $this->finishTransaction();
            }
            return $result;
        }
        return false;
    }

    /**
     * Check for autocommit status
     *
     * @param bool $checkByMysql Set TRUE to check mysql autocommit value instead of class property.
     *                           Class property works when transaction begins and finishes by class methods.
     * @return bool TRUE if autocommit is off and it's in transaction else FALSE
     */
    public function inTransaction($checkByMysql = false) {
        if ($checkByMysql === true) {
            return (bool)$this->fetchOne('SELECT @@autocommit');
        }
        return $this->inTransaction;
    }

    /**
     * Finish Transaction by setting autocommit to on
     *
     * @return bool
     */
    public function finishTransaction() {
        if (isset($this->mysqli)) {
            $this->inTransaction = false;
            return $this->mysqli->autocommit(true);
        }
        return false;
    }

    /**
     * Get Last insert id
     *
     * @param bool $renew Set TRUE to execute LAST_INSERT_ID() instead of MySQLi::$insert_id
     * @return string Last Insert Id
     */
    public function getLastInsertId($renew = false) {
        if (isset($this->mysqli)) {
            if ($renew === true) {
                return $this->fetchOne('SELECT LAST_INSERT_ID()');
            }
            return $this->mysqli->insert_id;
        }
        return -1;
    }

    /**
     * Get affected rows
     *
     * @return string Last Insert Id
     */
    public function getAffectedRows() {
        return isset($this->mysqli) ? $this->mysqli->affected_rows : -1;
    }

    /**
     * Close statement on destruction
     *
     * @return void
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * Check for errors in class and MySQLi
     *
     * return bool
     */
    public function hasError() {
        return !empty($this->error) || !empty($this->internal_error);
    }

    /**
     * Check and Sync MySQLi error with MySQLii errors
     *
     * @access protected
     * @param bool|string $msg Error Message.
     * @param bool|int    $num Error Number.
     * @param bool|string $state Error SQLState.
     * @return void
     */
    protected function _checkErrors($msg = false, $num = false, $state = false) {
        if ($this->mysqli) {
            $this->_error = self::ifEmpty(($msg === false ? $this->mysqli->error : $msg), '');
            $this->_errno = self::ifEmpty(($num === false ? $this->mysqli->errno : $num), 0);
            $this->_sqlstate = self::ifEmpty(($state === false ? $this->mysqli->sqlstate : $state), '00000');
        } else {
            $this->_error = $this->_errno = $this->_sqlstate = null;
        }
    }

    /**
     * Get MySQLi last error number
     *
     * @return int
     */
    public function getErrorNumber() {
        return $this->_errno;
    }

    /**
     * Get MySQLi last error message
     *
     * @return string
     */
    public function getError() {
        return $this->_error;
    }

    /**
     * Get MySQLi last sql state
     *
     * @return string
     */
    public function getSqlstate() {
        return $this->_sqlstate;
    }

    /**
     * Get MySQLii last internal error message
     *
     * @return string
     */
    public function getInternalError() {
        return $this->_internal_error;
    }

    /**
     * Return specified value if first parameter is NULL else first parameter
     *
     * @static
     * @param mixed $test Value to check
     * @param mixed $value Value to return when first parameter is NULL
     * @return mixed
     */
    public static function ifNull($test, $value) {
        return isset($test) ? $test : $value;
    }

    /**
     * Return specified value if first parameter is empty else first parameter
     *
     * @static
     * @param mixed $test Value to check
     * @param mixed $value Value to return when first parameter is empty
     * @return mixed
     */
    public static function ifEmpty($test, $value) {
        return empty($test) ? $value : $test;
    }

    /**
     * Set default MySQL delimiter
     *
     * @param $sql_delimiter default MySQL delimiter
     * @return void
     */
    public function setQueryDelimiter($sql_delimiter) {
        $this->query_delimiter = $sql_delimiter;
    }

    /**
     * Get default MySQL delimiter
     *
     * @return string Default MySQL delimiter
     */
    public function getQueryDelimiter() {
        return $this->query_delimiter;
    }
}
