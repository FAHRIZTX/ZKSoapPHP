ZK Soap PHP Library
======

A PHP Library For Manage Data From Fingerprint Machine with SOAP Protocol

## Features

 * Get Attendance Log with DateRange
 * Get User Information

## Requirements

 * PHP version 7.2 or higher
 * Fingerprint Machine Support ZK Web Service

## Easy Installation

### Install with composer

To install with [Composer](https://getcomposer.org/), simply require the
latest version of this package.

```bash
composer require fahriztx/zksoapphp
```

Make sure that the autoload file from Composer is loaded.

```php
// somewhere early in your project's loading, require the Composer autoloader
// see: http://getcomposer.org/doc/00-intro.md
require 'vendor/autoload.php';

```

## Quick Start

Just pass your IP, Port and Comkey :

* Get Attendance

```php
// reference the ZK Soap PHP namespace
use Fahriztx\Zksoapphp\Fingerprint;

// initial
$machine = Fingerprint::connect('192.168.1.175', '80', '123456');

// get machine status
echo "Machine Status : ".$machine->getStatus(); // connect | disconnect

// get all log data
print_r($machine->getAttendance()); // return Array of Attendance Log

// get all log data with date
print_r($machine->getAttendance('all', '2022-05-01')); // return Array of Attendance Log

// get all log data with date range
print_r($machine->getAttendance('all', '2022-05-01', '2022-05-10')); // return Array of Attendance Log

// get specific pin log data
print_r($machine->getAttendance(1)); // return Array of Attendance Log
// OR Array
print_r($machine->getAttendance([1, 2])); // return Array of Attendance Log

```

* Get User Information

```php
// reference the ZK Soap PHP namespace
use Fahriztx\Zksoapphp\Fingerprint;

// initial
$machine = Fingerprint::connect('192.168.1.175', '80', '123456');

// get machine status
echo "Machine Status : ".$machine->getStatus(); // connect | disconnect

// get all user data
print_r($machine->getUserInfo()); // return Array of User Info Data

// get specific pin user data
print_r($machine->getUserInfo(1)); // return Array of User Info Data
// OR Array
print_r($machine->getUserInfo([1, 2])); // return Array of User Info Data

```

## Changelog

* Add support fot PHP >= 8.0
* Fixing Non-static method Fahriztx\Zksoapphp\Fingerprint::connect() cannot be called statically
