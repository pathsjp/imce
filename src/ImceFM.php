<?php

/**
 * @file
 * Contains \Drupal\imce\ImceFM.
 */

namespace Drupal\imce;

use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\imce\Imce;

/**
 * Imce File Manager.
 */
class ImceFM {

  /**
   * The last created file manager.
   *
   * @var \Drupal\imce\ImceFM
   */
  public static $fm;

  /**
   * File manager configuration.
   *
   * @var array
   */
  public $conf;

  /**
   * Active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  public $user;

  /**
   * Active request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  public $request;

  /**
   * Current validation status for the configuration.
   *
   * @var boolean
   */
  public $validated;

  /**
   * Currently selected items.
   *
   * @var array
   */
  public $selection = array();

  /**
   * Folder tree.
   *
   * @var array
   */
  public $tree = array();

  /**
   * Active folder.
   *
   * @var \Drupal\imce\ImceFolder
   */
  public $activeFolder;

  /**
   * Response data.
   *
   * @var array
   */
  public $response = array();

  /**
   * Constructs the file manager.
   *
   * @param array $conf
   *   File manager configuration
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The active user
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The active request that contains parameters for file manager operations
   */
  public function __construct(array $conf, AccountProxyInterface $user, Request $request = NULL) {
    static::$fm = $this;
    $this->conf = $conf;
    $this->request = $request;
    $this->user = $user;
    $this->init();
  }

  /**
   * Initializes the file manager by validating the current configuration and request.
   */
  protected function init() {
    if (!isset($this->validated)) {
      // Create the root.
      $root = new ImceFolder('.');
      $root->setPath('.');
      // Check initialization error
      if ($error = $this->getInitError()) {
        if (!$this->getConf('silentInit')) {
          drupal_set_message($error, 'error');
        }
      }
      else {
        $this->initSelection();
      }
      $this->validated = $error === FALSE;
    }
    return $this->validated;
  }

  /**
   * Performs the initialization and returns the first error message.
   */
  protected function getInitError() {
    $conf = &$this->conf;
    // Check configuration options.
    $keys = array('folders', 'root_uri');
    foreach ($keys as $key) {
      if (empty($conf[$key])) {
        return t('Missing configuration %key.', array('%key' => $key));
      }
    }
    // Check root.
    $root_uri = $conf['root_uri'];
    if (!is_dir($root_uri)) {
      if (!mkdir($root_uri, $this->getConf('chmod_directory', 0775), TRUE)) {
        return t('Missing root folder.');
      }
    }
    // Check and add predefined folders
    foreach ($conf['folders'] as $path => $folder_conf) {
      $path = (string) $path;
      $uri = $this->createUri($path);
      if (is_dir($uri) || mkdir($uri, $this->getConf('chmod_directory', 0775), TRUE)) {
        $this->addFolder($path, $folder_conf);
      }
      else {
        unset($conf['folders'][$path]);
      }
    }
    if (!$conf['folders']) {
      return t('No valid folder definitions found.');
    }
    // Check and set active folder if provided.
    $path = $this->getPost('active_path');
    if (isset($path) && $path !== '') {
      if ($folder = $this->checkFolderPath($path)) {
        $this->activeFolder = $folder;
        // Remember active path
        if ($this->user->isAuthenticated()) {
          if (!isset($conf['folders'][$path]) || count($conf['folders']) > 1 || $folder->getPermission('browse_subfolders')) {
            $this->request->getSession()->set('imce_active_path', $path);
          }
        }
      }
      else {
        return t('Invalid active folder path: %path.', array('%path' => $path));
      }
    }
    return FALSE;
  }

  /**
   * Initiates the selection with a list of user provided item paths.
   */
  protected function initSelection() {
    $paths = $this->getPost('selection');
    if ($paths && is_array($paths)) {
      foreach ($paths as $path) {
        if ($item = $this->checkItemPath($path)) {
          $item->select();
        }
        // Remove non-existing paths from js
        else {
          $this->removePathFromJs($path);
        }
      }
    }
  }

  /**
   * Runs an operation on the file manager.
   */
  public function run($op = NULL) {
    if (!$this->validated) {
      return FALSE;
    }
    // Check operation.
    if (!isset($op)) {
      $op = $this->getOp();
    }
    if (!$op || !is_string($op)) {
      return FALSE;
    }
    // Validate security token.
    $token = $this->getPost('token');
    if (!$token || $token !== $this->getConf('token')) {
      drupal_set_message(t('Invalid security token.'), 'error');
      return FALSE;
    }
    // Let plugins handle the operation.
    $return = \Drupal::service('plugin.manager.imce.plugin')->handleOperation($op, $this);
    if ($return === FALSE) {
      drupal_set_message(t('Invalid operation %op.', array('%op' => $op)), 'error');
    }
    return $return;
  }

