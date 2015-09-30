#!/usr/bin/php
<?php
 
/**
 * Enable autoload
 */
require '../vendor/autoload.php';
 

// add data output
$stdout = TRUE;
$dump1090_ip = "127.0.0.1";
 
date_default_timezone_set('UTC');
  
if ($stdout) echo "Connecting to Dump1090 ...\n";

function create_socket($host, $port, &$errno, &$errstr) {
    $ip = gethostbyname($host);
    $s = socket_create(AF_INET, SOCK_STREAM, 0);
    if (socket_set_nonblock($s)) 
	{
        $r = @socket_connect($s, $ip, $port);
        if ($r || socket_last_error() == 114 || socket_last_error() == 115) 
		{
            return $s;
        }
    }
    $errno = socket_last_error($s);
    $errstr = socket_strerror($errno);
    socket_close($s);
    return false;
}

function connect() 
{
    global $dump1090_ip, $sockets,  $stdout ;    
	$s = create_socket($dump1090_ip,"30002", $errno, $errstr);
	if($s) 
	{		 	 
		if ($stdout) echo 'Connection in progress to Dump1090....'."\n";
	} 
	else 
	{
		if ($stdout) echo 'Connection failed to Dump1090 : '.$errno.' '.$errstr."\n";		 
	}
	return $s;
}
 
$timeout = 15;
$socket = connect(); 
if ($stdout) echo "Connected!\n";
sleep(1); 
$i = 1;
 
while ($i > 0) 
{   
	$buffer = socket_read($socket, 3000,PHP_NORMAL_READ);	
	$buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'',$buffer));
	if ($stdout) 
	{
		echo "line:".$buffer." \n";
	}
	if ($buffer != '') 
	{	 	 
		if(strlen($buffer)>=28)
		{
			$mes = new \Kletellier\Adsb($buffer);
			if($mes->isValid())
			{
				$icao = $mes->getIcao();
				if ($stdout)
				{
					echo "\ticao:".$icao." \n";	
				}
			 	if($mes->isAircraftIdentificationMsg())
				{
					if ($stdout) 
					{
						echo "\tident:".$mes->getIdent()." \n";
					}
				}

				if($mes->isAircraftPositionMsg())
				{		
					$altitude = $mes->getAltitude();
					if(isset($altitude) && $altitude!="")
					{							  
						if ($stdout) 
						{
							echo "\taltitude:".$altitude." \n";
						}
					}			
				}

				if($mes->isAircraftVelocitiesMsg())
				{					 
					if ($stdout) 
					{
						echo "\tspeed:".$mes->getSpeed()." \n";	
					}
					if ($stdout) 
					{
						echo "\theading:".$mes->getHeading()." \n";
					}
				}
			}
			else
			{
				if ($stdout) echo "bad CRC : ".$mes->getMsg()." \n";
			}
			
		}			 
	}	 
	else
	{				 
		@socket_close($socket);	
	}
}


?>
