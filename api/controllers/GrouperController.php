<?php
namespace PhalconRest\Controllers;
use Namshi\JOSE\SimpleJWS;

/**
 * Class UserController
 * @package PhalconRest\Controllers
 */
class GrouperController extends RESTController {

	private $format = "'%Y-%m-%d'";

	/**
	 * Get a list of all Issues (outstanding or not)
	 * @return mixed
	 */
	public function issues() {

		$request = $this->di->get('request');

		$defaultLimit = (int) $request->get("per_page", null, 25);
		$page = (int) $request->get("page", null, 1);
		$offset = (int) ($page - 1) * $defaultLimit;

		$outstanding = (boolean) $request->get("outStanding", null, false);
		
		if ($outstanding) {
			$query = "SELECT `legal head` as legalHead, subjectmatter as subjectMatter, issues1 as issue, count(issues1) as pCount FROM analysis WHERE suitno41 IS NULL or suitno41 = '' GROUP BY issues1, `legal head`, subjectmatter HAVING pCount > 1 ORDER BY `legal head` ASC LIMIT $offset, $defaultLimit";
			$aQuery = "SELECT  count(pk) as dataCount FROM analysis WHERE suitno41 IS NULL or suitno41 = '' GROUP BY issues1, `legal head`, subjectmatter HAVING dataCount > 1";
		} else {
			$query = "SELECT `legal head` as legalHead, subjectmatter as subjectMatter, issues1 as issue, count(issues1) as pCount FROM analysis WHERE suitno41 IS NOT NULL AND suitno41 <> '' GROUP BY issues1, `legal head`, subjectmatter HAVING pCount > 1 ORDER BY `legal head` ASC LIMIT $offset, $defaultLimit";
			$aQuery = "SELECT  count(pk) as dataCount FROM analysis WHERE suitno41 IS NOT NULL AND suitno41 <> '' GROUP BY issues1, `legal head`, subjectmatter HAVING dataCount > 1";
		}

		$data = $this->getDi()->getShared('db')->query($query)->fetchAll();
		$meta = $this->getDi()->getShared('db')->query($aQuery)->fetchAll();

		$response = $this->di->get('response');
		$response->setHeader('Data-Count', count($meta));
		$response->setHeader('Data-Page', $page);

		return $data;
	}

	/**
	 * Generic Handler for OPTIONS requests
	 * @return array
	 */
	public function info() {
		return array();
	}

	/**
	 * Get Statistics for all Issues
	 * @return array()
	 */
	public function issueStats() {
		$values = array("grouped"=>0, "unGrouped"=>0, "all"=>0, "singlePrinciple"=>0);
		$q = $this->getDi()->getShared('db');

		$values["grouped"] = count($q->query("SELECT pk FROM analysis WHERE suitno41 IS NOT NULL AND suitno41 <> ''  GROUP BY issues1, `legal head`, subjectmatter")->fetchAll());

		$values["unGrouped"] = count($q->query("SELECT count(pk) as cc FROM analysis WHERE suitno41 IS NULL or suitno41 = '' GROUP BY issues1, `legal head`, subjectmatter having cc > 1"));

		$values["singlePrinciple"] = count($q->query("SELECT count(pk) as cc FROM analysis GROUP BY issues1, `legal head`, subjectmatter having cc = 1")->fetchAll());

		$values["all"] = count($q->query("SELECT pk FROM analysis GROUP BY issues1, `legal head`, subjectmatter"));

		return $values;
	}

	/**
	 * Retrieve the Principles for an Issue
	 * 
	 * @return mixed
	 */
	public function issuePrinciples() {
		$request = $this->di->get('request');
		$issue = $request->get("issue");
		$subjectMatter = $request->get("subjectMatter");
		$legalHead = $request->get("legalHead");

		$data = $this->getDi()->getShared('db')->query("SELECT * FROM analysis WHERE issues1=:issue AND `legal head`=:legalHead AND subjectmatter = :subjectMatter", array("issue"=>$issue, "legalHead"=>$legalHead,
				"subjectMatter"=>$subjectMatter))->fetchAll();

		return $data;
	}

	/**
	 * Categorize selected Principle
	 * 
	 * @param $id
	 * @return array
	 */
	public function updatePrinciple($id) {
		$post = $this->requestBody;
		$category = $post->suitno41;
		
		return array($this->getDi()->getShared('db')->query("UPDATE analysis SET suitno41 = :category, dt_modified = NOW() WHERE pk= :pk", array("category"=>$category, "pk"=>$id))->execute());
	}


	/**
	 * Retrieve the Appellate Reviews for an Issue
	 *
	 * @return mixed
	 */
	public function reviews() {
		$request = $this->di->get('request');
		$issue = $request->get("issue");
		$subjectMatter = $request->get("subjectMatter");
		$legalHead = $request->get("legalHead");

		$data = $this->getDi()->getShared('db')->query("SELECT appellate_reviews.id, legalhead, subjectmatter, issue, title, content, name FROM appellate_reviews join users on users.id = user_id WHERE issue=:issue AND `legalhead`=:legalHead AND subjectmatter = :subjectMatter", array("issue"=>$issue, "legalHead"=>$legalHead,
			"subjectMatter"=>$subjectMatter))->fetchAll();

		return $data;
	}

	/**
	 * Create Appellate Reviews for an Issue
	 *
	 * @return mixed
	 */
	public function newReview() {
		$body = $this->requestBody;
		$headers = apache_request_headers();
		$value = explode(" ", $headers['Authorization'])[1];
		$jws = SimpleJWS::load($value, true);
		$user = $jws->getPayLoad()['uid'];

		error_log("Creating Review for user with iD: " . $user);

		$shared = $this->getDi()->getShared('db');
		$con = $shared->query("INSERT INTO appellate_reviews(legalhead, subjectmatter, issue, title, content, user_id) VALUES (?,?,?,?,?,?)", array($body->legalHead, $body->subjectMatter, $body->issue, $body->title,
			$body->content, $user));
		$con->execute();
		
		$id = $shared->lastInsertId();
		
		$data = $shared->query("SELECT appellate_reviews.id, legalhead, subjectmatter, issue, title, content, name FROM appellate_reviews join users on users.id = user_id WHERE appellate_reviews.id = $id")->fetch();

		return $data;
	}
}