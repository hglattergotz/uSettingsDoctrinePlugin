<?php
/**
 * Key/Value holder with a database backend. Essentially this is a parameter
 * holder that serializes to a database.
 * In addition to simply storing unrelated key/value pairs it is possible to
 * specify a group id for a group of key/value pairs to associate them.
 * This can be helpful if a set of key/value pairs make up a configuration of an
 * object that need to be retrieved as a complete set.
 *
 * A setting entry consists of the key, value, type and the group.
 *  - Key is the name of the key
 *  - Value is the value assigned to the key
 *  - Type is the php type of the value (string, integer, boolean, double)
 *  - Group is the group specifier to tie multiple entries together
 *
 * @package     uSettingsDoctrinePlugin
 * @subpackage  task
 * @author      Henning Glatter-Gotz <henning@glatter-gotz.com>
 */
class uSettings
{
  const INT = 'integer';
  const STRING = 'string';
  const BOOLEAN = 'boolean';
  const DOUBLE = 'double';

  /**
   * @var string The name of the table to use as a backend storage. By default
   *             this is 'Settings' which has the correct schema. If an
   *             alternate table is used then it is up the the developer to
   *             ensure that the schema is correct.
   */
  protected $tableName;

  /**
   * Constructor that sets the table name to be used.
   *
   * @param string $tableName
   */
  public function __construct($tableName = 'Settings')
  {
    $this->tableName = $tableName;
  }

  /**
   * Helper function to create a properly formatted array for a setting. This
   * can be used to create an array of settings, which can then be passed into
   * the newSettings() method.
   * 
   * @param string $key   The key
   * @param string $value The string representation of the value
   * @param string $type  One of self::INT, self::STRING, self::BOOLEAN,
   *                      self::DOUBLE
   * @param string $group The name of the group
   *
   * @return array
   */
  public static function mkArray($key, $value, $type, $group)
  {
    return array('key' => $key, 'value' => $value, 'type' => $type, 'group' => $group);
  }

  /**
   * Get a key of a specific group.
   *
   * @param string  $key            The key
   * @param string  $group          The name of the group
   * @param boolean $throwException Whether to throw an exception or not if the
   *                                key and group do not exist
   * @throws Exception
   *
   * @return mixed
   */
  public function get($key, $group, $throwException = true)
  {
    $setting = Doctrine::getTable($this->tableName)->getByKeyGroup($key, $group);

    if (null === $setting)
    {
      if ($throwException)
      {
        throw new Exception('The configuration setting with key \''.$key.
                            '\' and group \''.$group.'\' does not exist.');
      }
      else
      {
        return false;
      }
    }
    else
    {
      return $this->cast($setting->getValue(), $setting->getType());
    }
  }

  /**
   * Set/Update a value of an existing key and group.
   *
   * @param string $key             The key
   * @param string $value           The string representation of the value
   * @param string $group           The name of the group
   * @param boolean $throwException Whether to throw an exception or not if the
   *                                an error is encountered
   * @throws Exception
   *
   * @return boolean
   */
  public function set($key, $value, $group, $throwException = true)
  {
    try
    {
      Doctrine::getTable($this->tableName)->setByKeyGroup($key, $this->cast($value, self::STRING), $group);
    }
    catch (Exception $e)
    {
      if ($throwException)
      {
        throw new Exception($e->getMessage());
      }
      else
      {
        return false;
      }
    }

    return true;
  }

  /**
   * Get all the key/value pairs of a particular group.
   *
   * @param string $group           The name of the group
   * @param boolean $throwException Whether to throw an exception or not if the
   *                                an error is encountered
   * @throws Exception
   *
   * @return mixed                  Array of key/value pairs on success, false
   *                                on failuere if $throwException is false
   */
  public function getAllGroup($group, $throwException = true)
  {
    try
    {
      $settings = Doctrine::getTable($this->tableName)->getAllGroup($group);
      $results = array();

      foreach ($settings as $setting)
      {
        $results[$setting->getKey()] = $this->cast($setting->getValue(), $setting->getType());
      }

      return $results;
    }
    catch (Exception $e)
    {
      if ($throwException)
      {
        throw new Exception($e->getMessage());
      }
      else
      {
        return false;
      }
    }
  }

  /**
   * Set/Update all the keys of a particular group.
   *
   * @param array  $data           Array of key/value that also contain the type
   * @param <type> $group
   * @param <type> $throwException
   *
   * @return <type>
   */
  public function setAllGroup(array $data, $group, $throwException = true)
  {
    try
    {
      foreach ($data as $key => $value)
      {
        $this->set($key, $value, $group);
      }
    }
    catch (Exception $e)
    {
      if ($throwException)
      {
        throw new Exception($e->getMessage());
      }
      else
      {
        return false;
      }
    }

    return true;
  }

