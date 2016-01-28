<?php
/**
 * Created by PhpStorm.
 * User: Dapo
 * Date: 09-Dec-14
 * Time: 1:44 PM
 */

namespace PhalconRest\Controllers;


/**
 * Class AppController
 * @package PhalconRest\controllers
 */
class AppController extends RESTController {

	/**
	 * Get all Law Report types in the System
	 * @return array
	 */
	public function reports() {
		return Report::find()->toArray();
	}

	public function getLegalHeads() {

		$response = array();
		$tableName = "analysis";

		if (isset($_GET['court'])) {
			if ($_GET["court"]!='sc') $tableName .= "_" . $_GET["court"];
		}

//		$defaultLimit = isset($_GET["per_page"]) ? (int) $_GET["per_page"] : 2000;
//		$theOffset = ((isset($_GET["page"]) ? (int) $_GET["page"] : 2000) - 1) * $defaultLimit;

		if (isset($_GET['getStandard'])) {
			$response = $this->getDi()->getShared('db')->query("select `legalhead` from legal_heads order by
			`legalhead` ASC;")->fetchAll();
		} else if (isset($_GET['getHeads'])) {
			$response = $this->getDi()->getShared('db')->query("select `legal head` as legal_head, count(*) as places from $tableName where `legal head` is not null and `legal head` <>'' AND suitno NOT LIKE '%_deleted%' group by `legal head` order by `legal head` ASC;")->fetchAll();
		} else if (isset($_GET['doReplace'])) {
			$response = array($this->getDi()->getShared('db')->query("UPDATE IGNORE $tableName set `legal head` = :new, suitno41 = NULL, dt_modified=NOW() where `legal head` = :old", array("new"=>$_GET['new'], "old"=>$_GET['old']))->execute());
		} else if (isset($_GET['getSSubjectMatters'])) {
			$response = $this->getDi()->getShared('db')->query("select `subjectmatter` from subject_matters where legalhead = :lh order by `subjectmatter` ASC;", array("lh"=>$_GET['lh']))->fetchAll();
		} else if (isset($_GET['getSubjectMatters'])) {
			$response = $this->getDi()->getShared('db')->query("select subjectmatter as subject_matter, count(*) as places, (CASE WHEN subjectmatter IN (SELECT subjectmatter from subject_matters WHERE legalhead=:lh) THEN 1 ELSE 0 END ) as standard from $tableName where `legal head` = :lh AND suitno NOT LIKE '%_deleted%' group by `subjectmatter` order by `subjectmatter` ASC;", array("lh"=>$_GET['legal_head']))->fetchAll();
		} else if (isset($_GET['getSIssues'])) {
			$response = $this->getDi()->getShared('db')->query("select `issue` from standard_issues where legalhead = :lh and subjectmatter = :sm order by `subjectmatter` ASC;", array("lh"=>$_GET['lh'], "sm"=>$_GET['sm']))->fetchAll();
		} else if (isset($_GET['getIssues'])) {
			$response = $this->getDi()->getShared('db')->query("select issues1 as issue, count(*) as places, ( CASE WHEN issues1 IN (SELECT issue from standard_issues WHERE legalhead=:lh AND subjectmatter=:sm ) THEN 1 ELSE 0 END ) as standard from $tableName where `legal head` = :lh and subjectmatter = :sm AND suitno NOT LIKE '%_deleted%' group by `issues1` order by `issues1` ASC;", array("lh"=>$_GET['legal_head'], "sm"=>$_GET['subject']))->fetchAll();
		} else if (isset($_GET['getPrinciples'])) {
			$response = $this->getDi()->getShared('db')->query("select * from $tableName where `legal head` = :lh and subjectmatter = :sm and issues1 = :iss AND suitno NOT LIKE '%_deleted%' order by `date` ASC;", array("lh"=>$_GET['legal_head'], "sm"=>$_GET['subject'], "iss"=>$_GET['issue']))->fetchAll();
		}

		return $response;
	}


	/**
	 * @return array
	 */
	public function setAsStandard() {
		$post = $this->requestBody;
		$response = array();

		if (isset($post->subject_matter)) {
			$response = $this->getDi()->getShared('db')->query("insert ignore into subject_matters (legalhead, subjectmatter) values (:lh , :sm)", array("lh"=>$post->lh, "sm"=>$post->sm))->execute();
		} else if (isset($post->issue)) {
			$response = $this->getDi()->getShared('db')->query("insert ignore into standard_issues (legalhead, subjectmatter, issue) values (:lh, :sm, :iss)", array("lh"=>$post->lh, "sm"=>$post->sm, "iss"=>$post->iss))->execute();
		}

		return array($response);
	}

	/**
	 * Rename Subject Matter
	 * @return array
	 */
	public function updateSubjectMatter() {
		$post = $this->requestBody;
		$tableName = "analysis";

		if (isset($post->court)) { if ($post->court!='sc') $tableName .= "_" . $post->court; }

		if (strlen($post->old) > 0 && strlen($post->new) > 0) {
			$r1 = $this->getDi()->getShared('db')->query("UPDATE IGNORE $tableName set `subjectmatter` = :new, suitno41 = NULL,
			dt_modified=NOW() where `subjectmatter` = :old AND `legal head` = :lh;", array("old"=>$post->old,
				"new"=>$post->new, "lh"=>$post->lh))->execute();
			$r2 = $this->getDi()->getShared('db')->query("UPDATE IGNORE subject_matters set
			subjectmatter = :new where subjectmatter = :old and legalhead= :lh;", array("old"=>$post->old,
				"new"=>$post->new, "lh"=>$post->lh))->execute();
			$r3 = $this->getDi()->getShared('db')->query("UPDATE IGNORE standard_issues SET
			subjectmatter = :new WHERE legalhead = :lh AND subjectmatter = :old", array("old"=>$post->old,
				"new"=>$post->new, "lh"=>$post->lh))->execute();

