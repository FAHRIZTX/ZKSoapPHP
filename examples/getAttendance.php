<?php

require_once __DIR__."/../vendor/autoload.php";

use Fahriztx\Zksoapphp\Fingerprint;

$machine = Fingerprint::connect('192.168.1.175', '80', '123456');
echo "Machine Status : ".$machine->getStatus().PHP_EOL;
print_r($machine->getAttendance());
print_r($machine->getUserInfo());