import HighlightString from "../highlightString.js";

export default {
	props: [
		"choices",
		"selection"
	],
	data() {
		return {
			value: "",
			showSuggestions: false,
			cursor: false
		};
	},
	computed: {
		suggestions() {
			const search = this.value ? this.value.trim().toLowerCase() : "";
			if(search) {
				let exact = { name: search, highlightName: HighlightString(search, search) };
				const startsWith = [];
				const contains = [];
				for(const choice of this.choices) {
					const name = choice.name.toLowerCase();
					if(name === search)
						exact = { highlightName: HighlightString(name, search), ...choice };
					else {
						const pos = name.indexOf(search);
						if(pos === 0)
							startsWith.push({ highlightName: HighlightString(name, search), ...choice });
						else if(pos > 0)
							contains.push({ highlightName: HighlightString(name, search), ...choice });
					}
				}
				return [exact, ...startsWith, ...contains];
			} else
				return this.choices;
		}
	},
	watch: {
		selection(selection) {
			this.value = selection.name;
		}
	},
	created() {
		this.value = this.selection.name;
	},
	methods: {
		SearchInput(event) {
			this.value = event.target.value;
			this.showSuggestions = true;
		},
		SearchBlur() {
			this.value = this.selection.name;
			this.HideSuggestions;
		},
		HideSuggestions(event) {
			if(this.showSuggestions) {
				if(event)
					event.stopPropagation();
				this.showSuggestions = false;
				this.cursor = false;
			}
		},
		Previous() {
			if(this.suggestions.length) {
				this.showSuggestions = true;
				if(this.cursor) {
					let prev = false;
					for(const suggestion of this.suggestions)
						if(suggestion == this.cursor) {
							this.cursor = prev || this.suggestions[this.suggestions.length - 1];
							return;
						} else
							prev = suggestion;
				}
				this.cursor = this.suggestions[this.suggestions.length - 1];
			}
		},
		Next() {
			if(this.suggestions.length) {
				this.showSuggestions = true;
				if(this.cursor) {
					let found = false;
					for(const suggestion of this.suggestions)
						if(suggestion == this.cursor)
							found = true;
						else if(found) {
							this.cursor = suggestion;
							return;
						}
				}
				this.cursor = this.suggestions[0];
			}
		},
		Select(suggestion) {
			if(suggestion) {
				const value = { ...suggestion };
				delete value.highlightName;
				if(value.id)
					this.$emit("select", value);
				else
					this.$emit("add", value);
				this.value = value.name;
				this.HideSuggestions();
			}
		}
	},
	template: /*html*/ `
		<span class=suggestWithAdd>
			<input :value=value @input=SearchInput @dblclick="showSuggestions = true"
				@keydown.esc=HideSuggestions @blur=SearchBlur
				@keydown.up=Previous @keydown.down=Next
				@keydown.enter.stop=Select(cursor) @keydown.tab=Select(cursor)>
			<ol class=suggestions v-if=showSuggestions>
				<li v-for="suggest in suggestions" @mousedown.prevent=Select(suggest)
					:class="{add: !suggest.id, cursor: cursor == suggest}"
					v-html="suggest.highlightName || suggest.name"></li>
			</ol>
		</span>
	`
}
