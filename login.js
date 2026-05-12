
const FAKE_USER = {
  email: "test@test.com",
  password: "1234"
};

document.querySelector("form").addEventListener("submit", function(e) {
  e.preventDefault();

  const email    = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value;


  if (email === FAKE_USER.email && password === FAKE_USER.password) {
    localStorage.setItem("isLoggedIn", "true");
    localStorage.setItem("userEmail", email);

    alert("Connexion reussie !");
    window.location.href = "page_compte.html";
  } else {
    alert("Email ou mot de passe incorrect.");
  }
});