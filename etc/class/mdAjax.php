<?php
/**
 * Ajax return class for responding to ajax requests with json.
 * @author misterhaan
 */
class mdAjax {
	/**
	 * returned data object.  starts with ->fail set to false and should have other data added.
	 * @var object
	 */
	public $Data;

	/**
	 * Initializes return data object.
	 */
	public function mdAjax() {
		$this->Data = new stdClass();
		$this->Data->fail = false;
	}

	/**
	 * mark the request failed and add a reason.
	 * @param string $message failure reason
	 * @param object $dbObject database object that threw this error (optional)
	 */
	public function Fail(string $message, object $dbObject = null) {
		$this->Data->fail = true;
		if($dbObject)
			$message = rtrim($message, '.') . ':  ' . $dbObject->errno . ' ' . $dbObject->error;
		$this->Data->message = $message;
	}

	/**
	 * Send the ajax response.
	 */
	public function Send() {
		header('Content-Type: application/json');
		echo json_encode($this->Data);
	}
}
