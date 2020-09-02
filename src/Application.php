<?php

namespace Xzito\Applications;

use Xzito\Products\Product;
use Xzito\Portfolio\PortfolioPiece;
use Xzito\Portfolio\PortfolioPostType;

class Application {
  private $id;
  private $name;
  private $card_image;
  private $banner;
  private $main_copy;
  private $products_heading;
  private $related_products;
  private $related_products_count;
  private $related_projects;
  private $related_projects_count;
  private $projects_heading;
  private $list;
  private $form_top_content;
  private $form_id;

  public static function find_by_name($name) {
    $query = new \WP_Query([
      'posts_per_page' => 1,
      'post_type'      => ApplicationPostType::ID,
      'name'           => $name,
      'fields'         => 'ids',
    ]);

    $id = $query->posts[0];

    return new Application($id);
  }

  public static function find_related_attachments($name) {
    $query = new \WP_Query([
      'nopaging'    => true,
      'post_type'   => 'attachment',
      'post_status' => 'inherit',
      'fields'      => 'ids',
      'tax_query'   => [
        [
          'taxonomy' => Applications::TAXONOMY_ID,
          'field' => 'name',
          'terms' => $name,
        ],
      ],
    ]);

    return $query->posts ?? [];
  }

  private static function find_published($post_type, $post_ids) {
    $ids = [];

    foreach ($post_ids as $id) {
      $ids[] = intval($id);
    }

    $query = new \WP_Query([
      'nopaging' => true,
      'post_type' => $post_type,
      'post_status' => 'publish',
      'fields' => 'ids',
      'post__in' => $ids,
    ]);

    return $query->posts ?? [];
  }

  public function __construct($application_id = '') {
    $this->id = $application_id;
    $this->set_name();
    $this->set_banner();
    $this->set_main_copy();
    $this->set_card_image();
    $this->set_products_heading();
    $this->set_related_products();
    $this->set_related_products_count();
    $this->set_related_projects_count();
    $this->set_related_projects();
    $this->set_projects_heading();
    $this->set_list();
    $this->set_form_top_content();
    $this->set_form_id();
  }

  public function id() {
    return $this->id;
  }

  public function name() {
    return $this->name;
  }

  public function slug() {
    return get_post_field('post_name', $this->id);
  }

  public function link() {
    return get_post_permalink($this->id);
  }

  public function term() {
    return get_term_by('name', $this->name, Applications::TAXONOMY_ID);
  }

  public function card_image($size = 'thumbnail') {
    return wp_get_attachment_image_url($this->card_image, $size);
  }

  public function banner($size = 'full') {
    return wp_get_attachment_image_url($this->banner, $size);
  }

  public function main_copy() {
    $copy = $this->main_copy;

    $image_id = $copy['side_image'];
    $image_tag = wp_get_attachment_image($image_id, 'full');

    $copy['side_image_tag'] = $image_tag;

    return $copy;
  }

  public function products_heading() {
    return $this->products_heading;
  }

  public function related_products() {
    return $this->related_products;
  }

  public function related_products_count() {
    return $this->related_products_count;
  }

  public function related_projects_count() {
    return $this->related_projects_count;
  }

  public function related_projects() {
    return $this->related_projects;
  }

  public function projects_heading() {
    return $this->projects_heading;
  }

  public function list() {
    $list = $this->list;

    $list['side_image_tag'] = wp_get_attachment_image($list['side_image'], 'full' );
    return $list;
  }

  public function form_top_content() {
    return $this->form_top_content;
  }

  public function portfolio_archive_link() {
    $archive_link = get_post_type_archive_link('portfolio');
    $application_slug = $this->slug();

    return add_query_arg('filter-application', $application_slug, $archive_link);
  }

  public function form() {
    return $this->build_form(['id' => $this->form_id]);
  }

  private function set_name() {
    $default = 'Unnamed Application';

    $this->name = (get_field('application_info', $this->id)['name'] ?: $default);
  }

  private function set_main_copy() {
    $this->main_copy = get_field('application_main_copy', $this->id);
  }

  private function set_card_image() {
    $this->card_image = get_field('application_images', $this->id)['card'];
  }

  private function set_banner() {
    $this->banner = get_field('application_images', $this->id)['banner'];
  }

  private function set_products_heading() {
    $this->products_heading = get_field('products_heading', $this->id);
  }

  private function set_related_products() {
    $products_ids = get_field('products_applications', $this->id);

    $related_products = array_map(function ($product_id) {
      return new Product($product_id);
    }, $products_ids);

    $this->related_products = $related_products;
  }

  private function set_related_products_count() {
    $products_ids = get_field('products_applications', $this->id);

    $this->related_products_count = count($products_ids);
  }

  private function set_related_projects_count() {
    $projects_ids = get_field('portfolios_applications', $this->id);

    $this->related_projects_count = count($projects_ids);
  }

  private function set_related_projects() {
    $projects_ids = get_field('portfolios_applications', $this->id);

    $related_projects = array_map(function($project_id) {
      return new PortfolioPiece($project_id);
    }, self::find_published(PortfolioPostType::ID, $projects_ids));

    $this->related_projects = $related_projects;
  }

  private function set_projects_heading() {
    $this->projects_heading = get_field('projects_heading', $this->id);
  }

  private function set_list() {
    $this->list = get_field('list_with_side_image', $this->id);
  }

  private function set_form_top_content() {
    $this->form_top_content = get_field('callout_form', $this->id)['top_content'];
  }

  private function set_form_id() {
    $this->form_id = get_field('callout_form', $this->id)['form'];
  }

  private function build_form($form_args) {
    $defaults = [
      'id'               => '',
      'show_title'       => false,
      'show_description' => false,
      'show_inactive'    => false,
      'field_values'     => false,
      'ajax'             => true,
      'tabindex'         => null,
      'echo'             => false,
    ];

    $args = $form_args + $defaults;

    return gravity_form(...array_values($args));
  }
}
