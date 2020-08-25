<?php

namespace Drupal\imce;

/**
 * Image compress trait.
 */
trait ImceImageCompressTrait {


  public static function compressTinify($uri) {
    \Tinify\setkey(\Drupal::config('imce.settings')->get('tinify_api_key'));
    \Tinify\fromFile($uri)->toFile($uri);
  }

  public function compressGd($uri) {
    $info = getimagesize($uri);

    if ($info['mime'] == 'image/jpeg') {
      $image = imagecreatefromjpeg($uri);
    }
    elseif ($info['mime'] == 'image/gif') {
      $image = imagecreatefromgif($uri);
    }
    elseif ($info['mime'] == 'image/png') {
      $image = imagecreatefrompng($uri);
    }
    else {
      die('Unknown image file format');
    }

    // Compress and save file to jpg.
    imagejpeg($image, $uri, 70);

    // Return destination file.
    return $uri;
  }

  public function compressImagick($uri) {

    $imagePath = \Drupal::service('file_system')->realpath($uri);
    $img = new \Imagick();
    $img->readImage($imagePath);
    $img->setImageCompression(\Imagick::COMPRESSION_JPEG2000);
    $img->setImageCompressionQuality(70);
    $img->stripImage();
    $img->writeImage($imagePath);
  }

}
