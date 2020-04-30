import AppName from "../appName.js";
import ReportError from "../reportError.js";
import RecipeSearch from "./recipeSearch.js";

export default {
	props: [
		"hideSearch"
	],
	mixins: [
		ReportError
	],
	components: {
		recipeSearch: RecipeSearch
	},
	template: /*html*/ `
		<header id=titlebar>
			<img id=favicon src=favicon.svg alt="">
			<h2 id=sitetitle>${AppName.Full}</h2>
			<recipeSearch v-if=!hideSearch @error=Error($event)></recipeSearch>
		</header>
`
}
