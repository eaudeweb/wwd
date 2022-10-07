<?php
/**
 * @file
 * Eau de Web Theme theme file.
 */

$color = [
  'white' => theme_get_setting('sc__white'),
  'black' => theme_get_setting('sc__black'),
  'gray-100' => theme_get_setting('sc__gray_100'),
  'gray-200' => theme_get_setting('sc__gray_200'),
  'gray-300' => theme_get_setting('sc__gray_300'),
  'gray-400' => theme_get_setting('sc__gray_400'),
  'gray-500' => theme_get_setting('sc__gray_500'),
  'gray-600' => theme_get_setting('sc__gray_600'),
  'gray-700' => theme_get_setting('sc__gray_700'),
  'gray-800' => theme_get_setting('sc__gray_800'),
  'gray-900' => theme_get_setting('sc__gray_900'),
  'primary' => theme_get_setting('sc__primary'),
  'secondary' => theme_get_setting('sc__secondary'),
  'success' => theme_get_setting('sc__success'),
  'warning' => theme_get_setting('sc__warning'),
  'info' => theme_get_setting('sc__info'),
  'danger' => theme_get_setting('sc__danger'),
  'light' => theme_get_setting('sc__light'),
  'dark' => theme_get_setting('sc__dark'),
];

$link = [
  'color' => 
    theme_get_setting('sl__custom') ?
      theme_get_setting('sl__color') :
      $color['primary'],
  'h-color' => 
    theme_get_setting('sl__custom') ?
      theme_get_setting('sl__h_color') :
      $color['primary'],
];

function _root($color, $link) {
  $root = ':root {';

  foreach ($color as $v => $k ) {
    $root .= '--c-' . $v . ': ' . $k . ';';
  }

  $root .= '
    --rgb-white: ' . hexToRgb($color['white']) . ';
    --rgb-black: ' . hexToRgb($color['black']) . ';
    --rgb-primary: ' . hexToRgb($color['primary']) . ';
    --rgb-secondary: ' . hexToRgb($color['secondary']) . ';
    --rgb-success: ' . hexToRgb($color['success']) . ';
    --rgb-warning: ' . hexToRgb($color['warning']) . ';
    --rgb-info: ' . hexToRgb($color['info']) . ';
    --rgb-danger: ' . hexToRgb($color['danger']) . ';
    --rgb-light: ' . hexToRgb($color['light']) . ';
    --rgb-dark: ' . hexToRgb($color['dark']) . ';
    --rgb-body-color: ' . hexToRgb($color['dark']) . '??;
    --rgb-body-bg: ' . hexToRgb($color['dark']) . '??;
    --font-heading: ' . theme_get_setting('sf__f') . ';
    --font-monospace: ' . theme_get_setting('sf__sans_serif') . '??;
    --body-font-family: ' . theme_get_setting('sf__body') . ';
    --body-font-size: ' . theme_get_setting('sf__size') . 'rem;
    --body-line-height: ' . theme_get_setting('sf__line-height') . ';
    --link-color: ' . $link['color'] . ';
    --link-color-h: ' . $link['h-color'] . ';
    --link-decoration: ' . theme_get_setting('sl__decoration') . ';
    --link-decoration-h: ' . theme_get_setting('sl__h_decoration') . ';
  ';

  $root .= '}';
  return $root;
}

$page['#attached']['html_head'][] = [
  [
    '#tag' => 'style',
    '#value' => _root($color, $link),
  ],
  'cms--base'
];

function hexToRgb($hex, $normalize = FALSE) {
  $hex = substr($hex, 1);
  if (strlen($hex) == 3) {
    $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
  }
  $c = hexdec($hex);
  for ($i = 16; $i >= 0; $i -= 8) {
    $a[] = ($c >> $i & 0xff) / ($normalize ? 255 : 1);
  }
  $out = implode(",", $a);
  return $out;
}
