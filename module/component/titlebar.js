import AppName from "../appName.js";
import ReportError from "../reportError.js";
import RecipeSearch from "./recipeSearch.js";

export default {
	props: [
		"hideSearch"
	],
	methods: {
		GoHome() {
			location.hash = "";
		}
	},
	mixins: [
		ReportError
	],
	components: {
		recipeSearch: RecipeSearch
	},
	template: /*html*/ `
		<header id=titlebar>
			<img id=favicon src=favicon.svg alt="" @click=GoHome>
			<h2 id=sitetitle @click=GoHome>${AppName.Full}</h2>
			<recipeSearch v-if=!hideSearch @error=Error($event)></recipeSearch>
		</header>
`
}
