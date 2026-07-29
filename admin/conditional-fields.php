<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Return active categories and options for conditional-rule selectors.
 */
function bid_pdx_admin_get_condition_choices() {

    global $wpdb;

    $projects_table   = $wpdb->prefix . 'bid_pdx_projects';
    $categories_table = $wpdb->prefix . 'bid_pdx_categories';
    $items_table      = $wpdb->prefix . 'bid_pdx_items';

    $rows = $wpdb->get_results(
        "SELECT
            c.id AS category_id,
            c.project_id,
            c.name AS category_name,
            c.sort_order AS category_sort_order,
            p.name AS project_name,
            i.id AS item_id,
            i.name AS item_name,
            i.sort_order AS item_sort_order
         FROM {$categories_table} c
         INNER JOIN {$projects_table} p ON p.id = c.project_id
         LEFT JOIN {$items_table} i
            ON i.category_id = c.id
            AND i.active = 1
         WHERE c.active = 1
         AND p.active = 1
         ORDER BY
            p.sort_order ASC,
            p.name ASC,
            c.sort_order ASC,
            c.name ASC,
            i.sort_order ASC,
            i.name ASC"
    );

    $choices = [];

    foreach ($rows as $row) {

        $category_id = intval($row->category_id);

        if ($category_id <= 0) {
            continue;
        }

        if (!isset($choices[$category_id])) {
            $choices[$category_id] = [
                'category_id'        => $category_id,
                'project_id'         => intval($row->project_id),
                'project_name'       => sanitize_text_field($row->project_name),
                'category_name'      => sanitize_text_field($row->category_name),
                'category_sort_order' => intval($row->category_sort_order),
                'items'              => [],
            ];
        }

        $item_id = isset($row->item_id)
            ? intval($row->item_id)
            : 0;

        if ($item_id > 0) {
            $choices[$category_id]['items'][] = [
                'id'   => $item_id,
                'name' => sanitize_text_field($row->item_name),
            ];
        }
    }

    return array_values($choices);

}

/**
 * Convert submitted or stored condition item IDs into a clean integer array.
 */
function bid_pdx_admin_normalize_condition_item_ids($value) {

    if (is_array($value)) {
        $values = $value;
    } else {
        $values = preg_split('/[\s,]+/', (string) $value);
    }

    $item_ids = [];

    foreach ($values as $item_id) {
        $item_id = intval($item_id);

        if ($item_id > 0) {
            $item_ids[] = $item_id;
        }
    }

    return array_values(array_unique($item_ids));

}

/**
 * Validate one conditional visibility rule.
 *
 * Returns an empty string when valid, or a user-facing error message.
 */
function bid_pdx_admin_validate_condition_rule(
    $target_project_id,
    $target_category_id,
    $target_sort_order,
    $condition_category_id,
    $condition_item_ids
) {

    global $wpdb;

    $target_project_id     = intval($target_project_id);
    $target_category_id    = intval($target_category_id);
    $target_sort_order     = intval($target_sort_order);
    $condition_category_id = intval($condition_category_id);
    $condition_item_ids    = bid_pdx_admin_normalize_condition_item_ids(
        $condition_item_ids
    );

    if ($condition_category_id <= 0) {
        return '';
    }

    if (empty($condition_item_ids)) {
        return 'Please select at least one required answer for the conditional rule.';
    }

    if (
        $target_category_id > 0 &&
        $condition_category_id === $target_category_id
    ) {
        return 'A category or option cannot depend on an answer from its own category.';
    }

    $categories_table = $wpdb->prefix . 'bid_pdx_categories';
    $items_table      = $wpdb->prefix . 'bid_pdx_items';

    $condition_category = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, project_id, sort_order
             FROM {$categories_table}
             WHERE id = %d
             AND active = 1
             LIMIT 1",
            $condition_category_id
        )
    );

    if (!$condition_category) {
        return 'The selected conditional category is no longer available.';
    }

    if (
        $target_project_id <= 0 ||
        intval($condition_category->project_id) !== $target_project_id
    ) {
        return 'Conditional rules must use an earlier category from the same project.';
    }

    if (intval($condition_category->sort_order) >= $target_sort_order) {
        return 'Conditional rules must reference a category with a lower display order.';
    }

    $placeholders = implode(
        ',',
        array_fill(0, count($condition_item_ids), '%d')
    );

    $valid_item_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT id
             FROM {$items_table}
             WHERE category_id = %d
             AND active = 1
             AND id IN ({$placeholders})",
            array_merge(
                [$condition_category_id],
                $condition_item_ids
            )
        )
    );

    $valid_item_ids = array_map('intval', $valid_item_ids);

    if (
        count($valid_item_ids) !== count($condition_item_ids) ||
        !empty(array_diff($condition_item_ids, $valid_item_ids))
    ) {
        return 'One or more required answers do not belong to the selected conditional category.';
    }

    return '';

}

