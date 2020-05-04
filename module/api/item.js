import ApiBase from "./apiBase.js";

const urlbase = "api/item/";

/**
 * Javascript client for the item API
 */
export default class ItemApi extends ApiBase {
	/**
	* An item row
	* @typedef {Object} ItemRow
	* @property {number} id - Item ID
	* @property {string} name - Item name
	*/

	/**
	 * List all items the Mealodex knows about.
	 * @returns {Promise<ItemRow[]>} Items sorted by name
	 */
	static List() {
		return super.GET(urlbase + "list");
	}

	/**
	 * Look up an item by ID.
	 * @param {number} id - Item ID to look up
	 * @returns {Promise<ItemRow>} Item for the requested ID
	 */
	static ID(id) {
		return super.GET(`${urlbase}id/${id}`);
	}

	/**
	 * Look up an item by name.
	 * @param {string} name - Item name to look up
	 * @returns {Promise<ItemRow>} Item for the requested name
	 */
	static Name(name) {
		return super.GET(`${urlbase}name/${name}`);
	}

	/**
	 * Add an item to the Mealodex.
	 * @param {string} name - Name of item to add
	 * @returns {Promise<ItemRow>} Item added, or existing item with matching name
	 */
	static Add(name) {
		return super.POST(urlbase + "add", { name: name });
	}

	/**
	 * Update an existing item.
	 * @param {number} id - Item ID to update
	 * @param {object} data - Item data with at least one property to change
	 * @returns {Promise<ItemRow>} Updated item with the requested changes
	 */
	static Update(id, data) {
		return super.PATCH(`${urlbase}id/${id}`, data);
	}

	/**
	 * Replace an existing item.
	 * @param {number} id - Item ID to replace
	 * @param {string} name - New item name
	 * @returns {Promise<ItemRow>} Replaced item with the requested changes
	 */
	static Replace(id, name) {
		return super.PUT(`${urlbase}id/${id}`, { name: name });
	}
}
