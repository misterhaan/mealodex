import ApiBase from "./apiBase.js";

const urlbase = "api/unit/";

/**
 * Javascript client for the unit API
 */
export default class UnitApi extends ApiBase {
	/**
	* An item row
	* @typedef {Object} UnitRow
	* @property {number} id - Unit ID
	* @property {number} measure - Unit measurement type
	* @property {string} abbr - Unit abbreviation
	* @property {string} name - Unit name
	* @property {number} factor - Unit factor for converting among the same measure
	*/

	/**
	 * List all units the Mealodex knows about.
	 * @returns {Promise<UnitRow[]>} Units sorted by measure and size
	 */
	static List() {
		return super.GET(urlBase + "list");
	}
}