/**
 * Return the project and display order for one category.
 */
function bid_pdx_admin_get_category_context($category_id) {

    global $wpdb;

    $category_id = intval($category_id);

    if ($category_id <= 0) {
        return null;
    }

    $categories_table = $wpdb->prefix . 'bid_pdx_categories';

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, project_id, sort_order
             FROM {$categories_table}
             WHERE id = %d
             LIMIT 1",
            $category_id
        )
    );

}

/**
 * Render reusable conditional visibility fields.
 */
function bid_pdx_admin_render_condition_fields($args = []) {

    static $script_rendered = false;

    $args = wp_parse_args(
        $args,
        [
            'condition_category_id' => 0,
            'condition_item_ids'    => [],
            'condition_match'       => 'any',
            'exclude_category_id'   => 0,
        ]
    );

    $condition_category_id = intval($args['condition_category_id']);
    $condition_item_ids    = bid_pdx_admin_normalize_condition_item_ids(
        $args['condition_item_ids']
    );
    $exclude_category_id   = intval($args['exclude_category_id']);

    $condition_match = sanitize_key($args['condition_match']);

    if (!in_array($condition_match, ['any', 'all'], true)) {
        $condition_match = 'any';
    }

    $choices          = bid_pdx_admin_get_condition_choices();
    $category_contexts = [];

    foreach ($choices as $choice) {
        $choice_category_id = intval($choice['category_id']);

        $category_contexts[(string) $choice_category_id] = [
            'project_id' => intval($choice['project_id']),
            'sort_order' => intval($choice['category_sort_order']),
        ];
    }

    ?>
    <tr>
        <th scope="row">
            <label for="condition_category_id">Conditional Visibility</label>
        </th>
        <td>
            <div
                data-bid-pdx-condition-fields
                data-bid-pdx-target-category-id="<?php echo esc_attr($exclude_category_id); ?>"
                data-bid-pdx-category-contexts="<?php echo esc_attr(wp_json_encode($category_contexts)); ?>"
            >

                <p>
                    <label for="condition_category_id">
                        <strong>Show only when this earlier category has:</strong>
                    </label>
                </p>

                <select
                    name="condition_category_id"
                    id="condition_category_id"
                    class="regular-text"
                    data-bid-pdx-condition-category
                >
                    <option value="0">Always show</option>

                    <?php foreach ($choices as $choice) : ?>

                        <?php
                        $choice_category_id = intval($choice['category_id']);

                        if ($choice_category_id === $exclude_category_id) {
                            continue;
                        }
                        ?>

                        <option
                            value="<?php echo esc_attr($choice_category_id); ?>"
                            data-project-id="<?php echo esc_attr($choice['project_id']); ?>"
                            data-sort-order="<?php echo esc_attr($choice['category_sort_order']); ?>"
                            <?php selected($condition_category_id, $choice_category_id); ?>
                        >
                            <?php
                            echo esc_html(
                                $choice['project_name'] .
                                ' → ' .
                                $choice['category_name']
                            );
                            ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <p>
                    <label for="condition_item_ids">
                        <strong>Required answer or answers:</strong>
                    </label>
                </p>

                <select
                    name="condition_item_ids[]"
                    id="condition_item_ids"
                    class="regular-text"
                    multiple
                    size="7"
                    data-bid-pdx-condition-items
                >
                    <?php foreach ($choices as $choice) : ?>

                        <?php foreach ($choice['items'] as $item) : ?>

                            <option
                                value="<?php echo esc_attr($item['id']); ?>"
                                data-category-id="<?php echo esc_attr($choice['category_id']); ?>"
                                <?php selected(in_array(intval($item['id']), $condition_item_ids, true)); ?>
                            >
                                <?php
                                echo esc_html(
                                    $choice['project_name'] .
                                    ' → ' .
                                    $choice['category_name'] .
                                    ' → ' .
                                    $item['name']
                                );
                                ?>
                            </option>

                        <?php endforeach; ?>

                    <?php endforeach; ?>
                </select>

                <p>
                    <label for="condition_match">
                        <strong>Matching rule:</strong>
                    </label>
                </p>

                <select
                    name="condition_match"
                    id="condition_match"
                    data-bid-pdx-condition-match
                >
                    <option value="any" <?php selected($condition_match, 'any'); ?>>
                        Any selected answer
                    </option>
                    <option value="all" <?php selected($condition_match, 'all'); ?>>
                        All selected answers
                    </option>
                </select>

                <p class="description">
                    Leave this set to Always show when no conditional routing is needed.
                    Conditions should reference an earlier category in the same project.
                </p>

            </div>
        </td>
    </tr>
    <?php

    if ($script_rendered) {
        return;
    }

    $script_rendered = true;

    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-bid-pdx-condition-fields]').forEach(function (container) {
                const categorySelect = container.querySelector('[data-bid-pdx-condition-category]');
                const itemSelect = container.querySelector('[data-bid-pdx-condition-items]');
                const matchSelect = container.querySelector('[data-bid-pdx-condition-match]');
                const projectField = document.getElementById('project_id');
                const targetCategoryField = document.getElementById('category_id');
                const sortOrderField = document.getElementById('sort_order');

                let categoryContexts = {};

                try {
                    categoryContexts = JSON.parse(
                        container.dataset.bidPdxCategoryContexts || '{}'
                    );
                } catch (error) {
                    categoryContexts = {};
                }

                if (!categorySelect || !itemSelect || !matchSelect) {
                    return;
                }

                function getTargetContext() {
                    const initialCategoryId = String(
                        container.dataset.bidPdxTargetCategoryId || '0'
                    );

                    if (targetCategoryField) {
                        const categoryId = String(
                            targetCategoryField.value || '0'
                        );

                        const context = categoryContexts[categoryId] || {};

                        return {
                            categoryId: categoryId,
                            projectId: String(context.project_id || '0'),
                            sortOrder: parseInt(
                                context.sort_order || '0',
                                10
                            ),
                        };
                    }

                    return {
                        categoryId: initialCategoryId,
                        projectId: projectField
                            ? String(projectField.value || '0')
                            : '0',
                        sortOrder: sortOrderField
                            ? parseInt(sortOrderField.value || '0', 10)
                            : 0,
                    };
                }

                function refreshConditionItems() {
                    const categoryId = String(categorySelect.value || '0');
                    const hasCondition = categoryId !== '0';

                    Array.from(itemSelect.options).forEach(function (option) {
                        const isAvailable = (
                            hasCondition &&
                            option.dataset.categoryId === categoryId
                        );

                        option.hidden = !isAvailable;
                        option.disabled = !isAvailable;

                        if (!isAvailable) {
                            option.selected = false;
                        }
                    });

                    itemSelect.disabled = !hasCondition;
                    matchSelect.disabled = !hasCondition;
                }

                function refreshConditionCategories() {
                    const target = getTargetContext();

                    Array.from(categorySelect.options).forEach(function (option) {
                        const categoryId = String(option.value || '0');

                        if (categoryId === '0') {
                            option.hidden = false;
                            option.disabled = false;
                            return;
                        }

                        const optionSortOrder = parseInt(
                            option.dataset.sortOrder || '0',
                            10
                        );

                        const isAvailable = (
                            target.projectId !== '0' &&
                            option.dataset.projectId === target.projectId &&
                            optionSortOrder < target.sortOrder &&
                            categoryId !== target.categoryId
                        );

                        option.hidden = !isAvailable;
                        option.disabled = !isAvailable;
                    });

                    const selectedOption = categorySelect.options[
                        categorySelect.selectedIndex
                    ];

                    if (
                        categorySelect.value !== '0' &&
                        (!selectedOption || selectedOption.disabled)
                    ) {
                        categorySelect.value = '0';
                    }

                    refreshConditionItems();
                }

                categorySelect.addEventListener(
                    'change',
                    refreshConditionItems
                );

                if (projectField) {
                    projectField.addEventListener(
                        'change',
                        refreshConditionCategories
                    );
                }

                if (targetCategoryField) {
                    targetCategoryField.addEventListener(
                        'change',
                        refreshConditionCategories
                    );
                }

                if (sortOrderField && !targetCategoryField) {
                    sortOrderField.addEventListener(
                        'input',
                        refreshConditionCategories
                    );
                }

                refreshConditionCategories();
            });
        });
    </script>
    <?php

}