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

	public function validation()
	{
		// Prevent Users from Marking data as complete twice
		$this->validate(
			new Uniqueness(
				array(
					"field"   => array("ratio_id", "user_id"),
					"message" => "Ratio already exists"
				)
			)
		);

		return $this->validationHasFailed() != true;
	}

	/**
	 * Set CompleteRatio Table
	 *
	 * @return string
	 */
	public function getSource()
	{
		return "reversals";
	}
} 