			return array($r1, $r2, $r3);
		} else {
			return array();
		}

	}

	/**
	 * Rename Issue
	 * @return array
	 */
	public function updateIssue() {
		$post = $this->requestBody;
		$tableName = "analysis";

		if (isset($post->court)) { if ($post->court!='sc') $tableName .= "_" . $post->court; }

		if (strlen($post->old) > 0 && strlen($post->new) > 0) {
			$r1 = $this->getDi()->getShared('db')->query("UPDATE IGNORE $tableName set `issues1` = :new, suitno41 = NULL,
			dt_modified=NOW() where `issues1` = :old AND `legal head` = :lh AND subjectmatter = :sm;", array("old"=>$post->old,
				"new"=>$post->new, "lh"=>$post->lh, "sm"=>$post->sm))->execute();

			$r2 = $this->getDi()->getShared('db')->query("UPDATE IGNORE standard_issues SET
			issue = :new WHERE legalhead = :lh AND issue = :old AND subjectmatter = :sm", array("old"=>$post->old,
				"new"=>$post->new, "lh"=>$post->lh, "sm"=>$post->sm))->execute();

			return array($r1, $r2);
		} else {
			return array();
		}

	}

	/**
	 * Change Leagel Head for Subject Matter
	 * @return array
	 */
	public function changeLegalHead() {
		$post = $this->requestBody;
		$tableName = "analysis";

		if (isset($post->court)) { if ($post->court!='sc') $tableName .= "_" . $post->court; }

		if (strlen($post->old) > 0 && strlen($post->new) > 0) {
			$r1 = $this->getDi()->getShared('db')->query("UPDATE IGNORE $tableName set `legal head` = :new, suitno41 = NULL, dt_modified=NOW() where `legal head` = :old  AND subjectmatter = :sm;", array("old"=>$post->old, "new"=>$post->new,
				"sm"=>$post->sm))->execute();

			$r2 = $this->getDi()->getShared('db')->query("UPDATE IGNORE subject_matters set
			legalhead = :new where subjectmatter = :sm and legalhead= :old;", array("old"=>$post->old,
				"new"=>$post->new, "sm"=>$post->sm))->execute();
			$r3 = $this->getDi()->getShared('db')->query("UPDATE IGNORE standard_issues SET
			legalhead = :new WHERE legalhead = :old AND subjectmatter = :sm", array("old"=>$post->old,
				"new"=>$post->new, "sm"=>$post->sm))->execute();

			return array($r1, $r2, $r3);
		} else {
			return array();
		}

	}

	/**
	 * Change Subject Matter of an Issue
	 * @return array
	 */
	public function changeSubjectMatter() {
		$post = $this->requestBody;
		$tableName = "analysis";

		if (isset($post->court)) { if ($post->court!='sc') $tableName .= "_" . $post->court; }

		if (strlen($post->old) > 0 && strlen($post->new) > 0) {
			$r1 = $this->getDi()->getShared('db')->query("UPDATE IGNORE $tableName set `subjectmatter` = :new, suitno41 = NULL, dt_modified=NOW() where `subjectmatter` = :old AND issues1 = :iss AND `legal head` = :lh ", array("old"=>$post->old, "new"=>$post->new,
				"iss"=>$post->issue, "lh"=>$post->lh))->execute();

			$r2 = $this->getDi()->getShared('db')->query("UPDATE IGNORE standard_issues SET
			subjectmatter = :new WHERE issue = :iss AND subjectmatter = :old AND legalhead = :lh", array("old"=>$post->old,
				"new"=>$post->new, "iss"=>$post->issue, "lh"=>$post->lh))->execute();

			return array($r1, $r2);
		} else {
			return array();
		}

	}

	/**
	 * Merge Subject Matters
	 * @return array
	 */
	public function mergeSubjectMatters() {
		$post = $this->requestBody;
		$tableName = "analysis";
		$set = implode(",", $post->mergeSet);

		if (isset($post->court)) { if ($post->court!='sc') $tableName .= "_" . $post->court; }

		$r1 = $this->getDi()->getShared('db')->query("UPDATE IGNORE $tableName set `subjectmatter` = :new, suitno41 = NULL, dt_modified=NOW() where `legal head` = :lh AND subjectmatter IN ('$set');", array("new"=>$post->parent, "lh"=>$post->lh))->execute();

		$r2 = $this->getDi()->getShared('db')->query("UPDATE IGNORE subject_matters set subjectmatter = :new where legalhead = :lh AND subjectmatter IN ('$set');", array("new"=>$post->parent, "lh"=>$post->lh))->execute();

		$r3 = $this->getDi()->getShared('db')->query("UPDATE IGNORE standard_issues set subjectmatter = :new where legalhead = :lh AND subjectmatter IN ('$set');", array("new"=>$post->parent, "lh"=>$post->lh))->execute();

		return array($r1, $r2, $r3);
	}

	/**
	 * Merge Issues
	 * @return array
	 */
	public function mergeIssues() {
		$post = $this->requestBody;
		$tableName = "analysis";
		$set = implode(",", $post->mergeSet);

		if (isset($post->court)) { if ($post->court!='sc') $tableName .= "_" . $post->court; }

		$r1 = $this->getDi()->getShared('db')->query("UPDATE IGNORE $tableName set `issues1` = :new, suitno41 = NULL, dt_modified=NOW() where `legal head` = :lh AND subjectmatter= :sm AND issues1 IN ('$set');", array("new"=>$post->parent,
			"lh"=>$post->lh, "sm"=>$post->sm))->execute();

		$r2 = $this->getDi()->getShared('db')->query("UPDATE IGNORE standard_issues set issue = :new where subjectmatter = :sm AND legalhead = :lh AND issue IN ('$set');", array("new"=>$post->parent, "lh"=>$post->lh, "sm"=>$post->sm))->execute();

		return array($r1, $r2);
	}

	/**
	 * Detach Ratio
	 * @return array
	 */
	public function detachRatio() {
		$post = $this->requestBody;
		$tableName = "analysis";

		if (isset($post->court)) { if ($post->court!='sc') $tableName .= "_" . $post->court; }

		$r1 = $this->getDi()->getShared('db')->query("UPDATE IGNORE $tableName SET `issues1` = :iss, `legal head` = :lh, subjectmatter = :sm, suitno41 = NULL, dt_modified=NOW() WHERE pk = :pk", array("iss"=>$post->newIssue,
			"lh"=>$post->newLegalHead, "sm"=>$post->newSubjectMatter, "pk"=>$post->pk))->execute();

		return array($r1);
	}
} 