  /**
   * Adds a folder to the tree.
   */
  public function addFolder($path, array $conf = NULL) {
    // Existing.
    if ($folder = $this->getFolder($path)) {
      if (isset($conf)) {
        $folder->setConf($conf);
      }
      return $folder;
    }
    // New. Append to the parent.
    if ($parts = Imce::splitPath($path)) {
      list($parent_path, $name) = $parts;
      if ($parent = $this->addFolder($parent_path)) {
        $folder = new ImceFolder($name, $conf);
        $parent->appendItem($folder);
        return $folder;
      }
    }
  }

  /**
   * Returns a folder from the tree.
   */
  public function getFolder($path) {
    return isset($this->tree[$path]) ? $this->tree[$path] : NULL;
  }

  /**
   * Checks the existence of a user provided folder path.
   * Adds and returns the folder object if it is accessible.
   */
  public function checkFolderPath($path) {
    if (Imce::pathAccess($path, $this->conf)) {
      return $this->addFolder($path);
    }
  }

  /**
   * Checks the existence of a user provided item path.
   * Scans the parent folder and returns the item object if it is accessible.
   */
  public function checkItemPath($path) {
    if ($parts = Imce::splitPath($path)) {
      if ($folder = $this->checkFolderPath($parts[0])) {
        return $folder->checkItem($parts[1]);
      }
    }
  }

  /**
   * Creates an uri from a relative path.
   */
  public function createUri($path) {
    return Imce::joinPaths($this->getConf('root_uri'), $path);
  }

  /**
   * Returns the current operation in the request.
   */
  public function getOp() {
    return $this->getPost('jsop');
  }

  /**
   * Returns value of a posted parameter.
   */
  public function getPost($key, $default = NULL) {
    return $this->request ? $this->request->request->get($key, $default) : $default;
  }

  /**
   * Returns a configuration option.
   */
  public function getConf($key, $default = NULL) {
    return isset($this->conf[$key]) ? $this->conf[$key] : $default;
  }

  /**
   * Returns the contents of a directory.
   */
  public function scanDir($diruri, array $options = array()) {
    $options += array('name_filter' => $this->getNameFilter());
    $scanner = $this->getConf('scanner', 'Drupal\imce\Imce::scanDir');
    return call_user_func($scanner, $diruri, $options);
  }

  /**
   * Returns name filtering regexp.
   */
  public function getNameFilter() {
    return Imce::getNameFilter($this->conf);
  }

  /**
   * Returns currently selected items.
   */
  public function getSelection() {
    return $this->selection;
  }

  /**
   * Returns selected items grouped by parent folder path.
   */
  public function groupSelection() {
    return $this->groupItems($this->selection);
  }

  /**
   * Groups the items by parent path and type.
   */
  public function groupItems(array $items) {
    $group = array();
    foreach ($items as $item) {
      $path = $item->parent->getPath();
      $type = $item->type == 'folder' ? 'subfolders' : 'files';
      $group[$path][$type][$item->name] = $item;
    }
    return $group;
  }

  /**
   * Selects an item.
   */
  public function selectItem(ImceItem $item) {
    if (!$item->selected && $item->parent) {
      $item->selected = TRUE;
      $this->selection[] = $item;
    }
  }

  /**
   * Deselects an item.
   */
  public function deselectItem(ImceItem $item) {
    if ($item->selected) {
      $item->selected = FALSE;
      $index = array_search($item, $this->selection);
      if ($index !== FALSE) {
        array_splice($this->selection, $index, 1);
      }
    }
  }

  /**
   * Add an item to the response.
   */
  public function addItemToJs(ImceItem $item) {
    if ($parent = $item->parent) {
      if ($path = $parent->getPath()) {
        $name = $item->name;
        $uri = $item->getUri();
        if ($item->type === 'folder') {
          $this->response['added'][$path]['subfolders'][$name] = $this->getFolderProperties($uri);
        }
        else {
          $this->response['added'][$path]['files'][$name] = $this->getFileProperties($uri);
        }
      }
    }
  }

  /**
   * Sets an item as removed in the response.
   */
  public function removeItemFromJs(ImceItem $item) {
    $this->removePathFromJs($item->getPath());
  }

