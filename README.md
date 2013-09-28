piface api

About
=====
This is a generic REST API that interfaces with a PiFace board attached to a RaspberryPi.

Requirements
====
Hardware: Raspberry PI and PiFace module.
PHP 5.4+
Apache with mod_rewrite enabled
PHP SPI extension: https://github.com/frak/php_spi

Install
=====
# Clone the repo, install composer, install requirements from composer.json
    git clone https://github.com/natefanaro/piface_rest.git
	cd piface_rest
    curl -sS https://getcomposer.org/installer | php
	php composer.phar install
# Point apache to piface_rest/public_html
## Makke sure apache allows .htaccess files

API Endpoints
====

GET /input/
GET /led/
GET /output/
GET /relay/
GET /switch/

Returns the status of all devices in that category. The response object contains an indexed array. That is pin => state. 

Ex
    curl http://raspberrypi.local/api/output
    {"status":"200","response":[1,0,0,0,0,0,0,0]}

====

GET /input/:input
GET /led/:led
GET /output/:output
GET /relay/:relay
GET /switch/:switch

Returns the status of one device. The response object contains the value of the device (either 0 or 1). 

Ex
    curl http://raspberrypi.local/api/output/1
    {"status":"200","response":1}

====

GET /led/:led/:value
GET /output/:output/:value
GET /relay/:relay/:value

Sets the value of one device. Value can either be 0 or 1, The response object contains the new value of the device.

Ex
    curl http://raspberrypi.local/api/output/1/0
    {"status":"200","response":0}
    
====

GET /led/test/

Blinks the leds on and off in a pattern.

Status codes
====

Status codes mirror http status codes. 

200: Success
    {"status":"200","response":1}

404: Unknown endpoint. This could mean a bad path or you accessed a device that does not exist. (ie: led 40)
    {"status":"404","message":"Route not found"}

500: Exception or other error. The code and message are returned.
    {"status":"500","exception":{"code":8,"message":"Undefined offset: -1"}}

Issues
====
No authentication is currently supported.
	Current recommendation is to do this via htaccess.

One piface board is supported at a time 
	Four can be connected to the Raspberry Pi

Create a generic response function that can receive a status and message

Use the Middleware feature of Slim to handle responses.
	This can receive the status code from $app->response. The body of the message can be json_encoded.
	Probably easier to use the slim.after.router hook.
