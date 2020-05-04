import ReportError from "../reportError.js";
import SuggestWithAdd from "./suggestWithAdd.js";
import RecipeApi from "../api/recipe.js";
import IngredientApi from "../api/ingredient.js";
import UnitApi from "../api/unit.js";
import ItemApi from "../api/item.js";
import PrepApi from "../api/prep.js";

/**
 * Names for recipe complexity values
 */
const Complexity = [
	"unspecified",
	"easy",
	"average",
	"involved"
];

/**
 * Hundredths that convert to fractions we can display
 */
const AcceptableFractions = [
	0, 25, 33, 50, 66, 67, 75
];

/**
 * HTML tag names that can have custom validation applied
 */
const ValidationElements = [
	"input",
	"select"
];

/**
 * Compare two objects by their name property, case-insensitive.
 * @param {object} a First object
 * @param {object} b Second object
 * @returns {number} Sort indicator that is less than, equal, or greater than zero like a <=> b
 */
function SortByName(a, b) {
	return a.name.toLowerCase() > b.name.toLowerCase()
		? 1
		: a.name.toLowerCase() < b.name.toLowerCase()
			? -1
			: 0;
}

let NameCheckDelay = false;

export default {
	props: [
		"params"
	],
	data() {
		return {
			recipe: false,
			ingredients: false,

			loading: false,
			editing: false,
			nameCheck: false,
			saving: false,

			units: false,
			items: false,
			preps: false
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
		},
		valid() {
			return this.validTitle && this.allIngredientsValid;
		},
		validTitle() {
			return this.nameCheck && this.nameCheck.status == "valid";
		},
		allIngredientsValid() {
			return !this.ingredients || this.ingredients.every(this.IngredientValid);
		},
		/**
		 * Only here for the watcher
		 */
		recipeName() {
			return this.recipe ? this.recipe.name : "";
		}
	},
	watch: {
		params() {
			this.Load();
		},
		editing(editing) {
			if(editing) {
				setTimeout(() => {
					autosize($("textarea"));
					$("h1 input").first().focus();
				});
				if(!this.nameCheck)
					this.CheckName();
				if(!this.units)
					UnitApi.List().done(units => {
						this.units = units;
					}).fail(this.Error);
				if(!this.items)
					ItemApi.List().done(items => {
						this.items = items;
					}).fail(this.Error);
				if(!this.preps)
					PrepApi.List().done(preps => {
						this.preps = preps;
					}).fail(this.Error);
			}
		},
		recipeName() {
			if(NameCheckDelay) {
				clearTimeout(NameCheckDelay);
				NameCheckDelay = false;
			}
			if(this.editing)
				NameCheckDelay = setTimeout(this.CheckName, 350);
		}
	},
	created() {
		this.Load();
	},
	methods: {
		Load() {
			const recipeId = this.params.id;
			this.nameCheck = false;
			if(recipeId) {
				if(!this.recipe || this.recipe.id != recipeId) {
					this.editing = false;
					this.loading = true;
					Promise.all([
						RecipeApi.ID(recipeId).done(recipe => {
							this.recipe = recipe;
						}).fail(this.Error),
						IngredientApi.GetRecipe(recipeId).done(ingredients => {
							this.ingredients = ingredients;
						}).fail(this.Error)
					]).finally(() => {
						this.loading = false;
					});
				}
			} else {
				this.recipe = {
					name: "",
					instructions: "",
					complexity: 0,
					servings: 0
				};
				this.ingredients = [];
				this.AddIngredient();
				this.editing = true;
			}
		},
		CheckName() {
			NameCheckDelay = false;
			if(this.recipe.name) {
				this.nameCheck = { status: "checking", message: "Checking recipe title availability..." };
				RecipeApi.CheckName(this.recipe.name, this.recipe.id).done(result => {
					this.nameCheck = result;
				}).fail(err => {
					this.nameCheck = { status: "invalid", message: err.message || err };
				});
			} else
				this.nameCheck = { status: "invalid", message: "Recipe must have a unique title" };

		},
		AddIngredient(event) {
			this.ingredients.push({
				amount: 0,
				item: { id: null, name: null },
				prep: { id: null, name: null },
				sort: this.ingredients.length + 1,
				unit: { id: null, name: null }
			});
			if(event)
				setTimeout(() => $("input.amount").last().focus());
		},
		IngredientValid(ingredient) {
			return this.IngredientAmountValid(ingredient) && ingredient.unit.id && ingredient.item.id;
		},
		IngredientAmountValid(ingredient) {
			return ingredient.amount && AcceptableFractions.indexOf(Math.round((ingredient.amount - Math.floor(ingredient.amount)) * 100)) > -1;
		},
		RemoveIngredient(ingredient) {
			this.ingredients.splice(this.ingredients.indexOf(ingredient), 1);
		},
		SetItem(ingredient, item) {
			this.ingredients[this.ingredients.indexOf(ingredient)].item = item;
		},
		AddItem(ingredient, item) {
			this.SetItem(ingredient, item);
			ItemApi.Add(item.name).done(item => {
				this.items.push(item);
				this.items.sort(SortByName);
				this.SetItem(ingredient, item);
			}).fail(this.Error);
		},
		SetPrep(ingredient, prep) {
			this.ingredients[this.ingredients.indexOf(ingredient)].prep = prep;
		},
		AddPrep(ingredient, prep) {
			this.SetPrep(ingredient, prep);
			PrepApi.Add(prep.name, prep.description).done(prep => {
				this.preps.push(prep);
				this.preps.sort(SortByName);
				this.SetPrep(ingredient, prep);
			}).fail(this.Error);
		},
		Save() {
			this.editing = false;
			this.saving = true;
			const promise = this.recipe.id
				? RecipeApi.Update(this.recipe.id, { name: this.recipe.name, complexity: this.recipe.complexity, servings: this.recipe.servings, instructions: this.recipe.instructions })
				: RecipeApi.Add(this.recipe.name, this.recipe.complexity, this.recipe.servings, this.recipe.instructions);
			promise.done(recipe => {
				this.recipe = recipe;
				const cleanIng = [];
				for(const ing of this.ingredients)
					if(this.IngredientValid(ing))
						cleanIng.push({ amount: ing.amount, unit: ing.unit.id, item: ing.item.id, prep: ing.prep.id, sort: cleanIng.length + 1 });
				IngredientApi.SaveRecipe(this.recipe.id, cleanIng).done(ingredients => {
					this.ingredients = ingredients;
					if(this.params.id != this.recipe.id)
						location.hash = `#recipe!id=${this.recipe.id}`;
				}).fail(this.Error).always(() => {
					this.saving = false;
				});
			}).fail(err => {
				this.Error(err);
				this.saving = false;
			});
		}
	},
	mixins: [ReportError],
	components: {
		suggestWithAdd: SuggestWithAdd
	},
	directives: {
		validation: {
			bind(el, bind) {
				(ValidationElements.indexOf(el.nodeName.toLowerCase()) < 0
					? $(el).find(ValidationElements.join(","))[0]
					: el
				).setCustomValidity(bind.value.valid ? "" : bind.value.message);
			},
			update(el, bind) {
				(ValidationElements.indexOf(el.nodeName.toLowerCase()) < 0
					? $(el).find(ValidationElements.join(","))[0]
					: el
				).setCustomValidity(bind.value.valid ? "" : bind.value.message);
			}
		}
	},
	template: /*html*/ `
		<article class=recipe :class="{editing: editing}">
			<header v-if=!editing>
				<h1 :class="{loading: loading || saving}">{{recipe ? recipe.name : "Loading Recipe..."}}</h1>
				<button class=edit title="Edit this recipe" @click="editing = true"><span>edit</span></button>
			</header>
			<h1 v-if=editing class=singlelinefields>
				<label class=status :class=nameCheck.status :title=nameCheck.message>
					<input v-model.trim=recipe.name required placeholder="Recipe Title" v-validation="{valid: nameCheck.status != 'invalid', message: nameCheck.message}">
				</label>
			</h1>
			<ul class=facts v-if="hasFacts && !editing">
				<li v-if=recipe.lastServed class=lastServed :title="'Last served ' + recipe.lastServed">{{servedDaysAgo}} days ago</li>
				<li v-if=recipe.complexity class=complexity>{{complexityName}} prep</li>
				<li v-if=recipe.servings class=serves>serves {{recipe.servings}}</li>
			</ul>
			<section class="editfacts singlelinefields" v-if=editing>
				<label>
					<span class=label>Preparation:</span>
					<select v-model.number=this.recipe.complexity>
						<option value=0></option>
						<option value=1>${Complexity[1]}</option>
						<option value=2>${Complexity[2]}</option>
						<option value=3>${Complexity[3]}</option>
					</select>
				</label>
				<label>
					<span class=label>Servings:</span>
					<input type=number min=0 step=1 v-model.number=recipe.servings>
				</label>
			</section>
			<ul class="ingredients multifield" v-if=ingredients>
				<li v-if=!editing v-for="ingredient in ingredients">
					{{ingredient.displayAmount}}
					<template v-if=ingredient.unit.abbr>
						<abbr :title=ingredient.unit.name>{{ingredient.unit.abbr}}</abbr>
					</template>
					<template v-if=!ingredient.unit.abbr>{{ingredient.unit.name}}</template>
					{{ingredient.prep.name ? ingredient.item.name + ", " + ingredient.prep.name : ingredient.item.name}}
				</li>
				<li v-if=editing v-for="ingredient in ingredients">
					<label>
						<input class=amount type=number min=0 step=.01 required v-model.number=ingredient.amount v-validation="{valid: IngredientAmountValid(ingredient), message: 'Amount must be greater than zero and fractions must be ¼, ⅓, ½, ⅔, or ¾'}">
						<select class=unit required v-model=ingredient.unit v-validation="{valid: ingredient.unit.id, message: 'Select a unit'}">
							<option v-if=units v-for="unit in units" :value=unit>{{unit.abbr || unit.name}}</option>
						</select>
						<suggestWithAdd class=item :selection=ingredient.item :choices=items v-validation="{valid: ingredient.item.id, message: 'Select an item'}"
							@select="SetItem(ingredient, $event)" @add="AddItem(ingredient, $event)"></suggestWithAdd>
						<suggestWithAdd class=prep :selection=ingredient.prep :choices=preps
							@select="SetPrep(ingredient, $event)" @add="AddPrep(ingredient, $event)"></suggestWithAdd>
						<button class=del title="Remove this ingredient" @click=RemoveIngredient(ingredient)><span>del</span></button>
					</label>
				</li>
				<li v-if=editing><button class=add @click.prevent=AddIngredient>add another ingredient</button></li>
			</ul>
			<section class=instructions v-if="recipe && !editing" v-html=recipe.instructions></section>
			<label class="instructions singlelinefields" v-if="editing">
				<textarea v-model.trim=recipe.instructions></textarea>
			</label>
			<nav class=calltoaction v-if=editing><button class=save @click.prevent=Save :disabled=!valid>Save</button></nav>
		</article>
	`
}
