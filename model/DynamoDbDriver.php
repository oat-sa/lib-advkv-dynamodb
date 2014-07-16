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
namespace oat\kvDynamoDb\model;

//use common_persistence_KvDriver;
use common_persistence_AdvKvDriver;
//use common_persistence_KeyValuePersistence;
use common_persistence_AdvKeyValuePersistence;
use common_Logger;
use common_Exception;
use Aws\DynamoDb\DynamoDbClient;

/**
 * A driver for Amazon DynamoDB
 *
 * @author Joel Bout <joel@taotesting.com>
 */
//class DynamoDbDriver implements common_persistence_KvDriver
class DynamoDbDriver implements common_persistence_AdvKvDriver
{

    private $client;
    private $tableName;
    private $hPrefix = 'hPrfx_';

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
        //return new common_persistence_KeyValuePersistence($params, $this);
        return new common_persistence_AdvKeyValuePersistence($params, $this);
    }

    /**
     * (non-PHPdoc)
     * @see common_persistence_KvDriver::set()
     */
    public function set($key, $value, $ttl = null)
    {
        $result = $this->client->updateItem(array(
            'TableName' => $this->tableName,
            'Key' => array(
                'key' => array('S' => $key)
            ),
            'AttributeUpdates' => array(
                'value' => array(
                    'Action' => 'PUT',
                    'Value' => array('B' => $value)
                )
            ),
            'ReturnConsumedCapacity' => 'TOTAL'
        ));
        common_Logger::i('SET: ' . $key);
        return (int)($result->getPath('ConsumedCapacity/CapacityUnits') > 0);
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
                'key' => array('S' => $key)
            )
        ));
        common_Logger::i('GET: ' . $key);
        if (!isset($result['Item']['value']['B'])) {
            return false;
        } else {
            return base64_decode($result['Item']['value']['B']);
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
                'key' => array('S' => $key)
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
        $this->client->deleteItem(array(
            'TableName' => $this->tableName,
            'Key' => array(
                'key' => array('S' => $key)
            )
        ));
        common_Logger::i('DEL: ' . $key);
        return true; // to return ReturnConsumedCapacity by ConsumedCapacity
    }
    
    /**
     * Increments the value of a key by 1. The data in the key needs to be of a integer type
     * @param string $key The key to be incremented
     * @return integer|bool Returns the value of the incremented key if the operation succeeds and FALSE if the operation fails
     */
    public function incr($key) {
        try {
            $result = $this->client->updateItem(array(
                'TableName' => $this->tableName,
                'Key' => array(
                    'key' => array('S' => $key)
                ),
                'AttributeUpdates' => array(
                    'value' => array(
                        'Action' => 'ADD',
                        'Value' => array('N' => 1)
                    )
                ),
                'ReturnValues' => 'UPDATED_NEW'
            ));
            return $result['Attributes']['value']['N'];
        } catch (Exception $ex) {
            return false;
        }
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
            $attributesToUpdate[$this->hPrefix.$hashkey] = array (
                'Action' => 'PUT',
                'Value' => array('B' => $val)
            );
        }

        if (count($attributesToUpdate) > 0) {
            $result = $this->client->updateItem(array(
                'TableName' => $this->tableName,
                'Key' => array(
                    'key' => array('S' => $key)
                ),
                'AttributeUpdates' => $attributesToUpdate,
                'ReturnValues' => 'UPDATED_OLD'
            ));
        }
        return true;
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
                'key' => array('S' => $key)
            ),
            'ConsistentRead' => true,
            'AttributesToGet' => array( $this->hPrefix.$field )
        ));
        return isset($result['Item'][$this->hPrefix.$field]);
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
                'key' => array('S' => $key)
            ),
            'ConsistentRead' => true
        ));
        if ( isset($result['Item']) ) {
            $tempArray = $result['Item'];
            unset($result);
            unset($tempArray['key']); //remove the KEY from the resutlset
            $prefixLength = strlen($this->hPrefix);
            $returnArray = array();
            foreach ($tempArray as $taKey=>$val) {
                if (mb_substr($taKey, 0, $prefixLength) === $this->hPrefix) {
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
                'key' => array('S' => $key)
            ),
            'ConsistentRead' => true,
            'AttributesToGet' => array( $this->hPrefix.$field )
        ));
        return base64_decode($result['Item'][$this->hPrefix.$field]['B']);
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
                    'key' => array('S' => $key)
                ),
                'AttributeUpdates' => array(
                    $this->hPrefix.$field => array(
                        'Action' => 'PUT',
                        'Value' => array('B' => $value)
                    )
                )//,
                //'ReturnValues' => 'UPDATED_OLD'
            ));
            return true;
        } catch (Exception $ex) {
            return false;
        }
        //return (int)!isset($result['Attributes'][$field]);
    }
    
    public function keys($pattern) {
        throw new Exception('The keys($pattern) method is not implemented yet!');
    }

}
