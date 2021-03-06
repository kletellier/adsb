<?php 

namespace Kletellier; 

/**
 * Class giving some static tools
 * @package Kletellier/Adsb
 */
class AdsbUtils
{ 

  /**
   * Return byte array checksum table
   * @return array
   */
  public static function getChecksumTable()
  {
    return array(
      0x3935ea, 0x1c9af5, 0xf1b77e, 0x78dbbf, 0xc397db, 0x9e31e9, 0xb0e2f0, 0x587178,
      0x2c38bc, 0x161c5e, 0x0b0e2f, 0xfa7d13, 0x82c48d, 0xbe9842, 0x5f4c21, 0xd05c14,
      0x682e0a, 0x341705, 0xe5f186, 0x72f8c3, 0xc68665, 0x9cb936, 0x4e5c9b, 0xd8d449,
      0x939020, 0x49c810, 0x24e408, 0x127204, 0x093902, 0x049c81, 0xfdb444, 0x7eda22,
      0x3f6d11, 0xe04c8c, 0x702646, 0x381323, 0xe3f395, 0x8e03ce, 0x4701e7, 0xdc7af7,
      0x91c77f, 0xb719bb, 0xa476d9, 0xadc168, 0x56e0b4, 0x2b705a, 0x15b82d, 0xf52612,
      0x7a9309, 0xc2b380, 0x6159c0, 0x30ace0, 0x185670, 0x0c2b38, 0x06159c, 0x030ace,
      0x018567, 0xff38b7, 0x80665f, 0xbfc92b, 0xa01e91, 0xaff54c, 0x57faa6, 0x2bfd53,
      0xea04ad, 0x8af852, 0x457c29, 0xdd4410, 0x6ea208, 0x375104, 0x1ba882, 0x0dd441,
      0xf91024, 0x7c8812, 0x3e4409, 0xe0d800, 0x706c00, 0x383600, 0x1c1b00, 0x0e0d80,
      0x0706c0, 0x038360, 0x01c1b0, 0x00e0d8, 0x00706c, 0x003836, 0x001c1b, 0xfff409,
      0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000,
      0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000,
      0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000
      );
  }

  /**
   * Return char array for translating flight ident
   * @return string
   */
  public static function getCharArray()
  {
    return "#ABCDEFGHIJKLMNOPQRSTUVWXYZ#####_###############0123456789######";
  }

  /**
   * Function to get positive modulo
   * @param integer $a 
   * @param integer $b 
   * @return integer
   */
  private static function modPos($a,$b)
  {
    $tmp = $a % $b;
    if($tmp<0)
    {
      $tmp+= $b;
    }
    return $tmp;
  }

  /**
   * Compute coordinates from Odd and Even Message (for bette result even message must be catched in first)
   * @param \Kletellier\Adsb $adsb_odd 
   * @param  \Kletellier\Adsb $adsb_even 
   * @return key value array with latitude and longitude keys
   */
  public static function getCoordinates(\Kletellier\Adsb $adsb_odd,\Kletellier\Adsb $adsb_even)
  {
    $ret = array();
    $cprlat_odd = $adsb_odd->getLatitude();
    $cprlon_odd = $adsb_odd->getLongitude();
    $cprlat_even = $adsb_even->getLatitude();
    $cprlon_even = $adsb_even->getLongitude();
    $dlat_even = 360.0 / 60;
    $dlat_odd = 360.0 / 59;

    // calculate Latitude index
    $j = floor(((59 * $cprlat_even - 60 * $cprlat_odd) / 131072)  + 0.5);   

    $lat_even = $dlat_even * (  self::modPos($j,60)  + $cprlat_even / 131072);
    if($lat_even>=270)
    {
      $lat_even = $lat_even - 360;
    }

    $lat_odd = $dlat_odd * (  self::modPos($j,59) + $cprlat_odd / 131072);
    if($lat_odd>=270)
    {
      $lat_odd = $lat_odd - 360;
    }

    if(self::cprNL($lat_even)==self::cprNL($lat_odd))
    {
      $lat = "";
      $lon = "";
      if($adsb_even->getTs()>$adsb_odd->getTs())
      {
        $lat = $lat_even;
        $ni = self::cprN($lat_even,0);
        $m = floor(((($cprlon_even * (self::cprNL($lat_even)-1)) - ($cprlon_odd * self::cprNL($lat_even)))/131072.0) + 0.5 );
        $lon = (360.0/$ni) * ( self::modPos($m,$ni) + $cprlon_even/131072);       
      }
      else
      {
        $lat = $lat_odd;
        $ni = self::cprN($lat_odd,1);
        $m = floor(((($cprlon_even * (self::cprNL($lat_odd)-1)) - ($cprlon_odd * self::cprNL($lat_odd)))/131072) + 0.5 );
        $lon = (360.0/$ni) * ( self::modPos($m,$ni) + $cprlon_odd/131072);
      }   
      $lon -= floor(($lon+180)/360)*360;
      if ($lat > -91 && $lat < 91 && $lon > -181 && $lon < 181)
      {
         $ret["longitude"] = $lon;
         $ret["latitude"] = $lat;
      }
      else
      {
        $ret = FALSE;
      }
     
    }
    else
    {
      // incorrect value
      $ret = FALSE;
    }
    return $ret;
  }
  
