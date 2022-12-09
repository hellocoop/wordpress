window.addEventListener("DOMContentLoaded", () => {
	const loginFormRef = document.querySelector("#loginform")
	const loginFormInitialHeight = getComputedStyle(loginFormRef).height
	const toggleBtnEle = document.createElement("button")
	toggleBtnEle.id = "toggle-form"
	const toggleBtnTextEle = document.createElement("span")
	toggleBtnTextEle.textContent = "Continue with username or email"
	const toggleBtnDropdownEle = document.createElement("span")
	toggleBtnDropdownEle.style.position = "absolute"
	toggleBtnDropdownEle.style.right = 0
	toggleBtnDropdownEle.style.top = 0
	toggleBtnDropdownEle.innerHTML =
	`<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; opacity: 0.6;">
		<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
	</svg>`
	toggleBtnEle.append(toggleBtnTextEle, toggleBtnDropdownEle)
	loginFormRef.prepend(toggleBtnEle)

	//move lost your password link inside continue with username form
	const lostPasswordRef = document.querySelector("#login #nav")
	loginFormRef.appendChild(lostPasswordRef)
	lostPasswordRef.style.display = "block"

	let isCollapsed = true

	toggleBtnEle.addEventListener("click", (e) => {
		e.preventDefault()
	
		loginFormRef.style.height = isCollapsed ? "270px" : loginFormInitialHeight
		for(const child of loginFormRef.children) {
			if(child.id === "toggle-form") continue
			child.style.visibility = isCollapsed ? "visible" : "hidden"
		}

		toggleBtnDropdownEle.style.rotate = isCollapsed ? "180deg" : "0deg"
		toggleBtnDropdownEle.style.top = isCollapsed ? "-4px" : 0 //margin correction

		isCollapsed = !isCollapsed
	})
});
