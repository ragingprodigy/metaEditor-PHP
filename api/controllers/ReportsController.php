<?php
namespace PhalconRest\Controllers;
use PhalconRest\Models\User;

/**
 * Class UserController
 * @package PhalconRest\Controllers
 */
class ReportsController extends RESTController {

	private $format = "'%Y-%m-%d'";

	public function summary() {

		$request = $this->di->get('request');

		$period = $request->get("period", null, null);
		$from = $request->get("from", null, null);
		$to = $request->get("to", null, null);

		$who = $request->get("staff", null, null);

		$condition = "";

		switch($period) {
			case "day":
				$condition = "created LIKE CONCAT(CURRENT_DATE,'%')";
			break;
			case "week":
				$condition = "YEARWEEK(created, 1) = YEARWEEK(CURRENT_DATE, 1)";
			break;
			case "month":
				$condition = "MONTH(created) = MONTH(CURRENT_DATE)";
			break;
			case "span":
				$condition = "date_format(created, $this->format) BETWEEN date_format('$from', $this->format) AND date_format ('$to', $this->format)";
		}

		if ($who != "-1") {
			$condition .= " AND user_id = $who";
		}

		$query = "SELECT `action`, COUNT(*) as ct FROM action_logs WHERE $condition GROUP by `action`";

		$summary = $this->getDi()->getShared('db')->fetchAll($query);
		$completed = $this->getDi()->getShared('db')->fetchAll("SELECT COUNT(ratio_id) as ct FROM complete_ratio WHERE $condition");
		$reversals = $this->getDi()->getShared('db')->fetchAll("SELECT COUNT(ratio_id) as ct FROM reversals WHERE $condition");

		return array("summary"=>$summary, "completed"=>$completed, "reversals"=>$reversals);
	}

	public function details() {

		$request = $this->di->get('request');

		$period = $request->get("period", null, null);
		$from = $request->get("from", null, null);
		$to = $request->get("to", null, null);

		$who = $request->get("staff", null, null);
		$action = $request->get("action", null, null);

		$condition = "action = '$action' AND ";

		switch($period) {
			case "day":
				$condition .= "created LIKE CONCAT(CURRENT_DATE,'%')";
			break;
			case "week":
				$condition .= "YEARWEEK(created, 1) = YEARWEEK(CURRENT_DATE, 1)";
			break;
			case "month":
				$condition .= "MONTH(created) = MONTH(CURRENT_DATE)";
			break;
			case "span":
				$condition .= "date_format(created, $this->format) BETWEEN date_format('$from', $this->format) AND date_format ('$to', $this->format)";
		}

		if ($who != "-1") {
			$condition .= " AND user_id = $who";
		}

		$query = "SELECT * FROM action_logs WHERE $condition order by created ASC";

		$summary = $this->getDi()->getShared('db')->fetchAll($query);

		return $summary;
	}

	/**
	 * @return array() Users List
	 */
	public function staff() {
		$users = $this->getDi()->getShared('db')->fetchAll("SELECT id, username, name FROM users WHERE private_key is not null AND id<>2");

		return $users;
	}

}