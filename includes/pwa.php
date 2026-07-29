<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Return PWA branding for the current website.
 *
 * @return array
 */
function estimator_framework_get_pwa_profile() {

    $host = function_exists('bid_pdx_get_site_hostname')
        ? bid_pdx_get_site_hostname()
        : '';

    $brand_name = function_exists(
        'bid_pdx_get_site_profile_value'
    )
        ? bid_pdx_get_site_profile_value(
            'brand_name',
            get_bloginfo('name')
        )
        : get_bloginfo('name');

    $brand_name = sanitize_text_field($brand_name);

    if ('' === $brand_name) {
        $brand_name = 'Estimator';
    }

    $defaults = [
        'app_name'         => 'Rebidco',
        'short_name'       => 'Rebidco',
        'description'      => 'Compare project estimates and connect with trusted professionals.',
        'theme_color'      => '#242424',
        'background_color' => '#ffffff',
        'orientation'      => 'portrait',
        'icon_prefix'      => 'rebidco',
        'cache_prefix'     => 'rebidco-pwa-',
    ];

    $profiles = [
        'rebidco.com' => [
            'app_name'         => 'Rebidco',
            'short_name'       => 'Rebidco',
            'description'      => 'Compare project estimates and connect with trusted professionals.',
            'theme_color'      => '#242424',
            'background_color' => '#ffffff',
            'orientation'      => 'portrait',
            'icon_prefix'      => 'rebidco',
            'cache_prefix'     => 'rebidco-pwa-',
        ],
    ];

    $profile = isset($profiles[$host])
        ? wp_parse_args($profiles[$host], $defaults)
        : $defaults;

    $profile['app_name'] = sanitize_text_field(
        $profile['app_name']
    );

    $profile['short_name'] = sanitize_text_field(
        $profile['short_name']
    );

    $profile['description'] = sanitize_text_field(
        $profile['description']
    );

    $profile['orientation'] = sanitize_key(
        $profile['orientation']
    );

    $profile['icon_prefix'] = sanitize_file_name(
        $profile['icon_prefix']
    );

    $profile['cache_prefix'] = sanitize_key(
        $profile['cache_prefix']
    );

    $theme_color = sanitize_hex_color(
        $profile['theme_color']
    );

    $background_color = sanitize_hex_color(
        $profile['background_color']
    );

    $profile['theme_color'] = $theme_color
        ? $theme_color
        : '#2563eb';

    $profile['background_color'] = $background_color
        ? $background_color
        : '#f8fafc';

    return $profile;
}

/**
 * Determine whether the current page is part of the estimator app.
 *
 * @return bool
 */
function estimator_framework_is_pwa_surface() {

    if (is_front_page()) {
        return true;
    }

    if (!is_singular()) {
        return false;
    }

    $post = get_queried_object();

    if (!$post instanceof WP_Post) {
        return false;
    }

    return has_shortcode(
        (string) $post->post_content,
        'bid_pdx_estimator'
    );
}

/**
 * Return a URL for a plugin-owned PWA resource.
 *
 * @param string $resource Resource identifier.
 *
 * @return string
 */
function estimator_framework_get_pwa_resource_url($resource) {

    return add_query_arg(
        'estimator_pwa_resource',
        sanitize_key($resource),
        home_url('/')
    );
}

/**
 * Serve the web app manifest and service worker.
 */
