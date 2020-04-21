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
		}).then(
			result => successTransform ? successTransform(result) : result,
			request => {
				if(request.status == 503 && request.statusText == "Setup Needed" && !location.includes("setup.html"))
					location = "setup.html";
				const error = new Error(
					request.getResponseHeader("Content-Type").split(";")[0] == "text/plain"
						? request.responseText
						: `${request.status} ${request.statusText} from ${url}`
				);
				error.url = url;
				error.status = request.status;
				error.statusText = request.statusText;
				throw error;
			});
	}
}
