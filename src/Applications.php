<?php

namespace Xzito\Applications;

class Applications {
  public const QUERY_ARG = 'filter-by-application';
  public const TAXONOMY_ID = 'related_application';

  public static function all($options = ['return_type' => 'object']) {
    $return_type = self::set_return_type($options);

    $query = new \WP_Query([
      'nopaging' => true,
      'post_type' => ApplicationPostType::ID,
      'fields' => 'ids',
    ]);

    $application_ids = $query->posts ?? [];

    if ($return_type === 'ids') {
      $applications = $application_ids;
    } else {
      $applications = [];

      array_map(function ($id) use (&$applications) {
        $applications[] = new Application($id);
      }, $application_ids);
    }

    return $applications;
  }

  private static function set_return_type($options) {
    $return_types = ['id', 'name', 'slug', 'object'];
    $return_type = $options['return_type'];

    if (!in_array($return_type, $return_types)) {
      $return_type = 'object';
    }

    return $return_type;
  }

  private static function filter_query_arg() {
    return $_REQUEST[self::QUERY_ARG] ?? false;
  }

  private static function selected($filtered_slug, $application_slug) {
    if ($filtered_slug === $application_slug) {
      $markup = 'selected="selected"';
    }

    return $markup ?? '';
  }

  private static function in_media_library() {
    return (get_current_screen()->base === 'upload' ? true : false);
  }

  public function __construct() {
    add_action('plugins_loaded', [$this, 'create_options_page']);
    add_action('init', [$this, 'create_post_type'], 0);
    add_action('init', [$this, 'create_taxonomy'], 0);
    add_action('init', [$this, 'create_bulk_action'], 0);
    add_action('init', [$this, 'create_terms'], 10);
    add_action('restrict_manage_posts', [$this, 'create_filters']);
    add_action('acf/save_post', [$this, 'set_fields_on_save'], 20);
    add_action('wp_trash_post', [$this, 'destroy_terms']);

    add_filter('parse_query', [$this, 'filter_query']);
  }

  public function create_post_type() {
    new ApplicationPostType();
  }

  public function create_taxonomy() {
    new RelatedApplicationTaxonomy();
  }

  public function create_bulk_action() {
    new BulkAction();
  }

  public function create_options_page() {
    $page_title = ApplicationPostType::PLURAL_NAME . ' Page';
    $menu_title = ApplicationPostType::PLURAL_NAME . ' Page';
    $parent_slug = 'edit.php?post_type=' . ApplicationPostType::ID;

    if (function_exists('acf_add_options_sub_page')) {
      acf_add_options_sub_page([
        'page_title' => $page_title,
        'menu_title' => $menu_title,
        'parent_slug' => $parent_slug,
      ]);
    }
  }

  public function create_terms() {
    foreach (self::all() as $application) {
      $this->create_related_application_term($application);
    }
  }

  public function create_filters($post_type) {
    if (!self::in_media_library()) {
      return;
    }

    $filtered_slug = self::filter_query_arg() ?? '';

    $applications = Applications::all();
    sort($applications);

    $options = [];

    array_map(function ($application) use (&$options, $filtered_slug) {
      $selected = self::selected($filtered_slug, $application->slug());
      $slug = $application->slug();
      $name = $application->name();

      $options[] = "<option value=\"$slug\" $selected>$name</option>";
    }, $applications);

    $markup = '<select name=' . self::QUERY_ARG . '>';
    $markup .= '<option value="0">All applications</option>';
    $markup .= implode($options);
    $markup .= '</select>';
    $markup .= '&nbsp;';

    echo $markup;
  }

  public function filter_query($query) {
    if (!(is_admin() && $query->is_main_query())) {
      return $query;
    }

    if (!self::in_media_library()) {
      return $query;
    }

    if (!self::filter_query_arg()) {
      return $query;
    }

    if (self::filter_query_arg() === '0') {
      return $query;
    }

    $query->query_vars['tax_query'] = [
      [
        'taxonomy' => Applications::TAXONOMY_ID,
        'field' => 'slug',
        'terms' => self::filter_query_arg(),
      ],
    ];

    return $query;
  }

  public function set_fields_on_save($post_id) {
    if (!$this->will_set_on_save($post_id)) {
      return;
    }

    $application = new Application($post_id);

    $this->set_post_data($application);
    $this->set_post_thumbnail($application);
    $this->create_related_application_term($application);
  }

  public function destroy_terms($post_id) {
    if (!$this->will_set_on_save($post_id)) {
      return;
    }

    $application = new Application($post_id);

    $this->delete_related_application_term($application);
  }

  private function will_set_on_save($id) {
    return (get_post_type($id) == ApplicationPostType::ID ? true : false);
  }

  private function set_post_data($application) {
    wp_update_post([
      'ID' => $application->id(),
      'post_name' => $application->slug(),
      'post_title' => $application->name(),
    ]);
  }

  private function set_post_thumbnail($application) {
    set_post_thumbnail($application->id(), $application->card_image());
  }

  private function create_related_application_term($application) {
    if (get_post_status($application->id()) === 'publish') {
      wp_insert_term($application->name(), self::TAXONOMY_ID);
    }
  }

  private function delete_related_application_term($application) {
    $term = get_term_by('name', $application->name(), self::TAXONOMY_ID);

    wp_delete_term($term->term_id, self::TAXONOMY_ID);
  }
}
