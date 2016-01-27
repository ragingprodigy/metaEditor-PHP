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
	$api->post('/setStandard', 'setAsStandard');
	$api->post('/updateSubjectMatter', 'updateSubjectMatter');
	$api->post('/changeLegalHead', 'changeLegalHead');

	$api->post('/updateIssue', 'updateIssue');

	$api->post('/mergeSubjectMatters', 'mergeSubjectMatters');

	$api->get('/reports', 'reports');
	$api->post('/parts', 'parts');
	$api->post('/newBookCase/{id:[0-9]+}', 'newBookCase');
	$api->get('/bookCases/{id:[0-9]+}', 'getCases');
	$api->get('/alternateCitations/{id:[0-9]+}', 'getCaseAlternateCitations');
	$api->get('/fullCases', 'fullCases');
	$api->get('/fullCases/{id:[0-9]+}/{term}', 'fullCases');
	$api->get('/fullCases/{id:[0-9]+}/{term}/{type}', 'fullCases');
	$api->get('/caseList/{term}', 'caseList');
	$api->delete('/deleteCase/{id:[0-9]+}', 'deleteCase');

	return $api;
});