import ApiBase from "./apiBase.js";

const urlbase = "api/recipe/";

/**
 * Javascript client for the recipe API
 */
export default class RecipeApi extends ApiBase {
	/**
	* A recipe row
	* @typedef {Object} RecipeRow
	* @property {number} id - Recipe ID
	* @property {string} name - Recipe name
	* @property {string} lastServed - Recipe last served date
	* @property {int} complexity - Recipe complexity level
	* @property {int} servings - Recipe number of servings
	* @property {string} instructions - Recipe instructions in plain text
	*/

	/**
	 * List all recipes the Mealodex knows about.
	 * @returns {Promise<RecipeRow[]>} Recipes sorted by name (without instructions)
	 */
	static List() {
		return super.GET(urlbase + "list");
	}

	/**
	 * Search recipes in the Mealodex by name.
	 * @param {string} searchText
	 * @returns {Promise<RecipeRow[]>} Matching Recipes sorted by name (without instructions)
	 */
	static Search(searchText) {
		return super.GET(`${urlbase}search/${encodeURIComponent(searchText)}`);
	}

	/**
	 * Look up a recipe by ID.
	 * @param {number} id - Recipe ID to look up
	 * @returns {Promise<RecipeRow>} Recipe for the requested ID
	 */
	static ID(id) {
		return super.GET(`${urlbase}id/${id}`);
	}

	/**
	 * Add a recipe to the Mealodex.
	 * @param {string} name - Name of recipe to add
	 * @param {number} complexity - Complexity level of this recipe, or 0 for unspecified
	 * @param {number} servings - Number of servings this recipe makes, or 0 for unspecified
	 * @param {string} instructions - Instructions for this recipe
	 * @returns {Promise<RecipeRow>} Recipe added, or existing recipe with matching name
	 */
	static Add(name, complexity, servings, instructions) {
		return super.POST(urlbase + "add", {
			name: name,
			complexity: complexity,
			servings: servings,
			instructions: instructions
		});
	}

	/**
	 * Update the last served date for a recipe.
	 * @param {number} id - Recipe ID to update
	 * @param {string} [served] - Date to mark the recipe last served, or leave off for today
	 * @returns {Promise<RecipeRow>} Recipe updated with the new last served time
	 */
	static Serve(id, served) {
		return super.POST(`${urlbase}serve/${id}`, { served: served });
	}

	/**
	 * Update an existing recipe.
	 * @param {number} id - Recipe ID to update
	 * @param {object} data - Recipe data with at least one property to change
	 * @returns {Promise<RecipeRow>} Updated recipe with the requested changes
	 */
	static Update(id, data) {
		return super.PATCH(`${urlbase}id/${id}`, data);
	}
}