  /**
   * Sets a path as removed in the response.
   */
  public function removePathFromJs($path) {
    if (isset($path)) {
      $this->response['removed'][] = $path;
    }
  }

  /**
   * Returns js properties of a file.
   */
  public function getFileProperties($uri) {
    $properties = array('date' => filemtime($uri), 'size' => filesize($uri));
    if (preg_match('/\.(jpe?g|png|gif)$/i', $uri) && $info = getimagesize($uri)) {
      $properties['width'] = $info[0];
      $properties['height'] = $info[1];
    }
    return $properties;
  }

  /**
   * Returns js properties of a folder.
   */
  public function getFolderProperties($uri) {
    return array('date' => filemtime($uri));
  }

  /**
   * Returns the response data.
   */
  public function getResponse() {
    $defaults = array('jsop' => $this->getOp());
    if ($messages = $this->getMessages()) {
      $defaults['messages'] = $messages;
    }
    if ($folder = $this->activeFolder) {
      $defaults['active_path'] = $folder->getPath();
    }
    return $this->response + $defaults;
  }

  /**
   * Adds response data.
   */
  public function addResponse($key, $value) {
    return $this->response[$key] = $value;
  }

  /**
   * Returns the status messages.
   */
  public function getMessages() {
    return Imce::getMessages();
  }

  /**
   * Checks parent folder permissions of the given items.
   */
  public function validatePermissions(array $items, $file_perm = NULL, $subfolder_perm = NULL) {
    foreach ($this->groupItems($items) as $path => $content) {
      $parent = $this->getFolder($path);
      // Parent contains files but does not have the file permission
      if (!empty($content['files'])) {
        if (!isset($file_perm) || !$parent->getPermission($file_perm)) {
          return FALSE;
        }
      }
      // Parent contains subfolders but does not have the subfolder permission
      if (!empty($content['subfolders'])) {
        if (!isset($subfolder_perm) || !$parent->getPermission($subfolder_perm)) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  /**
   * Checks the existence of a predefined path.
   */
  public function validatePredefinedPath(array $items, $silent = FALSE) {
    foreach ($items as $item) {
      if ($item->type === 'folder' && ($folder = $item->hasPredefinedPath())) {
        if (!$silent) {
          drupal_set_message(t('%path is a predefined path and can not be modified.', array('%path' => $folder->getPath())), 'error');
        }
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Validates a file name.
   */
  public function validateFileName($filename, $silent = FALSE) {
    // Basic validation.
    if ($filename === '.' || $filename === '..' || !($len = strlen($filename)) || $len > 240) {
      return FALSE;
    }
    // Test name filters.
    if ($name_filter = $this->getNameFilter()) {
      if (preg_match($name_filter, $filename)) {
        if (!$silent) {
          drupal_set_message(t('%filename is not allowed.', array('%filename' => $filename)), 'error');
        }
        return FALSE;
      }
    }
    // Test chars forbidden in various operating systems.
    if (preg_match('@^\s|\s$|[/\\\\:\*\?"<>\|\x00-\x1F]@', $filename)) {
      if (!$silent) {
        drupal_set_message(t('%filename contains invalid characters. Use only alphanumeric characters for better portability.', array('%filename' => $filename)), 'error');
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Validates min/max image dimensions.
   */
  public function validateDimensions(array $items, $width, $height, $silent = FALSE) {
    // Check min dimensions
    if ($width < 1 || $height < 1) {
      return FALSE;
    }
    // Check max dimensions.
    $maxwidth = $this->getConf('maxwidth');
    $maxheight = $this->getConf('maxheight');
    if ($maxwidth && $width > $maxwidth || $maxheight && $height > $maxheight) {
      if (!$silent) {
        drupal_set_message(t('Image dimensions must be smaller than %dimensions pixels.', array('%dimensions' => $maxwidth . 'x' . $maxwidth)), 'error');
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Checks if all the selected items are images.
   */
  public function validateImageTypes(array $items, $silent = FALSE) {
    $regex = '/\.(' . preg_replace('/ +/', '|', preg_quote(trim($this->getConf('image_extensions', 'jpg jpeg png gif')))) . ')$/i';
    foreach ($items as $item) {
      if ($item->type === 'folder' || !preg_match($regex, $item->name)) {
        if (!$silent) {
          drupal_set_message(t('%name is not an image.', array('%name' => $item->name)), 'error');
        }
        return FALSE;
      }
    }
    return TRUE;
  }
}