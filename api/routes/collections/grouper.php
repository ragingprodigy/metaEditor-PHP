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
		->setPrefix('/v1/grouper')
		->setHandler('\PhalconRest\Controllers\GrouperController')
		->setLazy(true);

	// Issues Handlers
	$api->get('/issues', 'issues');
	$api->options('/issues', 'info');

	$api->get('/issues/principles', 'issuePrinciples');
	$api->options('/issues/principles', 'info');

	$api->get('/issues/stats', 'issueStats');
	$api->options('/issues/stats', 'info');


	$api->put('/principles/{id:[0-9]+}', 'updatePrinciple');
	$api->options('/principles/{id:[0-9]+}', 'info');
	
	$api->get('/reviews', 'reviews');
	$api->options('/reviews', 'info');
	
	$api->post('/reviews', 'newReview');
	$api->put('/reviews/{id:[0-9]+}', 'updateReview');

	return $api;
});