<?php

/**
 * @file
 * Webform requirements.
 */

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\webform\Plugin\WebformElement\ManagedFile;

/**
 * Implements hook_requirements().
 */
function webform_requirements($phase) {
  if ($phase !== 'runtime') {
    return [];
  }

  $requirements = [];

  /* ************************************************************************ */
  // Experimental modules.
  /* ************************************************************************ */

  // Warn if any experimental webform modules are installed.
  // @see system_requirements()
  $experimental = [];
  $enabled_modules = \Drupal::moduleHandler()->getModuleList();
  foreach ($enabled_modules as $module => $data) {
    $info = \Drupal::service('extension.list.module')->getExtensionInfo($module);
    if (isset($info['package']) && $info['package'] === 'Webform' && !empty($info['experimental'])) {
      $experimental[$module] = str_replace(' [EXPERIMENTAL]', '', $info['name']);
    }
  }
  if (!empty($experimental)) {
    $requirements['webform_experimental'] = [
      'title' => t('Webform: Experimental modules enabled'),
      'value' => t('Experimental webform modules found: %module_list. <a href=":url">Experimental modules</a> are provided for testing purposes only. Use at your own risk.', [
        '%module_list' => implode(', ', $experimental),
        ':url' => 'https://www.drupal.org/core/experimental',
      ]),
      'severity' => REQUIREMENT_WARNING,
    ];
  }

  /* ************************************************************************ */
  // Check HTML email handling.
  /* ************************************************************************ */

  /** @var \Drupal\webform\WebformEmailProviderInterface $email_provider */
  $email_provider = \Drupal::service('webform.email_provider');
  $mail_module_name = $email_provider->getModuleName();
  $mail_plugin_id = $email_provider->getMailPluginId();
  $mail_plugin_definition = $email_provider->getMailPluginDefinition();
  if ($mail_module_name || $mail_plugin_id) {
    $t_args = [
      '@module' => $mail_module_name,
      '@plugin_id' => $mail_plugin_id,
    ];
    if ($mail_plugin_definition) {
      $t_args += [
        '@plugin_label' => $mail_plugin_definition['label'],
        '@plugin_description' => $mail_plugin_definition['description'],
      ];
    }
    $requirements['webform_email'] = [
      'title' => t('Webform: HTML email support'),
      'value' => ($mail_module_name) ? t('Provided by the @module module.', $t_args) : t('Provided by the @plugin_id mail plugin.', $t_args),
      'description' => $mail_plugin_definition ? new FormattableMarkup('@plugin_label: @plugin_description', $t_args) : '',
      'severity' => REQUIREMENT_OK,
    ];
  }
  else {
    $requirements['webform_email'] = [
      'title' => t('Webform: HTML email support'),
      'value' => t('Unable to determine email module and/or provider'),
      'severity' => REQUIREMENT_ERROR,
    ];
  }

  /* ************************************************************************ */
  // Check private file upload.
  /* ************************************************************************ */

  $scheme_options = ManagedFile::getVisibleStreamWrappers();
  if (isset($scheme_options['private'])) {
    $requirements['webform_file_private'] = [
      'title' => t('Webform: Private files'),
      'value' => t('Private file system is set.'),
    ];
  }
  else {
    $requirements['webform_file_private'] = [
      'title' => t('Webform: Private files'),
      'value' => t('Private file system is not set.'),
      'description' => t('This must be changed in <a href="https://www.drupal.org/documentation/modules/file">settings.php</a>. For more information see: <a href="https://www.drupal.org/psa-2016-003">DRUPAL-PSA-2016-003</a>'),
      'severity' => REQUIREMENT_WARNING,
    ];
  }

  /* ************************************************************************ */
  // Check external libraries.
  /* ************************************************************************ */

  /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
  $libraries_manager = \Drupal::service('webform.libraries_manager');
  $requirements += $libraries_manager->requirements();

  /* ************************************************************************ */
  // Check Clientside validation module.
  /* ************************************************************************ */

  if (\Drupal::config('webform.settings')->get('requirements.clientside_validation')
    && \Drupal::moduleHandler()->moduleExists('clientside_validation')) {
    if (\Drupal::moduleHandler()->moduleExists('webform_clientside_validation')) {
      $requirements['webform_clientside_validation'] = [
        'title' => t('Webform: Clientside validation'),
        'value' => t('Webform Clientside Validation module installed.'),
      ];
    }
    else {
      $requirements['webform_clientside_validation'] = [
        'title' => t('Webform: Clientside validation'),
        'value' => t('Webform Clientside Validation module not installed.'),
        'description' => t('The Webform Clientside Validation module helps support Webform Clientside Validation integration. <a href=":href">Disable Webform Clientside Validation warning</a>', [':href' => Url::fromRoute('webform.config.advanced')->toString()]),
        'severity' => REQUIREMENT_WARNING,
      ];
    }
  }

  /* ************************************************************************ */
  // Check Webform jQuery UI Datepicker module.
  /* ************************************************************************ */

  $config_factory = \Drupal::configFactory();
  $has_datepicker = FALSE;
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();
    if (isset($data['elements']) && strpos($data['elements'], 'datepicker')) {
      $has_datepicker = TRUE;
      break;
    }
  }
  if ($has_datepicker && !\Drupal::moduleHandler()->moduleExists('webform_jqueryui_datepicker')) {
    $t_args = [
      ':href' => Url::fromRoute('entity.webform.collection', [], ['query' => ['search' => 'datepicker']])->toString(),
    ];
    $requirements['jquery_ui_datepicker'] = [
      'title' => t('Webform: jQuery UI Datepicker'),
      'value' => t('Webform jQuery UI Datepicker module not installed.'),
      'description' => t('The Webform jQuery UI Datepicker module is required to support datepickers. Disable this datepicker warning by <a href=":href">removing #datepicker property from all webforms</a>.', $t_args),
      'severity' => REQUIREMENT_WARNING,
    ];
  }

  /* ************************************************************************ */
  // Check SPAM protection.
  /* ************************************************************************ */

  if (\Drupal::config('webform.settings')->get('requirements.spam')) {
    $spam_protection = FALSE;
    $installed_projects = [
      '#prefix' => '<hr class="webform-hr"/><dl>',
      '#suffix' => '</dl>',
    ];
    $available_projects = [
      '#prefix' => '<hr class="webform-hr"/><dl>',
      '#suffix' => '</dl>',
    ];

    /** @var \Drupal\webform\WebformAddonsManagerInterface $addons_manager */
    $addons_manager = \Drupal::service('webform.addons_manager');
    $projects = $addons_manager->getProjects('spam');
    foreach ($projects as $project_name => $project) {
      $available_projects[$project_name] = [
        'title' => [
          '#type' => 'link',
          '#title' => $project['title'],
          '#url' => $project['url'],
          '#prefix' => '<dt><strong>',
          '#suffix' => '</strong></dt>',
        ],
        'description' => [
          '#markup' => $project['description'],
          '#prefix' => '<dd>',
          '#suffix' => '</dd>',
        ],
      ];
      if (\Drupal::moduleHandler()->moduleExists($project_name)) {
        $spam_protection = TRUE;
        $installed_projects[$project_name] = $available_projects[$project_name];
      }
    }
    if ($spam_protection) {
      $requirements['webform_spam'] = [
        'title' => t('Webform: Spam protection'),
        'value' => t('Webform Spam protection module installed.'),
        'description' => \Drupal::service('renderer')->renderInIsolation($installed_projects),
      ];
    }
    else {
      $requirements['webform_spam'] = [
        'title' => t('Webform: Spam protection'),
        'value' => t('Webform <a href=":href">Spam protection module</a> missing. Please install one of the below modules.', [':href' => 'https://www.drupal.org/node/206787']),
        'description' => \Drupal::service('renderer')->renderInIsolation($available_projects),
        'severity' => REQUIREMENT_WARNING,
      ];
    }
  }

  // Sort all requirements alphabetically.
  ksort($requirements);

  return $requirements;
}
