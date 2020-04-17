<?php
/**
 * Base class for API controllers.  Requests are formed as
 * [controller]/[endpoint] and served by a function named [method]_[endpoint]
 * in the mdApi class in [controller].php.
 * @author misterhaan
 */
abstract class mdApi {
	/**
	 * Respond to an API request or show API documentation.
	 */
	public static function Respond() {
		$ajax = new mdAjax();
		if(isset($_SERVER['PATH_INFO']) && substr($_SERVER['PATH_INFO'], 0, 1) == '/') {
			$method = $_SERVER['REQUEST_METHOD'];
			if(in_array($method, ['GET', 'POST'])) {
				$endpoint = substr($_SERVER['PATH_INFO'], 1);
				if(false === strpos($endpoint, '/')) {
					$method .= '_' . $endpoint;  // turn the HTTP method and the endpoint into a php method name
					if(method_exists(static::class, $method))
						static::$method($ajax);
					else
						$ajax->Fail("Requested endpoint does not exist on this controller or requires a different request method.");
				} else
					$ajax->Fail('Invalid request.');
			} else
				$ajax->Fail('Method ' . $method . ' is not supported.');
		} else
			$ajax->Fail('API request must include an endpoint.');
		$ajax->Send();
	}

	/**
	 * Gets the database connection object.  Redirects to setup if unable to
	 * connect for any reason.  APIs other than setup should use
	 * RequireLatestDatabase instead.
	 * @param mdAjax $ajax Ajax object for reporting an error.
	 * @return mysqli Database connection object.
	 */
	protected static function RequireDatabase(mdAjax $ajax) {
		if(@include_once dirname(DOCROOT) . '/.mdKeys.php') {
			$db = @new mysqli(mdKeysDB::HOST, mdKeysDB::USER, mdKeysDB::PASS, mdKeysDB::NAME);
			if(!$db->connect_errno) {
				// not checking for failure here because it's probably okay to keep going
				$db->real_query('set names \'utf8mb4\'');
				$db->set_charset('utf8mb4');
				return $db;
			} else
				self::FailToSetup($ajax, 'Error connecting to database.', $db);
		} else
			self::FailToSetup($ajax, 'Database connection details not specified.');
	}

	/**
	 * Gets the database connection object along with the configuration record.
	 * Redirects to setup if anything is missing.  APIs other than setup should
	 * use RequireLatestDatabase instead.
	 * @param mdAjax $ajax Ajax object for reporting an error.
	 * @return mysqli Database connection object.
	 */
	protected static function RequireDatabaseWithConfig(mdAjax $ajax) {
		$db = self::RequireDatabase($ajax);
		if($config = $db->query('select * from config limit 1'))
			if($config = $config->fetch_object()) {
				$db->config = $config;
				return $db;
			} else
				self::FailToSetup($ajax, 'Configuration not specified in database.');
		else
			self::FailToSetup($ajax, 'Error loading configuration from database.', $db);
	}

	/**
	 * Gets the database connection object, making sure it's on the latest
	 * version.  Redirects to setup if anything is missing.  If this function
	 * returns at all, it's safe to use the database connection object.
	 * @param mdAjax $ajax Ajax object for reporting an error.
	 * @return mysqli Database connection object.
	 */
	protected static function RequireLatestDatabase(mdAjax $ajax) {
		$db = self::RequireDatabaseWithConfig($ajax);
		if($db->config->structureVersion >= mdVersion::Structure && $db->config->dataVersion >= mdVersion::Data)
			return $db;
		else
			self::FailToSetup($ajax, 'Database upgrade required.');
	}

	/**
	 * Return an error message and redirect to setup to perform any required
	 * updates.  Stops execution of the current script.
	 * @param mdAjax $ajax Ajax object for reporting an error.
	 * @param string $message Error message to report.
	 * @param object $dbObject database object that threw this error (optional)
	 */
	private static function FailToSetup(mdAjax $ajax, string $message, object $dbObject = null) {
		$ajax->Fail($message, $dbObject);
		$ajax->Data->redirect = 'setup.html';
		$ajax->Send();
		die;
	}
}
