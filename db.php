<?php
/**
 * db.php - an evolving code DB library currently support mysql_* and mysqli_* PHP 
 * drivers.
 *
 * @author R. S. Doiel, <rsdoiel@usc.edu>
 */
error_reporting(E_ALL | E_STRICT);
@date_default_timezone_set(date_default_timezone_get());

/**
 * Db - is the database object for working with both old (i.e. mysql_*) and new (i.e.
 * mysqli_*) MySQL procedural calls in PHP 5.3 and above.
 */
class Db {
    /**
     * __construct: Constructor for Db objects.
     * @param $MYSQL_DB_CONNECT_STRING - DB connection info as URL
     *    (e.g. mysqli://DB_USERNAME:DB_PASSWORD@HOSTNAME/DB_NAME.
     */
    public function __construct($MYSQL_DB_CONNECT_STRING) {
        $url = parse_url($MYSQL_DB_CONNECT_STRING);
        $this->db_type = strtolower($url['scheme']);
        $this->db_name = basename($url['path']);
        $this->db_host = $url['host'];
        $this->db_user = $url['user'];
        $this->db_password = $url['pass'];
        $this->link = null;
        $this->last_insert_id = 0;
        $this->rows = array();
        $this->rows_affected = 0;
        $this->log_output = 'error_log';
    }

    /**
     * setLog - set the destination of the log output - browser or error_log.
     * @param $target - either "echo" or "error_log"
     * @return new log target
     */
    public function setLog($target) {
        if ($target === 'echo' || $target === 'error_log') {
            $this->log_output = $target;
        }
        return $this->log_output;
    }

    /**
     * logIt - emmit a log message if verbose is true
     * @param $msg - the message to emmit
     * @param $verbose - true, emmit message, otherwise ignore
     */
    public function logIt($msg, $verbose) {
        if ($verbose === true) {
            if ($this->log_output === 'error_log') {
                error_log($msg);
            } else {
                echo '<pre>Log: ' . $msg . '</pre>' . PHP_EOL;
            }
        }
    }


    /**
     * open - open a database connection (RDBMS/NoSQL).
     * @return true on success, false otherwise.
     */
    public function open () {
        $this->last_insert_id = 0;
        $this->rows = array();
        $this->rows_affected = 0;
        if ($this->db_type === 'mysqli') {
            $this->link = mysqli_connect($this->db_host, $this->db_user, $this->db_password, $this->db_name);
            return $this->link;
        } else if ($this->db_type === 'mysql') {
            $this->link = mysql_connect($this->db_host, $this->db_user, $this->db_password);
            if ($this->link) {
                $db_selected = mysql_select_db($this->db_name, $this->link);
                if (!$db_selected) {
                    error_log(mysql_error());
                    return false;
                }
                return $this->link;
            } else {
                error_log(mysql_error());
            }
        }
        error_log("Can't open db " . $this->db_name .
            ' by ' . $this->db_user);
        return false;
    }

    /**
     * close - Close the open database connection.
     * @return true if successful, false otherwise.
     */
    public function close () {
        $this->last_insert_id = 0;
        $this->rows = array();
        $this->rows_affected = 0;
        if ($this->db_type === 'mysqli' &&
            $this->link !== false) {
            mysqli_close($this->link);
            $this->link = null;
            return $this->link;
        } else if ($this->db_type === 'mysql' &&
                $this->link !== false) {
            mysql_close($this->link);
            $this->link = null;
            return $this->link;
        }
        error_log("Can't close db " . $this->db_name);
        return false;
    }