  /**
   * Create a new setting.
   *
   * @param string $key             The key
   * @param string $value           The string representation of the value
   * @param string $type            One of self::INT, self::STRING,
   *                                self::BOOLEAN, self::DOUBLE
   * @param string $group           The name of the group
   * @param boolean $throwException Whether to throw an exception or not if the
   *                                an error is encountered
   * @throws Exception
   *
   * @return boolean
   */
  public function newSetting($key, $value, $type, $group, $throwException = true)
  {
    try
    {
      Doctrine::getTable($this->tableName)->newSetting($key, $value, $type, $group);
    }
    catch (Exception $e)
    {
      if ($throwException)
      {
        throw new Exception($e->getMessage());
      }
      else
      {
        return false;
      }
    }

    return true;
  }

  /**
   * Create a number of new settings.
   *
   * @param array   $settings
   * @param boolean $throwException
   * @throws Exception
   *
   * @return boolean
   */
  public function newSettings(array $settings, $throwException = true)
  {
    try
    {
      foreach ($settings as $kv)
      {
        Doctrine::getTable($this->tableName)->newSetting($kv['key'], $kv['value'], $kv['type'], $kv['group']);
      }
    }
    catch (Exception $e)
    {
      if ($throwException)
      {
        throw new Exception($e->getMessage());
      }
      else
      {
        return false;
      }
    }

    return true;
  }

  /**
   * Check for existence of a setting.
   * 
   * @param string $key   The key to check for
   * @param string $group The group to check for
   * @return bolean
   */
  public function hasSetting($key, $group)
  {
    $setting = Doctrine::getTable($this->tableName)->getByKeyGroup($key, $group);

    return (null !== $setting);
  }
  
  /**
   * Remove the entire group from the database.
   *
   * @param string $group           The name of the group
   * @param boolean $throwException Whether to throw an exception or not if the
   *                                an error is encountered
   * @throws Exception
   *
   * $return boolean
   */
  public function removeGroup($group, $throwException = true)
  {
    try
    {
      Doctrine::getTable($this->tableName)->removeByGroup($group);
    }
    catch (Exception $e)
    {
      if ($throwException)
      {
        throw new Exception($e->getMessage());
      }
      else
      {
        return false;
      }
    }

    Doctrine::getTable($this->tableName)->optimize();

    return true;
  }

  /**
   * Remove a specific setting.
   *
   * @param string $key             The key
   * @param string $group           The name of the group
   * @param boolean $throwException Whether to throw an exception or not if the
   *                                an error is encountered
   * @throws Exception
   *
   * @return boolean
   */
  public function removeSetting($key, $group, $throwException = true)
  {
    try
    {
      Doctrine::getTable($this->tableName)->removeByKeyGroup($key, $group);
    }
    catch (Exception $e)
    {
      if ($throwException)
      {
        throw new Exception($e->getMessage());
      }
      else
      {
        return false;
      }
    }

    Doctrine::getTable($this->tableName)->optimize();

    return true;
  }

  /**
   * Casts a value to a specific type.
   *
   * @param string $value           The string representation of the value
   * @param string $type            One of self::INT, self::STRING,
   *                                self::BOOLEAN, self::DOUBLE
   * @throws Exception
   *
   * @return mixed
   */
  protected function cast($value, $type)
  {
    switch ($type)
    {
      case self::INT:
        return intval($value);
        break;
      case self::BOOLEAN:
        return $this->strtobool($value);
        break;
      case self::DOUBLE:
        return doubleval($value);
        break;
      case self::STRING:
        if (is_bool($value))
        {
          return $this->booltostr($value);
        }
        else
        {
          return strval($value);
        }
        break;
      default:
        throw new Exception('Unsupported type \''.$type.'\'');
        break;
    }
  }

  /**
   * Convert a string to a boolean.
   * 
   * @param string $value The string to be converted to a boolean
   *
   * @return boolean
   */
  protected function strtobool($value)
  {
    $val = strtolower($value);

    if ($val == 'yes' || $val == 'true' || $val == 'on' || $val == '1' || $val == 1)
    {
      return true;
    }
    else
    {
      return false;
    }
  }

  /**
   * Convert a boolean to a string representation.
   * 
   * @param boolean $value
   * @return string 
   */
  protected function booltostr($value)
  {
    return ($value) ? 'true' : 'false';
  }
}