<?php
require_once dirname(__DIR__) . '/etc/class/mealodex.php';

/**
 * Handler for setup API requests.
 * @author misterhaan
 */
class SetupApi extends mdApi {
	/**
	 * Get the current setup level.
	 */
	public static function currentLevel() {
		$result = (object)['level' => -4];
		if(!file_exists(dirname(DOCROOT) . '/.mdKeys.php'))
			$result->stepData = "File not found";
		else {
			require_once(dirname(DOCROOT) . '/.mdKeys.php');
			if(!class_exists("mdKeysDB"))
				$result->stepData = "Class not defined";
			elseif(!defined("mdKeysDB::HOST") || !defined("mdKeysDB::NAME") || !defined("mdKeysDB::USER") || !defined("mdKeysDB::PASS"))
				$result->stepData = "Class incomplete";
			else {
				$result->level = -3;
				$db = @new mysqli(mdKeysDB::HOST, mdKeysDB::USER, mdKeysDB::PASS, mdKeysDB::NAME);
				if(!$db || $db->connect_errno)
					$result->stepData = [
						"name" => mdKeysDB::NAME,
						"user" => mdKeysDB::USER,
						"pass" => mdKeysDB::PASS,
						"error" => $db->connect_errno . ' ' . $db->connect_error
					];
				else {
					$result->level = -2;
					$config = $db->query('select * from config limit 1');
					if(!$config || !($config = $config->fetch_object()))
						$result->stepData = $db->errno . ' ' . $db->error;
					else {
						$result->level = -1;
						if($config->structureVersion < mdVersion::Structure || $config->dataVersion < mdVersion::Data)
							$result->stepData = [
								"structureBehind" => mdVersion::Structure - $config->structureVersion,
								"dataBehind" => mdVersion::Data - $config->dataVersion
							];
						else
							$result->level = 0;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Get the current setup level.
	 */
	protected static function GET_level() {
		self::Success(self::currentLevel());
	}

	/**
	 * Configure the database connection.
	 */
	protected static function POST_configureDatabase() {
		if(isset($_POST['host'], $_POST['name'], $_POST['user'], $_POST['pass']) && ($host = trim($_POST['host']))
			&& ($name = trim($_POST['name'])) && ($user = trim($_POST['user'])) && ($pass = $_POST['pass'])) {
			$path = dirname(DOCROOT) . '/.mdKeys.php';
			$contents = '<?php
class mdKeysDB {
	const HOST = \'' . addslashes($_POST['host']) . '\';
	const NAME = \'' . addslashes($_POST['name']) . '\';
	const USER = \'' . addslashes($_POST['user']) . '\';
	const PASS = \'' . addslashes($_POST['pass']) . '\';
}';
			if($fh = fopen($path, 'w')) {
				fwrite($fh, $contents);
				self::Success(array_merge(self::currentLevel(), ['path' => $path, 'saved' => true]));
			} else
				self::Success(['path' => $path, 'saved' => false, 'message' => error_get_last()['message'], 'contents' => $contents]);
		} else
			self::NeedMoreInfo('Parameters host, name, user, and pass are all required and cannot be blank.');
	}

	/**
	 * Install the database at the latest version.
	 */
	protected static function POST_installDatabase() {
		$db = self::RequireDatabase();
		// tables, views, routines; then alphabetical order.  if anything has
		// dependencies that come later, it comes after its last dependency.
		$files = [
			'tables/config'
		];
		$db->autocommit(false);  // no partial database installations
		foreach($files as $file)
			self::RunQueryFile($file, $db);

		if($db->real_query('insert into config (structureVersion) values (' . +mdVersion::Structure . ')'))
			$db->commit();
		else
			self::DatabaseError('Error initializing configuration', $db);
	}

	/**
	 * Run all applicable upgrade scripts to bring the database up to the current version.
	 */
	protected static function POST_upgradeDatabase() {
		$db = self::RequireDatabaseWithConfig();
		$db->autocommit(false);  // each step should commit only if the entire step succeeds
		if($db->config->structureVersion < mdVersion::Structure)
			self::UpgradeDatabaseStructure($db);
		if($db->config->dataVersion < mdVersion::Data)
			self::UpgradeDatabaseData($db);
	}

	/**
	 * Upgrade database structure and update the structure version.
	 * @param mysqli $db Database connection object.
	 */
	private static function UpgradeDatabaseStructure(mysqli $db) {
		// add future structure upgrades here (older ones need to go first)
	}

	/**
	 * Upgrade database data and update the data version.
	 * @param mysqli $db Database connection object.
	 */
	private static function UpgradeDatabaseData(mysqli $db) {
		// add future data upgrades here (older ones need to go first)
	}

	/**
	 * Load a query from a file and run it.
	 * @param string $filepath File subdirectory and name without extension.
	 * @param mysqli $db Database connection object.
	 */
	private static function RunQueryFile(string $filepath, mysqli $db) {
		$sql = trim(file_get_contents(dirname(__DIR__) . '/etc/db/' . $filepath . '.sql'));
		if(substr($filepath, 0, 12) == 'transitions/') {  // transitions usually have more than one query
			if($db->multi_query($sql)) {
				while($db->next_result());  // these queries don't return results but we need to get past them to continue
				return;
			}
		} else {
			if($db->real_query($sql))
				return;
		}
		// if we haven't returned already, the query failed
		list($type, $name) = explode('s/', $filepath);
		self::DatabaseError("Error creating $name $type", $db);
	}
}
SetupApi::Respond();
