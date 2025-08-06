document.addEventListener("DOMContentLoaded", () => {
    const accountDiv = document.getElementById('account');
    if (typeof username === 'undefined') {
        accountDiv.innerHTML = `<a href="login.php" class="nav-link text-white" role="button">Log In</a>`;
    } else {
        let adminHTML = ""
        if (typeof ADMIN !== 'undefined') {
            adminHTML = `<li><a class="dropdown-item" href="admin.php">Admin</a></li>`;
        }
        accountDiv.innerHTML = `<a href="#" class="nav-link dropdown-toggle text-white" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    ${username}
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    ${adminHTML}
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                </ul>`;
    }
});