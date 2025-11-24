document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.querySelector(".login-form");
    const usernameInput = document.getElementById("username");
    const passwordInput = document.getElementById("password");

    // Hardcoded credentials
    const correctUsername = "rex";
    const correctPassword = "123";

    // Attach single event listener
    loginForm.addEventListener("submit", (event) => {
        event.preventDefault();

        const enteredUsername = usernameInput.value.trim();
        const enteredPassword = passwordInput.value;

        if (enteredUsername === correctUsername && enteredPassword === correctPassword) {
            // Redirect if credentials are correct
            window.location.href = "/admin/dashboard";
        } else {
            alert("Incorrect username or password.");
        }
    });
});
