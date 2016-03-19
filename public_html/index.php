<?php
require '../vendor/autoload.php';
use Pkj\Raspberry\PiFace\PiFaceDigital;

if (!class_exists('\Spi')) {
	die ("Spi extension must be installed (https://github.com/frak/php_spi)");
}

$app = new \Slim\Slim(array(
	'debug' => false
));

// Exception handler (make sure debug is false above)
$app->error(function (\Exception $e) use ($app) {
	print json_encode(array(
		'status' => '500', 
		'exception' => array(
			'code' => $e->getCode(),
			'message' => $e->getMessage()
		)
	));
});

$app->notFound(function () use ($app) {
	print json_encode(array(
		'status' => '404', 
		'message' => 'Route not found'
	));
});

$pi = PiFaceDigital::create();

// A COLD boot of Pi would give incorrect result of the INPUT, and RELAY/OUTPUT cannot be set until INIT() is executed once.
// But we cannot run INIT() every time because it would reset everything to OFF. This is the workaround by using one of the 
// LED OUTPUT to set/get the value to determine if INIT() is needed. Pi ports initialization has to be executed once only
// until next reboot. More elegant solution is to run .py script to init Pi at boot time, or use PHP APC/MemCache to 
// store a variable shared by all http requests. 

$testId = 7;
$original = $pi->getLeds()[$testId]->getValue();

if ($original == 1)
   $pi->getLeds()[$testId]->setValue(0);
else
   $pi->getLeds()[$testId]->setValue(1);

if ($pi->getLeds()[$testId]->getValue() == $original)
    $pi->init();
else
    $pi->getLeds()[$testId]->setValue($original);



// Defining min/max values
\Slim\Route::setDefaultConditions(array(
    'input'  => '[0-' . (count($pi->getInputPins()) - 1) . ']',
    'led'    => '[0-' . (count($pi->getLeds()) - 1) . ']',
    'output' => '[0-' . (count($pi->getOutputPins()) - 1)  . ']',
    'relay'  => '[0-' . (count($pi->getRelays()) - 1) . ']',
    'switch' => '[0-' . (count($pi->getSwitches()) - 1) . ']',
    'value'  => '[0-1]',
));

// Begin read only devices

// Input
$app->get('/input/', function () use ($app, $pi) {
	responseSuccess(outputAll($pi->getInputPins()));
});
$app->get('/input/:input', function ($input) use ($app, $pi) {
	responseSuccess($pi->getInputPins()[$input]->getValue());
});

// Switch
$app->get('/switch/', function () use ($app, $pi) {
	responseSuccess(outputAll($pi->getSwitches()));
});
$app->get('/switch/:switch', function ($switch) use ($app, $pi) {
	responseSuccess($pi->getSwitches()[$switch]->getValue());
});

// Begin read/write devices

// Output
$app->get('/output/', function () use ($app, $pi) {
	responseSuccess(outputAll($pi->getOutputPins()));
});
$app->get('/output/:output', function ($output) use ($app, $pi) {
	responseSuccess($pi->getOutputPins()[$output]->getValue());
});
$app->get('/output/:output/:value', function ($output, $value) use ($app, $pi) {
	$pi->getOutputPins()[$output]->setValue($value);
	responseSuccess(intval($value));
});

// LED (not really sure if this is needed, since this mirrors output)
$app->get('/led/', function () use ($app, $pi) {
	responseSuccess(outputAll($pi->getLeds()));
});
$app->get('/led/:led', function ($led) use ($app, $pi) {
	responseSuccess($pi->getLeds()[$led]->getValue());
});
$app->get('/led/:led/:value', function ($led, $value) use ($app, $pi) {
	$pi->getLeds()[$led]->setValue($value);
	responseSuccess(intval($value));
});

// Relay
$app->get('/relay/', function () use ($app, $pi) {
	responseSuccess(outputAll($pi->getRelays()));
});
$app->get('/relay/:relay', function ($relay) use ($app, $pi) {
	responseSuccess($pi->getRelays()[$relay]->getValue());
});
$app->get('/relay/:relay/:value', function ($relay, $value) use ($app, $pi) {
	$pi->getRelays()[$relay]->setValue($value);
	responseSuccess(intval($value));
});

/** 
 * This test will turn off all leds, then cycle through them 8 times.
 */
$app->get('/led/test/', function () use ($app, $pi) {

	// turn all off
	$i = 0;
	while ($i < 8) {
		$pi->getLeds()[$i]->setValue(0);
		$i++;
	}

	$i = 0;
	while ($i < 64) {
		$led = ($i % 8);
		$prev_led = $led;

		$prev_led--;
		if ($led == 0) {
			$prev_led = 7;
		}

		$pi->getLeds()[$prev_led]->setValue(0);
		$pi->getLeds()[$led]->setValue(1);
		$i++;

		usleep(50000);
	}

	$pi->getLeds()[7]->setValue(0);
	responseSuccess(1);
});

$app->run();

/** 
 * Gets values of inputs/outputs/etc
 *
 * @param Device class object
 * @return Array of values for given class of device
 */
function outputAll($items) {
	$out = array();
	foreach ($items as $pin) {
		$out[] = $pin->getValue();
	}
	return $out;
}

/** 
 * Prints a success response for the API
 * @todo I am not a fan of this. Find a way using the Slim framework to handle this.
 *
 * @param mixed $response Response message
 */
function responseSuccess($response) {
	print json_encode(array('status' => '200', 'response' => $response));
}
