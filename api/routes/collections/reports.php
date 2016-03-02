<?php
/**
 * Created by PhpStorm.
 * User: Dapo
 * Date: 09-Dec-14
 * Time: 1:43 PM
 */

return call_user_func(function(){

	$api = new \Phalcon\Mvc\Micro\Collection();

	$api
		->setPrefix('/v1/reports')
		->setHandler('\PhalconRest\Controllers\ReportsController')
		->setLazy(true);
	
	$api->get('/summary', 'summary');
	$api->get('/staff', 'staff');
	$api->get('/details', 'details');

	return $api;
});