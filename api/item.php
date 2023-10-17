<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once dirname(__DIR__) . '/etc/class/row/row.php';

/**
 * Handler for item API requests.
 * @author misterhaan
 */
class ItemApi extends Api {
	/**
	 * Look up an item row based on its ID.
	 * @param int $id ID of item row to look up
	 * @param mysqli $db Database connection
	 * @return object|bool Item row with requested ID, or false if not found
	 */
	public static function fromID(int $id, mysqli $db): mixed {
		if ($select = $db->prepare('select id, name from item where id=? limit 1'))
			if ($select->bind_param('i', $id))
				if ($select->execute()) {
					$item = new Row();
					if ($select->bind_result($item->id, $item->name))
						if ($select->fetch())
							return $item;
						else
							return false;
					else
						self::DatabaseError('Error binding item lookup result', $select);
				} else
					self::DatabaseError('Error executing item lookup', $select);
			else
				self::DatabaseError('Error binding parameters to look up item', $select);
		else
			self::DatabaseError('Error preparing to look up item', $db);
		return false;
	}

	/**
	 * Look up an item row based on its name.
	 * @param string $name Name of item row to look up
	 * @param mysqli $db Database connection
	 * @return object|bool Item row with requested name, or false if not found
	 */
	public static function fromName(string $name, mysqli $db): mixed {
		if ($select = $db->prepare('select id, name from item where name=? limit 1'))
			if ($select->bind_param('s', $name))
				if ($select->execute()) {
					$item = new Row();
					if ($select->bind_result($item->id, $item->name))
						if ($select->fetch())
							return $item;
						else
							return false;
					else
						self::DatabaseError('Error getting result from item lookup', $select);
				} else
					self::DatabaseError('Error executing item lookup', $select);
			else
				self::DatabaseError('Error binding parameters to look up item', $select);
		else
			self::DatabaseError('Error preparing to look up item', $db);
		return false;
	}

	/**
	 * List all items the Mealodex knows about.
	 */
	protected static function GET_list(): void {
		if ($db = self::RequireLatestDatabase())
			if ($select = $db->prepare('select id, name from item order by name'))
				if ($select->execute()) {
					$item = new Row();
					if ($select->bind_result($item->id, $item->name)) {
						$items = [];
						while ($select->fetch())
							$items[] = $item->dupe();
						self::Success($items);
					} else
						self::DatabaseError('Error binding item lookup results', $select);
				} else
					self::DatabaseError('Error looking up items', $select);
			else
				self::DatabaseError('Error preparing to look up items', $db);
	}

	/**
	 * Look up an item by ID.
	 * @param array $params First value is the item ID
	 */
	protected static function GET_id(array $params): void {
		if ($id = trim(array_shift($params)))
			if (is_numeric($id)) {
				$id = +$id;
				if ($db = self::RequireLatestDatabase())
					if ($item = self::fromID($id, $db))
						self::Success($item);
					else
						self::NotFound("No item at ID $id.");
			} else
				self::NeedMoreInfo("ID '$id' is not numeric.");
		else
			self::NeedMoreInfo('ID to look up needs to be passed in the URL as item/id/{ID}.');
	}

	/**
	 * Look up an item by name.
	 * @param array $params First value is the item name
	 */
	protected static function GET_name(array $params): void {
		if ($name = trim(array_shift($params))) {
			if ($db = self::RequireLatestDatabase())
				if ($item = self::fromName($name, $db))
					self::Success($item);
				else
					self::NotFound("No item named $name.");
		} else
			self::NeedMoreInfo('Name to look up needs to be passed in the URL as item/name/{Name}.');
	}

	/**
	 * Add an item to the Mealodex.
	 */
	protected static function POST_add(): void {
		if (isset($_POST['name']) && $name = trim($_POST['name'])) {
			if ($db = self::RequireLatestDatabase())
				if ($item = self::fromName($name, $db))
					self::Success($item);
				elseif ($putitem = $db->prepare('insert into item (name) values (?)'))
					if ($putitem->bind_param('s', $name))
						if ($putitem->execute()) {
							$id = $db->insert_id;
							$putitem->close();
							if ($item = self::fromID($id, $db))
								self::Success($item);
							else
								self::NotFound("Unable to look up item by ID $id after adding as $name.");
						} else
							self::DatabaseError('Error executing item add', $putitem);
					else
						self::DatabaseError('Error binding parameters to add item', $putitem);
				else
					self::DatabaseError('Error preparing to add item', $db);
		} else
			self::NeedMoreInfo('Name is required to add an item.');
	}

	/**
	 * Update one or more item properties.
	 * @param array $params First value is the item id
	 */
	protected static function PATCH_id(array $params): void {
		// since there's only one property to update it's either all or nothing
		self::PUT_id($params);
	}

	/**
	 * Replace an entire item.
	 * @param array $params First value is the item id
	 */
	protected static function PUT_id(array $params): void {
		if ($id = trim(array_shift($params)))
			if (is_numeric($id)) {
				$id = +$id;
				parse_str(file_get_contents("php://input"), $_PUT);
				if (isset($_PUT['name']) && $name = trim($_PUT['name'])) {
					if ($db = self::RequireLatestDatabase())
						if ($update = $db->prepare('update item set name=? where id=? limit 1'))
							if ($update->bind_param('si', $name, $id))
								if ($update->execute())
									if ($item = self::fromID($id, $db))
										self::Success($item);
									else
										self::NotFound("Unable to replace item ID $id because it could not be found.");
								else
									self::DatabaseError('Error replacing item', $update);
							else
								self::DatabaseError('Error binding parameters to replace an item', $update);
						else
							self::DatabaseError('Error preparing to replace an item', $db);
				} else
					self::NeedMoreInfo('Name is required to replace an item.');
			} else
				self::NeedMoreInfo("ID '$id' is not numeric.");
		else
			self::NeedMoreInfo('ID to look up needs to be passed in the URL as item/id/{ID}.');
	}
}
ItemApi::Respond();
