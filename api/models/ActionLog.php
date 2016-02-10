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

class ActionLog extends Model {

	protected $id;
	protected $user_id;
	protected $action;
	protected $meta;
	protected $items;

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
		return "action_logs";
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
	 * @param mixed $action
	 * @return ActionLog
	 */
	public function setAction($action)
	{
		$this->action = $action;
		return $this;
	}

	/**
	 * @param mixed $meta
	 * @return ActionLog
	 */
	public function setMeta($meta)
	{
		$this->meta = $meta;
		return $this;
	}

	/**
	 * @param int $items
	 * @return ActionLog
	 */
	public function setItems($items)
	{
		$this->items = $items;
		return $this;
	}
} 