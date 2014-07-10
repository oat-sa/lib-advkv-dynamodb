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

use common_persistence_KvDriver;
use common_persistence_KeyValuePersistence;
use common_Logger;
use common_Exception;
use Aws\DynamoDb\DynamoDbClient;

/**
 * A driver for Amazon DynamoDB
 *
 * @author Joel Bout <joel@taotesting.com>
 */
class DynamoDbDriver implements common_persistence_KvDriver
{

    private $client;
    private $tableName;

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
        return new common_persistence_KeyValuePersistence($params, $this);
    }

    /**
     * (non-PHPdoc)
     * @see common_persistence_KvDriver::set()
     */
    public function set($key, $value, $ttl = null)
    {
        $result = $this->client->putItem(array(
            'TableName' => $this->tableName,
            'Item' => array(
                'key' => array('S' => $key),
                'value' => array('B' => $value)
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
    
    public function hmSet($key, $fields) {
        $attributesToUpdate = array();

        foreach ($fields as $hashkey=>$val) {
            $attributesToUpdate[$hashkey] = array (
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
    
    public function hExists($key, $field) {
        $result = $this->client->getItem(array(
            'TableName' => $this->tableName,
            'Key' => array (
                'key' => array('S' => $key)
            ),
            'ConsistentRead' => true,
            'AttributesToGet' => array( $field )
        ));
        return isset($result['Item'][$field]);
    }

    public function hGetAll($key) {
        $result = $this->client->getItem(array(
            'TableName' => $this->tableName,
            'Key' => array (
                'key' => array('S' => $key)
            ),
            'ConsistentRead' => true
        ));
        if ( isset($result['Item']) ) {
            $returnArray = $result['Item'];
            unset($result);
            unset($returnArray['key']);
            foreach ($returnArray as $key=>$val) {
                $returnArray[$key] = base64_decode($val['B']);
            }
            return $returnArray;
        } else {
            return array();
        }
    }
    
    public function hGet($key, $field) {
        $result = $this->client->getItem(array(
            'TableName' => $this->tableName,
            'Key' => array (
                'key' => array('S' => $key)
            ),
            'ConsistentRead' => true,
            'AttributesToGet' => array( $field )
        ));
        return base64_decode($result['Item'][$field]['B']);
    }
    
    public function hSet($key, $field, $value) {
        $result = $this->client->updateItem(array(
            'TableName' => $this->tableName,
            'Key' => array(
                'key' => array('S' => $key)
            ),
            'AttributeUpdates' => array(
                $field => array(
                    'Action' => 'PUT',
                    'Value' => array('B' => $value)
                )
            ),
            'ReturnValues' => 'UPDATED_OLD'
        ));
        return (int)!isset($result['Attributes'][$field]);
    }
    
    public function keys($pattern) {
        throw new Exception('The keys($pattern) method is not implemented yet!');
    }

}