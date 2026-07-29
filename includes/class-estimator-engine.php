<?php

if (!defined('ABSPATH')) {
    exit;
}

class Bid_PDX_Estimator_Engine {

    /**
     * Get all active projects for the frontend project card screen.
     */
    public function get_project_cards() {

        $projects = $this->get_active_projects();

        $project_cards = [];

        foreach ($projects as $project) {
            $project_cards[] = [
                'id'          => intval($project->id),
                'name'        => $project->name,
                'slug'        => $project->slug,
                'description' => $project->description,
            ];
        }

        return $project_cards;

    }

    /**
     * Get the default estimator data.
     * Uses the first active project that has active categories and active options.
     */
    public function get_default_estimator_data() {

        $projects = $this->get_active_projects();

        if (empty($projects)) {
            return null;
        }

        foreach ($projects as $project) {

            $estimator_data = $this->get_project_estimator_data($project->id);

            if (!empty($estimator_data) && !empty($estimator_data['categories'])) {
                return $estimator_data;
            }

        }

        return null;

    }

    /**
     * Get estimator data for every active project.
     */
    public function get_all_projects_estimator_data() {

        $projects = $this->get_active_projects();

        $all_data = [];

        foreach ($projects as $project) {

            $estimator_data = $this->get_project_estimator_data($project->id);

            if (!empty($estimator_data)) {
                $all_data[] = $estimator_data;
            }

        }

        return $all_data;

    }

    /**
     * Get estimator data for one project.
     */
    public function get_project_estimator_data($project_id) {

        $project = Bid_PDX_Project_Manager::get($project_id);

        if (!$project || intval($project->active) !== 1) {
            return null;
        }

        $categories = Bid_PDX_Category_Manager::get_by_project($project->id);

        $prepared_categories = [];

        foreach ($categories as $category) {

            if (intval($category->active) !== 1) {
                continue;
            }

            $selection_type = isset($category->selection_type)
                ? sanitize_text_field($category->selection_type)
                : 'single';

            if (!in_array($selection_type, ['single', 'multiple'], true)) {
                $selection_type = 'single';
            }

            $items = Bid_PDX_Item_Manager::get_active_by_category($category->id);

            $prepared_items = [];

            foreach ($items as $item) {
                $prepared_items[] = $this->prepare_item($item);
            }

            if (empty($prepared_items)) {
                continue;
            }

            $prepared_categories[] = [
                'id'                    => intval($category->id),
                'project_id'            => intval($category->project_id),
                'name'                  => $category->name,
                'slug'                  => $category->slug,
                'description'           => $category->description,
                'selection_type'        => $selection_type,
                'condition_category_id' => isset($category->condition_category_id)
                    ? intval($category->condition_category_id)
                    : 0,
                'condition_item_ids'    => $this->prepare_condition_item_ids(
                    isset($category->condition_item_ids) ? $category->condition_item_ids : ''
                ),
                'condition_match'       => $this->prepare_condition_match(
                    isset($category->condition_match) ? $category->condition_match : 'any'
                ),
                'items'                 => $prepared_items,
            ];

        }

        return [
            'project' => [
                'id'          => intval($project->id),
                'name'        => $project->name,
                'slug'        => $project->slug,
                'description' => $project->description,
            ],
            'categories' => $prepared_categories,
        ];

    }

    /**
     * Calculate estimate from selected item IDs.
     */
    public function calculate_estimate($selected_item_ids) {

        if (!is_array($selected_item_ids)) {
            return [
                'min'   => 0,
                'max'   => 0,
                'items' => [],
            ];
        }

        $total_min = 0;
        $total_max = 0;
        $selected_items = [];

        foreach ($selected_item_ids as $item_id) {

            $item = Bid_PDX_Item_Manager::get($item_id);

            if (!$item || intval($item->active) !== 1) {
                continue;
            }

            $total_min += floatval($item->price_min);
            $total_max += floatval($item->price_max);

            $selected_items[] = $this->prepare_item($item);

        }

        return [
            'min'   => $total_min,
            'max'   => $total_max,
            'items' => $selected_items,
        ];

    }

    /**
     * Prepare item for frontend or estimate summary.
     */
    private function prepare_item($item) {

        return [
            'id'                    => intval($item->id),
            'category_id'           => intval($item->category_id),
            'name'                  => $item->name,
            'slug'                  => $item->slug,
            'description'           => $item->description,
            'unit'                  => $item->unit,
            'price_min'             => floatval($item->price_min),
            'price_max'             => floatval($item->price_max),
            'condition_category_id' => isset($item->condition_category_id)
                ? intval($item->condition_category_id)
                : 0,
            'condition_item_ids'    => $this->prepare_condition_item_ids(
                isset($item->condition_item_ids) ? $item->condition_item_ids : ''
            ),
            'condition_match'       => $this->prepare_condition_match(
                isset($item->condition_match) ? $item->condition_match : 'any'
            ),
        ];

    }

