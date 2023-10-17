<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once dirname(__DIR__) . '/etc/class/row/row.php';

/**
 * Handler for recipe API requests.
 * @author misterhaan
 */
class RecipeApi extends Api {
	/**
	 * Look up a recipe row based on its ID.
	 * @param int $id ID of recipe row to look up
	 * @param mysqli $db Database connection
	 * @return object|bool Recipe row with requested ID, or false if not found
	 */
	public static function fromID(int $id, mysqli $db): mixed {
		if ($select = $db->prepare('select id, name, lastServed, complexity, servings, instructions from recipe where id=? limit 1'))
			if ($select->bind_param('i', $id))
				if ($select->execute()) {
					$recipe = new Row();
					if ($select->bind_result($recipe->id, $recipe->name, $recipe->lastServed, $recipe->complexity, $recipe->servings, $recipe->instructions))
						if ($select->fetch())
							return $recipe;
						else
							return false;
					else
						self::DatabaseError('Error binding result from recipe lookup', $select);
				} else
					self::DatabaseError('Error executing recipe lookup', $select);
			else
				self::DatabaseError('Error binding parameters to look up recipe', $select);
		else
			self::DatabaseError('Error preparing to look up recipe', $db);
		return false;
	}

	/**
	 * Update the name property of a recipe.
	 * @param int $id ID of the recipe to update
	 * @param string $name New name for the recipe
	 * @param mysqli $db Database connection
	 */
	public static function updateName(int $id, string $name, mysqli $db): void {
		if ($update = $db->prepare('update recipe set name=? where id=? limit 1'))
			if ($update->bind_param('si', $name, $id))
				if ($update->execute())
					$update->close();
				else
					self::DatabaseError('Error updating recipe name', $update);
			else
				self::DatabaseError('Error binding parameters to update recipe name', $update);
		else
			self::DatabaseError('Error preparing to update recipe name', $db);
	}

	/**
	 * Update the last served property of a recipe.
	 * @param int $id ID of the recipe to update
	 * @param int $lastServed New last served property for the recipe, as a Unix timestamp
	 * @param mysqli $db Database connection
	 */
	public static function updateLastServed(int $id, int $lastServed, mysqli $db): void {
		if ($update = $db->prepare('update recipe set lastServed=from_unixtime(?) where id=? limit 1'))
			if ($update->bind_param('ii', $lastServed, $id))
				if ($update->execute())
					$update->close();
				else
					self::DatabaseError('Error updating recipe last served', $update);
			else
				self::DatabaseError('Error binding parameters to update recipe last served', $update);
		else
			self::DatabaseError('Error preparing to update recipe last served', $db);
	}

	/**
	 * Update the complexity property of a recipe.
	 * @param int $id ID of the recipe to update
	 * @param int $complexity New complexity property for the recipe
	 * @param mysqli $db Database connection
	 */
	public static function updateComplexity(int $id, int $complexity, mysqli $db): void {
		if ($update = $db->prepare('update recipe set complexity=? where id=? limit 1'))
			if ($update->bind_param('ii', $complexity, $id))
				if ($update->execute())
					$update->close();
				else
					self::DatabaseError('Error updating recipe complexity', $update);
			else
				self::DatabaseError('Error binding parameters to update recipe complexity', $update);
		else
			self::DatabaseError('Error preparing to update recipe complexity', $db);
	}

	/**
	 * Update the servings property of a recipe.
	 * @param int $id ID of the recipe to update
	 * @param int $servings New servings property for the recipe
	 * @param mysqli $db Database connection
	 */
	public static function updateServings(int $id, int $servings, mysqli $db): void {
		if ($update = $db->prepare('update recipe set servings=? where id=? limit 1'))
			if ($update->bind_param('ii', $servings, $id))
				if ($update->execute())
					$update->close();
				else
					self::DatabaseError('Error updating recipe servings', $update);
			else
				self::DatabaseError('Error binding parameters to update recipe servings', $update);
		else
			self::DatabaseError('Error preparing to update recipe servings', $db);
	}

	/**
	 * Update the instructions property of a recipe.
	 * @param int $id ID of the recipe to update
	 * @param string $instructions New instructions property for the recipe
	 * @param mysqli $db Database connection
	 */
	public static function updateInstructions(int $id, string $instructions, mysqli $db): void {
		if ($update = $db->prepare('update recipe set instructions=? where id=? limit 1'))
			if ($update->bind_param('si', $instructions, $id))
				if ($update->execute())
					$update->close();
				else
					self::DatabaseError('Error updating recipe instructions', $update);
			else
				self::DatabaseError('Error binding parameters to update recipe instructions', $update);
		else
			self::DatabaseError('Error preparing to update recipe instructions', $db);
	}

