<?php
require_once dirname(__DIR__) . '/etc/class/api.php';
require_once dirname(__DIR__) . '/etc/class/row/ingredient.php';

/**
 * Handler for ingredient API requests.
 * @author misterhaan
 */
class IngredientApi extends Api {
	/**
	 * Get all ingredients for a recipe.
	 * @param int $recipe ID of recipe to look up
	 * @param mysqli $db Database connection
	 * @return IngredientRow[] Recipe ingredients
	 */
	public static function recipeIngredients(int $recipe, mysqli $db): array {
		if ($list = $db->prepare('select ig.sort, it.id as item_id, it.name as item_name, ig.amount, u.id as unit_id, u.measure as unit_measure, u.abbr as unit_abbr, u.name as unit_name, u.factor as unit_factor, p.id as prep_id, p.name as prep_name from ingredient as ig left join item as it on it.id=ig.item left join unit as u on u.id=ig.unit left join prep as p on p.id=ig.prep where ig.recipe=? order by ig.sort'))
			if ($list->bind_param('i', $recipe))
				if ($list->execute()) {
					$ingredient = new IngredientRow();
					if ($list->bind_result($ingredient->sort, $ingredient->item->id, $ingredient->item->name, $ingredient->amount, $ingredient->unit->id, $ingredient->unit->measure, $ingredient->unit->abbr, $ingredient->unit->name, $ingredient->unit->factor, $ingredient->prep->id, $ingredient->prep->name)) {
						$ingredients = [];
						while ($list->fetch())
							$ingredients[] = $ingredient->dupe();
						return $ingredients;
					} else
						self::DatabaseError('Error binding recipe ingredient list result', $list);
				} else
					self::DatabaseError('Error listing recipe ingredients', $list);
			else
				self::DatabaseError('Error binding parameter to list recipe ingredients', $list);
		else
			self::DatabaseError('Error preparing to list units', $db);
	}

	/**
	 * List all the ingredients for a recipe.
	 * @param array $params First value is recipe ID
	 */
	protected static function GET_recipe(array $params): void {
		if ($recipe = trim(array_shift($params)))
			if (is_numeric($recipe)) {
				$recipe = +$recipe;
				if ($db = self::RequireLatestDatabase())
					self::Success(self::recipeIngredients($recipe, $db));
			} else
				self::NeedMoreInfo("Recipe ID '$recipe' is not numeric.");
		else
			self::NeedMoreInfo('Recipe ID to look up ingredients must be passed in the URL as ingredient/recipe/{ID}.');
	}

	/**
	 * Add or replace all the ingredients for a recipe.
	 * @param array $params First value is recipe ID
	 */
	protected static function PUT_recipe(array $params): void {
		if ($recipe = trim(array_shift($params)))
			if (is_numeric($recipe))
				if ($json = file_get_contents('php://input'))
					if ($ingredients = json_decode($json))
						if (is_array($ingredients)) {
							if ($db = self::RequireLatestDatabase()) {
								$db->autocommit(false);
								if ($del = $db->prepare('delete from ingredient where recipe=?'))
									if ($del->bind_param('i', $recipe))
										if ($del->execute())
											if ($ins = $db->prepare('insert into ingredient (recipe, sort, item, amount, unit, prep) values (?, ?, ?, ?, ?, ?)'))
												if ($ins->bind_param('iiidii', $recipe, $sort, $item, $amount, $unit, $prep)) {
													foreach ($ingredients as $ingredient) {
														$sort = $ingredient->sort;
														$item = $ingredient->item;
														$amount = $ingredient->amount;
														$unit = $ingredient->unit;
														$prep = $ingredient->prep;
														if (!$ins->execute())
															self::DatabaseError('Error adding ingredient #$sort', $ins);
													}
													$db->commit();
													self::Success(self::recipeIngredients($recipe, $db));
												} else
													self::DatabaseError('Error binding parameters to add ingredients', $ins);
											else
												self::DatabaseError('Error preparing to add ingredients', $db);
										else
											self::DatabaseError('Error clearing previous ingredients', $del);
									else
										self::DatabaseError('Error binding parameter to clear previous ingredients', $del);
								else
									self::DatabaseError('Error preparing to clear previous ingredients', $db);
							}
						} else
							self::NeedMoreInfo('Request body must be a json array of ingredients.');
					else
						self::NeedMoreInfo('Unable to decode json ingredients:  ' . json_last_error_msg());
				else
					self::NeedMoreInfo('Ingredient(s) to add to recipe must be included as a JSON body.');
			else
				self::NeedMoreInfo("Recipe ID '$recipe' is not numeric.");
		else
			self::NeedMoreInfo('Recipe ID to add ingredients to must be passed in the URL as ingredient/recipe/{ID}.');
	}
}
IngredientApi::Respond();
