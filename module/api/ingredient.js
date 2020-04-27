import ApiBase from "./apiBase.js";

const urlbase = "api/ingredient/";

/**
 * Javascript client for the ingredient API
 */
export default class IngredientApi extends ApiBase {
	/**
	 * An ingredient row
	 * @typedef {Object} IngredientRow
	 * @property {number} sort - Which position the ingredient is in the list
	 * @property {ItemRow|number} item - Food item or ID of food item
	 * @property {number} amount - How much of the ingredient
	 * @property {UnitRow|number} unit - Unit that goes with the amount or ID of unit
	 * @property {PrepRow|number} prep - How to prepare the ingredient or preparation ID
	 */

	/**
	 * List all the ingredients for a recipe.
	 * @param {number} recipe - ID of recipe to get ingredients for
	 * @returns {Promise<IngredientRow[]>} Ingredients of the requested recipe
	 */
	static GetRecipe(recipe) {
		return super.GET(`${urlbase}recipe/${recipe}`);
	}

	/**
	 * Add all the ingredients for a recipe.
	 * @param {number} recipe - ID of recipe to set ingredients for
	 * @param {IngredientRow[]} ingredients - All ingredients for the recipe
	 * @returns {Promise<IngredientRow[]>}
	 */
	static AddRecipe(recipe, ingredients) {
		return super.Put(`${urlbase}recipe/${recipe}`, JSON.stringify(ingredients));
	}
}