    /**
     * execute - run an SQL query against the database.
     * @param $sql - explicit SQL statement or SQL template for prepare statement (require's mysqli)
     * @param $params - an ordered array of substitution to apply in the prepared statement.
     * @param $verbose - if true then log errors
     * @return true if successful, false otherwise 
     */
    public function executeSQL ($sql, $params = array(), $verbose = false) {
        if ($this->db_type === 'mysqli') {
        // use a regular SQL query
        // use a prepared statement
            $stmt = mysqli_stmt_init($this->link);
            if (mysqli_stmt_prepare($stmt, $sql) === false) {
                $this->logIt("Can't prepare $sql", $verbose);
                return false;
            }
            if (count($params) > 0) {
                // Review each of the args and generate a type string
                $type_string = '';
                for($i = 0; $i < count($params); $i += 1) {
                        if (is_int($params[$i])) {
                            $type_string .= 'i';
                        } else if (is_float($params[$i])) {
                            $type_string .= 'd';
                        } else {
                            $type_string .= 's';
                        }
                }
                // Make sure we're passing array parameters by references.
                $call_params = array($stmt, $type_string);
                for($i = 0; $i < count($params); $i += 1) {
                        $call_params[] = &$params[$i];
                }
                call_user_func_array('mysqli_stmt_bind_param', 
                                         $call_params);
            }
            mysqli_stmt_execute($stmt);
            if (stripos($sql, 'SELECT') === 0) {
                    $this->rows_affected = mysqli_stmt_num_rows($stmt);
                    $result = mysqli_stmt_get_result($stmt);
            } else {
                    $this->rows_affected = mysqli_stmt_affected_rows($stmt);
                    $result = false;
            }
            $this->logIt("Prepared SQL: " . print_r($stmt, true), $verbose);
            $this->last_insert_id = mysqli_insert_id($this->link);

            // Now gather up the results, close the statement.
            $this->rows = array();
            if ($result !== false) {
                while($row = mysqli_fetch_assoc($result)) {
                    $this->rows[] = $row;
                }
            }
            mysqli_stmt_close($stmt);
            return true;
        } else if ($this->db_type === "mysql") {
            if (strpos($sql, '?') > -1) {
                $parts = explode('?', $sql);
                $assembled = array();
                for ($i = 0, $j = 0; $i < count($parts); $i += 1) {
                    $assembled[] = $parts[$i];
                    $call_param = '';
                    if ($j < count($params)) {
                        if (is_int($params[$j])) {
                            $call_param = intval($params[$j]);
                        } else if (is_float($params[$j])) {
                            $call_param = floatval($params[$j]);
                        } else {
                            $call_param = '"' . mysql_real_escape_string($params[$j], $this->link) . '"';
                        }
                        $assembled[] = $call_param;
                        $j += 1;
                    }
                }
                $sql = implode('', $assembled);
            }
            $this->logIt("SQL: " . $sql, $verbose);

            $qry = mysql_query($sql, $this->link);
            if (mysql_errno($this->link) !== 0) {
                $this->logIt("SQL MySQL Error: " . mysql_error($this->link), $verbose);
            }
            if (stripos($sql, 'SELECT') === 0) {
                    $this->rows_affected = mysql_num_rows($qry);
                    $this->logIt("SQL num_rows(): " . $this->rows_affected, $verbose);
                    $result = true;
            } else {
                    $this->rows_affected = mysql_affected_rows($this->link);
                    $result = false;
            }
            $this->last_insert_id = mysql_insert_id($this->link);
            $this->rows = array();
            if ($result !== false) {
                while($row = mysql_fetch_assoc($qry)) {
                    $this->rows[] = $row;
                }
            }
            if ($result !== false) {
                mysql_free_result($qry);
            }
            return true;
        }
        $this->logIt("Can't execute $sql", $verbose);
        return false;
    }

    /**
     * getRow - shift a row from an SQL query results
     * @return an associative array of the row or false if no row available.
     */
    public function getRow () {
       $row = array_shift($this->rows);
       return $row;
    }

    /**
     * lastInsertRowId - get the id of the last inserted row.
     * @return the row if or false
     */
    public function lastInsertRowId () {
        return $this->last_insert_id;
    }

    /**
     * rowsAffected - get the last value saved for rows affected.
     * @return the number of rows affected.
     */
    public function rowsAffected() {
        return $this->rows_affected;
    }
    
    /**
     * getRowCount - get the number of rows saved in $this->rows
     */
    public function rowCount() {
        return count($this->rows);
    }
}
?>
