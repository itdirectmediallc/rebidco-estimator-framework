(function () {
    'use strict';

    var config = window.estimatorFrameworkPwa || {};

    if (
        !('serviceWorker' in navigator) ||
        !config.serviceWorkerUrl
    ) {
        return;
    }

    window.addEventListener(
        'load',
        function () {
            navigator.serviceWorker.register(
                config.serviceWorkerUrl,
                {
                    scope:
                        config.serviceWorkerScope || '/'
                }
            ).catch(function (error) {
                console.error(
                    'Estimator Framework service worker registration failed.',
                    error
                );
            });
        }
    );
})();
