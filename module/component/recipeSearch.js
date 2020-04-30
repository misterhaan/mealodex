import ReportError from "../reportError.js";
import RecipeApi from "../api/recipe.js";

const BlurDelay = 250;

function HighlightString(str, search) {
	const html = $("<div/>").text(str).html();
	return search ? html.replace(new RegExp("(" + EscapeRegExp(search) + ")", "ig"), "<em>$1</em>") : html;
}

function EscapeRegExp(str) {
	return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
}

export default {
	data() {
		return {
			showSuggestions: false,
			menu: [],
			searchText: "",
			cursor: false
		};
	},
	computed: {
		recipeSuggestions() {
			const search = this.searchText ? this.searchText.trim().toLowerCase() : "";
			if(search) {
				const startsWith = [];
				const contains = [];
				for(const recipe of this.menu) {
					const match = recipe.name.toLowerCase().indexOf(search);
					if(match === 0)
						startsWith.push({ name: HighlightString(recipe.name, search), id: recipe.id });
					else if(match > 0)
						contains.push({ name: HighlightString(recipe.name, search), id: recipe.id });
				}
				return [...startsWith, ...contains];
			} else
				return this.menu.map(r => { return { name: r.name, id: r.id }; });
		}
	},
	created() {
		RecipeApi.List().done(list => {
			this.menu = list
		}).fail(this.Error);
	},
	methods: {
		SearchInput(event) {
			this.searchText = event.target.value;
			this.showSuggestions = true;
		},
		SearchBlur() {
			setTimeout(this.HideSuggestions, BlurDelay);
		},
		HideSuggestions(event) {
			if(this.showSuggestions) {
				if(event)
					event.stopPropagation();
				this.showSuggestions = false;
				this.cursor = false;
			}
		},
		ViewRecipe(id) {
			this.HideSuggestions();
			this.searchText = "";
			location.hash = "#recipe!id=" + id;
			$(":focus").blur();
		},
		Previous() {
			if(this.recipeSuggestions.length) {
				this.showSuggestions = true;
				if(this.cursor) {
					let prev = false;
					for(const recipe of this.recipeSuggestions)
						if(recipe.id == this.cursor) {
							this.cursor = prev ? prev.id : this.recipeSuggestions[this.recipeSuggestions.length - 1].id;
							return;
						} else
							prev = recipe;
				}
				this.cursor = this.recipeSuggestions[this.recipeSuggestions.length - 1].id;
			}
		},
		Next() {
			if(this.recipeSuggestions.length) {
				this.showSuggestions = true;
				if(this.cursor) {
					let found = false;
					for(const recipe of this.recipeSuggestions)
						if(recipe.id == this.cursor)
							found = true;
						else if(found) {
							this.cursor = recipe.id;
							return;
						}
				}
				this.cursor = this.recipeSuggestions[0].id;
			}
		}
	},
	mixins: [
		ReportError
	],
	template: /*html*/ `
		<label id=recipesearch title="Find a recipe">
			<input type=search placeholder="Find a recipe" @input=SearchInput :value=searchText
				@dblclick="showSuggestions = true"
				@keydown.esc=HideSuggestions @blur=SearchBlur
				@keydown.up=Previous @keydown.down=Next
				@keydown.enter.stop=ViewRecipe(cursor) maxlength=64>
			<ol class=suggestions v-if=showSuggestions>
				<li class=choice v-for="recipe in recipeSuggestions" v-html=recipe.name
					:class="{cursor: recipe.id == cursor}" @click.prevent=ViewRecipe(recipe.id)></li>
			</ol>
		</label>
	`
}
