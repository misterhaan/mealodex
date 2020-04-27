<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once dirname(__DIR__) . '/etc/class/row/row.php';

/**
 * Handler for prep API requests.
 * @author misterhaan
 */
class PrepApi extends Api {
	/**
	 * Look up a prep row based on its ID.
	 * @param int $id ID of prep row to look up
	 * @param mysqli $db Database connection
	 * @return object|bool Prep row with requested ID, or false if not found
	 */
	public static function fromID(int $id, mysqli $db) {
		if($select = $db->prepare('select id, name, description from prep where id=? limit 1'))
			if($select->bind_param('i', $id))
				if($select->execute()) {
					$prep = new Row();
					if($select->bind_result($prep->id, $prep->name, $prep->description))
						if($select->fetch())
							return $prep;
						else
							return false;
					else
						self::DatabaseError('Error binding result from prep lookup', $select);
				} else
					self::DatabaseError('Error executing prep lookup', $select);
			else
				self::DatabaseError('Error binding parameters to look up prep', $select);
		else
			self::DatabaseError('Error preparing to look up prep', $db);
		return false;
	}

	/**
	 * Look up a prep row based on its name.
	 * @param string $name Name of prep row to look up
	 * @param mysqli $db Database connection
	 * @return object|bool Prep row with requested name, or false if not found
	 */
	public static function fromName(string $name, mysqli $db) {
		if($select = $db->prepare('select id, name, description from prep where name=? limit 1'))
			if($select->bind_param('s', $name))
				if($select->execute()) {
					$prep = new Row();
					if($select->bind_result($prep->id, $prep->name, $prep->description))
						if($select->fetch())
							return $prep;
						else
							return false;
					else
						self::DatabaseError('Error binding result from prep lookup', $select);
				} else
					self::DatabaseError('Error executing prep lookup', $select);
			else
				self::DatabaseError('Error binding parameters to look up prep', $select);
		else
			self::DatabaseError('Error preparing to look up prep', $db);
		return false;
	}

	/**
	 * Update the name property of a prep.
	 * @param int $id ID of the prep to update
	 * @param string $name New name for the prep
	 * @param mysqli $db Database connection
	 */
	public static function updateName(int $id, string $name, mysqli $db) {
		if($update = $db->prepare('update prep set name=? where id=? limit 1'))
			if($update->bind_param('si', $name, $id))
				if($update->execute())
					$update->close();
				else
					self::DatabaseError('Error updating prep name', $update);
			else
				self::DatabaseError('Error binding parameters to update prep name', $update);
		else
			self::DatabaseError('Error preparing to update prep name', $db);
	}

	/**
	 * Update the description property of a prep.
	 * @param int $id ID of the prep to update
	 * @param string $name New description for the prep
	 * @param mysqli $db Database connection
	 */
	public static function updateDescription(int $id, string $description, mysqli $db) {
		if($update = $db->prepare('update prep set description=? where id=? limit 1'))
			if($update->bind_param('si', $description, $id))
				if($update->execute())
					$update->close();
				else
					self::DatabaseError('Error updating prep description', $update);
			else
				self::DatabaseError('Error binding parameters to update prep description', $update);
		else
			self::DatabaseError('Error preparing to update prep description', $db);
	}

		/**
	 * List all preps the Mealodex knows about.
	 */
	protected static function GET_list() {
		if($db = self::RequireLatestDatabase())
			if($select = $db->prepare('select id, name, description from prep order by name'))
				if($select->execute()) {
					$prep = new Row();
					if($select->bind_result($prep->id, $prep->name, $prep->description)) {
						$preps = [];
						while($select->fetch())
							$preps[] = $prep->dupe();
						self::Success($preps);
					} else
						self::DatabaseError('Error binding result from looking up preps', $select);
				} else
					self::DatabaseError('Error looking up preps', $select);
			else
				self::DatabaseError('Error preparing to look up preps', $db);
	}

	/**
	 * Add a prep to the Mealodex.
	 */
	protected static function POST_add() {
		if(isset($_POST['name']) && $name = trim($_POST['name'])) {
			$description = isset($_POST['description']) ? trim($_POST['description']) : '';
			if($db = self::RequireLatestDatabase())
				if($prep = self::fromName($name, $db))
					self::Success($prep);
				elseif($putprep = $db->prepare('insert into prep (name, description) values (?, ?)'))
					if($putprep->bind_param('ss', $name, $description))
						if($putprep->execute()) {
							$id = $db->insert_id;
							$putprep->close();
							if($prep = self::fromID($id, $db))
								self::Success($prep);
							else
								self::NotFound("Unable to look up prep by ID $id after adding as $name.");
						} else
							self::DatabaseError('Error executing prep add', $putprep);
					else
						self::DatabaseError('Error binding parameters to add prep', $putprep);
				else
					self::DatabaseError('Error preparing to add prep', $db);
		} else
			self::NeedMoreInfo('Name is required to add an prep.');
	}

	/**
	 * Update one or more prep properties.
	 * @param array $params First value is the prep ID
	 */
	protected static function PATCH_id(array $params) {
		if($id = trim(array_shift($params)))
			if(is_numeric($id)) {
				$id = +$id;
				parse_str(file_get_contents("php://input"), $patch);
				if(isset($patch['name']) || isset($patch['description'])) {
					if($db = self::RequireLatestDatabase()) {
						$db->autocommit(false);  // in case we're updating multiple properties, make sure we get all of them
						if(isset($patch['name']))
							if($name = trim($patch['name']))
								self::updateName($id, $name, $db);
							else
								self::NeedMoreInfo('Name cannot be blank.  To update other fields but leave name as-is, do not specify the name property.');
						if(isset($patch['description']))
							self::updateDescription($id, $patch['description'], $db);
						$db->commit();  // both updates succeeded, so safe to save the result
						if($prep = self::fromID($id, $db))
							self::Success($prep);
						else
							self::NotFound("Unable to update prep ID $id because it could not be found.");
					}
				} else
					self::NeedMoreInfo('Name and / or description required to update a prep.');
			} else
				self::NeedMoreInfo("ID '$id' is not numeric.");
		else
			self::NeedMoreInfo('ID to look up needs to be passed in the URL as prep/id/{ID}.');
	}

	/**
	 * Replace an entire prep.
	 * @param array $params First value is the prep ID
	 */
	protected static function PUT_id(array $params) {
		if($id = trim(array_shift($params)))
			if(is_numeric($id)) {
				$id = +$id;
				parse_str(file_get_contents("php://input"), $_PUT);
				if(isset($_PUT['name'], $_PUT['description']) && $name = trim($_PUT['name'])) {
					$description = trim($_PUT['description']);
					if($db = self::RequireLatestDatabase())
						if($update = $db->prepare('update prep set name=?, description=? where id=? limit 1'))
							if($update->bind_param('ssi', $name, $description, $id))
								if($update->execute())
									if($prep = self::fromID($id, $db))
										self::Success($prep);
									else
										self::NotFound("Unable to replace prep ID $id because it could not be found.");
								else
									self::DatabaseError('Error replacing prep', $update);
							else
								self::DatabaseError('Error binding parameters to replace a prep', $update);
						else
							self::DatabaseError('Error preparing to replace a prep', $db);
				} else
					self::NeedMoreInfo('Name and description are required to replace a prep.');
			} else
				self::NeedMoreInfo("ID '$id' is not numeric.");
		else
			self::NeedMoreInfo('ID to look up needs to be passed in the URL as prep/id/{ID}.');
	}
}
PrepApi::Respond();
