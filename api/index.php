<?php

use Phalcon\Config\Adapter\Ini as IniConfig;
use Phalcon\DI\FactoryDefault as DefaultDI;
use Phalcon\Loader;

/**
 * By default, namespaces are assumed to be the same as the path.
 * This function allows us to assign namespaces to alternative folders.
 * It also puts the classes into the PSR-0 autoLoader.
 */
$loader = new Loader();
$loader->registerNamespaces(array(
	'PhalconRest\Models' => __DIR__ . '/models/',
	'PhalconRest\Controllers' => __DIR__ . '/controllers/',
	'PhalconRest\Exceptions' => __DIR__ . '/exceptions/',
	'PhalconRest\Responses' => __DIR__ . '/responses/'
))->register();

/**
 * @var DefaultDI
 */
$di = new DefaultDI();

$di->set('collections', function(){
	return include('./routes/routeLoader.php');
});

$di->setShared('config', function() {
	$fileName = $_SERVER['SERVER_ADDR'] == '127.0.0.1' ? "config" : "production";

	return new IniConfig("config/$fileName.ini");
});

$di->setShared('session', function(){
	$session = new \Phalcon\Session\Adapter\Files();
	$session->start();
	return $session;
});

$di->set('modelsCache', function() {

	//Cache data for one day by default
	$frontCache = new \Phalcon\Cache\Frontend\Data(array(
		'lifetime' => 3600
	));

	//File cache settings
	$cache = new \Phalcon\Cache\Backend\File($frontCache, array(
		'cacheDir' => __DIR__ . '/cache/'
	));

	return $cache;
});

$di->set('db', function() use ($di) {
	$config = $di->getShared('config');

	return new \Phalcon\Db\Adapter\Pdo\Mysql (array(
		'host' => $config->database->host,
		'username' => $config->database->username,
		'password' => $config->database->password,
		'dbname' => $config->database->dbname
	));
});

/**
 * If our request contains a body, it has to be valid JSON.  This parses the 
 * body into a standard Object and makes that vailable from the DI.  If this service
 * is called from a function, and the request body is nto valid JSON or is empty,
 * the program will throw an Exception.
 */
$di->setShared('requestBody', function() {
	$in = file_get_contents('php://input');
	$in = json_decode($in, FALSE);

	// JSON body could not be parsed, throw exception
	if($in === null){
		throw new \PhalconRest\Exceptions\HTTPException(
			'There was a problem understanding the data sent to the server by the application.',
			409,
			array(
				'dev' => 'The JSON body sent to the server was unable to be parsed.',
				'internalCode' => 'REQ1000',
				'more' => ''
			)
		);
	}

	return $in;
});

$app = new Phalcon\Mvc\Micro();
$app->setDI($di);

$app->before(function() use ($app, $di) {

	switch($app->getRouter()->getRewriteUri()) {
		case '/v1/users/login/':
		case '/v1/users/register/':
		case '/v1/reports/summary':
		case '/v1/reports/staff':
		case '/v1/reports/details':
		case '/example/route':
			return true;
			break;
	}

	// Basic auth, for programmatic responses
	$headers = apache_request_headers();
	if(isset($headers['X_API_KEY'])){
		$user = new \PhalconRest\Controllers\UserController();
		if (!$user->loginWithPrivateKey($headers['X_API_KEY']))
			throw new \PhalconRest\Exceptions\HTTPException("Invalid/Expired API Key", 403);
		else
			return true;
	}

	// If we made it this far, we have no valid auth method, throw a 401.
	throw new \PhalconRest\Exceptions\HTTPException(
		'Must login or provide credentials.',
		401,
		array(
			'dev' => 'Please provide credentials by either passing in a session token via cookie, or providing password and username via BASIC authentication.',
			'internalCode' => 'Unauth:1'
		)
	);
});


/**
 * Mount all of the collections, which makes the routes active.
 */
foreach($di->get('collections') as $collection){
	$app->mount($collection);
}


/**
 * The base route return the list of defined routes for the application.
 * This is not strictly REST compliant, but it helps to base API documentation off of.
 * By calling this, you can quickly see a list of all routes and their methods.
 */
$app->get('/', function() use ($app){
	$routes = $app->getRouter()->getRoutes();
	$routeDefinitions = array('GET'=>array(), 'POST'=>array(), 'PUT'=>array(), 'PATCH'=>array(), 'DELETE'=>array(), 'HEAD'=>array(), 'OPTIONS'=>array());
	foreach($routes as $route){
		$method = $route->getHttpMethods();
		$routeDefinitions[$method][] = $route->getPattern();
	}
	return $routeDefinitions;
});

/**
 * After a route is run, usually when its Controller returns a final value,
 * the application runs the following function which actually sends the response to the client.
 *
 * The default behavior is to send the Controller's returned value to the client as JSON.
 * However, by parsing the request querystring's 'type' paramter, it is easy to install
 * different response type handlers.  Below is an alternate csv handler.
 */
$app->after(function() use ($app) {

	// OPTIONS have no body, send the headers, exit
	if($app->request->getMethod() == 'OPTIONS'){
		$app->response->setStatusCode('200', 'OK');
		$app->response->send();
		return;
	}

	// Respond by default as JSON
	if(!$app->request->get('type') || $app->request->get('type') == 'json'){

		// Results returned from the route's controller.  All Controllers should return an array
		$records = $app->getReturnedValue();

		$response = new \PhalconRest\Responses\JSONResponse();
		$response->useEnvelope(true) //this is default behavior
			->convertSnakeCase(true) //this is also default behavior
			->send($records);

		return;
	}
	else if($app->request->get('type') == 'csv'){

		$records = $app->getReturnedValue();
		$response = new \PhalconRest\Responses\CSVResponse();
		$response->useHeaderRow(true)->send($records);

		return;
	}
	else {
		throw new \PhalconRest\Exceptions\HTTPException(
			'Could not return results in specified format',
			403,
			array(
				'dev' => 'Could not understand type specified by type paramter in query string.',
				'internalCode' => 'NF1000',
				'more' => 'Type may not be implemented. Choose either "csv" or "json"'
			)
		);
	}
});

/**
 * The notFound service is the default handler function that runs when no route was matched.
 * We set a 404 here unless there's a suppress error codes.
 */
$app->notFound(function () use ($app) {
	throw new \PhalconRest\Exceptions\HTTPException(
		'Not Found.',
		404,
		array(
			'dev' => 'That route was not found on the server.',
			'internalCode' => 'NF1000',
			'more' => 'Check route for mispellings.'
		)
	);
});

/**
 * If the application throws an HTTPException, send it on to the client as json.
 * Else wise, just log it.
 */
set_exception_handler(function($exception) use ($app){
	//HTTPException's send method provides the correct response headers and body
	if(is_a($exception, 'PhalconRest\\Exceptions\\HTTPException')){
		$exception->send();
	}
	error_log($exception);
	error_log($exception->getTraceAsString());
});

$app->handle();

