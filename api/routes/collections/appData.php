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
		->setPrefix('/v1')
		->setHandler('\PhalconRest\Controllers\AppController')
		->setLazy(true);
	
	$api->get('/legalHeads', 'getLegalHeads');
	$api->get('/setStandard', 'setAsStandard');

	$api->get('/reports', 'reports');
	$api->post('/parts', 'parts');
	$api->post('/newPart', 'createPart');
	$api->get('/allCases', 'allCases');
	$api->post('/newCase', 'newCase');
	$api->post('/notReported', 'notReported');
	$api->post('/inconsistent', 'inconsistent');
	$api->post('/nowReported', 'nowReported');
	$api->post('/consistent', 'consistent');
	$api->post('/allocate', 'allocate');
	$api->post('/newBookCase/{id:[0-9]+}', 'newBookCase');
	$api->get('/bookCases/{id:[0-9]+}', 'getCases');
	$api->get('/alternateCitations/{id:[0-9]+}', 'getCaseAlternateCitations');
	$api->get('/fullCases', 'fullCases');
	$api->get('/fullCases/{id:[0-9]+}/{term}', 'fullCases');
	$api->get('/fullCases/{id:[0-9]+}/{term}/{type}', 'fullCases');
	$api->get('/caseList/{term}', 'caseList');

	$api->get('/dashboard', 'dashboard');

	$api->delete('/deleteCase/{id:[0-9]+}', 'deleteCase');
	$api->delete('/deleteProfile/{id:[0-9]+}', 'deleteProfile');

	$api->delete('/removeBCBinding/{id:[0-9]+}', 'removeBCBinding');

	$api->post('/updateBC', 'updateBC');
	$api->post('/bulkUpdateBC', 'bulkUpdateBC');
	$api->post('/updateCASE', 'updateCASE');

	return $api;
});