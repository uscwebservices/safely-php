<?php
/**
 * assert.php - a library of simple assert functions. If an assertion fails
 * it throws an exception. It is designed to follow the pattern of NodeJS's 
 * assert library.
 *
 * @author R. S. Doiel, <rsdoiel@usc.edu>
 *
 * copyright (c) 2010 University of Southern California
 */

/**
 * Assert class creates an Assert object designed to have the
 * same method structure and calling behavoir as NodeJS's assert module.
 */
class Assert {

  /**
   * truthyness - Tests if actual is equal to expected using the operator provided.
   *
   * @param $actual
   * @param $expected
   * @param $message
   * @param $operator
   */
  private function truthyness ($actual, $expected, $message, $operator) {
    switch ($operator) {
      case '==' :
        if ($actual == $expected) {
          return true;
        }
        throw new Exception ($message);
        break;
      case '!=' :
        if ($actual != $expected) {
          return true;
        }
        throw new Exception ($message);
        break;
      case '===' :
        if ($actual === $expected) {
          return true;
        }
        throw new Exception ($message);
        break;
      case '!==' :
        if ($actual !== $expected) {
          return true;
        }
        throw new Exception ($message);
        break;
      case '<' :
        if ($actual < $expected) {
          return true;
        }
        throw new Exception ($message);
        break;
      case '>' :
        if ($actual > $expected) {
          return true;
        }
        throw new Exception ($message);
        break;
      case '<=' :
        if ($actual <= $expected) {
          return true;
        }
        throw new Exception ($message);
        break;
      case '>=' :
        if ($actual >= $expected) {
          return true;
        }
        throw new Exception ($message);
        break;
      default:
        throw new Exception ("Don't know what to do with " . $operator);
        break;
    }
  }

  /**
   * ok - Tests if value is a true  value, it is equivalent to 
   * Assert::equal(true, value, message);
   * @param $value - value to be compared with true.
   * @param $message - the message to use if an exception is found.
   * @return true if assertion OK otherwise it throws and exception with 
   * the $exception_message
   */
  public function ok ($value, $message) {
    return Assert::truthyness(true, $value, $message, '==');
  }

  /**
   * notOk - Tests if value is a NOT true  value, it is equivalent to 
   * Assert::equal(false, value, message);
   * @param $value - value to be compared with true.
   * @param $message - the message to use if an exception is found.
   * @return true if assertion OK otherwise it throws and exception with 
   * the $exception_message
   */
  public function notOk ($value, $message) {
    return Assert::truthyness(true, $value, $message, '!=');
  }

  /**
   * fail - Assert a failure. It is equal to
   * Assert::equal(false, true, message);
   * @param $message - the message to use if an exception is found.
   * @return true if assertion OK otherwise it throws and exception with 
   * the $exception_message
   */
  public function fail ($message) {
    return Assert::truthyness(true, false, $message, '==');
  }

  /**
   * isTrue - asserts $value is equal to true, it is equivalent to 
   * Assert::equal(true, value, message);
   * @param $value - value to be compared with true.
   * @param $message - the message to use if an exception is found.
   * @return true if assertion OK otherwise it throws and exception with 
   * the $exception_message
   */
  public function isTrue ($value, $message) {
    return Assert::truthyness(true, $value, $message, '==');
  }

  /**
   * isFalse - asserts $value is equal to false, it is equivalent to 
   * Assert::equal(false, value, message);
   * @param $value - value to be compared with true.
   * @param $message - the message to use if an exception is found.
   * @return true if assertion OK otherwise it throws and exception with 
   * the $exception_message
   */
  public function isFalse ($value, $message) {
    return Assert::truthyness(false, $value, $message, '==');
  }

  
  /**
   * equal - Tests shallow, coercive equality with the equal comparison 
   * operator ( == ).
   * @param $actual - the value your are evaluating
   * @param $expected - the expacted value
   * @param $message - the message pass to the exception
   * @return true or throw error with $message.
   */
  public function equal ($actual, $expected, $message) {
    if ($actual == $expected) {
      return true;
    }
    throw new Exception($message);
  }

  /**
   * notEqual - Tests shallow, coercive non-equality with the not equal
   * comparison operator ( !=  ).  
   *
   * @param $actual
   * @param $expected
   * @param $message
   * @return true or throw error with $message.
   */
  public function notEqual ($actual, $expected, $message) {
    return Assert::truthyness($actual, $expected, $message, '!=');
  }
  
  /**
   * strictEqual -
   *
   * @param $actual
   * @param $expected
   * @param $message
   * @return true or throw error with $message.
   */
  public function strictEqual ($actual, $expected, $message) {
    return Assert::truthyness($actual, $expected, $message, '===');
  }
  
  /**
   * strictNotEqual -
   *
   * @param $actual
   * @param $expected
   * @param $message
   * @return true or throw error with $message.
   */
  public function strictNotEqual ($actual, $expected, $message) {
    return Assert::truthyness($actual, $expected, $message, '!==');
  }


  /**
   * notTrue
   * @param $actual
   * @param $message
   * @return true or throw error with $message.
   */
  public function notTrue ($actual, $message) {
    return Assert::truthyness($actual, true, $message, '!=');
  }


  /**
   * notFalse
   * @param $actual
   * @param $message
   * @return true or throw error with $message.
   */
  public function notFalse ($actual, $message) {
    return Assert::truthyness($actual, false, $message, '!=');
  }


  /**
   * strictNotTrue
   * @param $actual
   * @param $message
   * @return true or throw error with $message.
   */
  public function strictNotTrue ($actual, $message) {
    return Assert::truthyness($actual, true, $message, '!==');
  }


  /**
   * strictNotFalse
   * @param $actual
   * @param $message
   * @return true or throw error with $message.
   */
  public function strictNotFalse ($actual, $message) {
    return Assert::truthyness($actual, false, $message, '!==');
  }
}

$assert = new Assert();
?>
