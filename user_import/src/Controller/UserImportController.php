<?php

declare(strict_types = 1);

namespace Drupal\user_import\Controller;

/**
 * @file
 * Contains UserImportController to display form page.
 */

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;

/**
 * Class UserImportController.
 *
 * @package \Drupal\user_import\Controller
 */
class UserImportController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * UserImportController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The Form Builder.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Display form page to import CSV file.
   *
   * @return array
   *   Form fields.
   */
  public function importPage() {
    $form = $this->formBuilder->getForm('\Drupal\user_import\Form\UserImportForm');
    $build['form'] = $form;
    return $build;
  }

}
