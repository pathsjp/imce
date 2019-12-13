<?php

namespace Drupal\imce\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\imce\Imce;
use Drupal\imce\ImcePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for Imce Profile entities.
 */
class ImceProfileForm extends EntityForm {

  /**
   * Plugin manager for Imce Plugins.
   *
   * @var \Drupal\imce\ImcePluginManager
   */
  protected $pluginManagerImce;

  /**
   * The construct method.
   *
   * @param \Drupal\imce\ImcePluginManager $plugin_manager_imce
   *   Plugin manager for Imce Plugins.
   */
  public function __construct(ImcePluginManager $plugin_manager_imce) {
    $this->pluginManagerImce = $plugin_manager_imce;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.imce.plugin')
    );
  }

  /**
   * Folder permissions.
   *
   * @var array
   */
  public $folderPermissions;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $imce_profile = $this->getEntity();
    // Check duplication.
    if ($this->getOperation() === 'duplicate') {
      $imce_profile = $imce_profile->createDuplicate();
      $imce_profile->set('label', $this->t('Duplicate of @label', ['@label' => $imce_profile->label()]));
      $this->setEntity($imce_profile);
    }
    // Label.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $imce_profile->label(),
      '#maxlength' => 64,
      '#required' => TRUE,
      '#weight' => -20,
    ];
    // Id.
    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [get_class($imce_profile), 'load'],
        'source' => ['label'],
      ],
      '#default_value' => $imce_profile->id(),
      '#maxlength' => 32,
      '#required' => TRUE,
      '#weight' => -20,
    ];
    // Description.
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $imce_profile->get('description'),
      '#weight' => -10,
    ];
    // Conf.
    $conf = [
      '#tree' => TRUE,
    ];
    // Extensions.
    $conf['extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions'),
      '#default_value' => $imce_profile->getConf('extensions'),
      '#maxlength' => 255,
      '#description' => $this->t('Separate extensions with a space, and do not include the leading dot.') . ' ' . $this->t('Set to * to allow all extensions.'),
      '#weight' => -9,
    ];
    // File size.
    $maxsize = file_upload_max_size();
    $conf['maxsize'] = [
      '#type' => 'number',
      '#min' => 0,
      '#max' => ceil($maxsize / 1024 / 1024),
      '#step' => 'any',
      '#size' => 8,
      '#title' => $this->t('Maximum file size'),
      '#default_value' => $imce_profile->getConf('maxsize'),
      '#description' => $this->t('Maximum allowed file size per upload.') . ' ' . $this->t('Your PHP settings limit the upload size to %size.', ['%size' => format_size($maxsize)]),
      '#field_suffix' => $this->t('MB'),
      '#weight' => -8,
    ];
    // Quota.
    $conf['quota'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 'any',
      '#size' => 8,
      '#title' => $this->t('Disk quota'),
      '#default_value' => $imce_profile->getConf('quota'),
      '#description' => $this->t('Maximum disk space that can be allocated by a user.'),
      '#field_suffix' => $this->t('MB'),
      '#weight' => -7,
    ];
    // Image dimensions.
    $conf['dimensions'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['dimensions-wrapper form-item']],
      '#weight' => -6,
    ];
    $conf['dimensions']['label'] = [
      '#markup' => '<label>' . $this->t('Maximum image dimensions') . '</label>',
    ];
    $conf['dimensions']['maxwidth'] = [
      '#type' => 'number',
      '#default_value' => $imce_profile->getConf('maxwidth'),
      '#maxlength' => 5,
      '#min' => 0,
      '#size' => 8,
      '#placeholder' => $this->t('Width'),
      '#field_suffix' => ' x ',
      '#parents' => ['conf', 'maxwidth'],
    ];
    $conf['dimensions']['maxheight'] = [
      '#type' => 'number',
      '#default_value' => $imce_profile->getConf('maxheight'),
      '#maxlength' => 5,
      '#min' => 0,
      '#size' => 8,
      '#placeholder' => $this->t('Height'),
      '#field_suffix' => $this->t('pixels'),
      '#parents' => ['conf', 'maxheight'],
    ];
    $conf['dimensions']['description'] = [
      '#markup' => '<div class="description">' . $this->t('Images exceeding the limit will be scaled down.') . '</div>',
    ];
    // Replace method.
    $conf['replace'] = [
      '#type' => 'radios',
      '#title' => $this->t('Upload replace method'),
      '#default_value' => $imce_profile->getConf('replace', FileSystemInterface::EXISTS_RENAME),
      '#options' => [
        FileSystemInterface::EXISTS_RENAME => $this->t('Keep the existing file renaming the new one'),
        FileSystemInterface::EXISTS_REPLACE => $this->t('Replace the existing file with the new one'),
        FileSystemInterface::EXISTS_ERROR => $this->t('Keep the existing file rejecting the new one'),
      ],
      '#description' => $this->t('Select the replace method for existing files during uploads.'),
      '#weight' => -5,
    ];
    // Image thumbnails
    if (function_exists('image_style_options')) {
      $conf['thumbnail_style'] = [
        '#type' => 'select',
        '#title' => $this->t('Thumbnail style'),
        '#options' => image_style_options(),
        '#default_value' => $imce_profile->getConf('thumbnail_style'),
        '#description' => $this->t('Select a thumbnail style from the list to make the file browser display inline image previews. Note that this could reduce the performance of the file browser drastically.'),
      ];
    }
    // Folders.
    $conf['folders'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Folders'),
      'description' => ['#markup' => '<div class="description">' . $this->t('You can use user tokens in folder paths, e.g. @tokens.', ['@tokens' => '[user:uid], [user:name]']) . ' ' . $this->t('Subfolders inherit parent permissions when subfolder browsing is enabled.') . '</div>'],
      '#weight' => 10,
    ];
    $folders = $imce_profile->getConf('folders', []);
    $index = 0;
    foreach ($folders as $folder) {
      $conf['folders'][] = $this->folderForm($index++, $folder);
    }
    $conf['folders'][] = $this->folderForm($index++);
    $conf['folders'][] = $this->folderForm($index);
    $form['conf'] = $conf;
    // Add library.
    $form['#attached']['library'][] = 'imce/drupal.imce.admin';
    // Call plugin form alterers.
    $this->pluginManagerImce->alterProfileForm($form, $form_state, $imce_profile);
    return parent::form($form, $form_state);
  }

  /**
   * Returns folder form elements.
   */
  public function folderForm($index, array $folder = []) {
    $folder += ['path' => '', 'permissions' => []];
    $form = [
      '#type' => 'container',
      '#attributes' => ['class' => ['folder-container']],
    ];

    $fieldPrefix = $this->t('root');
    $slach = '/';
    $form['path'] = [
      '#type' => 'textfield',
      '#default_value' => $folder['path'],
      '#field_prefix' => '&lt;' . $fieldPrefix . '&gt;' . $slach,
    ];
    $form['permissions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Permissions'),
      '#attributes' => ['class' => ['folder-permissions']],
    ];
    $perms = $this->permissionInfo();
    $form['permissions']['all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('All permissions'),
      '#default_value' => isset($folder['permissions']['all']) ? $folder['permissions']['all'] : 0,
    ];
    foreach ($perms as $perm => $title) {
      $form['permissions'][$perm] = [
        '#type' => 'checkbox',
        '#title' => $title,
        '#default_value' => isset($folder['permissions'][$perm]) ? $folder['permissions'][$perm] : 0,
        '#states' => [
          'disabled' => ['input[name="conf[folders][' . $index . '][permissions][all]"]' => ['checked' => TRUE]],
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check folders.
    $folders = [];
    foreach ($form_state->getValue(['conf', 'folders']) as $i => $folder) {
      $path = trim($folder['path']);
      // Empty path.
      if ($path === '') {
        continue;
      }
      // Validate path.
      if (!Imce::regularPath($path)) {
        return $form_state->setError($form['conf']['folders'][$i]['path'], $this->t('Invalid folder path.'));
      }
      // Remove empty permissions.
      $folder['permissions'] = array_filter($folder['permissions']);
      $folder['path'] = $path;
      $folders[$path] = $folder;
    }
    // No valid folders.
    if (!$folders) {
      return $form_state->setError($form['conf']['folders'][0]['path'], $this->t('You must define a folder.'));
    }
    $form_state->setValue(['conf', 'folders'], array_values($folders));
    // Call plugin validators.
    $this->pluginManagerImce->validateProfileForm($form, $form_state, $this->getEntity());
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $imce_profile = $this->getEntity();
    $status = $imce_profile->save();
    if ($status == SAVED_NEW) {
      $this->messenger()
        ->addMessage($this->t('Profile %name has been added.', ['%name' => $imce_profile->label()]));
    }
    elseif ($status == SAVED_UPDATED) {
      $this->messenger()
        ->addMessage($this->t('The changes have been saved.'));
    }
    $form_state->setRedirect('entity.imce_profile.edit_form', ['imce_profile' => $imce_profile->id()]);
  }

  /**
   * Returns folder permission definitions.
   */
  public function permissionInfo() {
    if (!isset($this->folderPermissions)) {
      $this->folderPermissions = $this->pluginManagerImce->permissionInfo();
    }
    return $this->folderPermissions;
  }

}