function estimator_framework_maybe_serve_pwa_resource() {

    if (
        !isset($_GET['estimator_pwa_resource']) ||
        !is_string($_GET['estimator_pwa_resource'])
    ) {
        return;
    }

    $resource = sanitize_key(
        wp_unslash($_GET['estimator_pwa_resource'])
    );

    if (
        'manifest' !== $resource &&
        'service-worker' !== $resource
    ) {
        return;
    }

    $profile = estimator_framework_get_pwa_profile();
    $app_url = trailingslashit(home_url('/'));

    $scope_path = wp_parse_url(
        $app_url,
        PHP_URL_PATH
    );

    if (
        !is_string($scope_path) ||
        '' === $scope_path
    ) {
        $scope_path = '/';
    }

    $scope_path = trailingslashit($scope_path);

    status_header(200);
    nocache_headers();

    header('X-Content-Type-Options: nosniff');
    header('X-Robots-Tag: noindex, nofollow');

    if ('manifest' === $resource) {
        $icon_base = ESTIMATOR_FRAMEWORK_URL
            . 'assets/icons/'
            . $profile['icon_prefix'];

        $manifest = [
            'id'               => $app_url,
            'name'             => $profile['app_name'],
            'short_name'       => $profile['short_name'],
            'description'      => $profile['description'],
            'lang'             => get_bloginfo('language'),
            'start_url'        => $app_url,
            'scope'            => $app_url,
            'display'          => 'standalone',
            'orientation'      => $profile['orientation'],
            'background_color' => $profile['background_color'],
            'theme_color'      => $profile['theme_color'],
            'icons'            => [
                [
                    'src'     => $icon_base . '-192.png',
                    'sizes'   => '192x192',
                    'type'    => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src'     => $icon_base . '-512.png',
                    'sizes'   => '512x512',
                    'type'    => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ];

        header(
            'Content-Type: application/manifest+json; charset=utf-8'
        );

        echo wp_json_encode(
            $manifest,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    header(
        'Content-Type: application/javascript; charset=utf-8'
    );

    header(
        'Service-Worker-Allowed: ' . $scope_path
    );

    $cache_prefix = $profile['cache_prefix'];

    $cache_name = $cache_prefix
        . ESTIMATOR_FRAMEWORK_VERSION;

    $service_worker = sprintf(
        <<<'JS'
const CACHE_NAME = %s;
const CACHE_PREFIX = %s;
const APP_HOME = %s;

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function (cache) {
                return cache.add(
                    new Request(
                        APP_HOME,
                        {
                            cache: 'reload'
                        }
                    )
                );
            })
            .catch(function () {
                return undefined;
            })
            .then(function () {
                return self.skipWaiting();
            })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys()
            .then(function (keys) {
                return Promise.all(
                    keys.map(function (key) {
                        if (
                            key.indexOf(CACHE_PREFIX) === 0 &&
                            key !== CACHE_NAME
                        ) {
                            return caches.delete(key);
                        }

                        return undefined;
                    })
                );
            })
            .then(function () {
                return self.clients.claim();
            })
    );
});

self.addEventListener('fetch', function (event) {
    if (event.request.method !== 'GET') {
        return;
    }

    const requestUrl = new URL(event.request.url);

    if (requestUrl.origin !== self.location.origin) {
        return;
    }

    if (
        requestUrl.pathname.indexOf('/wp-admin/') !== -1 ||
        requestUrl.pathname.indexOf('/wp-login.php') !== -1
    ) {
        return;
    }

    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then(function (response) {
                    if (response && response.ok) {
                        const responseCopy = response.clone();

                        caches.open(CACHE_NAME)
                            .then(function (cache) {
                                cache.put(
                                    event.request,
                                    responseCopy
                                );
                            });
                    }

                    return response;
                })
                .catch(function () {
                    return caches.match(event.request)
                        .then(function (cachedResponse) {
                            if (cachedResponse) {
                                return cachedResponse;
                            }

                            return caches.match(APP_HOME);
                        });
                })
        );

        return;
    }

    if (
        event.request.destination === 'style' ||
        event.request.destination === 'script' ||
        event.request.destination === 'image' ||
        event.request.destination === 'font'
    ) {
        event.respondWith(
            caches.match(event.request)
                .then(function (cachedResponse) {
                    const networkResponse = fetch(event.request)
                        .then(function (response) {
                            if (response && response.ok) {
                                const responseCopy =
                                    response.clone();

                                caches.open(CACHE_NAME)
                                    .then(function (cache) {
                                        cache.put(
                                            event.request,
                                            responseCopy
                                        );
                                    });
                            }

                            return response;
                        })
                        .catch(function () {
                            if (cachedResponse) {
                                return cachedResponse;
                            }

                            return Response.error();
                        });

                    return cachedResponse || networkResponse;
                })
        );
    }
});
JS,
        wp_json_encode($cache_name),
        wp_json_encode($cache_prefix),
        wp_json_encode($app_url)
    );

    echo $service_worker;

    exit;
}

add_action(
    'template_redirect',
    'estimator_framework_maybe_serve_pwa_resource',
    0
);

/**
 * Print site-specific PWA metadata.
 */
function estimator_framework_render_pwa_head() {

    if (!estimator_framework_is_pwa_surface()) {
        return;
    }

    $profile = estimator_framework_get_pwa_profile();

    $manifest_url =
        estimator_framework_get_pwa_resource_url(
            'manifest'
        );

    $apple_icon_url =
        ESTIMATOR_FRAMEWORK_URL
        . 'assets/icons/'
        . $profile['icon_prefix']
        . '-180.png';
    ?>

    <link
        rel="manifest"
        href="<?php echo esc_url($manifest_url); ?>"
    >

    <link
        rel="apple-touch-icon"
        sizes="180x180"
        href="<?php echo esc_url($apple_icon_url); ?>"
    >

    <meta
        name="theme-color"
        content="<?php
            echo esc_attr($profile['theme_color']);
        ?>"
    >

    <meta
        name="mobile-web-app-capable"
        content="yes"
    >

    <meta
        name="apple-mobile-web-app-capable"
        content="yes"
    >

    <meta
        name="apple-mobile-web-app-status-bar-style"
        content="default"
    >

    <meta
        name="apple-mobile-web-app-title"
        content="<?php
            echo esc_attr($profile['short_name']);
        ?>"
    >

    <?php
}

add_action(
    'wp_head',
    'estimator_framework_render_pwa_head',
    1
);

/**
 * Register the site-specific service worker.
 */
function estimator_framework_enqueue_pwa_script() {

    if (!estimator_framework_is_pwa_surface()) {
        return;
    }

    wp_enqueue_script(
        'estimator-framework-pwa',
        ESTIMATOR_FRAMEWORK_URL
            . 'assets/js/pwa.js',
        [],
        ESTIMATOR_FRAMEWORK_VERSION,
        true
    );

    wp_localize_script(
        'estimator-framework-pwa',
        'estimatorFrameworkPwa',
        [
            'serviceWorkerUrl' =>
                estimator_framework_get_pwa_resource_url(
                    'service-worker'
                ),
            'serviceWorkerScope' =>
                trailingslashit(home_url('/')),
        ]
    );
}

add_action(
    'wp_enqueue_scripts',
    'estimator_framework_enqueue_pwa_script',
    20
);
