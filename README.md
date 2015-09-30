## Ads-b decoder for PHP

This component decode DF17 raw message from dump1090 program (port 30002).
[dump1090] (https://github.com/antirez/dump1090)

For using it : 

in the composer.json file add this require :

```
"require": {
    "kletellier/adsb": "dev-master"
}
```

after composer update you can use it by adding the composer autoload to your project.

```php 
require 'vendor/autoload.php';
```

###Example : 

```php 

use Kletellier\Adsb;
// use message 8D4CA2AC9940E9B0600403144BEB
$adsb = new Adsb("8D4CA2AC9940E9B0600403144BEB");
if($adsb->isValid())
{
    // message CRC  is correct
    // you can test the message type
    if($adsb->isisAircraftVelocitiesMsg())
    {
        echo "Aircraft Icao code : " . $adsb->getIcao(); ."\n"; // present in all messages
        echo "Actual speed : " . $adsb->getSpeed()."\n";
        echo "Actual heading : " . $adsb->getHeading()."\n";
    }
    if($adsb->isAircraftIdentificationMsg())
    {
        echo "Aircraft Icao code : " . $adsb->getIcao(); ."\n"; // present in all messages
        echo "Actual flight number : " . $adsb->getIdent()."\n";         
    }
}
```

For retrieve position it's an different way.
You must have 2 messages, one odd message, and one even message.

Example : 

```php 

use Kletellier\Adsb;
use Kletellier\AdsbUtils;

// use message 
$adsb_odd = new Adsb("*8D4CA1FA58BDF40C1A61F7846E27;",1443430837);
$adsb_even = new Adsb("*8D4CA1FA58BDF095F26507CB5A87;",1443430840);
// this message was necessary from same icao aircraft and a position message 
// test : $adsb_odd->isAircraftPositionMsg() must be true
// check odd and even bit 
if($adsb_odd->getOe=="1" && $adsb_even->getOe=="0")
{
    $latlon = AdsbUtils::getCoordinates($adsb_odd,$adsb_even);
    echo "airplane : " . $adsb_odd->getIcao . "\n";
    echo "latitude : " . $latlon["latitude"] . "\n";
    echo "longitude : " . $latlon["longitude"] . "\n";
}
  
```