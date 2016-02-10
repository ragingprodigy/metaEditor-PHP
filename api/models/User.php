<?php
/**
 * Created by PhpStorm.
 * User: Dapo
 * Date: 08-Dec-14
 * Time: 9:39 AM
 *
 * Class User
 * @package PhalconRest\Models
 */

namespace PhalconRest\Models;
use Phalcon\Mvc\Model;
use PhalconRest\Exceptions\HTTPException;

class User extends Model {

	/**
	 * @var
	 */
	protected $id;

	/**
	 * @var
	 */
	protected $name;

	/**
	 * @var
	 */
	protected $username;

	/**
	 * @var
	 */
	protected $password;

	protected $private_key;
	protected $expires;
	protected $can_login;
	protected $role;

	public function getPrivateKey() {
		return $this->private_key;
	}

	public function setPrivateKey($pKey) {
		if (empty($pKey)) throw new HTTPException("Invalid Key");

		$this->private_key = $pKey;
	}

	public function getExpires() {
		return $this->expires;
	}

	public function setExpires($date) {
		$this->expires = $date;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param $name
	 *
	 * @throws HTTPException
	 */
	public function setName($name) {
		//The name is too short?
		if (strlen($name) < 5) {
			throw new HTTPException('The name is too short');
		}

		$this->name = $name;
	}

	/**
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param $username
	 *
	 * @throws HTTPException
	 */
	public function setUsername($username) {
		if (strlen($username) < 4)
			throw new HTTPException('Username is too short');
		if (strlen($username) > 25)
			throw new HTTPException('Username is too long');

		$this->username = $username;
	}

	/**
	 * @param $password
	 */
	public function setPassword($password) {
		$this->password = $password;
	}

	public function getPassword() {
		return $this->password;
	}

	/**
	 * Set Users Table
	 *
	 * @return string
	 */
	public function getSource()
	{
		return "users";
	}

	public function initialize() {
		$this->addBehavior(new \Phalcon\Mvc\Model\Behavior\Timestampable(array(
			'beforeValidationOnCreate' => array(
				'field' => 'created',
				'format' => 'Y-m-d H:i:s'
			),
			'beforeValidationOnUpdate' => array(
				'field' => 'modified',
				'format' => 'Y-m-d H:i:s'
			),
		)));
	}

	/**
	 * @return mixed
	 */
	public function getCanLogin()
	{
		return $this->can_login;
	}

	/**
	 * @param mixed $can_login
	 */
	public function setCanLogin($can_login)
	{
		$this->can_login = $can_login;
	}

	/**
	 * @param mixed $role
	 * @return User
	 */
	public function setRole($role)
	{
		$this->role = $role;
		return $this;
	}
} 