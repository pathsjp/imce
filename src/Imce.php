<?php

/**
 * @file
 * Contains \Drupal\imce\Imce.
 */

namespace Drupal\imce;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\imce\ImceFM;

/**
 * Container class for static methods.
 */
class Imce {

  /**
   * Checks if a user has an imce profile assigned for a file scheme.
   */
  public static function access(AccountProxyInterface $user = NULL, $scheme = NULL) {
    return (bool) static::userProfile($user, $scheme);
  }

  /**
   * Returns a response for an imce request.
   */
  public static function response(Request $request, AccountProxyInterface $user, $scheme) {
    // Handle json request.
    if ($request->request->has('jsop')) {
      $fm = static::userFM($user, $scheme, $request);
      $fm->run();
      // Return html response if the flag is set.
      if ($request->request->get('return_html')) {
        return new Response('<html><body><textarea>' . Json::encode($fm->getResponse())  . '</textarea></body></html>');
      }
      return new JsonResponse($fm->getResponse());
    }
    // Prepare main page.
    $conf = static::userConf($user, $scheme);
    // Add active path to the conf.
    if (!isset($conf['active_path']) && $user->isAuthenticated() && $path = $request->getSession()->get('imce_active_path')) {
      if (static::pathAccess($path, $conf)) {
        $conf['active_path'] = $path; 
      }
    }
    // Set initial messages.
    if ($messages = static::getMessages()) {
      $conf['messages'] = $messages;
    }
    // Build the page.
    $page = array();
    $page['#attached']['drupalSettings']['imce'] = $conf;
    $page['#attached']['library'][] = 'imce/drupal.imce';
    // Add meta for robots.
    $robots = array(
      '#tag' => 'meta',
      '#attributes' => array(
        'name' => 'robots',
        'content' => 'noindex,nofollow',
      ),
    );
    $page['#attached']['html_head'][] = array($robots, 'robots');
    // Add plugin components.
    \Drupal::service('plugin.manager.imce.plugin')->buildPage($page, $user);
    // Render the page.
    $output = \Drupal::service('bare_html_page_renderer')->renderBarePage($page, t('File manager'), 'imce_page', array('#show_messages' => FALSE));
    return new Response($output);
  }

  /**
   * Returns a file manager instance for a user.
   */
  public static function userFM(AccountProxyInterface $user, $scheme = NULL, Request $request = NULL) {
    if ($conf = static::userConf($user, $scheme)) {
      return new ImceFM($conf, $user, $request);
    }
  }

  /**
   * Returns processed profile configuration for a user.
   */
  public static function userConf(AccountProxyInterface $user = NULL, $scheme = NULL) {
    $user = isset($user) ? $user : \Drupal::currentUser();
    $scheme = isset($scheme) ? $scheme : file_default_scheme();
    if (!$profile = static::userProfile($user, $scheme)) {
      return;
    }
    $conf = $profile->getConf();
    $conf['scheme'] = $scheme;
    $conf['pid'] = $profile->id();
    // Convert MB to bytes
    $conf['maxsize'] *= 1048576;
    $conf['quota'] *= 1048576;
    // Set root uri and url
    $conf['root_uri'] = $conf['scheme'] . '://';
    // file_create_url requires a filepath for some schemes like private://
    $conf['root_url'] = preg_replace('@/(?:%2E|\.)$@i', '', file_create_url($conf['root_uri'] . '.'));
    // Convert to relative
    if (!\Drupal::config('imce.settings')->get('abs_urls')) {
      $conf['root_url'] = file_url_transform_relative($conf['root_url']);
    }
    $conf['token'] = $user->isAnonymous() ? 'anon' : \Drupal::csrfToken()->get('imce');
    // Process folders
    $conf['folders'] = Imce::processUserFolders($conf['folders'], $user);
    // Call plugin processors
    \Drupal::service('plugin.manager.imce.plugin')->processUserConf($conf, $user);
    return $conf;
  }

