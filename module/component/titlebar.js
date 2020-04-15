export default {
	props: [
		"title"
	],
	template: /*html*/ `
		<header id=titlebar>
			<img id=favicon src=favicon.svg alt="">
			<h2 id=sitetitle>{{title}}</h2>
		</header>
`
}