  /**
   * Return latitude index
   * @param float $lat 
   * @return integer latitude index
   */
  public static function cprNL($lat) 
  {
      if ($lat < 0) $lat = -$lat;              
      if ($lat < 10.47047130) return 59;
      if ($lat < 14.82817437) return 58;
      if ($lat < 18.18626357) return 57;
      if ($lat < 21.02939493) return 56;
      if ($lat < 23.54504487) return 55;
      if ($lat < 25.82924707) return 54;
      if ($lat < 27.93898710) return 53;
      if ($lat < 29.91135686) return 52;
      if ($lat < 31.77209708) return 51;
      if ($lat < 33.53993436) return 50;
      if ($lat < 35.22899598) return 49;
      if ($lat < 36.85025108) return 48;
      if ($lat < 38.41241892) return 47;
      if ($lat < 39.92256684) return 46;
      if ($lat < 41.38651832) return 45;
      if ($lat < 42.80914012) return 44;
      if ($lat < 44.19454951) return 43;
      if ($lat < 45.54626723) return 42;
      if ($lat < 46.86733252) return 41;
      if ($lat < 48.16039128) return 40;
      if ($lat < 49.42776439) return 39;
      if ($lat < 50.67150166) return 38;
      if ($lat < 51.89342469) return 37;
      if ($lat < 53.09516153) return 36;
      if ($lat < 54.27817472) return 35;
      if ($lat < 55.44378444) return 34;
      if ($lat < 56.59318756) return 33;
      if ($lat < 57.72747354) return 32;
      if ($lat < 58.84763776) return 31;
      if ($lat < 59.95459277) return 30;
      if ($lat < 61.04917774) return 29;
      if ($lat < 62.13216659) return 28;
      if ($lat < 63.20427479) return 27;
      if ($lat < 64.26616523) return 26;
      if ($lat < 65.31845310) return 25;
      if ($lat < 66.36171008) return 24;
      if ($lat < 67.39646774) return 23;
      if ($lat < 68.42322022) return 22;
      if ($lat < 69.44242631) return 21;
      if ($lat < 70.45451075) return 20;
      if ($lat < 71.45986473) return 19;
      if ($lat < 72.45884545) return 18;
      if ($lat < 73.45177442) return 17;
      if ($lat < 74.43893416) return 16;
      if ($lat < 75.42056257) return 15;
      if ($lat < 76.39684391) return 14;
      if ($lat < 77.36789461) return 13;
      if ($lat < 78.33374083) return 12;
      if ($lat < 79.29428225) return 11;
      if ($lat < 80.24923213) return 10;
      if ($lat < 81.19801349) return 9;
      if ($lat < 82.13956981) return 8;
      if ($lat < 83.07199445) return 7;
      if ($lat < 83.99173563) return 6;
      if ($lat < 84.89166191) return 5;
      if ($lat < 85.75541621) return 4;
      if ($lat < 86.53536998) return 3;
      if ($lat < 87.00000000) return 2;
      return 1;
      // function to calculate this table
      // $ret = 1;
      // try 
      // {
      //   $nz = 60;
      //   $a = 1 - cos(pi() * 2 / $nz);
      //   $b = cos( pi() / 180 * abs($lat));
      //   $c = pow($b,2);
      //   $nl = 2 * pi() / (acos(1-$a/$c));
      //   return (int)$nl;
      // } 
      // catch (Exception $e) 
      // {
        
      // }
      // return $ret;
  }

  public static function cprN($lat,$is_Odd) 
  {
    $nl = self::cprNL($lat) - $is_Odd;
    if ($nl < 1) 
      {
        $nl=1;
      }
    return $nl;           
  }
} 