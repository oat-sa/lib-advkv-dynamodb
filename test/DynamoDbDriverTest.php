<?php

/*$client->createTable(array(
  'AttributeDefinitions' => array(
    array(
      'AttributeName' => 'key',
      'AttributeType' => 'S'
    )
  ) ,
  'KeySchema' => array(
    array(
      'AttributeName' => 'key',
      'KeyType' => 'HASH'
    )
  ) ,
  'ProvisionedThroughput' => array(
    'ReadCapacityUnits' => 10,
    'WriteCapacityUnits' => 5
  ) ,
  'TableName' => "taoKeyValueStorage"
));*/

require_once '/var/www/tao.localdomain/tao/test/TaoPhpUnitTestRunner.php';
include_once '/var/www/tao.localdomain/tao/includes/raw_start.php';

class DynamoDbDriverTestCase extends TaoPhpUnitTestRunner
{
    
    private $conn;
    
    public function setUp() {
        //$this->conn = common_persistence_KeyValuePersistence::getPersistence('serviceState');
        $this->conn = common_persistence_KeyValuePersistence::getPersistence('keyValueResult');
    }
    
    public function tearDown() {
        
    }
    
    public function testVarious() {
        echo "\n";
        //$this->conn->set('qweqwe', 'ewqewq');
        //echo '['.$this->conn->get('qweqwe').']';
        //echo (int)$this->conn->hSet('tc1', 'ct1', 'tctctc');
        //echo (int)$this->conn->hSet('', 'ct2', 'tc2tc2tc2');
        //echo $this->conn->hGet('tc1', 'ct1');
        
        echo (int)$this->conn->del('prepo1');
        echo (int)$this->conn->hSet('prepo1', 'key', 'value nekvo');
        echo (int)$this->conn->set('prepo1', 'jiji');
        print_r( $this->conn->hGetAll('prepo1') );
        echo $this->conn->hGet('prepo1', 'key');
        echo (int)$this->conn->hExists('prepo1', 'key');
        echo (int)$this->conn->hExists('prepo1', 'keya');
        
        $this->conn->hmSet('prepo1', array('dve'=>'dvaise', 'tri'=>'triise'));
        print_r( $this->conn->hGetAll('prepo1') );
        
    }
    
}
