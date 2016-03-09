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

class CompleteRatio extends Model {

	protected $id;
	protected $user_id;
	protected $ratio_id;

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set ActionLog Table
	 *
	 * @return string
	 */
	public function getSource()
	{
		return "complete_ratio";
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
	 * @param mixed $user_id
	 * @return ActionLog
	 */
	public function setUserId($user_id)
	{
		$this->user_id = $user_id;
		return $this;
	}

	/**
	 * @param mixed $ratio_id
	 * @return CompleteRatio
	 */
	public function setRatioId($ratio_id)
	{
		$this->ratio_id = $ratio_id;
		return $this;
	}
} 