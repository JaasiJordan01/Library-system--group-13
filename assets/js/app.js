document.addEventListener('submit', function (event) {
    var form = event.target;
    var message = form.getAttribute('data-confirm');

    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

document.addEventListener('click', function (event) {
    var menuToggle = event.target.closest('[data-menu-toggle]');
    if (menuToggle) {
        var sidebar = menuToggle.closest('.sidebar');
        var isExpanded = menuToggle.getAttribute('aria-expanded') === 'true';
        var menuLabel = menuToggle.querySelector('[data-menu-label]');

        if (sidebar) {
            sidebar.classList.toggle('is-menu-open', !isExpanded);
            menuToggle.setAttribute('aria-expanded', String(!isExpanded));
            if (menuLabel) {
                menuLabel.textContent = isExpanded ? 'Menu' : 'Close';
            }
        }
        return;
    }

    var toggle = event.target.closest('[data-toggle-password]');
    if (!toggle) {
        return;
    }

    var input = document.getElementById(toggle.getAttribute('data-toggle-password'));
    if (!input) {
        return;
    }

    var visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    toggle.textContent = visible ? 'Hide' : 'Show';
    toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
});

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') {
        return;
    }

    var sidebar = document.querySelector('.sidebar.is-menu-open');
    var menuToggle = sidebar && sidebar.querySelector('[data-menu-toggle]');
    if (sidebar && menuToggle) {
        sidebar.classList.remove('is-menu-open');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.querySelector('[data-menu-label]').textContent = 'Menu';
        menuToggle.focus();
    }
});