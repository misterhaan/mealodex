<?php
require_once dirname(__DIR__) . '/etc/class/mealodex.php';

/**
 * Handler for setup API requests.
 * @author misterhaan
 */
class SetupApi extends mdApi {
	/**
	 * Get the current setup level.
	 * @param mdAjax $ajax Ajax object for returning data or reporting an error.
	 */
	protected static function GET_level(mdAjax $ajax) {
		$ajax->Data->level = -4;
		if(!file_exists(dirname(DOCROOT) . '/.mdKeys.php')) {
			$ajax->Data->stepData = "File not found";
			return;
		}
		require_once(dirname(DOCROOT) . '/.mdKeys.php');
		if(!class_exists("mdKeysDB")) {
			$ajax->Data->stepData = "Class not defined";
			return;
		}
		if(!defined("mdKeysDB::HOST") || !defined("mdKeysDB::NAME") || !defined("mdKeysDB::USER") || !defined("mdKeysDB::PASS")) {
			$ajax->Data->stepData = "Class incomplete";
			return;
		}
		$ajax->Data->level = -3;
		$db = @new mysqli(mdKeysDB::HOST, mdKeysDB::USER, mdKeysDB::PASS, mdKeysDB::NAME);
		if(!$db || $db->connect_errno) {
			$ajax->Data->stepData = [
				"name" => mdKeysDB::NAME,
				"user" => mdKeysDB::USER,
				"pass" => mdKeysDB::PASS,
				"error" => $db->connect_errno . ' ' . $db->connect_error
			];
			return;
		}
		$ajax->Data->level = -2;
		$config = $db->query('select * from config limit 1');
		if(!$config || !($config = $config->fetch_object())) {
			$ajax->Data->stepData = $db->errno . ' ' . $db->error;
			return;
		}
		$ajax->Data->level = -1;
		if($config->structureVersion < mdVersion::Structure || $config->dataVersion < mdVersion::Data) {
			$ajax->Data->stepData = [
				"structureBehind" => mdVersion::Structure - $config->structureVersion,
				"dataBehind" => mdVersion::Data - $config->dataVersion
			];
			return;
		}
		$ajax->Data->level = 0;
	}

	/**
	 * Configure the database connection.
	 * @param mdAjax $ajax Ajax object for returning data or reporting an error.
	 */
	protected static function POST_configureDatabase(mdAjax $ajax) {
		if(isset($_POST['host'], $_POST['name'], $_POST['user'], $_POST['pass']) && ($host = trim($_POST['host']))
			&& ($name = trim($_POST['name'])) && ($user = trim($_POST['user'])) && ($pass = $_POST['pass'])) {
			$ajax->Data->path = dirname(DOCROOT) . '/.mdKeys.php';
			$contents = '<?php
class mdKeysDB {
	const HOST = \'' . addslashes($_POST['host']) . '\';
	const NAME = \'' . addslashes($_POST['name']) . '\';
	const USER = \'' . addslashes($_POST['user']) . '\';
	const PASS = \'' . addslashes($_POST['pass']) . '\';
}';
			if($fh = fopen($ajax->Data->path, 'w')) {
				fwrite($fh, $contents);
				$ajax->Data->saved = true;
				static::GET_level($ajax);
			} else {
				$ajax->Data->saved = false;
				$ajax->Data->message = error_get_last()["message"];
				$ajax->Data->contents = $contents;
			}
		} else
			$ajax->Fail('Parameters host, name, user, and pass are all required and cannot be blank.');
	}

	/**
	 * Install the database at the latest version.
	 * @param mdAjax $ajax Ajax object for returning data or reporting an error.
	 */
	protected static function POST_installDatabase(mdAjax $ajax) {
		$db = self::RequireDatabase($ajax);
		// tables, views, routines; then alphabetical order.  if anything has
		// dependencies that come later, it comes after its last dependency.
		$files = [
			'tables/config'
		];
		$db->autocommit(false);  // no partial database installations
		foreach($files as $file) {
			if(!self::RunQueryFile($file, $db, $ajax)) {
				list($type, $name) = explode('s/', $file);
				$ajax->Fail('Error creating ' . $name . ' ' . $type, $db);
				return;
			}
		}
		if($db->real_query('insert into config (structureVersion) values (' . +mdVersion::Structure . ')'))
			$db->commit();
		else
			$ajax->Fail('Error initializing configuration.', $db);
	}

	/**
	 * Run all applicable upgrade scripts to bring the database up to the current version.
	 * @param mdAjax $ajax Ajax object for returning data or reporting an error.
	 */
	protected static function POST_upgradeDatabase(mdAjax $ajax) {
		$db = self::RequireDatabaseWithConfig($ajax);
		$db->autocommit(false);  // each step should commit only if the entire step succeeds
		if($db->config->structureVersion < mdVersion::Structure)
			if(!self::UpgradeDatabaseStructure($db, $ajax))
				return;
		if($db->config->dataVersion < mdVersion::Data)
			self::UpgradeDatabaseData($db, $ajax);
	}

	/**
	 * Upgrade database structure and update the structure version.
	 * @param mysqli $db Database connection object.
	 * @param mdAjax $ajax Ajax object for reporting an error.
	 * @return boolean True if successful
	 */
	private static function UpgradeDatabaseStructure(mysqli $db, mdAjax $ajax) {
		// add future structure upgrades here (older ones need to go first)
		return true;
	}

	/**
	 * Upgrade database data and update the data version.
	 * @param mysqli $db Database connection object.
	 * @param mdAjax $ajax Ajax object for reporting an error.
	 */
	private static function UpgradeDatabaseData(mysqli $db, mdAjax $ajax) {
		// add future data upgrades here (older ones need to go first)
	}

	/**
	 * Load a query from a file and run it.
	 * @param string $filepath File subdirectory and name without extension.
	 * @param mysqli $db Database connection object.
	 * @param mdAjax $ajax Ajax object for reporting an error.
	 * @return bool True if successful
	 */
	private static function RunQueryFile(string $filepath, mysqli $db, mdAjax $ajax) {
		$sql = trim(file_get_contents(dirname(__DIR__) . '/etc/db/' . $filepath . '.sql'));
		if(substr($filepath, 0, 12) == 'transitions/') {  // transitions usually have more than one query
			if($db->multi_query($sql)) {
				while($db->next_result());  // these queries don't return results but we need to get past them to continue
				return true;
			}
		} else {
			if(substr($sql, -1) == ';')
				$sql = substr($sql, 0, -1);
			if($db->real_query($sql))
				return true;
		}
		$ajax->Fail('Error running query file ' . $filepath . '.', $db);
		return false;
	}
}
SetupApi::Respond();
