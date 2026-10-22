<?php
/** Polylang integration: independent editorial fields, shared operational data. */
if (!defined('ABSPATH')) exit;

add_filter('pll_get_post_types', static function ($types, $settings) {
    $types['tour'] = 'tour';
    $types['destination'] = 'destination';
    unset($types['booking'], $types['tourism_site']);
    return $types;
}, 10, 2);
add_filter('pll_get_taxonomies', static function ($taxonomies, $settings) {
    foreach (array('tour_category', 'tour_type', 'tour_tag', 'departamento', 'provincia', 'distrito') as $name) $taxonomies[$name] = $name;
    return $taxonomies;
}, 10, 2);

function tourism_core_shared_language_meta() {
    return array('_thumbnail_id', '_tour_hero_image', '_destination_hero_image', 'tourism_price_from', 'tourism_price_before', 'tourism_price_discount_percent', 'tourism_price_currency', 'tourism_duration', 'tourism_difficulty', 'tourism_group_size', 'tourism_max_elevation', 'tourism_duration_options', 'tourism_difficulty_options', 'tourism_group_size_options', 'tourism_max_elevation_options', 'tourism_tour_order', 'tourism_tour_sku', 'tourism_offer_availability', 'tourism_price_valid_until', '_wetravel_button_code');
}
add_filter('pll_copy_post_metas', static function ($metas, $sync = false, $from = 0, $to = 0, $lang = '') {
    if (!in_array(get_post_type($from), array('tour', 'destination'), true)) return $metas;
    if ($sync && get_post_meta($from, '_tourism_preserve_language_values', true)) return array();
    // Polylang's initial copy includes rich text and nested repeaters, preserving HTML.
    if (!$sync) return array_values(array_unique(array_merge($metas, array_filter(array_keys(get_post_meta($from)), static function ($key) { return str_starts_with($key, 'tourism_') || in_array($key, tourism_core_shared_language_meta(), true); }))));
    // Never synchronize translated text, captions, itineraries or policy repeaters.
    return tourism_core_shared_language_meta();
}, 10, 5);

add_filter('pll_translate_post_meta', static function ($value, $key, $lang) {
    if ($key !== 'tourism_destinations' || !is_array($value) || !function_exists('pll_get_post')) return $value;
    return array_values(array_filter(array_map(static fn($id) => pll_get_post((int)$id, $lang) ?: (int)$id, $value)));
}, 10, 3);

// Synchronize only shared values when a tour is saved outside Polylang's editor.
add_action('save_post_tour', static function ($id) {
    if (!function_exists('pll_get_post_translations') || wp_is_post_revision($id) || wp_is_post_autosave($id)) return;
    if (get_post_meta($id, '_tourism_preserve_language_values', true)) return;
    static $running = false;
    if ($running) return;
    $running = true;
    foreach (pll_get_post_translations($id) as $translation) {
        if ((int)$translation === (int)$id) continue;
        foreach (tourism_core_shared_language_meta() as $key) {
            if (metadata_exists('post', $id, $key)) update_post_meta($translation, $key, get_post_meta($id, $key, true));
            else delete_post_meta($translation, $key);
        }
        $destinations = get_post_meta($id, 'tourism_destinations', true);
        if (is_array($destinations)) update_post_meta($translation, 'tourism_destinations', apply_filters('pll_translate_post_meta', $destinations, 'tourism_destinations', pll_get_post_language($translation)));
    }
    $running = false;
}, 100);

/** Resolve a source category slug to its equivalent in the active language. */
function tourism_core_language_term_slug($slug, $taxonomy) {
    if (!function_exists('pll_get_term') || !function_exists('pll_current_language') || !pll_current_language()) return $slug;
    $source = get_term_by('slug', $slug, $taxonomy);
    if (!$source) return $slug;
    $id = pll_get_term($source->term_id);
    $term = $id ? get_term($id, $taxonomy) : null;
    return $term instanceof WP_Term ? $term->slug : $slug;
}
