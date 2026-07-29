<?php

if (!defined('ABSPATH')) {
    exit;
}

$estimator_id = 'bid-pdx-estimator-' . wp_rand(1000, 9999);

$project_cards = isset($project_cards) && is_array($project_cards)
    ? $project_cards
    : [];

$all_projects_estimator_data = isset($all_projects_estimator_data) && is_array($all_projects_estimator_data)
    ? $all_projects_estimator_data
    : [];

$trade_priority = [
    'kitchen remodel'  => 1,
    'bathroom remodel' => 2,
    'basement remodel' => 3,
    'basement'         => 3,
    'home addition'    => 4,
    'addition'         => 4,
];

foreach ($project_cards as $project_index => $project_card) {
    $project_cards[$project_index]['_original_index'] = $project_index;
}

usort($project_cards, function ($first_project, $second_project) use ($trade_priority) {
    $first_name  = isset($first_project['name']) ? strtolower(trim($first_project['name'])) : '';
    $second_name = isset($second_project['name']) ? strtolower(trim($second_project['name'])) : '';

    $first_priority  = isset($trade_priority[$first_name]) ? $trade_priority[$first_name] : 999;
    $second_priority = isset($trade_priority[$second_name]) ? $trade_priority[$second_name] : 999;

    if ($first_priority === $second_priority) {
        $first_index  = isset($first_project['_original_index']) ? intval($first_project['_original_index']) : 0;
        $second_index = isset($second_project['_original_index']) ? intval($second_project['_original_index']) : 0;

        return $first_index <=> $second_index;
    }

    return $first_priority <=> $second_priority;
});

foreach ($project_cards as $project_index => $project_card) {
    unset($project_cards[$project_index]['_original_index']);
}

$projects_by_id = [];

foreach ($all_projects_estimator_data as $project_data) {
    if (!empty($project_data['project']['id'])) {
        $projects_by_id[intval($project_data['project']['id'])] = $project_data;
    }
}

$has_projects = !empty($project_cards);
$show_view_all_trades_button = count($project_cards) > 4;

$site_profile = bid_pdx_get_site_profile();
$brand_name   = $site_profile['brand_name'];
$site_class   = 'bid-pdx-site-' . sanitize_html_class(sanitize_title($brand_name));

?>

<div
    id="<?php echo esc_attr($estimator_id); ?>"
    class="bid-pdx-estimator <?php echo esc_attr($site_class); ?>"
    data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
    data-nonce="<?php echo esc_attr(wp_create_nonce('bid_pdx_submit_lead')); ?>"