	/**
	 * List all recipes the Mealodex knows about.  Does not include ingredients or instructions
	 */
	protected static function GET_list(): void {
		if ($db = self::RequireLatestDatabase())
			if ($select = $db->prepare('select id, name, lastServed, complexity from recipe order by name'))
				if ($select->execute()) {
					$recipe = new Row();
					if ($select->bind_result($recipe->id, $recipe->name, $recipe->lastServed, $recipe->complexity)) {
						$recipes = [];
						while ($select->fetch())
							$recipes[] = $recipe->dupe();
						self::Success($recipes);
					} else
						self::DatabaseError('Error binding results from looking up recipes', $select);
				} else
					self::DatabaseError('Error looking up recipes', $select);
			else
				self::DatabaseError('Error preparing to look up recipes', $db);
	}

	/**
	 * List all recipes that match a provided search text
	 * @param string[] $params First item is the search text
	 */
	protected static function GET_search(array $params): void {
		if ($search = trim(array_shift($params))) {
			if ($db = self::RequireLatestDatabase())
				if ($select = $db->prepare('select id, name, lastServed, complexity from recipe where name like concat(\'%\',?,\'%\') order by not name like concat(?,\'%\'), name'))
					if ($select->bind_param('ss', $search, $search))
						if ($select->execute()) {
							$recipe = new Row();
							if ($select->bind_result($recipe->id, $recipe->name, $recipe->lastServed, $recipe->complexity)) {
								$recipes = [];
								while ($select->fetch())
									$recipes[] = $recipe->dupe();
								self::Success($recipes);
							} else
								self::DatabaseError('Error binding results from recipe search', $select);
						} else
							self::DatabaseError('Error searching recipes', $select);
					else
						self::DatabaseError('Error binding parameters to search recipes', $select);
				else
					self::DatabaseError('Error preparing to search recipes', $db);
		} else
			self::GET_list();  // no search parameters means we list everything
	}

	/**
	 * Look up a recipe by ID.
	 * @param array $params First value is the recipe ID
	 */
	protected static function GET_id(array $params): void {
		if ($id = trim(array_shift($params)))
			if (is_numeric($id)) {
				$id = +$id;
				if ($db = self::RequireLatestDatabase())
					if ($recipe = self::fromID($id, $db))
						self::Success($recipe);
					else
						self::NotFound("No recipe at ID $id.");
			} else
				self::NeedMoreInfo("ID '$id' is not numeric.");
		else
			self::NeedMoreInfo('ID to look up needs to be passed in the URL as recipe/id/{ID}.');
	}

	/**
	 * Check if a name is available for a recipe.  If the recipe is already in
	 * the database, the optional id parameter should be specified with the
	 * recipe ID to avoid saying the name isn't available because the same recipe
	 * is already using it.
	 * @param array $params First value is the name to check
	 */
	protected static function GET_checkName(array $params): void {
		if ($name = trim(array_shift($params))) {
			$id = isset($_GET['id']) && is_numeric($_GET['id']) ? +$_GET['id'] : 0;
			$db = self::RequireLatestDatabase();
			if ($chk = $db->prepare('select id from recipe where name=? and not id=? limit 1'))
				if ($chk->bind_param('si', $name, $id))
					if ($chk->execute())
						if ($chk->bind_result($dupe))
							if ($chk->fetch())
								self::Success(['status' => 'invalid', 'message' => "Already in use by recipe $dupe"]);
							else
								self::Success(['status' => 'valid', 'message' => 'Available']);
						else
							self::DatabaseError('Error binding result from duplicate name check', $chk);
					else
						self::DatabaseError('Error checking for duplicate name', $chk);
				else
					self::DatabaseError('Error binding parameters to check for duplicate name', $chk);
			else
				self::DatabaseError('Error preparing to check for duplicate name', $db);
		} else
			self::Success(['status' => 'invalid', 'message' => 'Cannot be blank']);
	}

