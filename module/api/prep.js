import ApiBase from "./apiBase.js";

const urlbase = "api/prep/";

/**
 * Javascript client for the prep API
 */
export default class PrepApi extends ApiBase {
	/**
	* A prep row
	* @typedef {Object} PrepRow
	* @property {number} id - Prep ID
	* @property {string} name - Prep name
	* @property {string} description - Prep description
	*/

	/**
	 * List all preps the Mealodex knows about.
	 * @returns {Promise<PrepRow[]>} Preps sorted by name
	 */
	static List() {
		return super.GET(urlBase + "list");
	}

	/**
	 * Add a prep to the Mealodex.
	 * @param {string} name - Name of prep to add
	 * @param {string} description - Description of prep to add
	 * @returns {Promise<PrepRow>} Prep added, or existing prep with matching name
	 */
	static Add(name, description) {
		return super.POST(url, { name: name, description: description });
	}

	/**
	 * Update an existing prep.
	 * @param {number} id - Prep ID to update
	 * @param {object} data - Prep data with at least one property to change
	 * @returns {Promise<PrepRow>} Updated prep with the requested changes
	 */
	static Update(id, data) {
		return super.PATCH(`${urlbase}id/${id}`, data);
	}

	/**
	 * Replace an existing prep.
	 * @param {number} id - Prep ID to replace
	 * @param {string} name - New prep name
	 * @param {string} description - Description of prep to add
	 * @returns {Promise<PrepRow>} Replaced prep with the requested changes
	 */
	static Replace(id, name) {
		return super.PUT(`${urlbase}id/${id}`, { name: name, description: description });
	}
}