  /**
   * Returns imce configuration profile for a user.
   */
  public static function userProfile(AccountProxyInterface $user = NULL, $scheme = NULL) {
    $profiles = &drupal_static(__METHOD__, array());
    $user = isset($user) ? $user : \Drupal::currentUser();
    $scheme = isset($scheme) ? $scheme : file_default_scheme();
    $profile = &$profiles[$user->id()][$scheme];
    if (!isset($profile)) {
      // Check stream wrapper
      if ($wrapper = \Drupal::service('stream_wrapper_manager')->getViaScheme($scheme)) {
        $imce_settings = \Drupal::config('imce.settings');
        $roles_profiles = $imce_settings->get('roles_profiles', array());
        $user_roles = array_flip($user->getRoles());
        $storage = \Drupal::entityManager()->getStorage('imce_profile');
        foreach ($roles_profiles as $rid => $profiles) {
          if (isset($user_roles[$rid]) && !empty($profiles[$scheme])) {
            if ($profile = $storage->load($profiles[$scheme])) {
              return $profile;
            }
          }
        }
      }
      $profile = FALSE;
    }
    return $profile;
  }

  /**
   * Processes user folders.
   */
  public static function processUserFolders(array $folders, AccountProxyInterface $user) {
    $ret = array();
    $token_service = \Drupal::token();
    $token_data = array('user' => $user);
    foreach ($folders as $folder) {
      $path = $token_service->replace($folder['path'], $token_data);
      if (static::validPath($path)) {
        $ret[$path] = $folder;
        unset($ret[$path]['path']);
      }
    }
    return $ret;
  }

  /**
   * Checks a permission in an imce page build.
   */
  public static function permissionInPage($permission, array $page) {
    if (!empty($page['#attached']['drupalSettings']['imce'])) {
      return static::permissionInConf($permission, $page['#attached']['drupalSettings']['imce']);
    }
    return FALSE;
  }

