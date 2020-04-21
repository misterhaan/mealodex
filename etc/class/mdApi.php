<?php
/**
 * Base class for API controllers.  Requests are formed as
 * [controller]/[endpoint] with optional parameters separated by / after the
 * endpoind, and served by a function named [method]_[endpoint] in the mdApi
 * class in [controller].php.
 * @author misterhaan
 */
abstract class mdApi {
	/**
	 * Respond to an API request or show API documentation.
	 */
	public static function Respond() {
		if(isset($_SERVER['PATH_INFO']) && substr($_SERVER['PATH_INFO'], 0, 1) == '/') {
			$method = $_SERVER['REQUEST_METHOD'];
			if(in_array($method, ['GET', 'POST', 'PUT', 'PATCH'])) {
				$params = explode('/', substr($_SERVER['PATH_INFO'], 1));
				$method .= '_' . array_shift($params);  // turn the HTTP method and the endpoint into a php method name
				if(method_exists(static::class, $method))
					static::$method($params);
				else
					self::NotFound('Requested endpoint does not exist on this controller or requires a different request method.');
			} else
				self::NotFound("Method $method is not supported.");
		} else
			self::NeedMoreInfo('API request must include an endpoint.');
	}

	/**
	 * Gets the database connection object.  Redirects to setup if unable to
	 * connect for any reason.  APIs other than setup should use
	 * RequireLatestDatabase instead.
	 * @return mysqli Database connection object.
	 */
	protected static function RequireDatabase() {
		if(@include_once dirname(DOCROOT) . '/.mdKeys.php') {
			$db = @new mysqli(mdKeysDB::HOST, mdKeysDB::USER, mdKeysDB::PASS, mdKeysDB::NAME);
			if(!$db->connect_errno) {
				// it's probably okay to keep going if we can't set the character set
				$db->real_query('set names \'utf8mb4\'');
				$db->set_charset('utf8mb4');
				return $db;
			} else {
				$db = false;
				self::NeedSetup('Error connecting to database.', $db);
			}
		} else
			self::NeedSetup('Database connection details not specified.');
	}

	/**
	 * Gets the database connection object along with the configuration record.
	 * Redirects to setup if anything is missing.  APIs other than setup should
	 * use RequireLatestDatabase instead.
	 * @return mysqli Database connection object.
	 */
	protected static function RequireDatabaseWithConfig() {
		$db = self::RequireDatabase();
		if($config = $db->query('select * from config limit 1'))
			if($config = $config->fetch_object()) {
				$db->config = $config;
				return $db;
			} else
				self::NeedSetup('Configuration not specified in database.');
		else
			self::NeedSetup('Error loading configuration from database.', $db);
	}

	/**
	 * Gets the database connection object, making sure it's on the latest
	 * version.  Redirects to setup if anything is missing.  If this function
	 * returns at all, it's safe to use the database connection object.
	 * @return mysqli Database connection object.
	 */
	protected static function RequireLatestDatabase() {
		$db = self::RequireDatabaseWithConfig();
		if($db->config->structureVersion >= mdVersion::Structure && $db->config->dataVersion >= mdVersion::Data)
			return $db;
		else
			self::NeedSetup('Database upgrade required.');
	}

	/**
	 * Send a successful response.
	 * @param mixed $data Response data (optional)
	 */
	protected static function Success($data = true) {
		header('Content-Type: application/json');
		die(json_encode($data));
	}

	/**
	 * Reject the request because it is missing required information.
	 * @param string $message short message describing what's missing and how to provide it.
	 */
	protected static function NeedMoreInfo(string $message) {
		http_response_code(422);
		header('Content-Type: text/plain');
		die($message);
	}

	/**
	 * Mark the request as encountering a database error.
	 * @param string $message failure reason
	 * @param mysqli|mysqli_result $dbObject database object that threw this error (optional)
	 */
	protected static function DatabaseError(string $message, object $dbObject = null) {
		http_response_code(500);
		header('Content-Type: text/plain');
		if($dbObject)
			$message .= ":  $dbObject->errno $dbObject->error";
		die($message);
	}

	/**
	 * Mark the request as not found.  This probably only makes sense for get
	 * requests that look up an item by a key.
	 * @param string $message short message describing what was not found
	 */
	protected static function NotFound(string $message = '') {
		http_response_code(404);
		header('Content-Type: text/plain');
		die($message);
	}

	/**
	 * Return an error message and redirect to setup to perform any required
	 * updates.  Stops execution of the current script.
	 * @param string $message Error message to report.
	 * @param mysqli|mysqli_result $dbObject database object that threw this error (optional)
	 */
	private static function NeedSetup(string $message, object $dbObject = null) {
		$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
		header("$protocol 503 Setup Needed");
		header('Content-Type: text/plain');
		if($dbObject)
			$message .= ":  $dbObject->errno $dbObject->error";
		die($message);
	}
}
