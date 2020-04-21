<?php
require_once dirname(__DIR__) . '/etc/class/api.php';

/**
 * Handler for item API requests.
 * @author misterhaan
 */
class ItemApi extends Api {
	/**
	 * Look up an item row based on its ID.
	 * @param int $id ID of item row to look up
	 * @param mysqli $db Database connection
	 * @return mixed Item row with requested ID, or false if not found
	 */
	public static function fromID(int $id, mysqli $db) {
		if($getitem = $db->prepare('select name from item where id=? limit 1'))
			if($getitem->bind_param('i', $id))
				if($getitem->execute())
					if($item = $getitem->get_result())
						if($item = $item->fetch_object()) {
							$item->id = $id;
							return $item;
						} else
							return false;
					else
						self::DatabaseError('Error getting result from item lookup', $getitem);
				else
					self::DatabaseError('Error executing item lookup', $getitem);
			else
				self::DatabaseError('Error binding parameters to look up item', $getitem);
		else
			self::DatabaseError('Error preparing to look up item', $db);
		return false;
	}

	/**
	 * Look up an item row based on its name.
	 * @param string $name Name of item row to look up
	 * @param mysqli $db Database connection
	 * @return mixed Item row with requested name, or false if not found
	 */
	public static function fromName(string $name, mysqli $db) {
		if($getitem = $db->prepare('select id, name from item where name=? limit 1'))
			if($getitem->bind_param('s', $name))
				if($getitem->execute())
					if($item = $getitem->get_result())
						if($item = $item->fetch_object())
							return $item;
						else
							return false;
					else
						self::DatabaseError('Error getting result from item lookup', $getitem);
				else
					self::DatabaseError('Error executing item lookup', $getitem);
			else
				self::DatabaseError('Error binding parameters to look up item', $getitem);
		else
			self::DatabaseError('Error preparing to look up item', $getitem);
		return false;
	}

	/**
	 * List all items the Mealodex knows about.
	 */
	protected static function GET_list() {
		if($db = self::RequireLatestDatabase())
			if($itemResult = $db->query('select id, name from item order by name')) {
				$items = [];
				while($item = $itemResult->fetch_object())
					$items[] = $item;
				self::Success($items);
			} else
				self::DatabaseError('Error looking up items', $db);
	}

	/**
	 * Look up an item by ID.
	 * @param array $params First item is the item ID
	 */
	protected static function GET_id(array $params) {
		if($id = trim(array_shift($params)))
			if(is_numeric($id)) {
				$id = +$id;
				if($db = self::RequireLatestDatabase())
					if($item = self::fromID($id, $db))
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
	 * @param array $params First item is the item name
	 */
	protected static function GET_name(array $params) {
		if($name = trim(array_shift($params))) {
			if($db = self::RequireLatestDatabase())
				if($item = self::fromName($name, $db))
					self::Success($item);
				else
					self::NotFound("No item named $name.");
		} else
			self::NeedMoreInfo('Name to look up needs to be passed in the URL as item/name/{Name}.');
	}

	/**
	 * Add an item to the Mealodex.
	 */
	protected static function POST_add() {
		if(isset($_POST['name']) && $name = trim($_POST['name'])) {
			if($db = self::RequireLatestDatabase())
				if($item = self::fromName($name, $db))
					self::Success($item);
				elseif($putitem = $db->prepare('insert into item (name) values (?)'))
					if($putitem->bind_param('s', $name))
						if($putitem->execute()) {
							$id = $db->insert_id;
							$putitem->close();
							if($item = self::fromID($id, $db))
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
	 */
	protected static function PATCH_id(array $params) {
		// since there's only one property to update it's either all or nothing
		self::PUT_id($params);
	}

	/**
	 * Replace an entire item.
	 */
	protected static function PUT_id(array $params) {
		if($id = trim(array_shift($params)))
			if(is_numeric($id)) {
				$id = +$id;
				parse_str(file_get_contents("php://input"), $_PUT);
				if(isset($_PUT['name']) && $name = trim($_PUT['name'])) {
					if($db = self::RequireLatestDatabase())
						if($update = $db->prepare('update item set name=? where id=? limit 1'))
							if($update->bind_param('si', $name, $id))
								if($update->execute())
									if($item = self::fromID($id, $db))
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
