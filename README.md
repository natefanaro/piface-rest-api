## piface-rest-api

### About

This is a generic REST API that interfaces with a PiFace board attached to a RaspberryPi.

### Requirements

##### Hardware
  * Raspberry PI and PiFace module.

##### Software 
  * PHP 5.4+
  * Apache with mod_rewrite enabled
  * PHP SPI extension: https://github.com/frak/php_spi

### Install

1. `git clone https://github.com/natefanaro/piface_rest.git`
1. `cd piface_rest`
1. `curl -sS https://getcomposer.org/installer | php`
1. `php composer.phar install`
1. Point apache to piface_rest/public_html
  * Makke sure apache allows .htaccess files

### API Endpoints

#### Obtaining state

* GET /input/
* GET /led/
* GET /output/
* GET /relay/
* GET /switch/

Returns the status of all devices in that category. The response object contains an indexed array. That is pin => state. 

*Example*

    curl http://raspberrypi.local/api/output
    {"status":"200","response":[1,0,0,0,0,0,0,0]}

====

* GET /input/:input
* GET /led/:led
* GET /output/:output
* GET /relay/:relay
* GET /switch/:switch

Returns the status of one device. The response object contains the value of the device (either 0 or 1). 

*Example*

    curl http://raspberrypi.local/api/output/1
    {"status":"200","response":1}

====

#### Setting state

* GET /led/:led/:value
* GET /output/:output/:value
* GET /relay/:relay/:value

Sets the value of one device. Value can either be 0 or 1, The response object contains the new value of the device.

*Example*

    curl http://raspberrypi.local/api/output/1/0
    {"status":"200","response":0}
    
====

#### Selftest

* GET /led/test/

Blinks the leds on and off in a pattern.

### Status codes

Status codes mirror http status codes. 

**200** Success

    {"status":"200","response":1}

**404** Unknown endpoint. This could mean a bad path or you accessed a device that does not exist. (ie: led 40)

    {"status":"404","message":"Route not found"}

**500** Exception or other error. The code and message are returned.

    {"status":"500","exception":{"code":8,"message":"Undefined offset: -1"}}
