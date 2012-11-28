<?php

require_once('../homecards-proxy.php');

class HCLeadTest extends PHPUnit_Framework_TestCase
{
    public function testLogin()
    {
        $stack = array();
        $this->assertEmpty($stack);
 
        return $stack;
    }
 
    /**
     * @depends testEmpty
     */
    public function testPush(array $stack)
    {
        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack)-1]);
        $this->assertNotEmpty($stack);
 
        return $stack;
    }
 
    /**
     * @depends testPush
     */
    public function testPop(array $stack)
    {
        $this->assertEquals('foo', array_pop($stack));
        $this->assertEmpty($stack);
    }
}
?>

