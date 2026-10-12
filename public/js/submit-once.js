/**
 * Forms marked data-submit-once can only be sent once.
 *
 * Logging in replaces the session, so a second click (common when the server is
 * slow) arrives with a session that no longer exists and fails with "page
 * expired", even though the first click logged the user in.
 */
(function () {
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.hasAttribute || !form.hasAttribute('data-submit-once')) return;
        if (form.dataset.sending === '1') {
            e.preventDefault();
            return;
        }
        form.dataset.sending = '1';
        form.querySelectorAll('button[type="submit"], button:not([type])').forEach(function (button) {
            button.dataset.label = button.innerHTML;
            button.disabled = true;
            button.textContent = button.getAttribute('data-sending-text') || 'Please wait…';
        });
    });

    // Back button restores the page from cache with the button still disabled
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form[data-submit-once]').forEach(function (form) {
            delete form.dataset.sending;
            form.querySelectorAll('button[disabled]').forEach(function (button) {
                if (button.dataset.label) button.innerHTML = button.dataset.label;
                button.disabled = false;
            });
        });
    });
})();
