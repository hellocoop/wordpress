async function navigateToHelloAuthRequestUrl(api_url_str, redirect_to_path) {
	try {
		if (!redirect_to_path) {
			redirect_to_path = location.pathname + location.search
		}

		api_url = new URL(api_url_str)
		api_url.searchParams.append('redirect_to_path', redirect_to_path)
		api_url.searchParams.append('cache_buster', new Date().getTime())

		const res = await fetch(api_url.href)

		const json = await res.json()
		window.location.href = json['url']
	} catch (err) {
		console.error(err)
	}
}
