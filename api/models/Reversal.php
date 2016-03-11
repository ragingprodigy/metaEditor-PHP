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
use Phalcon\Mvc\Model\Validator\Uniqueness;

class Reversal extends CompleteRatio {

	protected $reversed_by;

	public function validation()
	{
		// Prevent Users from Marking data as complete twice
		$this->validate(
			new Uniqueness(
				array(
					"field"   => "ratio_id, user_id",
					"message" => "Ratio already exists"
				)
			)
		);

		return $this->validationHasFailed() != true;
	}

	/**
	 * Set Reversal Table
	 *
	 * @return string
	 */
	public function getSource()
	{
		return "reversals";
	}

	/**
	 * @param mixed $reversed_by
	 * @return Reversal
	 */
	public function setReversedBy($reversed_by)
	{
		$this->reversed_by = $reversed_by;
		return $this;
	}
} 