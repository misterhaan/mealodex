import "../../external/jquery-3.4.1.min.js";

export default class ApiBase {
	static GET(url, successTransform) {
		return this.GETwithParams(url, {}, successTransform)
	}
	static GETwithParams(url, data, successTransform) {
		return this.Ajax("GET", url, data, successTransform)
	}
	static POST(url, data, successTransform) {
		return this.Ajax("POST", url, data || {}, successTransform);
	}
	static Ajax(method, url, data, successTransform) {
		return $.ajax({
			method: method,
			url: url,
			data: data,
			dataType: "json"
		}).then(result => {
			if(result.fail) {
				if(result.redirect)
					location = result.redirect;
				throw new Error(result.message);
			} else if(successTransform)
				return successTransform(result);
			else {  // default transform is to delete the fail property and return everything else
				delete result.fail;
				return result;
			}
		}, request => {
			throw new Error(request.status + " " + request.statusText + " from " + url);
		});
	}
}
