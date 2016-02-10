<?php
/**
 * Created by PhpStorm.
 * User: Dapo
 * Date: 08-Dec-14
 * Time: 9:48 AM
 */

return call_user_func(function(){

	$userCollection = new \Phalcon\Mvc\Micro\Collection();

	$userCollection
		->setPrefix('/v1/users')
		->setHandler('\PhalconRest\Controllers\UserController')
		->setLazy(true);

	$userCollection->get('/', 'index');
	$userCollection->post('/login', 'login');
	$userCollection->post('/register', 'register');

	return $userCollection;
});