    /**
     * Convert stored condition IDs into an integer array.
     */
    private function prepare_condition_item_ids($value) {

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
     * Return a supported condition matching mode.
     */
    private function prepare_condition_match($value) {

        $value = sanitize_key($value);

        return in_array($value, ['any', 'all'], true)
            ? $value
            : 'any';

    }

    /**
     * Evaluate category and option conditions against submitted selections.
     */
    public function evaluate_selection($project_data, $selected_item_ids) {

        $requested_item_ids = [];

        if (is_array($selected_item_ids)) {
            foreach ($selected_item_ids as $item_id) {
                $item_id = intval($item_id);

                if ($item_id > 0) {
                    $requested_item_ids[] = $item_id;
                }
            }
        }

        $requested_item_ids = array_values(array_unique($requested_item_ids));
        $requested_lookup   = array_fill_keys($requested_item_ids, true);

        $visible_category_ids    = [];
        $visible_item_ids        = [];
        $selected_by_category    = [];
        $valid_selected_item_ids = [];

        if (
            empty($project_data['categories']) ||
            !is_array($project_data['categories'])
        ) {
            return [
                'requested_item_ids'      => $requested_item_ids,
                'visible_category_ids'    => [],
                'visible_item_ids'        => [],
                'selected_by_category'    => [],
                'valid_selected_item_ids' => [],
            ];
        }

        foreach ($project_data['categories'] as $category) {

            $category_id = isset($category['id'])
                ? intval($category['id'])
                : 0;

            if ($category_id <= 0) {
                continue;
            }

            $category_is_visible = $this->condition_is_met(
                isset($category['condition_category_id'])
                    ? $category['condition_category_id']
                    : 0,
                isset($category['condition_item_ids'])
                    ? $category['condition_item_ids']
                    : [],
                isset($category['condition_match'])
                    ? $category['condition_match']
                    : 'any',
                $selected_by_category
            );

            if (!$category_is_visible) {
                continue;
            }

            $category_selected_ids = [];
            $category_visible_ids  = [];

            if (!empty($category['items']) && is_array($category['items'])) {
                foreach ($category['items'] as $item) {

                    $item_id = isset($item['id'])
                        ? intval($item['id'])
                        : 0;

                    if ($item_id <= 0) {
                        continue;
                    }

                    $item_is_visible = $this->condition_is_met(
                        isset($item['condition_category_id'])
                            ? $item['condition_category_id']
                            : 0,
                        isset($item['condition_item_ids'])
                            ? $item['condition_item_ids']
                            : [],
                        isset($item['condition_match'])
                            ? $item['condition_match']
                            : 'any',
                        $selected_by_category
                    );

                    if (!$item_is_visible) {
                        continue;
                    }

                    $category_visible_ids[] = $item_id;
                    $visible_item_ids[]     = $item_id;

                    if (isset($requested_lookup[$item_id])) {
                        $category_selected_ids[]   = $item_id;
                        $valid_selected_item_ids[] = $item_id;
                    }
                }
            }

            $selected_by_category[$category_id] = $category_selected_ids;

            if (empty($category_visible_ids)) {
                continue;
            }

            $visible_category_ids[] = $category_id;
        }

        return [
            'requested_item_ids'      => $requested_item_ids,
            'visible_category_ids'    => array_values(array_unique($visible_category_ids)),
            'visible_item_ids'        => array_values(array_unique($visible_item_ids)),
            'selected_by_category'    => $selected_by_category,
            'valid_selected_item_ids' => array_values(array_unique($valid_selected_item_ids)),
        ];

    }

    /**
     * Determine whether one stored condition is satisfied.
     */
    private function condition_is_met(
        $condition_category_id,
        $condition_item_ids,
        $condition_match,
        $selected_by_category
    ) {

        $condition_category_id = intval($condition_category_id);
        $condition_item_ids    = $this->prepare_condition_item_ids($condition_item_ids);

        if ($condition_category_id <= 0 || empty($condition_item_ids)) {
            return true;
        }

        $selected_item_ids = isset($selected_by_category[$condition_category_id])
            && is_array($selected_by_category[$condition_category_id])
            ? array_map('intval', $selected_by_category[$condition_category_id])
            : [];

        $condition_match = $this->prepare_condition_match($condition_match);

        if ($condition_match === 'all') {
            return empty(array_diff($condition_item_ids, $selected_item_ids));
        }

        return !empty(array_intersect($condition_item_ids, $selected_item_ids));

    }

    /**
     * Get active projects only.
     */
    private function get_active_projects() {

        $projects = Bid_PDX_Project_Manager::get_all();

        $active_projects = [];

        foreach ($projects as $project) {
            if (intval($project->active) === 1) {
                $active_projects[] = $project;
            }
        }

        return $active_projects;

    }

}