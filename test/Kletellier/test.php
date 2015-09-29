<?php  

use Kletellier\Adsb;
use Kletellier\AdsbUtils;

class AdsbTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test if Adsb create instance
     * @return void
     */
    public function testIsInstance()
    {          
        $inst = new Adsb();           
        $this->assertNotNull($inst);

        $inst = new Adsb("*8D4068B6E908BE0009381053B699;");
        $this->assertEquals("8D4068B6E908BE0009381053B699",$inst->getMsg());
        $this->assertGreaterThan(0,$inst->getTs());
    }

    public function testChecksum()
    {
        // test checksum valid
        $inst = new Adsb("*8D4068B6E908BE0009381053B699;");
        $this->assertEquals(true,$inst->isValid());

        $inst = new Adsb("*8D4068B6E908BE0009381053B799;");
        $this->assertEquals(false,$inst->isValid());
    }

    public function testVelocityMsg()
    {
         // test velocity message
        $inst = new Adsb("*8D4CA2AC9940E9B0600403144BEB;");
        $this->assertEquals("17",$inst->getDf());
        $this->assertEquals("19",$inst->getTc());
        $this->assertEquals("5",$inst->getCa());
        $this->assertEquals("4CA2AC",$inst->getIcao());
        $this->assertEquals(true,$inst->isAircraftVelocitiesMsg());
        $this->assertNotNull($inst->getSpeed());
        $this->assertNotNull($inst->getHeading());
        $this->assertEquals(451.7, $inst->getSpeed(), '', 0.1);
        $this->assertEquals(148.9, $inst->getHeading(), '', 0.1);
    }

    public function testPositionMsg()
    {
         // test position message
        $inst = new Adsb("*8D392AE09065B061A27FDD0E4D79;");
        $this->assertEquals("17",$inst->getDf());
        $this->assertEquals("18",$inst->getTc());
        $this->assertEquals("0",$inst->getOe()); // odd or even bit
        $this->assertEquals(true,$inst->isAircraftPositionMsg());  

        $inst = new Adsb("*8D7608EC605FA7DDBC7D4F5C494A;");
        $this->assertEquals("17",$inst->getDf());
        $this->assertEquals("12",$inst->getTc());
        $this->assertEquals("1",$inst->getOe()); // odd or even bit
        $this->assertEquals(true,$inst->isAircraftPositionMsg());  
    }

    public function testIdentMsg()
    {
        // test ident message
        $inst = new Adsb("*8D503DB0200C61F550C8202BA2C7;");
        $this->assertEquals("17",$inst->getDf());
        $this->assertEquals("4",$inst->getTc());
        $this->assertEquals("CFG5TL",$inst->getIdent());
        $this->assertEquals("503DB0",$inst->getIcao());
        $this->assertEquals(true,$inst->isAircraftIdentificationMsg());
    }

    public function testCoordinatesMsg()
    {
        // test coordinates messages  
        $even = new Adsb("*8D4CA1FA58BDF095F26507CB5A87;",1443430840);
        $odd = new Adsb("*8D4CA1FA58BDF40C1A61F7846E27;",1443430837);
        $coord = AdsbUtils::getCoordinates($odd,$even);        
        $this->assertEquals(true,$odd->isAircraftPositionMsg());  
        $this->assertEquals(true,$even->isAircraftPositionMsg()); 
        $this->assertEquals("4CA1FA",$even->getIcao());
        $this->assertEquals("4CA1FA",$odd->getIcao());
        $this->assertEquals("0",$even->getOe());  
        $this->assertEquals("1",$odd->getOe()); 
        $this->assertInternalType('array',$coord);
        $this->assertEquals(48.87858581543, $coord["latitude"], '', 0.01);
        $this->assertEquals(1.8214064378005, $coord["longitude"], '', 0.01);
        
    }

    public function testValue()
    {
        // test values exists
        $inst = new Adsb("*8D4068B6E908BE0009381053B699;");
        $this->assertEquals("17",$inst->getDf());
        $this->assertEquals("29",$inst->getTc());
        $this->assertEquals("5",$inst->getCa());
        $this->assertEquals("4068B6",$inst->getIcao());
    }

    public function testJsonEncoding()
    {
        $inst = new Adsb("*8D503DB0200C61F550C8202BA2C7;");
        $json = json_decode($inst->toJson());
       
        $this->assertEquals("17",$json->df);
        $this->assertEquals("4",$json->tc);
        $this->assertEquals("CFG5TL",$json->ident);
        $this->assertEquals("503DB0",$json->icao);
    }

    public function testJsonDecoding()
    {
        $inst = new Adsb("*8D503DB0200C61F550C8202BA2C7;");
        $json = $inst->toJson();

        $inst2 = new Adsb();
        $inst2->fromJson($json);

        $this->assertEquals($inst,$inst2);
    }

    /**
     * Test CPR Latitude calculation
     * @return void
     */
    public function testCPNL()
    {
        $val = AdsbUtils::cprNL(45);
        $this->assertEquals(42,$val);

        $val = AdsbUtils::cprNL(67);
        $this->assertEquals(23,$val);

        $val = AdsbUtils::cprNL(84);
        $this->assertEquals(5,$val);
    }

     /**
     * Test CPR Latitude calculation odd and even
     * @return void
     */
    public function testCPN()
    {
        $val = AdsbUtils::cprN(45,false);
        $this->assertEquals(42,$val);

        $val = AdsbUtils::cprN(45,true);
        $this->assertEquals(41,$val);
    }
     
    
}