import Vue from "../external/vue.esm.browser.min.js";
import AppName from "./appName.js";
import TitleBar from "./component/titlebar.js";
import StatusBar from "./component/statusbar.js";

new Vue({
	el: "#mealodex",
	components: {
		titlebar: TitleBar,
		statusbar: StatusBar
	},
	template: /*html*/ `
		<div id=mealodex>
			<titlebar :title="'${AppName.Full}'"></titlebar>
			<statusbar></statusbar>
		</div>
`
});
