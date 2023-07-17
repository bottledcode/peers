async function logout() {
    await Clerk.signOut();
    window.location.href = "/logout";
}
