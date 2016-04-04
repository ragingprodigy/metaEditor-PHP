<?php
namespace PhalconRest\Controllers;

use PhalconRest\Exceptions\HTTPException;
use PhalconRest\Models\User;
use Namshi\JOSE\SimpleJWS;

/**
 * Class UserController
 * @package PhalconRest\Controllers
 */
class UserController extends RESTController {

	/**
	 * Exchange Username and Password for Auth Token
	 *
	 * @throws HTTPException
	 * @return array
	 */
	public function login() {
		$username = $this->requestBody->username;
		$pwd = $this->requestBody->password;

		/** @var User $user */
		$user = User::findFirstByUsername($username);
		if ($user && ($user->getPassword()==md5($pwd))) {

			$user->setExpires(date("Y-m-d H:i:s", strtotime("+5 minutes")));
			$user->setPrivateKey(md5(time().$user->getName()."lp"));
			$user->save();

			$this->session->set("user", $user);
			return array(
				"private_key"=>$user->getPrivateKey(),
				"expires"=>$user->getExpires(),
				"user"=>$user->toArray()
			);
		}
		else
			throw new HTTPException("Invalid Username/Password", 401);
	}

	/**
	 * Exchange Username and Password for JWToken
	 *
	 * @throws HTTPException
	 * @return array
	 */
	public function login_jwt() {

		$username = $this->requestBody->username;
		$pwd = $this->requestBody->password;

		/** @var User $user */
		$user = User::findFirstByUsername($username);
		if ($user && ($user->getPassword()==md5($pwd))) {

			$user->setExpires(date("Y-m-d H:i:s", strtotime("+5 minutes")));
			$user->setPrivateKey(md5(time().$user->getName()."lp"));
			$user->save();

			// TODO: Generate JWT Here
			$jws  = new SimpleJWS(array(
				'alg' => 'RS256'
			));
			$jws->setPayload(array(
				'uid' => $user->getId(),
				"name" => $user->getName()
			));

			return array(
				"token"=>$jws->getTokenString(),
				"expires"=>$user->getExpires()
			);
		}
		else
			throw new HTTPException("Invalid Username/Password", 401);
	}

	public function info() {
		return array();
	}

	/**
	 * @return array
	 */
	public function index() {
		return array("WWW");
	}

	/**
	 * Register New User
	 *
	 * @return array
	 * @throws HTTPException
	 */
	public function register() {
		$pwd = $this->request->getPost("password");
		$username = $this->request->getPost("username");
		$name = $this->request->getPost("name");

		if (!empty($pwd) && !empty($username) && !empty($name)) {
			$user = new User();
			$user->setName($name);
			$user->setUsername($username);
			$user->setPassword(md5($pwd));

			$user->create();

			if ($user->getMessages())
				return $this->modelError($user);

			return $user->toArray();
		} else
			throw new HTTPException("Invalid Request",400);
	}

	/**
	 * Authorize a Request Using the API Key
	 * Extend access expiry by 5 minutes thereafter
	 *
	 * @param $privateKey
	 *
	 * @throws HTTPException
	 * @return bool
	 */
	public function loginWithPrivateKey($privateKey) {
		/** @var User $user */
		$user = User::findFirstByPrivateKey($privateKey);

		if ($user) {
			if (strtotime($user->getExpires()) > strtotime("now")) {
				$user->setExpires(date("Y-m-d H:i:s", strtotime("+20 minutes")));
				$user->save();
				$this->session->set("user", $user);
				return true;
			} else
				throw new \PhalconRest\Exceptions\HTTPException("Application Access has expired. Please login again", 409);
		}
		return false;
	}

}