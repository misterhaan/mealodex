import AppName from "../appName.js";

export default {
	template: /*html*/ `
		<header id=titlebar>
			<img id=favicon src=favicon.svg alt="">
			<h2 id=sitetitle>${AppName.Full}</h2>
		</header>
`
}
