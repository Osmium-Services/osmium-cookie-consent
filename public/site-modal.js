(function () {
    var cookieName = (window.OsmiumCookieConsent && window.OsmiumCookieConsent.cookieName) || 'osmium_cookie_consent';

    function getConsentCookie() {
        var match = document.cookie.match(new RegExp('(?:^|; )' + cookieName + '=([^;]*)'));
        return match ? match[1] : null;
    }

    function setConsentCookie(value) {
        var maxAge = 365 * 24 * 60 * 60;
        document.cookie = cookieName + '=' + value + '; path=/; max-age=' + maxAge + '; SameSite=Lax';
    }

    function applyConsent(value) {
        setConsentCookie(value);
        document.dispatchEvent(new CustomEvent('osmium:consent', { detail: { value: value } })); // Services (Analytics, Meta Pixel, Clarity...) listen for this
    }

    var banner = document.getElementById('site-welcome-dialog');
    if (banner) {
        var bannerBackdrop = document.getElementById('site-welcome-backdrop');

        function dismissBanner() {
            banner.remove();
            if (bannerBackdrop) bannerBackdrop.remove();
        }

        var acceptBtn = document.getElementById('site-welcome-accept');
        var rejectBtn = document.getElementById('site-welcome-reject');

        if (acceptBtn) {
            acceptBtn.addEventListener('click', function () {
                applyConsent('accepted');
                dismissBanner();
            });
        }

        if (rejectBtn) {
            rejectBtn.addEventListener('click', function () {
                applyConsent('rejected');
                dismissBanner();
            });
        }
    }

    var ribbon = document.getElementById('site-status-toggle');
    var statusModal = document.getElementById('site-status-dialog');
    var statusBackdrop = document.getElementById('site-status-backdrop');
    var statusValue = document.getElementById('site-status-value');

    if (ribbon && statusModal) {
        function renderStatus() {
            var value = getConsentCookie();
            statusValue.textContent = value === 'accepted' ? 'accepted' : value === 'rejected' ? 'denied' : 'not yet set';
        }

        function openStatusModal() {
            renderStatus();
            statusModal.hidden = false;
            if (statusBackdrop) statusBackdrop.hidden = false;
        }

        function closeStatusModal() {
            statusModal.hidden = true;
            if (statusBackdrop) statusBackdrop.hidden = true;
        }

        ribbon.addEventListener('click', openStatusModal);
        if (statusBackdrop) statusBackdrop.addEventListener('click', closeStatusModal);

        var statusAcceptBtn = document.getElementById('site-status-accept');
        var statusRejectBtn = document.getElementById('site-status-reject');

        if (statusAcceptBtn) {
            statusAcceptBtn.addEventListener('click', function () {
                applyConsent('accepted');
                closeStatusModal();
            });
        }

        if (statusRejectBtn) {
            statusRejectBtn.addEventListener('click', function () {
                applyConsent('rejected');
                closeStatusModal();
            });
        }
    }
})();