	/**
	 * Add a recipe to the Mealodex.
	 */
	protected static function POST_add(): void {
		if (isset($_POST['name']) && $name = trim($_POST['name'])) {
			$complexity = isset($_POST['complexity']) ? +trim($_POST['complexity']) : 0;
			$servings = isset($_POST['servings']) ? +trim($_POST['servings']) : 0;
			$instructions = isset($_POST['instructions']) ? trim($_POST['instructions']) : '';
			if ($db = self::RequireLatestDatabase())
				if ($ins = $db->prepare('insert into recipe (name, complexity, servings, instructions) values (?, ?, ?, ?)'))
					if ($ins->bind_param('siis', $name, $complexity, $servings, $instructions))
						if ($ins->execute()) {
							$id = $db->insert_id;
							$ins->close();
							if ($recipe = self::fromID($id, $db))
								self::Success($recipe);
							else
								self::NotFound("Unable to look up recipe by ID $id after adding as $name.");
						} else
							self::DatabaseError('Error executing recipe add', $ins);
					else
						self::DatabaseError('Error binding parameters to add recipe', $ins);
				else
					self::DatabaseError('Error preparing to add recipe', $db);
		} else
			self::NeedMoreInfo('Name is required to add a recipe.');
	}

	/**
	 * Mark a recipe as last served now, or the specified date.
	 * @param array $params First value is the recipe ID
	 */
	protected static function POST_serve(array $params): void {
		if ($id = trim(array_shift($params)))
			if (is_numeric($id)) {
				$id = +$id;
				$served = isset($_POST['served']) && $_POST['served'] ? strtotime(trim($_POST['served'])) : time();
				if ($db = self::RequireLatestDatabase()) {
					self::updateLastServed($id, $served, $db);
					if ($recipe = self::fromID($id, $db))
						self::Success($recipe);
					else
						self::NotFound("Unable to update last served for recipe ID $id because it could not be found.");
				}
			} else
				self::NeedMoreInfo("ID '$id' is not numeric.");
		else
			self::NeedMoreInfo('ID to look up needs to be passed in the URL as item/id/{ID}.');
	}

	/**
	 * Update one or more recipe properties
	 * @param array $params First value is the recipe ID
	 */
	protected static function PATCH_id(array $params): void {
		if ($id = trim(array_shift($params)))
			if (is_numeric($id)) {
				$id = +$id;
				parse_str(file_get_contents("php://input"), $patch);
				if (
					isset($patch['name']) || isset($patch['lastServed'])
					|| isset($patch['complexity']) || isset($patch['servings'])
					|| isset($patch['instructions'])
				) {
					if ($db = self::RequireLatestDatabase()) {
						$db->autocommit(false);  // in case we're updating multiple properties, make sure we get all of them
						if (isset($patch['name']))
							if ($name = trim($patch['name']))
								self::updateName($id, $name, $db);
							else
								self::NeedMoreInfo('Name cannot be blank.  To update other fields but leave name as-is, do not specify the name property.');
						if (isset($patch['lastServed']))
							if ($lastServed = strtotime($patch['lastServed']))
								self::updateLastServed($id, $lastServed, $db);
							else
								self::NeedMoreInfo('Unable to understand \'' . $patch['lastServed'] . '\' as a date.  To update other fields but leave lastServed unchanged, do not specify the lastServed property.');
						if (isset($patch['complexity']))
							if (is_numeric($patch['complexity']))
								self::updateComplexity($id, +$patch['complexity'], $db);
							else
								self::NeedMoreInfo('Complexity must be numeric.  To update other fields but leave complexity unchanged, do not specify the complexity property.');
						if (isset($patch['servings']))
							if (is_numeric($patch['servings']))
								self::updateServings($id, +$patch['servings'], $db);
							else
								self::NeedMoreInfo('Servings must be numeric.  To update other fields but leave servings unchanged, do not specify the servings property.');
						if (isset($patch['instructions']))
							self::updateInstructions($id, trim($patch['instructions']), $db);
						$db->commit();  // all updates succeeded, so safe to save the result
						if ($prep = self::fromID($id, $db))
							self::Success($prep);
						else
							self::NotFound("Unable to update prep ID $id because it could not be found.");
					}
				} else
					self::NeedMoreInfo('Name, lastServed, complexity, servings, and / or instructions required to update a recipe.');
			} else
				self::NeedMoreInfo("ID '$id' is not numeric.");
		else
			self::NeedMoreInfo('ID to look up needs to be passed in the URL as item/id/{ID}.');
	}
}
RecipeApi::Respond();
