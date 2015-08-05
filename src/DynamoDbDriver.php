<?php
/**  
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *               
 * 
 */
namespace oat\kvDynamoDb;

use common_persistence_AdvKvDriver;
use common_persistence_AdvKeyValuePersistence;
use common_Logger;
use common_Exception;
use Aws\DynamoDb\DynamoDbClient;

/**
 * A driver for Amazon DynamoDB
 *
 * @author Joel Bout <joel@taotesting.com>
 */
class DynamoDbDriver implements common_persistence_AdvKvDriver
{

    private $client;
    private $tableName;
    const HPREFIX = 'hPrfx_', SIMPLE_KEY_NAME = 'key', SIMPLE_VALUE_NAME = 'value';
    

    /**
     * (non-PHPdoc)
     *
     * @see common_persistence_Driver::connect()
     */
    function connect($key, array $params)
    {
        $this->client = DynamoDbClient::factory(array(
                    'key' => $params['key'],
                    'secret' => $params['secret'],
                    'region' => $params['region']//,
                    //'validation' => false,
                    //'credentials.cache' => true
        ));
        $this->tableName = $params['table'];
        common_Logger::i('connect');
        return new common_persistence_AdvKeyValuePersistence($params, $this);
    }

    /**
     * (non-PHPdoc)
     * @see common_persistence_KvDriver::set()
     */
    public function set($key, $value, $ttl = null)
    {
        try {
            if (gettype($value) === 'integer') {
                $valueType = 'N';
            } else {
                $valueType = 'B';
            }
            $result = $this->client->updateItem(array(
                'TableName' => $this->tableName,
                'Key' => array(
                    self::SIMPLE_KEY_NAME => array('S' => $key)
                ),
                'AttributeUpdates' => array(
                    self::SIMPLE_VALUE_NAME => array(
                        'Action' => 'PUT',
                        'Value' => array($valueType => $value)
                    )
                ),
                'ReturnConsumedCapacity' => 'TOTAL'
            ));
            common_Logger::i('SET: ' . $key);
            return (bool)($result->getPath('ConsumedCapacity/CapacityUnits') > 0);
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * (non-PHPdoc)
     * @see common_persistence_KvDriver::get()
     */
    public function get($key)
    {
        $result = $this->client->getItem(array(
            'ConsistentRead' => true,
            'TableName' => $this->tableName,
            'Key' => array(
                self::SIMPLE_KEY_NAME => array('S' => $key)
            )
        ));
        common_Logger::i('GET: ' . $key);
        if ( isset($result['Item'][self::SIMPLE_VALUE_NAME]['B']) ) {
            return base64_decode($result['Item'][self::SIMPLE_VALUE_NAME]['B']);
        } elseif ( isset($result['Item'][self::SIMPLE_VALUE_NAME]['N']) ) {
             return (int)$result['Item'][self::SIMPLE_VALUE_NAME]['N'];
        } else {
            return false;
        }
    }

    /**
     * (non-PHPdoc)
     * @see common_persistence_KvDriver::exists()
     */
    public function exists($key)
    {
        $result = $this->client->getItem(array(
            'ConsistentRead' => true,
            'TableName' => $this->tableName,
            'Key' => array(
                self::SIMPLE_KEY_NAME => array('S' => $key)
            )
        ));
        common_Logger::i('EXISTS: ' . $key);
        return (bool)(count($result) > 0);
    }

    /**
     * (non-PHPdoc)
     * @see common_persistence_KvDriver::del()
     */
    public function del($key)
    {
        try {
            $this->client->deleteItem(array(
                'TableName' => $this->tableName,
                'Key' => array(
                    self::SIMPLE_KEY_NAME => array('S' => $key)
                )
            ));
            common_Logger::i('DEL: ' . $key);
        } catch (Exception $ex) {
            return false;
        }
        return true;
    }
    
    /**
     * Increments the value of a key by 1. The data in the key needs to be of a integer type
     * @param string $key The key to be incremented
     * @return integer|bool Returns the value of the incremented key if the operation succeeds and FALSE if the operation fails
     */
    public function incr($key) {
        $result = $this->client->updateItem(array(
            'TableName' => $this->tableName,
            'Key' => array(
                self::SIMPLE_KEY_NAME => array('S' => $key)
            ),
            'AttributeUpdates' => array(
                self::SIMPLE_VALUE_NAME => array(
                    'Action' => 'ADD',
                    'Value' => array('N' => 1)
                )
            ),
            'ReturnValues' => 'UPDATED_NEW'
        ));
        return (int)$result['Attributes'][self::SIMPLE_VALUE_NAME]['N'];
    }
    
    /**
     * Sets the specified fields to their respective values in the hash stored at key. <br />
     * This command overwrites any existing fields in the hash. <br />
     * If key does not exist, a new key holding a hash is created. <br />
     * 
     * @param string $key The key on which the operation will be applied to
     * @param array $fields An associative array with the key=>value pairs to be set
     * @return boolean Returns TRUE if the operation is successfull
     */
    public function hmSet($key, $fields) {
        $attributesToUpdate = array();

        if (!is_array($fields)) {
            return false;
        }
        foreach ($fields as $hashkey=>$val) {
            $attributesToUpdate[self::HPREFIX.$hashkey] = array (
                'Action' => 'PUT',
                'Value' => array('B' => $val)
            );
        }

        if (count($attributesToUpdate) > 0) {
            try {
                $result = $this->client->updateItem(array(
                    'TableName' => $this->tableName,
                    'Key' => array(
                        self::SIMPLE_KEY_NAME => array('S' => $key)
                    ),
                    'AttributeUpdates' => $attributesToUpdate,
                    'ReturnValues' => 'UPDATED_OLD'
                ));
                return true;
            } catch (Exception $ex) {
                return false;
            }

        } else {
            return false;
        }
    }
    
    /**
     * Determine if a hash field exists at $key
     * @param string $key The key on which to perform the check
     * @param string $field The field name to check for
     * @return boolean Returns TRUE if the field exists and FALSE otherwise
     */
    public function hExists($key, $field) {
        $result = $this->client->getItem(array(
            'TableName' => $this->tableName,
            'Key' => array (
                self::SIMPLE_KEY_NAME => array('S' => $key)
            ),
            'ConsistentRead' => true,
            'AttributesToGet' => array( self::HPREFIX.$field )
        ));
        return isset($result['Item'][self::HPREFIX.$field]);
    }

    /**
     * Returns all fields and values of the hash stored at key
     * @param string $key The key to get all hash fields from
     * @return aray An associative array containing all the keys and values of the hashes
     */
    public function hGetAll($key) {
        $result = $this->client->getItem(array(
            'TableName' => $this->tableName,
            'Key' => array (
                self::SIMPLE_KEY_NAME => array('S' => $key)
            ),
            'ConsistentRead' => true
        ));
        if ( isset($result['Item']) ) {
            $tempArray = $result['Item'];
            unset($result);
            unset($tempArray[self::SIMPLE_KEY_NAME]); //remove the KEY from the resutlset
            $prefixLength = strlen(self::HPREFIX);
            $returnArray = array();
            foreach ($tempArray as $taKey=>$val) {
                if (mb_substr($taKey, 0, $prefixLength) === self::HPREFIX) {
                    $returnArray[ mb_substr($taKey, $prefixLength) ] = base64_decode($val['B']);
                    unset($tempArray[$taKey]); // unset data as soon as we don't need it so we could free memory
                } else {
                    unset($tempArray[$taKey]);
                }
            }
            return $returnArray;
        } else {
            return array();
        }
    }
    
    /**
     * Returns the value associated with field in the hash stored at key
     * @param string $key The desired key to get a hash value from
     * @param string $field The name of the hash field to get
     * @return mixed The value stored at the specified hash field
     */
    public function hGet($key, $field) {
        $result = $this->client->getItem(array(
            'TableName' => $this->tableName,
            'Key' => array (
                self::SIMPLE_KEY_NAME => array('S' => $key)
            ),
            'ConsistentRead' => true,
            'AttributesToGet' => array( self::HPREFIX.$field )
        ));
        if (isset($result['Item'][self::HPREFIX.$field])) {
            return base64_decode($result['Item'][self::HPREFIX.$field]['B']);
        } else {
            return false;
        }
    }
    
    /**
     * Sets field in the hash stored at key to value. If key does not exist, a new key holding a hash is created. <br />
     * If field already exists in the hash, it is overwritten. <br />
     * @param string $key The key at which to set a hash
     * @param string $field The field to set a value to
     * @param mixed $value The value to be set
     * @return integer Returns 1 if field is a new field in the hash and value was set, 0 if field already exists in the hash and the value was updated
     */
    public function hSet($key, $field, $value) {
        if ( !($key!=='') || !($field!=='') ) {
            return false;
        }
        try {
            $result = $this->client->updateItem(array(
                'TableName' => $this->tableName,
                'Key' => array(
                    self::SIMPLE_KEY_NAME => array('S' => $key)
                ),
                'AttributeUpdates' => array(
                    self::HPREFIX.$field => array(
                        'Action' => 'PUT',
                        'Value' => array('B' => $value)
                    )
                )
            ));
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }
    
    /**
     * Returns all keys that match the pattern given in $pattern. If an asterisk is used it returns all keys that start with the string that precede the asterisk.<br />
     * If an asterisk is not used then it returns all keys containing the $pattern.
     * @param string $pattern
     * @return array An array containing all matched keys
     */
    public function keys($pattern) {
        $astPos = mb_strpos($pattern, '*');
        if ( $astPos !== false && $astPos > 0 ) {
            $comparisonOpearator = 'BEGINS_WITH';
            $comparisonValue = mb_substr($pattern, 0, $astPos);
        } else {
            $comparisonOpearator = 'CONTAINS';
            $comparisonValue = $pattern;
        }
        
        $iterator = $this->client->getIterator('Scan', array(
            'TableName' => $this->tableName,
            'AttributesToGet' => array(self::SIMPLE_KEY_NAME),
            'ReturnConsumedCapacity' => 'TOTAL',
            'ScanFilter' => array(
                self::SIMPLE_KEY_NAME => array(
                    'AttributeValueList' => array(
                        array('S' => $comparisonValue)
                    ),
                    'ComparisonOperator' => $comparisonOpearator
                )
            )
        ));
        
        $keysArray = array();
        foreach ($iterator as $item) {
            $keysArray[] = $item[self::SIMPLE_KEY_NAME]['S'];
        }
        return $keysArray;
    }

}
