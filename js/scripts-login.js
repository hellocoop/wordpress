let loginFormRef;
let toggleButtonRef;
const toggleText = {
	unhidden: "Show username and password login",
	hidden: "Hide username and password login"
}

window.onload = () => {
	loginFormRef = document.querySelector("#loginform") //save reference to the login form
	loginFormRef.style.display = "none" //hide login form
	toggleButtonRef = document.querySelector("#login-form-toggle") //save reference to the toggle button
	toggleButtonRef.textContent = toggleText.unhidden //populate toggle button with text
}

function toggleLoginForm(){
	const isHidden = loginFormRef.style.display === "none"   //gets display state of login form - hidden or visible
	if(isHidden) {
		loginFormRef.style.display = "block"
		toggleButtonRef.textContent = toggleText.hidden
	} else {
		loginFormRef.style.display = "none"
		toggleButtonRef.textContent = toggleText.unhidden
	}
}
