# Estimator Framework Plugin Instructions

## Scope and Architecture

This repository is the Rebidco WordPress estimator plugin. `estimator-framework.php` defines version constants, loads modules, enqueues frontend CSS, and registers activation. Keep domain and database logic in `includes/`, dashboard pages in `admin/`, rendered markup in `templates/`, and browser assets in `assets/`.

Key modules include manager classes for projects, categories, items, and leads; schema installation and version upgrades; default-data installers; lead and contractor actions; the Rebidco site profile; shortcode rendering; and PWA resources. The public entry point is `[bid_pdx_estimator]`, optionally with `project_id`.

The existing `bid_pdx_`, `Bid_PDX_*`, and `bid-pdx-` names are established plugin interfaces despite the Rebidco branding; do not rename them or import behavior or assets from another site casually. Estimator markup, interactions, `assets/css/frontend.css`, icons, and the intro background belong to this plugin, not the theme.

## Commands and Validation

No Composer/npm manifest, build script, lint configuration, automated tests, or coverage requirement is tracked. Do not invent a repository command or claim automated coverage. Validate relevant behavior in a WordPress environment and record what was checked.

For applicable changes, manually check activation and database upgrades; admin project/category/item/lead/settings flows; default and `project_id` shortcode routes; conditional choices and estimate totals; lead and contractor submissions; Rebidco profile rendering; responsive UI; and manifest, service-worker, and offline fallback behavior.

## Conventions and Safety

Match the existing four-space PHP/JavaScript indentation and multiline layout. Use prefixed `snake_case` functions (`bid_pdx_` or `estimator_framework_`), `Bid_PDX_*` classes, `class-*.php` files, and `bid-pdx-` frontend selectors. Sanitize and unslash request input, escape output at rendering boundaries, verify nonces and capabilities, and prepare SQL.

Keep the plugin header version and `ESTIMATOR_FRAMEWORK_VERSION` synchronized. Treat schema, upgrade, and default-data edits as data-sensitive: the installers run from WordPress hooks, and the master installer replaces categories and options for supported project slugs. Preserve installed data and customer leads, and verify upgrade or installer behavior away from production before approval. Follow the workspace safety and reporting rules in the root `AGENTS.md`.
