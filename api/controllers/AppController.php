<?php
/**
 * Created by PhpStorm.
 * User: Dapo
 * Date: 09-Dec-14
 * Time: 1:44 PM
 */

namespace PhalconRest\Controllers;
use Phalcon\Mvc\ModelInterface;
use PhalconRest\Models\Audit;
use PhalconRest\Models\AuditDetail;
use PhalconRest\Models\Book;
use PhalconRest\Models\BookCase;
use PhalconRest\Models\Matter;
use PhalconRest\Models\Report;


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
			$response = array($this->getDi()->getShared('db')->query("update $tableName set `legal head` = :new, suitno41 = NULL, dt_modified=NOW() where `legal head` = :old", array("new"=>$_GET['new'], "old"=>$_GET['old']))->execute());
		} else if (isset($_GET['getSSubjectMatters'])) {
			$response = $this->getDi()->getShared('db')->query("select `subjectmatter` from subject_matters where legalhead = :lh order by `subjectmatter` ASC;", array("lh"=>$_GET['lh']))->fetchAll();
		} else if (isset($_GET['getSubjectMatters'])) {
			$response = $this->getDi()->getShared('db')->query("select subjectmatter as subject_matter, count(*) as places, (CASE WHEN subjectmatter IN (SELECT subjectmatter from subject_matters WHERE legalhead=:lh) THEN 1 ELSE 0 END ) as standard from $tableName where `legal head` = :lh AND suitno NOT LIKE '%_deleted%' group by `subjectmatter` order by `subjectmatter` ASC;", array("lh"=>$_GET['legal_head']))->fetchAll();
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
			$r1 = $this->getDi()->getShared('db')->query("update $tableName set `subjectmatter` = :new, suitno41 = NULL,
			dt_modified=NOW() where `subjectmatter` = :old AND `legal head` = :lh;", array("old"=>$post->old,
				"new"=>$post->new, "lh"=>$post->lh))->execute();
			$r2 = $this->getDi()->getShared('db')->query("update subject_matters set
			subjectmatter = :new where subjectmatter = :old and legalhead= :lh;", array("old"=>$post->old,
				"new"=>$post->new, "lh"=>$post->lh))->execute();
			$r3 = $this->getDi()->getShared('db')->query("UPDATE standard_issues SET
			subjectmatter = :new WHERE legalhead = :lh AND subjectmatter = :old", array("old"=>$post->old,
				"new"=>$post->new, "lh"=>$post->lh))->execute();

			return array($r1, $r2, $r3);
		} else {
			echo "No Data";
			return array();
		}

	}

	public function mergeSubjectMatters() {

	}

	/**
	 * Get all Parts in a book for a specified year
	 * @return array
	 */
	public function parts() {
		$year = $this->requestBody->year;
		$report_id = $this->requestBody->report;

		return Book::find(array(
			"year=$year AND report_id=$report_id",
			"order"=>"part ASC"
		))->toArray();
	}

	/**
	 * Create a new Book Part
	 * @return array
	 */
	public function createPart() {
		$year = $this->requestBody->year;
		$report_id = $this->requestBody->report;
		$part = $this->requestBody->newPart;

		$model = new Book;
		$model->create(array(
			"year"=>$year,
			"report_id"=>$report_id,
			"part"=>$part,
			"deleted"=>0
		));

		return $this->postSave($model);
	}

	/**
	 * Create a new Case
	 *
	 * @return array
	 */
	public function newCase() {
		$caseTitle = $this->requestBody->caseTitle;
		$suitno = $this->requestBody->suitno;

		$model = new Matter;
		$model->create(array(
			"case_title"=>$caseTitle,
			"suitno"=>$suitno,
			"inconsistent"=>0,
			"not_reported"=>0,
			"deleted"=>0
		));

		return $this->postSave($model);
	}

	/**
	 * Create new Book Case
	 *
	 * @param $book_id
	 *
	 * @return array
	 */
	public function newBookCase($book_id) {
		$model = new BookCase;
		$model->create(array(
			"case_id"=>$this->requestBody->case_id,
			"citation"=>$this->requestBody->citation,
			"page"=>$this->requestBody->page,
			"book_id"=>$book_id,
			"deleted"=>0
		));

		if ($model->getMessages())
			return $this->modelError($model);

		return array("bookCase"=>$model->toArray(), "case"=>$model->getMatter()->toArray());
	}

	/**
	 * @param $model ModelInterface
	 *
	 * @return array
	 */
	private function postSave($model) {
		if ($model->getMessages())
			return $this->modelError($model);

		return $model->toArray();
	}

	/**
	 * Get the Cases for the Selected Book
	 *
	 * @param $id
	 *
	 * @return array
	 */
	public function getCases($id) {
		/** @var BookCase[] $bookCases */
		$bookCases = BookCase::findByBookId($id);//Book::findFirst($id)->getBookCase()->toArray();
		$return = array();
		if ($bookCases)
			foreach ($bookCases as $bc) {
				if (!$bc->deleted)
					$return[] = array("bookCase"=>$bc->toArray(), "case"=>$bc->getMatter()->toArray());
			}

		return $return;
	}

	/**
	 * Fetch All Cases
	 *
	 * @return array
	 */
	public function allCases($id=null) {
		if ($id==null)
			return Matter::find(array(
				"deleted=0", "order"=>"case_title ASC"
			))->toArray();
		else
			return array();
	}

	/**
	 * @param $term
	 *
	 * @return \PhalconRest\Models\Matter[]
	 */
	public function caseList($term) {
		$condition = " AND (case_title LIKE :term: OR suitno LIKE :term: OR lpelr_citation LIKE :term: OR lpelr_case_title LIKE :term:)";

		/** @var Matter[] $cases */
		$cases = Matter::find(array(
			"deleted=0".$condition,
			"order"=>"case_title ASC",
			"bind"=>array("term"=>"%$term%")
		));

		return $cases->toArray();
	}

	/**
	 * @param        $page
	 * @param string $term
	 * @param string $type
	 *
	 * @return array
	 */
	public function fullCases($page, $term="", $type="") {
		$pageSize = 10;
		$condition = empty($term)||$term==""||$term=="undefined"?"":" AND (case_title LIKE :term: OR suitno LIKE :term: OR lpelr_citation LIKE :term: OR lpelr_case_title LIKE :term:)";

		if (!empty($type)) {
			$condition .= $type=="unreported"?" AND not_reported=1":" AND inconsistent=1";
		}

		/** @var Matter[] $cases */
		$cases = Matter::find(array(
			"deleted=0".$condition,
			"order"=>"case_title ASC",
			"bind"=>array("term"=>"%$term%"),
			"limit"=>array(
				"number"=>$pageSize,
				"offset"=>($page-1)*$pageSize
			)
		));

		$total = Matter::count(array(
			"deleted=0".$condition,
			"bind"=>array("term"=>"%$term%"),
		));

		$return = array();

		if ($cases)
			foreach ($cases as $case) {
				$bc = $case->getBookCase("deleted=0");

				$return[] = array("case"=>$case->toArray(), "bookCases"=>$bc->toArray());
			}

		return array("data"=>$return, "count"=>(int) $total);
	}

	/**
	 * Mark a case as not reported
	 *
	 * @return array
	 */
	public function notReported() {
		$model = Matter::findFirst($this->requestBody->id);
		$model->not_reported = 1;

		$model->save();
		return $this->postSave($model);
	}

	/**
	 * Mark a case as now reported
	 *
	 * @return array
	 */
	public function nowReported() {
		$model = Matter::findFirst($this->requestBody->id);
		$model->not_reported = 0;

		$model->save();
		return $this->postSave($model);
	}

	/**
	 * Mark a case as inconsistent
	 *
	 * @return array
	 */
	public function inconsistent() {
		$model = Matter::findFirst($this->requestBody->id);
		$model->inconsistent = 1;

		$model->save();
		return $this->postSave($model);
	}

	/**
	 * Mark a case as consistent
	 *
	 * @return array
	 */
	public function consistent() {
		$model = Matter::findFirst($this->requestBody->id);
		$model->inconsistent = 0;

		$model->save();
		return $this->postSave($model);
	}

	/**
	 * Set a Matter's LPELR Citation
	 *
	 * @return array
	 */
	public function allocate() {
		$model = Matter::findFirst($this->requestBody->id);
		$model->lpelr_citation = $this->requestBody->citation;

		$model->save();
		return $this->postSave($model);
	}

	/**
	 * Update the details of a BookCase
	 *
	 * @return array
	 */
	public function updateBC() {
		$model = BookCase::findFirst($this->requestBody->id);
		$model->citation = $this->requestBody->citation;
		$model->page = $this->requestBody->page;

		$model->save();
		return $this->postSave($model);
	}

	/**
	 * Update the citation of a particular book
	 *
	 * @return array
	 */
	public function bulkUpdateBC() {
		$bcs = BookCase::find(array(
			"deleted=0 AND report_id=:report:",
			"bind"=>array("report"=>"%".$this->requestBody->reportId."%")
		));

		foreach ($bcs as $bc) {
			$bc->citation = $this->requestBody->citation;
			$bc->update();
		}

		return array("OK");
	}

	/**
	 * Update the details of a CASE
	 *
	 * @return array
	 */
	public function updateCASE() {
		$model = Matter::findFirst($this->requestBody->id);
		$model->case_title = $this->requestBody->caseTitle;
		$model->suitno = $this->requestBody->suitno;

		$model->save();
		return $this->postSave($model);
	}

	/**
	 * Get a case's alternate citations and associated
	 *
	 * @param $id
	 *
	 * @return array
	 */
	public function getCaseAlternateCitations($id) {
		$return = array();

		$result=$this->getDi()->getShared('db')->query("SELECT bc.id as bc_id, name, acronym, year, part, citation, page FROM tbl_book_cases bc JOIN tbl_books b ON book_id = b.id JOIN tbl_reports r ON r.id=report_id WHERE bc.case_id =:id AND bc.deleted = :no ",
			array("id" => $id, "no"=> 0));
		$data = $result->fetchAll();

		if (count($data))
			$return = $data;

		return $return;
	}

	/**
	 * Delete a Case
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	public function deleteCase($id) {
		//return array($id);
		return array(Matter::findFirst($id)->delete());
	}

	/**
	 * Delete a BookCase Record
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	public function removeBCBinding($id) {
		return array(BookCase::findFirst($id)->delete());
	}

	/**
	 * Delete a Profile
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	public function deleteProfile($id) {
		//return array($id);
		return array(BookCase::findFirst($id)->delete());
	}

	/**
	 * Get reporting Data for the Dashboard
	 *
	 * @return array
	 */
	public function dashboard() {
		$return = array();

		$today = date('Y-m-d');
		$todayLastWeek = date('Y-m-d', strtotime("-1 week"));

		$weekStart = date('Y-m-d', strtotime(date('D')=="Mon"?"now":"last monday"));
		$weekEnd = date('Y-m-d', strtotime(date('D')=="Sun"?"now":"next sunday"));

		$lastWeekStart = date('Y-m-d', strtotime($weekStart." -7 days"));
		$lastWeekEnd = date('Y-m-d', strtotime($weekEnd." -7 days"));

		$profiledToday = Audit::count("DATE_FORMAT(created_at, '%Y-%m-%d')='$today' AND type='C' AND model_name LIKE '%BookCase'");
		$pDayLastWeek = Audit::count("DATE_FORMAT(created_at, '%Y-%m-%d')='$todayLastWeek' AND type='C' AND model_name LIKE '%BookCase'");

		$return['dayProfiled'] = array(
			'today'=>(int) $profiledToday,
			'diff'=>($profiledToday-$pDayLastWeek),
			'up'=>$profiledToday>=$pDayLastWeek,
			'margin'=>$pDayLastWeek>0?number_format(((abs($profiledToday-$pDayLastWeek)/$pDayLastWeek)*100),2):$profiledToday*100
		);

		$profiledWeek = Audit::count("DATE_FORMAT(created_at, '%Y-%m-%d') BETWEEN '$weekStart' AND '$weekEnd' AND type='C' AND model_name LIKE '%BookCase'");
		$pLastWeek = Audit::count("DATE_FORMAT(created_at, '%Y-%m-%d') BETWEEN '$lastWeekStart' AND '$lastWeekEnd' AND type='C' AND model_name LIKE '%BookCase'");

		$return['weekProfiled'] = array(
			'week'=>(int) $profiledWeek,
			'diff'=>($profiledWeek-$pLastWeek),
			'up'=>$profiledWeek>=$pLastWeek,
			'margin'=>$pLastWeek>0?number_format(((abs($profiledWeek-$pLastWeek)/$pLastWeek)*100),2):$profiledWeek*100
		);

		$auditDetails = AuditDetail::find(array("field_name='lpelr_citation'", "columns"=>"audit_id"));

		$audit_ids = array();
		foreach ($auditDetails as $ad)
			$audit_ids[] = $ad->audit_id;

		$fields = implode(",",$audit_ids);

		$allocatedToday = Audit::count("DATE_FORMAT(created_at, '%Y-%m-%d')='$today' AND type='U' AND model_name LIKE '%Matter' AND id IN ($fields)");
		$aDayLastWeek = Audit::count("DATE_FORMAT(created_at, '%Y-%m-%d')='$todayLastWeek' AND type='U' AND model_name LIKE '%Matter' AND id IN ($fields)");

		$return['dayAllocated'] = array(
			'today'=>(int) $allocatedToday,
			'diff'=>($allocatedToday-$aDayLastWeek),
			'up'=>$allocatedToday>=$aDayLastWeek,
			'margin'=>$aDayLastWeek>0?number_format(((abs($allocatedToday-$aDayLastWeek)/$aDayLastWeek)*100),2):$allocatedToday*100
		);

		$allocatedWeek = Audit::count("DATE_FORMAT(created_at, '%Y-%m-%d') BETWEEN '$weekStart' AND '$weekEnd' AND type='U' AND model_name LIKE '%Matter' AND id IN($fields)");
		$aLastWeek = Audit::count("DATE_FORMAT(created_at, '%Y-%m-%d') BETWEEN '$lastWeekStart' AND '$lastWeekEnd' AND type='U' AND model_name LIKE '%Matter' AND id IN ($fields)");

		$return['weekAllocated'] = array(
			'week'=>(int) $allocatedWeek,
			'diff'=>($allocatedWeek-$aLastWeek),
			'up'=>$allocatedWeek>=$aLastWeek,
			'margin'=>$aLastWeek>0?number_format(((abs($allocatedWeek-$aLastWeek)/$aLastWeek)*100),2):$allocatedWeek*100
		);

		$return['total'] = array(
			'allocated' => Matter::count("deleted=0 AND lpelr_citation <>'' AND lpelr_citation IS NOT NULL"),
			'scAllocated' => Matter::count("suitno LIKE '%SC%' AND deleted=0 AND lpelr_citation <>'' AND lpelr_citation IS NOT NULL"),
			'caAllocated' => Matter::count("suitno LIKE '%CA%' AND deleted=0 AND lpelr_citation <>'' AND lpelr_citation IS NOT NULL"),
			'inconsistent' => Matter::count("deleted=0 AND inconsistent=1"),
			'scInconsistent' => Matter::count("suitno LIKE '%SC%' AND deleted=0 AND inconsistent=1"),
			'caInconsistent' => Matter::count("suitno LIKE '%CA%' AND deleted=0 AND inconsistent=1"),
			'unreported' => Matter::count("deleted=0 AND not_reported=1"),
			'scUnreported' => Matter::count("suitno LIKE '%SC%' AND deleted=0 AND not_reported=1"),
			'caUnreported' => Matter::count("suitno LIKE '%CA%' AND deleted=0 AND not_reported=1")
		);

		$return['total']['sum'] = $return['total']['allocated'] + $return['total']['inconsistent'] + $return['total']['unreported'];

		$return['profiledChart'] = $this->getDI()->getShared("db")->query("select r.name, r.acronym, count(bc.id) as profiled from tbl_reports r left outer join tbl_books b on r.id=report_id JOIN tbl_book_cases bc ON bc.book_id = b.id WHERE bc.deleted=0")->fetchAll();

		$return['allocatedChart'] = $this->getDI()->getShared("db")->query("select r.name, r.acronym, count(c.id) as allocated from tbl_reports r JOIN tbl_books b on r.id=report_id JOIN tbl_book_cases bc ON bc.book_id = b.id join tbl_cases c  on (c.id = bc.case_id AND lpelr_citation <> '' AND lpelr_citation IS NOT NULL) group  by acronym")->fetchAll();

		return $return;
	}
} 