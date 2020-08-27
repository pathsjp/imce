<?php

namespace Drupal\imce;

/**
 * Image compress trait.
 */
trait ImceImageCompressTrait {

  /**
   * Optimize image with Tinify API.
   *
   * @param string $uri
   *   The image uri.
   *
   * @return string
   *   The image uri.
   */
  public static function compressTinify($uri) {
    \Tinify\setkey(\Drupal::config('imce.settings')->get('tinify_api_key'));
    \Tinify\fromFile($uri)->toFile($uri);

    return $uri;
  }

  /**
   * Optimize image with GD library.
   *
   * @param string $uri
   *   The image uri.
   *
   * @return string
   *   The image uri.
   */
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
    imagejpeg($image, $uri, \Drupal::config('imce.settings')->get('quality_gd'));

    // Return destination file.
    return $uri;
  }

  /**
   * Optimize image with Imagick library.
   *
   * @param string $uri
   *   The image uri.
   *
   * @return string
   *   The image uri.
   */
  public function compressImagick($uri) {

    $imagePath = \Drupal::service('file_system')->realpath($uri);
    $img = new \Imagick();
    $img->readImage($imagePath);
    $img->setImageCompression(\Imagick::COMPRESSION_JPEG2000);
    $img->setImageCompressionQuality(\Drupal::config('imce.settings')->get('quality_imagick'));
    $img->stripImage();
    $img->writeImage($imagePath);

    return $uri;
  }

  /**
   * No compress.
   *
   * @param string $uri
   *   The string uri.
   *
   * @return bool
   *   Return false.
   */
  public function noCompress($uri) {
    return FALSE;
  }

  /**
   * The image compress.
   *
   * This method is a gateway to the compression method chosen.
   *
   * @param string $uri
   *   The string uri.
   *
   * @return string
   *   The string uri.
   */
  public function imageCompress($uri) {
    if (!exif_imagetype($uri)) {
      return FALSE;
    }

    $compressType = \Drupal::config('imce.settings')->get('compress_type');
    $this->$compressType($uri);
  }

}
