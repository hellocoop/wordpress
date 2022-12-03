async function navigateToHelloAuthRequestUrl() {
	try {
		const res = await fetch('/wp-json/hello-login/v1/auth_url?redirect_to_path=' + encodeURIComponent(location.pathname+location.search))
		const json = await res.json()
		window.location.href = json['url']
	} catch (err) {
		console.error(err)
	}
}
