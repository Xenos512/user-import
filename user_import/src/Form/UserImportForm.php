<?php

declare(strict_types = 1);

namespace Drupal\user_import\Form;

/**
 * @file
 * Contains \Drupal\user_import\Form\UserImportForm.
 */

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;
use Drupal\user_import\Importer\UserImport;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Class UserImportForm.
 *
 * @package Drupal\user_import\Form
 */
class UserImportForm extends FormBase {

  protected $configFactory;

  protected $userImport;

  /**
   * A language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  public $languageManager;

  /**
   * UserImportForm constructor.
   *
   * @param \Drupal\user_import\Importer\UserImport $user_import
   *   Get user_import for alerts.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   A language manager.
   */
  public function __construct(UserImport $user_import,
                              LanguageManagerInterface $language_manager) {
    $this->userImport = $user_import;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user_import'),
      $container->get('language_manager')
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface.
   */
  public function getFormId() {
    return 'user_import_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();

    $form['#tree'] = TRUE;
    $form['config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User roles'),
    ];

    $roles = user_role_names();

    // Limit roles to display.
    $roles = array_intersect_key($roles, array_flip(['anonymous', 'administrator',
    ]));

    $form['config']['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select roles to be given to users.'),
      '#options' => $roles,
      '#required' => TRUE,
    ];
    // Special handling for the inevitable "Authenticated user" role.
    $form['config']['roles'][RoleInterface::AUTHENTICATED_ID] = [
      '#default_value' => TRUE,
      '#disabled' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import users'),
      '#button_type' => 'primary',
    ];
    // By default, render the form using theme_system_config_form().
    $form['#theme'] = 'system_config_form';

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Please upload a CSV file here to import new users and to subscribed them to content alerts.'),
    ];

    $form['file'] = [
      '#type' => 'file',
      '#title' => 'CSV file upload',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate options at least one must be selected.
    $roles = $form_state->getValue(['config', 'roles']);

    $roles_selected = array_filter($roles, function ($item) {
      return ($item);
    });
    if (empty($roles_selected)) {
      $form_state->setErrorByName('roles', $this->t('Please select at least one role to apply to the imported user(s).'));
    }

    // Validate file it should have something to submit.
    $this->file = file_save_upload('file', $form['file']['#upload_validators']);
    if (!$this->file[0]) {
      $form_state->setErrorByName('file');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file = $this->file[0];
    $roles = $form_state->getValue(['config', 'roles']);
    $config = [
      'roles' => array_filter($roles, function ($item) {
        return ($item);
      }),
    ];

    //$content_alerts = $form_state->getValue(['content_alerts']) ? $form_state->getValue(['content_alerts']) : [];

    if ($created = $this->userImport->processUpload($file, $config)) {
      drupal_set_message($this->t('Successfully imported @count users.', ['@count' => count($created)]));
    }
    else {
      drupal_set_message($this->t('No users imported.'));
    }
    $form_state->setRedirect('user_import.page');
  }

}
