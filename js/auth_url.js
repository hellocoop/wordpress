async function navigateToHelloAuthRequestUrl(redirect_to_path) {
	try {
		if (!redirect_to_path) {
			redirect_to_path = location.pathname + location.search;
		}

		const res = await fetch('/wp-json/hello-login/v1/auth_url?redirect_to_path=' + encodeURIComponent(redirect_to_path))
		const json = await res.json()
		window.location.href = json['url']
	} catch (err) {
		console.error(err)
	}
}
