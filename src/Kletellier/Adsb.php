<?php 

namespace Kletellier; 

use Stringy\Stringy as S;

/**
 * Class to decode DF17 Ads-b message
 * @package Kletellier/Adsb
 */
class Adsb
{ 
    protected $_df; // Downlink Format bytes 1 to 5 (base 1)
    protected $_ca; // CA bytes 6 to 8
    protected $_tc; // Type Code bytes 33 to 37
    protected $_oe; // Odd or Even flag bytes 54, 0 for even
    protected $_msg; // message instance of sliceablestringy
    protected $_bin; // binary version of message
    protected $_icao; // icao code for plane
    protected $_ident; // get ident callsign
    protected $_cprlatitude; // latitude
    protected $_cprlongitude; // longitude
    protected $_altitude; // altitude
    protected $_speed; // speed in kts
    protected $_heading; // heading in degrees
    protected $_ts; // timestamp of creating
    protected $_valid; /// checksum is ok

    /**
     * Return if message is valid (checksum is ok)
     * @return boolean
     */
    public function isValid()
    {
      return $this->_valid;
    }

    /**
     * Define rimestamp (Epoch) of message (default value is now) => DateTime::now()->format('U');
     * @param integer $value timestamp 
     * @return $this
     */
    public function setTs($value)
    {
      $this->_ts = $value;
      return $this;
    }

    /**
     * Return timestamp (Epoch) of message
     * @return integer timestamp
     */
    public function getTs()
    {
      return $this->_ts;
    }

    /**
     * Return speed in kts (only if message isAircraftVelocitiesMsg)
     * @return float speed in kts
     */
    public function getSpeed()
    {
      return $this->_speed;
    }

    /**
     * Return heading in degrees (only if message isAircraftVelocitiesMsg)
     * @return float heading in degrees
     */
    public function getHeading()
    {
      return $this->_heading;
    }

    /**
     * Return altitude in feets (only if message isAircraftPositionMsg)
     * @return float altitude in feet
     */
    public function getAltitude()
    {
      return $this->_altitude;
    }

    /**  
     * Return CPR longitude in 17 bits encoded
     * @return integer CPR longitude
     */
    public function getLongitude()
    {
      return $this->_cprlongitude;
    }

    /**
     * Return CPR latitude in 17 bits encoded
     * @return integer CPR latitude
     */
    public function getLatitude()
    {
      return $this->_cprlatitude;
    }

    /**
     * Return flight identification
     * @return string flight identificaion
     */
    public function getIdent()
    {
      return $this->_ident;
    }

    /**
     * Return DF17 message
     * @return string message
     */
    public function getMsg()
    {
      return $this->_msg->__toString();
    }

    /**
     * Return Downlink Format
     * @return string downlink format (only 17 are parsed)
     */
    public function getDf()
    {
      return $this->_df;
    }

    /**
     * Return Message SubType
     * @return string message subtype
     */
    public function getCa()
    {
      return $this->_ca;
    }

    /**
     * Return Type Code
     * @return integer type code message
     */
    public function getTc()
    {
      return $this->_tc;
    }
    
    /**
     * Return Odd or Even bit
     * @return integer odd or even bit (1 for odd)
     */
    public function getOe()
    {
      return $this->_oe;
    }  

    /**
     * Get Icao registration aircraft code
     * @return string aircraft icao code
     */
    public function getIcao()
    {
      return $this->_icao;
    }

    /**
     * Return this message is ident message
     * @return boolean true if the message give ident
     */
    public function isAircraftIdentificationMsg()
    {
      return ($this->_df==17 && $this->_tc>=1 && $this->_tc<=4) ? true : false;
    }

    /**
     * Return this message is localisation message
     * @return boolean true if the message give altitude,longitude,latitude
     */
    public function isAircraftPositionMsg()
    {
      return ($this->_df==17 && $this->_tc>=9 && $this->_tc<=18) ? true : false;
    }

    /**
     * Return this message is velocities message
     * @return boolean true if the message give heading and speed
     */
    public function isAircraftVelocitiesMsg()
    {
      return ($this->_df==17 && $this->_tc==19) ? true : false;
    }

    /**
     * Constructor
     * @param string $value DF17 message 28 characters 
     * @param integer $ts EPoch time of this message
     * @return void
     */
    public function __construct($value = "",$ts = "")
    {
      if(!function_exists('gmp_strval'))
      {
        throw new \Exception("php_gmp extension must be enabled");
      }
      if($value!="")
      {        
        $this->parse($value,$ts);
      }
    }

    /**
     * Parse message
     * @param string $value  DF17 message 28 characters 
     * @param integer $ts EPoch time of this message
     * @return void
     */
    public function parse($value,$ts="")
    {
      $this->init();
      if(strlen($value)>=28)
      {
        $this->_ts = $ts;
        if($this->_ts=="")
        {
          $dt = new \DateTime();
          $this->_ts = $dt->format('U');  
        }              
        $this->prepare($value);
        $this->performChecksum();
        if($this->_valid)
        {
          $this->extractDf();
          $this->extractTc();
          $this->extractCa();
          $this->extractOe();
          $this->extractIcao();
          $this->extractIdent();
          $this->extractPosition();
          $this->extractSpeedHeading();
        }        
      }      
    }

