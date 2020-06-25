<?php

namespace Xzito\Applications;
class ApplicationsPage {
  private $name;
  private $banner;
  private $about;
  private $cta;

  public function __construct() {
    $this->set_name();
    $this->set_banner();
    $this->set_about();
    $this->set_cta();
  }

  public function name() {
    return $this->name;
  }

  public function banner($image_size = 'full') {
    return wp_get_attachment_image_url($this->banner, $image_size);
  }

  public function link() {
    return get_post_type_archive_link(ApplicationPostType::ID);
  }

  public function about() {
    return $this->about;
  }

  public function cta($size = 'thumbnail') {
    $cta = $this->cta;

    $cta['image'] = wp_get_attachment_image_url($cta['image'], $size);

    return $cta;
  }

  private function set_name() {
    $this->name = get_field('applications_page', 'options')['page_title'];
  }

  private function set_banner() {
    $this->banner = get_field('applications_page', 'options')['banner'];
  }

  private function set_about() {
    $this->about = get_field('applications_page', 'options')['about'];
  }

  private function set_cta() {
    $cta_settings   = get_field('applications_page', 'options')['cta'];
    $overlay        = $cta_settings['overlay_color'];
    $overlay_colors = [
      'light' => '#F5F5F5',
      'blue'  => '#293583'
    ];

    $cta_data = [
      'show'           => $cta_settings['show'],
      'heading'        => $cta_settings['heading'],
      'overlay_color'  => $overlay,
      'overlay_hex'    => $overlay_colors[$overlay],
      'text'           => $cta_settings['text'],
      'button_text'    => $cta_settings['button_text'],
      'link'           => $cta_settings['link'],
      'side_image_tag' => wp_get_attachment_image($cta_settings['image'], '1200x0'),
      'bg_image_url'   => wp_get_attachment_image_url($cta_settings['bg_image'], 'fullwidth'),
    ];

    $this->cta = $cta_data;
  }
}
