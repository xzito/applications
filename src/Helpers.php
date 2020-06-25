<?php

namespace Xzito\Applications;

class Helpers {
  public static function move_array_element($array, $from_index, $to_index) {
    $part_1 = array_splice($array, $from_index, 1);
    $part_2 = array_splice($array, 0, $to_index);

    return array_merge($part_2, $part_1, $array);
  }
}