    /**
     * Export message to json structure
     * @return string json structure
     */
    public function toJson()
    {
      $arr = array();
      $arr["df"] = $this->_df;
      $arr["ca"] = $this->_ca;
      $arr["tc"] = $this->_tc;
      $arr["oe"] = $this->_oe;
      $arr["icao"] = $this->_icao;
      $arr["ident"] = $this->_ident;
      $arr["altitude"] = $this->_altitude;
      $arr["cprlongitude"] = $this->_cprlongitude;
      $arr["cprlatitude"] = $this->_cprlatitude;
      $arr["heading"] = $this->_heading;
      $arr["speed"] = $this->_speed;
      $arr["ts"] = $this->_ts;
      $arr["valid"] = $this->_valid;
      $arr["msg"] = $this->getMsg();
      return json_encode($arr);
    }

    /**
     * Fill message property from json string
     * @param string $json DF17 json encoded message
     * @return void
     */
    public function fromJson($json)
    {
      $obj = json_decode($json);
      if(isset($obj->msg) && isset($obj->ts))
      {
        $this->parse($obj->msg,$obj->ts);
      }
    }

    /**
     * Check if the message CRC is ok
     * @return void
     */
    private function performChecksum()
    {
      $ct = AdsbUtils::getChecksumTable();
      $this->_valid = false;
      $checksum = intval(substr($this->_msg,22,6),16);
      $crc = 0;
      for ($i=0; $i < strlen($this->_bin) ; $i++) 
      {        
        if ($this->_bin[$i])
          {
            $crc = $crc^intval($ct[$i],0);
          }         
      }      

      if($crc==$checksum)
      {
        $this->_valid = true;
      }
   
    }

    /**
     * Prepare message for parsing (trim text message and create binary version)
     * @param string $value DF17 message
     * @return void
     */
    private function prepare($value)
    {
      $this->_msg = S::create($value)->removeLeft('*')->removeRight(';');    
      $this->_bin = gmp_strval(gmp_init($this->getMsg(),16), 2);
    } 

    /**
     * Extract speed and heading from velocities message
     * @return void
     */
    private function extractSpeedHeading()
    {
      if($this->isAircraftVelocitiesMsg())
      {
        $v_ew_dir = intval(substr($this->_bin,45,1));
        $v_ew = intval(substr($this->_bin,46,10),2);
        $v_ns_dir = intval(substr($this->_bin,56,1));
        $v_ns = intval(substr($this->_bin,57,10),2);
        if ($v_ew_dir) $v_ew = -1*$v_ew;
        if ($v_ns_dir) $v_ns = -1*$v_ns;
        $this->_speed = sqrt($v_ns*$v_ns+$v_ew*$v_ew);
        $this->_heading = atan2($v_ew,$v_ns)*360.0/(2*pi());
        if ($this->_heading <0) $this->_heading = $this->_heading+360;
      }
    }

    /**
     * Extract CPR position from position message
     * @return void
     */
    private function extractPosition()
    {
      if($this->isAircraftPositionMsg())
      {
        $this->_cprlatitude = intval(substr($this->_bin,54,17),2);
        $this->_cprlongitude = intval(substr($this->_bin,71,17),2);
        // check altitude flag
        $flag = intval(substr($this->_bin,47,1));
        if($flag==1)
        {
          $level = intval(substr($this->_bin,40,7).substr($this->_bin,48,4),2);
          $this->_altitude = ($level * 25)-1000;
        }
      }
    }

    /**
     * Extract Odd or Even bit
     * @return void
     */
    private function extractOe()
    {
      $data = substr($this->_bin,53,1);        
      $this->_oe = base_convert($data,2,10);
    }

    /**
     * Extract Downlink format
     * @return void
     */
    private function extractDf()
    {      
       $data = substr($this->_bin,0,5);        
       $this->_df = base_convert($data,2,10);
    }

    /**
     * Extract type code
     * @return void
     */
    private function extractTc()
    {             
       $data = substr($this->_bin,32,5);  
       $this->_tc= base_convert($data,2,10);
    }

    /**
     * Extract message sub type
     * @return type
     */
    private function extractCa()
    {
      $data = substr($this->_bin,5,3);  
      $this->_ca= base_convert($data,2,10);
    }

    /**
     * Extract flight identification form identification message
     * @return void
     */
    private function extractIdent()
    {
      $this->_ident = "";
      if($this->isAircraftIdentificationMsg())
      {
        $data = substr($this->_bin,40,56);
        $car = AdsbUtils::getCharArray();
        $ident = "";
        for ($i=0; $i < 8; $i++) 
        {   
          $ident .= $car[intval(substr($data,(0 + $i * 6),6),2)];  
        }
        $ident = str_replace('_','',$ident);
        $ident = str_replace('#','',$ident);
        $this->_ident = $ident;
      }     
    }

    /**
     * Extract aircraft icao code 
     * @return void
     */
    private function extractIcao()
    {
      $this->_icao = "";
      if($this->_df=="17")
      { 
        $this->_icao  = substr($this->getMsg(),2,6);  
      }      
    }

    /**
     * initialize all values for instance
     * @return void
     */
    private function init()
    {
      $this->_df="";
      $this->_ca="";
      $this->_tc="";
      $this->_oe="";
      $this->_icao="";
      $this->_ident="";
      $this->_altitude="";
      $this->_cprlongitude="";
      $this->_cprlatitude="";
      $this->_heading="";
      $this->_speed="";
      $this->_ts=0;
      $this->_valid=false;
    }
   
}