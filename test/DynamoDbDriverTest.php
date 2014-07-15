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

require_once dirname(__FILE__) . '/../../tao/test/TaoPhpUnitTestRunner.php';
include_once dirname(__FILE__) . '/../../tao/includes/raw_start.php';

class DynamoDbDriverTestCase extends TaoPhpUnitTestRunner
{
    
    private $driver;
    
    public function setUp() {
        TaoPhpUnitTestRunner::initTest();
        $this->driver = common_persistence_KeyValuePersistence::getPersistence('keyValueResult');
    }
    
    public function tearDown() {
        
    }
    
    public function testVarious() {
        
        $this->driver->set('arebepakpak', 1);
        $this->driver->incr('arebepakpak');
        $this->driver->incr('arebepakpak');
        $this->driver->incr('manjasgrozde');
        $this->driver->incr('manjasgrozde');
        echo $this->driver->get('arebepakpak');
        echo $this->driver->get('manjasgrozde');
        
        exit;
        
        return;
        echo "\n";
        //$this->conn->set('qweqwe', 'ewqewq');
        //echo '['.$this->conn->get('qweqwe').']';
        //echo (int)$this->conn->hSet('tc1', 'ct1', 'tctctc');
        //echo (int)$this->conn->hSet('', 'ct2', 'tc2tc2tc2');
        //echo $this->conn->hGet('tc1', 'ct1');
        
        echo (int)$this->driver->del('prepo1');
        echo (int)$this->driver->hSet('prepo1', 'key', 'value nekvo');
        echo (int)$this->driver->set('prepo1', 'jiji');
        print_r( $this->driver->hGetAll('prepo1') );
        echo $this->driver->hGet('prepo1', 'key');
        echo (int)$this->driver->hExists('prepo1', 'key');
        echo (int)$this->driver->hExists('prepo1', 'keya');
        
        $this->driver->hmSet('prepo1', array('dve'=>'dvaise', 'tri'=>'triise'));
        print_r( $this->driver->hGetAll('prepo1') );
        
    }
    
    public function testKvobekvo() {
        //$this->assertEquals('bar', 'baz');
        //echo 'testkvobekvo';
        //echo get_class($this);
        //print_r(get_class_methods( get_class($this) ) );
        //$this->assertEquals(1, 2);
    }
    
    public function testNeznam() {
        //
        //echo 'testneznam';
    }
    
    public function testSet() {
        $this->assertTrue( $this->driver->set('phpUnitTestKey', 1) );
    }
    
    /**
     * @depends testSet
     */
    public function testGet() {
        $this->assertSame( $this->driver->get('phpUnitTestKey'), '1');
    }
    
    /**
     * @depends testSet
     */
    public function testExists() {
        $this->assertTrue( $this->driver->exists('phpUnitTestKey') );
    }
    
    /**
     * @depends testSet
     */
    public function testIncr() {
        echo (int)$this->driver->exists('phpUnitTestKey');
        //echo $this->driver->incr('phpUnitTestKey');
        //$this->assertSame( $this->driver->incr('phpUnitTestKey'), '2' );
    }

    /**
     * @depends testSet
     */
    public function testDel() {
        $this->assertTrue( $this->driver->del('phpUnitTestKey') );
    }
    
    public function testHmSet() {
        
    }
    
    public function testHExists() {
        
    }
    
    public function testHGetAll() {
        
    }
    
    public function testHGet() {
        
    }
    
    public function testHSet() {
        
    }
    
    public function testKeys() {
        
    }
    
}