>

    <link
        rel="stylesheet"
        href="<?php echo esc_url(BID_PDX_URL . 'assets/css/frontend.css?ver=' . BID_PDX_VERSION); ?>"
        media="all"
    >

    <div class="bid-pdx-intro-screen" style="background-image: url('<?php echo esc_url($site_profile['intro_background_url']); ?>');">
        <div class="bid-pdx-intro-inner">
            <h1 class="bid-pdx-intro-title"><?php echo esc_html($brand_name); ?></h1>
            <div class="bid-pdx-intro-line"></div>
            <h2 class="bid-pdx-intro-subtitle">
                <?php if ($site_profile['intro_line_1'] !== '') : ?><span><?php echo esc_html($site_profile['intro_line_1']); ?></span><?php endif; ?>
                <?php if ($site_profile['intro_line_2'] !== '') : ?><span><?php echo esc_html($site_profile['intro_line_2']); ?></span><?php endif; ?>
                <?php if ($site_profile['intro_line_3'] !== '') : ?><span><?php echo esc_html($site_profile['intro_line_3']); ?></span><?php endif; ?>
            </h2>

            <?php if ($site_profile['service_area'] !== '') : ?>
                <p class="bid-pdx-intro-location"><?php echo esc_html($site_profile['service_area']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <header class="bid-pdx-brand-header" aria-label="<?php echo esc_attr($brand_name . ' header'); ?>">
            <a class="bid-pdx-brand-link" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr($brand_name . ' home'); ?>">
                <span class="bid-pdx-brand-name"><?php echo esc_html($brand_name); ?></span>
            </a>

            <button
                type="button"
                class="bid-pdx-pros-open"
                aria-haspopup="dialog"
                aria-controls="<?php echo esc_attr($estimator_id); ?>-pros-modal"
            >
                Pros
            </button>
        </header>

    <div class="bid-pdx-main-content">




        <?php if (!$has_projects) : ?>

            <div class="bid-pdx-empty">
                <strong>Estimator setup is not complete yet.</strong>
                <br>
                Please add active projects in the Estimator admin area.
            </div>

        <?php else : ?>

            <div class="bid-pdx-project-screen">

                <h2>What are you planning?</h2>

                <p>
                    Choose your project type first.
                </p>

                <div class="bid-pdx-project-grid">

                    <?php foreach ($project_cards as $project_index => $project_card) : ?>

                        <button
                            type="button"
                            class="bid-pdx-project-card<?php echo $project_index >= 4 ? ' bid-pdx-project-card-extra' : ''; ?>"
                            data-project-id="<?php echo esc_attr($project_card['id']); ?>"
                        >

                            <h3><?php echo esc_html($project_card['name']); ?></h3>

                            <p>
                                <?php if (!empty($project_card['description'])) : ?>
                                    <?php echo esc_html($project_card['description']); ?>
                                <?php else : ?>
                                    Get a budget range for your project.
                                <?php endif; ?>
                            </p>

                        </button>

                    <?php endforeach; ?>

                </div>

                <?php if ($show_view_all_trades_button) : ?>

                    <div class="bid-pdx-view-all-wrap">
                        <button
                            type="button"
                            class="bid-pdx-view-all-trades"
                            aria-expanded="false"
                        >
                            View All Trades
                        </button>
                    </div>

                <?php endif; ?>

            </div>

            <div class="bid-pdx-estimator-screen">

                <?php foreach ($project_cards as $project_card) : ?>

                    <?php
                    $project_id = intval($project_card['id']);
                    $project_data = isset($projects_by_id[$project_id]) ? $projects_by_id[$project_id] : null;

                    $has_project_estimator = !empty($project_data)
                        && !empty($project_data['project'])
                        && !empty($project_data['categories']);

                    $category_count = $has_project_estimator ? count($project_data['categories']) : 0;
                    ?>

                    <div
                        class="bid-pdx-project-estimator"
                        data-project-id="<?php echo esc_attr($project_id); ?>"
                        data-project-name="<?php echo esc_attr($project_card['name']); ?>"
                    >

                        <?php if (!$has_project_estimator) : ?>

                            <div class="bid-pdx-estimator-header">

                                <h2>
                                    <?php echo esc_html($project_card['name']); ?>
                                </h2>

                            </div>

                            <div class="bid-pdx-empty">
                                <strong>This estimator is not set up yet.</strong>
                                <br>
                                Please add categories and estimator options for <?php echo esc_html($project_card['name']); ?> in the Estimator admin area.
                            </div>

                        <?php else : ?>

                            <div class="bid-pdx-estimator-header">

                                <h2>
                                    <?php echo esc_html($project_data['project']['name']); ?>
                                </h2>

                            </div>

                            <div class="bid-pdx-progress-wrap">

                                <div class="bid-pdx-progress-top">
                                    <span class="bid-pdx-progress-step">Step 1 of <?php echo esc_html($category_count); ?></span>
                                    <span class="bid-pdx-progress-category"></span>
                                </div>

                                <div class="bid-pdx-progress-bar">
                                    <div class="bid-pdx-progress-fill"></div>
                                </div>

                            </div>

                            <div class="bid-pdx-step-screen">

                                <div class="bid-pdx-options">

                                    <?php foreach ($project_data['categories'] as $index => $category) : ?>

                                        <section
                                            class="bid-pdx-category bid-pdx-category-step"
                                            data-step-index="<?php echo esc_attr($index); ?>"
                                            data-category-id="<?php echo esc_attr($category['id']); ?>"
                                            data-category-name="<?php echo esc_attr($category['name']); ?>"
                                            data-selection-type="<?php echo esc_attr($category['selection_type']); ?>"
                                            data-condition-category-id="<?php echo esc_attr($category['condition_category_id']); ?>"
                                            data-condition-item-ids="<?php echo esc_attr(implode(',', array_map('intval', $category['condition_item_ids']))); ?>"
                                            data-condition-match="<?php echo esc_attr($category['condition_match']); ?>"
                                        >

                                            <h3><?php echo esc_html($category['name']); ?></h3>

                                            <p class="bid-pdx-category-helper">
                                                <?php echo esc_html($category['selection_type'] === 'multiple' ? 'Choose any that apply' : 'Choose one'); ?>
                                            </p>

                                            <?php if (!empty($category['description'])) : ?>
                                                <p class="bid-pdx-category-description">
                                                    <?php echo esc_html($category['description']); ?>
                                                </p>
                                            <?php endif; ?>

                                            <div class="bid-pdx-option-grid">

                                                <?php foreach ($category['items'] as $item) : ?>

                                                    <label
                                                        class="bid-pdx-option"
                                                        data-condition-category-id="<?php echo esc_attr($item['condition_category_id']); ?>"
                                                        data-condition-item-ids="<?php echo esc_attr(implode(',', array_map('intval', $item['condition_item_ids']))); ?>"
                                                        data-condition-match="<?php echo esc_attr($item['condition_match']); ?>"
                                                    >

                                                        <input
                                                            type="<?php echo esc_attr($category['selection_type'] === 'multiple' ? 'checkbox' : 'radio'); ?>"
                                                            name="bid_pdx_project_<?php echo esc_attr($project_id); ?>_category_<?php echo esc_attr($category['id']); ?>"
                                                            class="bid-pdx-option-input"
                                                            value="<?php echo esc_attr($item['id']); ?>"
                                                            data-name="<?php echo esc_attr($item['name']); ?>"
                                                            data-min="<?php echo esc_attr($item['price_min']); ?>"
                                                            data-max="<?php echo esc_attr($item['price_max']); ?>"
                                                        >

                                                        <span class="bid-pdx-option-name">
                                                            <?php echo esc_html($item['name']); ?>
                                                        </span>

                                                        <?php if (!empty($item['description'])) : ?>
                                                            <span class="bid-pdx-option-description">
                                                                <?php echo esc_html($item['description']); ?>
                                                            </span>
                                                        <?php endif; ?>

                                                    </label>

                                                <?php endforeach; ?>

                                            </div>

                                        </section>

                                    <?php endforeach; ?>

                                    <div class="bid-pdx-form-message bid-pdx-step-message"></div>

                                    <div class="bid-pdx-step-actions">

                                        <button type="button" class="bid-pdx-step-button bid-pdx-step-prev">
                                            Back
                                        </button>

                                        <button type="button" class="bid-pdx-step-button bid-pdx-step-next">
                                            Next
                                        </button>

                                    </div>

                                </div>

                            </div>

                            <div class="bid-pdx-review-screen">

                                <div class="bid-pdx-review-layout">

                                    <div class="bid-pdx-review-card">

                                        <h3>Your Estimated Cost</h3>

                                        <div class="bid-pdx-total">
                                            $0 - $0
                                        </div>

                                        <p class="bid-pdx-summary-note">
                                            This is an estimated budget range. A contractor will provide a detailed estimate after reviewing your project.
                                        </p>

                                        <h3>Selected Options</h3>

                                        <ul class="bid-pdx-selected-list">
                                            <li>No options selected yet.</li>
                                        </ul>

                                        <button type="button" class="bid-pdx-edit-selections">
                                            Edit Selections
                                        </button>

                                    </div>

                                    <div class="bid-pdx-review-card">

                                        <form class="bid-pdx-lead-form">

                                            <h3 class="bid-pdx-find-contractors-title">Find a Pro</h3>

                                            <p class="bid-pdx-lead-intro">
                                                Submit your details and we will connect you with a contractor who fits your project.
                                            </p>

                                            <div class="bid-pdx-field">
                                                <label>Name *</label>
                                                <input type="text" name="customer_name" required>
                                            </div>

                                            <div class="bid-pdx-field">
                                                <label>Email *</label>
                                                <input type="email" name="customer_email" autocomplete="email" required>
                                            </div>

                                            <div class="bid-pdx-field">
                                                <label>Confirm Email *</label>
                                                <input type="email" name="customer_email_confirm" autocomplete="email" required>
                                            </div>

                                            <div class="bid-pdx-honeypot" aria-hidden="true">
                                                <label>Company Website</label>
                                                <input type="text" name="company_website" tabindex="-1" autocomplete="off">
                                            </div>

                                            <input type="hidden" name="form_started_at" value="<?php echo esc_attr(time()); ?>">

                                            <div class="bid-pdx-field">
                                                <label>Phone *</label>
                                                <input type="tel" name="customer_phone" autocomplete="tel" required>
                                            </div>

                                            <div class="bid-pdx-field">
                                                <label>Project Address *</label>
                                                <input type="text" name="zip_code" required>
                                            </div>

                                            <div class="bid-pdx-field">
                                                <label>Describe Your Project *</label>
                                                <textarea name="message" placeholder="Tell us anything important about your project." required></textarea>
                                            </div>

                                            <button type="submit" class="bid-pdx-button bid-pdx-submit-lead">
                                                Submit Request
                                            </button>

                                            <div class="bid-pdx-form-message"></div>

                                        </form>

                                    </div>

                                </div>

                            </div>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            </div>

            <script>
                (function () {
                    const estimator = document.getElementById('<?php echo esc_js($estimator_id); ?>');

                    if (!estimator) {
                        return;
                    }

                    const projectScreen = estimator.querySelector('.bid-pdx-project-screen');
                    const estimatorScreen = estimator.querySelector('.bid-pdx-estimator-screen');
                    const projectCards = estimator.querySelectorAll('.bid-pdx-project-card');
                    const projectPanels = estimator.querySelectorAll('.bid-pdx-project-estimator');
                    const viewAllButton = estimator.querySelector('.bid-pdx-view-all-trades');

                    const ajaxUrl = estimator.dataset.ajaxUrl;
                    const nonce = estimator.dataset.nonce;

                    const moneyFormatter = new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: 'USD',
                        maximumFractionDigits: 0
                    });

                    function getProjectPanel(projectId) {
                        let selectedPanel = null;

                        projectPanels.forEach(function (panel) {
                            if (panel.dataset.projectId === String(projectId)) {
                                selectedPanel = panel;
                            }
                        });

                        return selectedPanel;
                    }

                    function clearActiveProject() {
                        projectPanels.forEach(function (panel) {
                            panel.classList.remove('is-active');
                        });
                    }

                    function openProject(projectId) {
                        const panel = getProjectPanel(projectId);

                        if (!panel) {
                            return;
                        }

                        clearActiveProject();

                        projectScreen.style.display = 'none';
                        estimatorScreen.classList.add('is-open');
                        panel.classList.add('is-active');

                        panel.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }

                    function goBackToProjects() {
                        clearActiveProject();

                        estimatorScreen.classList.remove('is-open');
                        projectScreen.style.display = 'block';

                        estimator.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }

                    function setupProjectPanel(panel) {
                        const steps = panel.querySelectorAll('.bid-pdx-category-step');
                        const inputs = panel.querySelectorAll('.bid-pdx-option-input');
                        const totalElement = panel.querySelector('.bid-pdx-total');
                        const selectedList = panel.querySelector('.bid-pdx-selected-list');
                        const leadForm = panel.querySelector('.bid-pdx-lead-form');
                        const submitButton = panel.querySelector('.bid-pdx-submit-lead');
                        const formMessage = panel.querySelector('.bid-pdx-lead-form .bid-pdx-form-message');
                        const stepMessage = panel.querySelector('.bid-pdx-step-message');
                        const prevStepButton = panel.querySelector('.bid-pdx-step-prev');
                        const nextStepButton = panel.querySelector('.bid-pdx-step-next');
                        const progressWrap = panel.querySelector('.bid-pdx-progress-wrap');
                        const progressStep = panel.querySelector('.bid-pdx-progress-step');
                        const progressCategory = panel.querySelector('.bid-pdx-progress-category');
                        const progressFill = panel.querySelector('.bid-pdx-progress-fill');
                        const stepScreen = panel.querySelector('.bid-pdx-step-screen');
                        const reviewScreen = panel.querySelector('.bid-pdx-review-screen');
                        const editSelectionsButton = panel.querySelector('.bid-pdx-edit-selections');

                        if (
                            !steps.length ||
                            !inputs.length ||
                            !totalElement ||
                            !selectedList ||
                            !leadForm ||
                            !submitButton ||
                            !formMessage ||
                            !prevStepButton ||
                            !nextStepButton ||
                            !progressWrap ||
                            !stepScreen ||
                            !reviewScreen ||
                            !editSelectionsButton
                        ) {
                            return;
                        }

                        const projectId = panel.dataset.projectId || 0;
                        const projectName = panel.dataset.projectName || '';

                        let currentStep = 0;
                        let currentMin = 0;
                        let currentMax = 0;
                        let currentSelectedItems = [];
                        let currentSelectedItemIds = [];
                        let visibleSteps = Array.from(steps);

                        function parseConditionItemIds(value) {
                            return String(value || '')
                                .split(',')
                                .map(function (itemId) {
                                    return parseInt(itemId, 10);
                                })
                                .filter(function (itemId) {
                                    return Number.isInteger(itemId) && itemId > 0;
                                });
                        }

                        function conditionIsMet(element, selectedByCategory) {
                            const conditionCategoryId = parseInt(
                                element.dataset.conditionCategoryId || '0',
                                10
                            );

                            const conditionItemIds = parseConditionItemIds(
                                element.dataset.conditionItemIds || ''
                            );

                            if (conditionCategoryId <= 0 || conditionItemIds.length === 0) {
                                return true;
                            }

                            const selectedItemIds = selectedByCategory[conditionCategoryId] || [];
                            const conditionMatch = element.dataset.conditionMatch === 'all'
                                ? 'all'
                                : 'any';

                            if (conditionMatch === 'all') {
                                return conditionItemIds.every(function (itemId) {
                                    return selectedItemIds.indexOf(itemId) !== -1;
                                });
                            }

                            return conditionItemIds.some(function (itemId) {
                                return selectedItemIds.indexOf(itemId) !== -1;
                            });
                        }

                        function refreshConditionalVisibility() {
                            const currentElement = visibleSteps[currentStep] || null;
                            const selectedByCategory = {};
                            const nextVisibleSteps = [];

                            steps.forEach(function (step) {
                                const categoryId = parseInt(step.dataset.categoryId || '0', 10);
                                const categoryIsVisible = conditionIsMet(
                                    step,
                                    selectedByCategory
                                );

                                const stepInputs = Array.from(
                                    step.querySelectorAll('.bid-pdx-option-input')
                                );

                                if (!categoryIsVisible) {
                                    step.hidden = true;
                                    step.classList.remove('is-active');

                                    stepInputs.forEach(function (input) {
                                        const option = input.closest('.bid-pdx-option');

                                        input.checked = false;

                                        if (option) {
                                            option.hidden = false;
                                            option.classList.remove('is-selected');
                                        }
                                    });

                                    selectedByCategory[categoryId] = [];
                                    return;
                                }

                                let visibleOptionCount = 0;

                                stepInputs.forEach(function (input) {
                                    const option = input.closest('.bid-pdx-option');
                                    const optionIsVisible = option
                                        ? conditionIsMet(option, selectedByCategory)
                                        : true;

                                    if (option) {
                                        option.hidden = !optionIsVisible;
                                    }

                                    if (!optionIsVisible) {
                                        input.checked = false;

                                        if (option) {
                                            option.classList.remove('is-selected');
                                        }

                                        return;
                                    }

                                    visibleOptionCount += 1;
                                });

                                selectedByCategory[categoryId] = stepInputs
                                    .filter(function (input) {
                                        const option = input.closest('.bid-pdx-option');

                                        return input.checked && (!option || !option.hidden);
                                    })
                                    .map(function (input) {
                                        return parseInt(input.value || '0', 10);
                                    })
                                    .filter(function (itemId) {
                                        return Number.isInteger(itemId) && itemId > 0;
                                    });

                                if (visibleOptionCount === 0) {
                                    step.hidden = true;
                                    step.classList.remove('is-active');
                                    return;
                                }

                                step.hidden = false;
                                nextVisibleSteps.push(step);
                            });

                            visibleSteps = nextVisibleSteps;

                            if (
                                currentElement &&
                                visibleSteps.indexOf(currentElement) !== -1
                            ) {
                                currentStep = visibleSteps.indexOf(currentElement);
                            } else if (visibleSteps.length === 0) {
                                currentStep = 0;
                            } else {
                                currentStep = Math.min(
                                    currentStep,
                                    visibleSteps.length - 1
                                );
                            }

                            return visibleSteps;
                        }

                        function clearMessage() {
                            formMessage.textContent = '';
                            formMessage.className = 'bid-pdx-form-message';
                        }

                        function showMessage(type, message) {
                            formMessage.textContent = message;
                            formMessage.className = 'bid-pdx-form-message is-' + type;
                        }

                        function clearStepMessage() {
                            if (!stepMessage) {
                                return;
                            }

                            stepMessage.textContent = '';
                            stepMessage.className = 'bid-pdx-form-message bid-pdx-step-message';
                        }

                        function showStepMessage(type, message) {
                            if (!stepMessage) {
                                return;
                            }

                            stepMessage.textContent = message;
                            stepMessage.className = 'bid-pdx-form-message bid-pdx-step-message is-' + type;
                        }

                        function showStepScreen() {
                            stepScreen.classList.remove('is-hidden');
                            progressWrap.classList.remove('is-hidden');
                            reviewScreen.classList.remove('is-visible');
                            panel.classList.remove('is-reviewing');
                            clearMessage();
                        }

                        function showReviewScreen() {
                            updateEstimate();

                            stepScreen.classList.add('is-hidden');
                            progressWrap.classList.add('is-hidden');
                            reviewScreen.classList.add('is-visible');
                            panel.classList.add('is-reviewing');
                            clearStepMessage();
                            clearMessage();

                            panel.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }

                        function currentStepIsRequiredAndEmpty() {
                            refreshConditionalVisibility();

                            const activeStep = visibleSteps[currentStep];

                            if (!activeStep) {
                                return false;
                            }

                            const selectionType = activeStep.dataset.selectionType || 'single';
                            const checkedInputs = activeStep.querySelectorAll(
                                '.bid-pdx-option-input:checked'
                            );

                            return selectionType === 'single' && checkedInputs.length === 0;
                        }

                        function updateStep() {
                            showStepScreen();
                            refreshConditionalVisibility();

                            steps.forEach(function (step) {
                                step.classList.remove('is-active');
                            });

                            const totalSteps = visibleSteps.length;
                            const activeStep = visibleSteps[currentStep];
                            const activeCategoryName = activeStep
                                ? activeStep.dataset.categoryName
                                : '';

                            if (activeStep) {
                                activeStep.classList.add('is-active');
                            }

                            if (progressStep) {
                                progressStep.textContent = totalSteps > 0
                                    ? 'Step ' + (currentStep + 1) + ' of ' + totalSteps
                                    : 'No available steps';
                            }

                            if (progressCategory) {
                                progressCategory.textContent = activeCategoryName;
                            }

                            if (progressFill) {
                                progressFill.style.width = totalSteps > 0
                                    ? (((currentStep + 1) / totalSteps) * 100) + '%'
                                    : '0%';
                            }

                            prevStepButton.disabled = false;
                            nextStepButton.disabled = totalSteps === 0;

                            if (totalSteps > 0 && currentStep === totalSteps - 1) {
                                nextStepButton.textContent = 'Review';
                            } else {
                                nextStepButton.textContent = 'Next';
                            }

                            clearStepMessage();
                        }

                        function updateEstimate() {
                            refreshConditionalVisibility();

                            currentMin = 0;
                            currentMax = 0;
                            currentSelectedItems = [];
                            currentSelectedItemIds = [];

                            inputs.forEach(function (input) {
                                const option = input.closest('.bid-pdx-option');
                                const step = input.closest('.bid-pdx-category-step');
                                const isAvailable = (
                                    option &&
                                    step &&
                                    !option.hidden &&
                                    !step.hidden
                                );

                                if (!isAvailable) {
                                    input.checked = false;
                                }

                                if (option) {
                                    option.classList.toggle(
                                        'is-selected',
                                        isAvailable && input.checked
                                    );
                                }

                                if (!isAvailable || !input.checked) {
                                    return;
                                }

                                const min = parseFloat(input.dataset.min || 0);
                                const max = parseFloat(input.dataset.max || 0);
                                const name = input.dataset.name || 'Selected option';
                                const itemId = input.value || '';

                                currentMin += min;
                                currentMax += max;

                                currentSelectedItems.push(name);

                                if (itemId !== '') {
                                    currentSelectedItemIds.push(itemId);
                                }
                            });

                            totalElement.textContent =
                                moneyFormatter.format(currentMin) + ' - ' + moneyFormatter.format(currentMax);

                            selectedList.innerHTML = '';

                            if (currentSelectedItems.length === 0) {
                                const li = document.createElement('li');
                                li.textContent = 'No options selected yet.';
                                selectedList.appendChild(li);

                                clearMessage();

                                return;
                            }

                            currentSelectedItems.forEach(function (name) {
                                const li = document.createElement('li');
                                li.textContent = name;
                                selectedList.appendChild(li);
                            });
                        }

                        inputs.forEach(function (input) {
                            input.addEventListener('change', function () {
                                refreshConditionalVisibility();
                                updateEstimate();
                                updateStep();
                                clearStepMessage();
                            });
                        });
                        prevStepButton.addEventListener('click', function () {
                            if (currentStep <= 0) {
                                goBackToProjects();
                                return;
                            }

                            currentStep -= 1;
                            updateStep();

                            panel.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        });

                        nextStepButton.addEventListener('click', function () {
                            if (currentStepIsRequiredAndEmpty()) {
                                showStepMessage('error', 'Please choose one option before continuing.');
                                return;
                            }

                            if (currentStep < visibleSteps.length - 1) {
                                currentStep += 1;
                                updateStep();

                                panel.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'start'
                                });

                                return;
                            }

                            updateEstimate();

                            if (currentSelectedItems.length === 0) {
                                showStepMessage('error', 'Please select at least one estimator option.');
                                return;
                            }

                            showReviewScreen();
                        });

                        editSelectionsButton.addEventListener('click', function () {
                            updateStep();

                            panel.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        });

                        leadForm.addEventListener('submit', function (event) {
                            event.preventDefault();

                            clearMessage();
                            updateEstimate();

                            if (currentSelectedItems.length === 0) {
                                showMessage('error', 'Please select at least one estimator option.');
                                return;
                            }

                            const formData = new FormData(leadForm);
                            const customerEmail = String(formData.get('customer_email') || '').trim().toLowerCase();
                            const customerEmailConfirm = String(formData.get('customer_email_confirm') || '').trim().toLowerCase();

                            if (customerEmail !== customerEmailConfirm) {
                                showMessage('error', 'Please make sure both email fields match.');
                                return;
                            }

                            formData.append('action', 'bid_pdx_submit_lead');
                            formData.append('nonce', nonce);
                            formData.append('project_id', projectId);
                            formData.append('project_name', projectName);
                            formData.append('estimate_min', currentMin);
                            formData.append('estimate_max', currentMax);
                            formData.append('selected_options', JSON.stringify(currentSelectedItems));
                            formData.append('selected_item_ids', JSON.stringify(currentSelectedItemIds));

                            submitButton.disabled = true;
                            submitButton.textContent = 'Submitting...';

                            fetch(ajaxUrl, {
                                method: 'POST',
                                body: formData,
                                credentials: 'same-origin'
                            })
                                .then(function (response) {
                                    return response.json();
                                })
                                .then(function (data) {
                                    if (data.success) {
                                        const successMessage = data.data.message || 'Thank you for your request. We will review your project and contact you soon.';

                                        leadForm.innerHTML = '<div class="bid-pdx-success-state"><h3>Thank you for your request.</h3><p>' + successMessage + '</p></div>';
                                    } else {
                                        showMessage('error', data.data.message || 'Something went wrong. Please try again.');
                                    }
                                })
                                .catch(function () {
                                    showMessage('error', 'Something went wrong. Please try again.');
                                })
                                .finally(function () {
                                    submitButton.disabled = false;
                                    submitButton.textContent = 'Submit Request';
                                });
                        });

                        updateStep();
                        updateEstimate();
                    }

                    if (viewAllButton) {
                        viewAllButton.addEventListener('click', function () {
                            const isShowingAll = projectScreen.classList.toggle('is-showing-all');

                            viewAllButton.setAttribute('aria-expanded', isShowingAll ? 'true' : 'false');
                            viewAllButton.textContent = isShowingAll ? 'Show Main Trades' : 'View All Trades';
                        });
                    }

                    projectCards.forEach(function (card) {
                        card.addEventListener('click', function () {
                            openProject(card.dataset.projectId);
                        });
                    });

                    projectPanels.forEach(function (panel) {
                        setupProjectPanel(panel);
                    });
                })();
            </script>

        <?php endif; ?>


            <footer class="bid-pdx-app-footer" aria-label="Estimator legal footer">
                <span>Copyright &copy; <?php echo esc_html(date_i18n('Y')); ?> <?php echo esc_html($brand_name); ?></span>
                <span aria-hidden="true"> &middot; </span>
                <button
                    type="button"
                    class="bid-pdx-footer-link bid-pdx-legal-open"
                    aria-haspopup="dialog"
                    aria-controls="<?php echo esc_attr($estimator_id); ?>-legal-modal"
                >
                    Privacy &amp; Terms
                </button>
                <span aria-hidden="true"> &middot; </span>
                <button
                    type="button"
                    class="bid-pdx-footer-link bid-pdx-about-open"
                    aria-haspopup="dialog"
                    aria-controls="<?php echo esc_attr($estimator_id); ?>-about-modal"
                >
                    About
                </button>
            </footer>

    </div>


    <button
        id="<?php echo esc_attr($estimator_id); ?>-install-button"
        class="estimator-install-button"
        type="button"
    >
        Add Estimator to Home Screen
    </button>

    <div
        id="<?php echo esc_attr($estimator_id); ?>-pros-modal"
        class="bid-pdx-modal bid-pdx-pros-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="<?php echo esc_attr($estimator_id); ?>-pros-title"
        hidden
    >
        <div class="bid-pdx-modal-dialog">
            <button type="button" class="bid-pdx-modal-close bid-pdx-pros-close" aria-label="Close contractor information popup">X</button>

            <h2 id="<?php echo esc_attr($estimator_id); ?>-pros-title">For Contractors</h2>

            <p>
                Want to receive homeowner project requests? Send us your business details for review.
            </p>

            <form class="bid-pdx-pro-form">
                <div class="bid-pdx-pro-form-grid">
                    <div class="bid-pdx-field">
                        <label>Name *</label>
                        <input type="text" name="pro_name" required>
                    </div>

                    <div class="bid-pdx-field">
                        <label>Business Name *</label>
                        <input type="text" name="pro_business_name" required>
                    </div>

                    <div class="bid-pdx-field">
                        <label>Email *</label>
                        <input type="email" name="pro_email" required>
                    </div>

                    <div class="bid-pdx-field">
                        <label>Phone *</label>
                        <input type="tel" name="pro_phone" required>
                    </div>

                    <div class="bid-pdx-field">
                        <label>License Number</label>
                        <input type="text" name="pro_license">
                    </div>

                    <div class="bid-pdx-field">
                        <label>Service Areas</label>
                        <input type="text" name="pro_service_areas">
                    </div>

                    <div class="bid-pdx-field bid-pdx-field-full">
                        <label>Services Provided *</label>
                        <textarea name="pro_services" placeholder="Describe the services your business provides." required></textarea>
                    </div>
                </div>

                <button type="submit" class="bid-pdx-button bid-pdx-submit-lead">
                    Send Contractor Info
                </button>

                <p class="bid-pdx-form-message"></p>
            </form>
        </div>
    </div>

    <div
        id="<?php echo esc_attr($estimator_id); ?>-legal-modal"
        class="bid-pdx-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="<?php echo esc_attr($estimator_id); ?>-legal-title"
        hidden
    >
        <div class="bid-pdx-modal-dialog">
            <button type="button" class="bid-pdx-modal-close bid-pdx-legal-close" aria-label="Close privacy and terms popup">X</button>

            <h2 id="<?php echo esc_attr($estimator_id); ?>-legal-title">Privacy &amp; Terms</h2>

            <p>
                This page provides general project estimate ranges and lead request tools. It is not a final quote, contract, inspection, or guarantee of contractor availability.
            </p>

            <h3>Privacy</h3>
            <p>
                When you submit a form, we may collect your name, email address, phone number, project address, project details, estimated budget range, and selected estimator options. This information is used to respond to your request, understand your project, provide customer support, and connect you with appropriate service providers.
            </p>
            <p>
                We do not sell your personal information. We may share submitted project information with contractors, service providers, website hosting tools, email systems, security tools, or other vendors only as needed to operate the website and respond to your request.
            </p>
            <p>
                We use reasonable administrative, technical, and organizational safeguards, but no website or email system can be guaranteed completely secure. You may contact us to request access, correction, or deletion of your submitted information where required by applicable law.
            </p>

            <h3>Terms</h3>
            <p>
                Estimate ranges are for planning purposes only. Final pricing may change based on site conditions, labor, materials, permits, code requirements, access, design choices, hidden damage, change orders, and contractor review.
            </p>
            <p>
                Contractors are independent businesses. You are responsible for reviewing any final proposal, license, insurance, references, warranty, scope of work, payment terms, and written agreement before hiring a contractor.
            </p>
            <p>
                Use of this estimator does not create a contractor-client relationship, guarantee service quality, or guarantee that your project will be accepted. Do not use this estimator for emergency service requests.
            </p>
        </div>
    </div>

    <div
        id="<?php echo esc_attr($estimator_id); ?>-about-modal"
        class="bid-pdx-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="<?php echo esc_attr($estimator_id); ?>-about-title"
        hidden
    >
        <div class="bid-pdx-modal-dialog">
            <button type="button" class="bid-pdx-modal-close bid-pdx-about-close" aria-label="Close about popup">X</button>

            <h2 id="<?php echo esc_attr($estimator_id); ?>-about-title">About <?php echo esc_html($brand_name); ?></h2>

            <p>
                <?php echo esc_html($site_profile['about_text']); ?>
            </p>

            <ul class="bid-pdx-about-points">
                <li>Choose a project type.</li>
                <li>Select the options that match your scope.</li>
                <li>Review an instant budget range.</li>
                <li>Submit your request to connect with local contractors.</li>
            </ul>
        </div>
    </div>

    <script>
        (function () {
            const estimator = document.getElementById('<?php echo esc_js($estimator_id); ?>');

            if (!estimator) {
                return;
            }


            const prosOpenButton = estimator.querySelector('.bid-pdx-pros-open');
            const prosModal = estimator.querySelector('#<?php echo esc_js($estimator_id); ?>-pros-modal');
            const prosCloseButton = estimator.querySelector('.bid-pdx-pros-close');
            const legalOpenButton = estimator.querySelector('.bid-pdx-legal-open');
            const legalModal = estimator.querySelector('#<?php echo esc_js($estimator_id); ?>-legal-modal');
            const legalCloseButton = estimator.querySelector('.bid-pdx-legal-close');
            const aboutOpenButton = estimator.querySelector('.bid-pdx-about-open');
            const aboutModal = estimator.querySelector('#<?php echo esc_js($estimator_id); ?>-about-modal');
            const aboutCloseButton = estimator.querySelector('.bid-pdx-about-close');
            const proForm = estimator.querySelector('.bid-pdx-pro-form');

            function openBidPdxModal(modal, closeButton) {
                if (!modal) {
                    return;
                }

                modal.hidden = false;
                modal.classList.add('is-open');

                if (closeButton) {
                    closeButton.focus();
                }
            }

            function closeBidPdxModal(modal, openButton) {
                if (!modal) {
                    return;
                }

                modal.classList.remove('is-open');
                modal.hidden = true;

                if (openButton) {
                    openButton.focus();
                }
            }

            function setupBidPdxModal(openButton, modal, closeButton) {
                if (!openButton || !modal) {
                    return;
                }

                openButton.addEventListener('click', function () {
                    openBidPdxModal(modal, closeButton);
                });

                if (closeButton) {
                    closeButton.addEventListener('click', function () {
                        closeBidPdxModal(modal, openButton);
                    });
                }

                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeBidPdxModal(modal, openButton);
                    }
                });
            }

            setupBidPdxModal(prosOpenButton, prosModal, prosCloseButton);
            setupBidPdxModal(legalOpenButton, legalModal, legalCloseButton);
            setupBidPdxModal(aboutOpenButton, aboutModal, aboutCloseButton);

            if (proForm) {
                proForm.addEventListener('submit', function (event) {
                    event.preventDefault();

                    const formMessage = proForm.querySelector('.bid-pdx-form-message');
                    const submitButton = proForm.querySelector('button[type="submit"]');
                    const formData = new FormData(proForm);

                    formData.append('action', 'estimator_framework_submit_contractor');
                    formData.append('nonce', estimator.dataset.nonce || '');

                    if (formMessage) {
                        formMessage.textContent = '';
                        formMessage.className = 'bid-pdx-form-message';
                    }

                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.textContent = 'Submitting...';
                    }

                    fetch(estimator.dataset.ajaxUrl || '', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (data) {
                            if (data.success) {
                                const successMessage = data.data.message || 'Thank you for your request. We will review your contractor application and contact you soon.';

                                proForm.innerHTML = '<div class="bid-pdx-success-state"><h3>Thank you for your request.</h3><p>' + successMessage + '</p></div>';
                                return;
                            }

                            if (formMessage) {
                                formMessage.textContent = data.data.message || 'Something went wrong. Please try again.';
                                formMessage.className = 'bid-pdx-form-message is-error';
                            }
                        })
                        .catch(function () {
                            if (formMessage) {
                                formMessage.textContent = 'Something went wrong. Please try again.';
                                formMessage.className = 'bid-pdx-form-message is-error';
                            }
                        })
                        .finally(function () {
                            if (submitButton && proForm.isConnected) {
                                submitButton.disabled = false;
                                submitButton.textContent = 'Send Contractor Info';
                            }
                        });
                });
            }

            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape') {
                    return;
                }

                if (prosModal && prosModal.classList.contains('is-open')) {
                    closeBidPdxModal(prosModal, prosOpenButton);
                }

                if (legalModal && legalModal.classList.contains('is-open')) {
                    closeBidPdxModal(legalModal, legalOpenButton);
                }

                if (aboutModal && aboutModal.classList.contains('is-open')) {
                    closeBidPdxModal(aboutModal, aboutOpenButton);
                }
            });

            const introScreen = estimator.querySelector('.bid-pdx-intro-screen');

            if (!introScreen) {
                estimator.classList.add('is-ready');
                return;
            }

            window.setTimeout(function () {
                introScreen.classList.add('is-fading');

                window.setTimeout(function () {
                    estimator.classList.add('is-ready');
                    introScreen.setAttribute('hidden', 'hidden');

                    const projectScreen = estimator.querySelector('.bid-pdx-project-screen');

                    if (projectScreen) {
                        projectScreen.style.display = 'block';
                    }

                }, 700);
            }, 3000);
        })();
    </script>


    <script>
        (function () {
            const installButton = document.getElementById('<?php echo esc_js($estimator_id); ?>-install-button');

            if (!installButton) {
                return;
            }

            let deferredPrompt = null;
            let promptOpen = false;
            const userAgent = navigator.userAgent || '';

            function isStandalone() {
                return window.matchMedia('(display-mode: standalone)').matches ||
                    window.navigator.standalone === true;
            }

            function isMobileDevice() {
                return /Android|iPhone|iPad|iPod/i.test(userAgent);
            }

            function isIOSDevice() {
                return /iPhone|iPad|iPod/i.test(userAgent);
            }

            function isIOSSafari() {
                return isIOSDevice() &&
                    /Safari/i.test(userAgent) &&
                    !/CriOS|FxiOS|EdgiOS/i.test(userAgent);
            }

            function showInstallButton() {
                if (!isStandalone() && isMobileDevice()) {
                    installButton.style.display = 'flex';
                }
            }

            function hideInstallButton() {
                installButton.style.display = 'none';
            }

            if (isStandalone() || !isMobileDevice()) {
                hideInstallButton();
                return;
            }

            showInstallButton();

            window.addEventListener('beforeinstallprompt', function (event) {
                event.preventDefault();
                deferredPrompt = event;
                showInstallButton();
            });

            installButton.addEventListener('click', async function () {
                if (promptOpen) {
                    return;
                }

                if (isIOSDevice()) {
                    if (isIOSSafari()) {
                        window.alert(
                            'To install this app on iPhone:\n\n' +
                            '1. Tap the Share button.\n' +
                            '2. Scroll down.\n' +
                            '3. Tap Add to Home Screen.'
                        );
                    } else {
                        window.alert(
                            'To install this app on iPhone:\n\n' +
                            '1. Open this website in Safari.\n' +
                            '2. Tap the Share button.\n' +
                            '3. Tap Add to Home Screen.'
                        );
                    }

                    return;
                }

                if (!deferredPrompt) {
                    window.alert(
                        'To install this app, open your browser menu and choose Install App or Add to Home Screen.'
                    );
                    return;
                }

                promptOpen = true;
                deferredPrompt.prompt();

                const choice = await deferredPrompt.userChoice;

                if (choice && choice.outcome === 'accepted') {
                    hideInstallButton();
                }

                deferredPrompt = null;
                promptOpen = false;
            });

            window.addEventListener('appinstalled', hideInstallButton);
        })();
    </script>


</div>
