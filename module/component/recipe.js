import ReportError from "../reportError.js";
import RecipeApi from "../api/recipe.js";
import IngredientApi from "../api/ingredient.js";

const Complexity = [
	"unspecified",  // should not be referenced
	"easy",
	"average",
	"involved"
];

export default {
	props: [
		"params"
	],
	data() {
		return {
			recipe: false,
			ingredients: false
		};
	},
	computed: {
		hasFacts() {
			return this.recipe && (this.recipe.lastServed || this.recipe.complexity || this.recipe.servings);
		},
		servedDaysAgo() {
			const served = new Date(this.recipe.lastServed);
			const now = new Date();
			now.setHours(0, 0, 0, 0);
			return Math.round((now - served) / 86400000);
		},
		complexityName() {
			return Complexity[this.recipe.complexity];
		}
	},
	watch: {
		params() {
			this.Load();
		}
	},
	created() {
		this.Load();
	},
	methods: {
		Load() {
			const recipeId = this.params.id;
			RecipeApi.ID(recipeId).then(recipe => {
				this.recipe = recipe;
			}).fail(this.Error);
			IngredientApi.GetRecipe(recipeId).then(ingredients => {
				this.ingredients = ingredients;
			}).fail(this.Error);
		}
	},
	mixins: [ReportError],
	template: /*html*/ `
		<article>
			<h1 :class="{loading: !recipe}">{{recipe ? recipe.name : "Loading Recipe..."}}</h1>
			<ul class=facts v-if=hasFacts>
				<li v-if=recipe.lastServed class=lastServed :title="'Last served ' + recipe.lastServed">{{servedDaysAgo}} days ago</li>
				<li v-if=recipe.complexity class=complexity>{{complexityName}} prep</li>
				<li v-if=recipe.servings class=serves>serves {{recipe.servings}}</li>
			</ul>
			<section class=ingredients>
				<ul v-if=ingredients>
					<li v-for="ingredient in ingredients">
						{{ingredient.displayAmount}}
						<template v-if=ingredient.unit.abbr>
							<abbr :title=ingredient.unit.name>{{ingredient.unit.abbr}}</abbr>
						</template>
						<template v-if=!ingredient.unit.abbr>{{ingredient.unit.name}}</template>
						{{ingredient.prep.name ? ingredient.item.name + ", " + ingredient.prep.name : ingredient.item.name}}
					</li>
				</ul>
				<p class=loading v-if=!ingredients>Loading ingredients...</p>
			</section>
			<section class=instructions v-if=recipe v-html=recipe.instructions></section>
		</article>
`
}