  /**
   * Checks a permission in a profile conf.
   */
  public static function permissionInConf($permission, array $conf) {
    if (!empty($conf['folders'])) {
      foreach ($conf['folders'] as $folder_conf) {
        if (static::permissionInFolderConf($permission, $folder_conf)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Checks a permission in a folder conf.
   */
  public static function permissionInFolderConf($permission, $folder_conf) {
    if ($folder_conf && !empty($folder_conf['permissions'])) {
      $permissions = $folder_conf['permissions'];
      return isset($permissions[$permission]) ? (bool) $permissions[$permission] : !empty($permissions['all']);
    }
    return FALSE;
  }

  /**
   * Checks if a folder path is accessible by a profile conf.
   */
  public static function pathAccess($path, array $conf) {
    // Predefined
    if (isset($conf['folders'][$path])) {
      return TRUE;
    }
    // Inherited
    if (!empty($conf['folders']) && static::validPath($path) && is_dir(static::joinPaths($conf['root_uri'], $path))) {
      foreach ($conf['folders'] as $folder_path => $folder_conf) {
        $is_root = $folder_path === '.';
        if ($is_root || strpos($path . '/', $folder_path . '/') === 0) {
          if (static::permissionInFolderConf('browse_subfolders', $folder_conf)) {
            // Validate the rest of the path.
            if ($filter = static::getNameFilter($conf)) {
              $rest = $is_root ? $path : substr($path, strlen($folder_path) + 1);
              foreach (explode('/', $rest) as $name) {
                if (preg_match($filter, $name)) {
                  return FALSE;
                }
              }
            }
            return TRUE;
          }
        }
      }
    }
  }

  /**
   * Splits a path into dirpath and filename.
   */
  public static function splitPath($path) {
    if (is_string($path) && $path != '') {
      $parts = explode('/', $path);
      $filename = array_pop($parts);
      $dirpath = implode('/', $parts);
      if ($filename !== '') {
        return array($dirpath === '' ? '.' : $dirpath, $filename);
      }
    }
  }

  /**
   * Creates a fle path by joining a dirpath and a filename.
   */
  public static function joinPaths($dirpath, $filename) {
    if ($dirpath === '.') {
      return $filename;
    }
    if ($filename === '.') {
      return $dirpath;
    }
    if (substr($dirpath, -1) !== '/') {
      $dirpath .= '/';
    }
    return $dirpath . $filename;
  }

  /**
   * Checks structure of a folder path.
   */
  public static function validPath($path) {
    return is_string($path) && ($path == '.' || !preg_match('@\\\\|(^|/)\.*(/|$)@', $path));
  }

  /**
   * Returns name filtering regexp from a profile conf.
   */
  public static function getNameFilter(array $conf) {
    $filters = isset($conf['name_filters']) ? $conf['name_filters'] : array();
    if (empty($conf['allow_dot_files'])) {
      $filters[] = '^\.|\.$';
    }
    return $filters ? '/' . implode('|', $filters) . '/' : '';
  }

  /**
   * Returns the contents of a directory.
   */
  public static function scanDir($diruri, array $options = array()) {
    $content = array('files' => array(), 'subfolders' => array());
    $browse_files = isset($options['browse_files']) ? $options['browse_files'] : TRUE;
    $browse_subfolders = isset($options['browse_subfolders']) ? $options['browse_subfolders'] : TRUE;
    if (!$browse_files && !$browse_subfolders) {
      return $content;
    }
    if (!$opendir = opendir($diruri)) {
      return $content + array('error' => TRUE);
    }
    // Prepare filters
    $name_filter = empty($options['name_filter']) ? FALSE : $options['name_filter'];
    $callback = empty($options['filter_callback']) ? FALSE : $options['filter_callback'];
    $uriprefix = substr($diruri, -1) === '/' ? $diruri : $diruri . '/';
    while (($filename = readdir($opendir)) !== FALSE) {
      // Exclude special names.
      if ($filename === '.' || $filename === '..') {
        continue;
      }
      // Check filter regexp.
      if ($name_filter && preg_match($name_filter, $filename)) {
        continue;
      }
      // Check browse permissions.
      $fileuri = $uriprefix . $filename;
      $is_dir = is_dir($fileuri);
      if ($is_dir ? !$browse_subfolders : !$browse_files) {
        continue;
      }
      // Execute callback.
      if ($callback) {
        $result = $callback($filename, $is_dir, $fileuri, $options);
        if ($result === 'continue') continue;
        elseif ($result === 'break') break;
      }
      $content[$is_dir ? 'subfolders' : 'files'][$filename] = $fileuri;
    }
    closedir($opendir);
    return $content;
  }

  /**
   * Returns a managed file entity by uri.
   * Optionally creates it.
   */
  public static function getFileEntity($uri, $create = FALSE, $save = FALSE) {
    $file = FALSE;
    if ($files = entity_load_multiple_by_properties('file', array('uri' => $uri))) {
      $file = reset($files);
    }
    elseif ($create) {
      $file = static::createFileEntity($uri, $save);
    }
    return $file;
  }

  /**
   * Creates a file entity with an uri.
   */
  public static function createFileEntity($uri, $save = FALSE) {
    $values = array(
      'uri' => $uri,
      'uid' => \Drupal::currentUser()->id(),
      'status' => 1,
      'filesize' => filesize($uri),
      'filename' => \Drupal::service('file_system')->basename($uri),
      'filemime' => \Drupal::service('file.mime_type.guesser')->guess($uri),
    );
    $file = entity_create('file', $values);
    if ($save) {
      $file->save();
    }
    return $file;
  }

  /**
   * Returns status messages by sanitizing the unsafe ones.
   */
  public static function getMessages() {
    if ($messages = drupal_get_messages()) {
      foreach ($messages as &$group) {
        foreach ($group as &$message) {
          $message = SafeMarkup::escape($message);
        }
      }
    }
    return $messages;
  }

  /**
   * Checks if the selected file paths are accessible by a user with Imce.
   * Returns the accessible paths.
   */
  public static function checkFilePaths(array $paths, AccountProxyInterface $user, $scheme) {
    $ret = array();
    // Initiate user's file manager to validate the selected paths.
    if ($fm = Imce::userFM($user, $scheme)) {
      // Check paths
      foreach ($paths as $path) {
        $item = $fm->checkItemPath($path);
        if ($item && $item->type === 'file') {
          $ret[] = $path;
        }
      }
    }
    return $ret;
  }
}