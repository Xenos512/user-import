<?php

declare(strict_types = 1);

namespace Drupal\user_import\Importer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Service that imports users and subscribe to alerts.
 */
class UserImport {

  private $subscriptionManager;

  /**
   * An entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * Create subscription manager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   An entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Processes uploaded CSV file and output appropriate message.
   *
   * @param \Drupal\file\Entity\File $file
   *   The File entity to process.
   * @param array $config
   *   An array of configuration containing:
   *   - roles: an array of role ids to assign to the user.
   */
  public function processCsvImport(File $file, array $config) {
    if ($created = $this->processImport($file, $config)) {
      drupal_set_message($this->t('Successfully imported @count users.',
        ['@count' => count($created)]));
    }
    else {
      drupal_set_message($this->t('No users imported.'));
    }
  }

  /**
   * Process file by creating new user for each values.
   *
   * @param \Drupal\file\Entity\File $file
   *   The File entity to process.
   * @param array $config
   *   An array of configuration containing:
   *   - roles: an array of role ids to assign to the user.
   *
   * @return array
   *   This may return results or empty array.
   */
  public function processImport(File $file, array $config) {
    $handle = fopen($file->destination, 'r');
    $created = [];
    while ($row = fgetcsv($handle)) {
      if ($values = $this->prepareRow($row, $config)) {
        if ($uid = $this->createUser($values)) {
          $created[$uid] = $values;
        }
      }
    }
    return $created;
  }

  /**
   * Prepares a new user from an upload row and current config.
   *
   * @param array $row
   *   A row from the currently uploaded file.
   * @param array $config
   *   An array of configuration containing:
   *   - roles: an array of role ids to assign to the user.
   *
   * @return array
   *   New user values suitable for User::create().
   */
  public function prepareRow(array $row, array $config) {
    $preferred_username = (strtolower($row[0] . $row[1]));
    $i = 0;
    while ($this->usernameExists($i ? $preferred_username . $i : $preferred_username)) {
      $i++;
    }
    $username = $i ? $preferred_username . $i : $preferred_username;

    return [
      'uid' => NULL,
      'name' => $username,
      'field_name_first' => $row[0],
      'field_name_last' => $row[1],
      'pass' => NULL,
      'mail' => trim($row[2]),
      'int' => trim($row[2]),
      'status' => 1,
      'created' => REQUEST_TIME,
      'roles' => array_values($config['roles']),
    ];
  }

  /**
   * Returns user whose name matches $username.
   *
   * @param string $username
   *   Username to check.
   *
   * @return array
   *   Users whose names match username.
   */
  private function usernameExists($username) {

    return $this->entityTypeManager->getStorage('user')->loadByProperties([
      'name' => $username,
    ]);
  }

  /**
   * Processes an uploaded CSV file, creating a new user for each row of values.
   *
   * @param \Drupal\file\Entity\File $file
   *   The File entity to process.
   * @param array $config
   *   An array of configuration containing:
   *   - roles: an array of role ids to assign to the user.
   *
   * @return array
   *   An associative array of values from the filename keyed by new uid.
   */
  public function processUpload(File $file, array $config) {
    $handle = fopen($file->destination, 'r');
    $created = [];
    while ($row = fgetcsv($handle)) {
      if ($values = $this->prepareRow($row, $config)) {
        if ($user = $this->createUser($values)) {
          $uid = $user->id();
          $created[$uid] = $values;
        }
      }
    }
    return $created;
  }
public function testFunc() {
  \Drupal::logger('my_module')->notice("testFunc worked.");
}
  /**
   * Creates a new user from prepared values.
   *
   * @param array $values
   *   Values prepared from prepareRow().
   */
  public function createUser(array $values) {
    $user = User::create($values);
    try {
      if ($user->save()) {
        return $user;
      }
    }
    catch (EntityStorageException $e) {
      drupal_set_message($this->t('Could not create user %fname %lname 
      (username: %uname) (email: %email); exception: %e', [
        '%e' => $e->getMessage(),
        '%fname' => $values['field_name_first'],
        '%lname' => $values['field_name_last'],
        '%uname' => $values['name'],
        '%email' => $values['mail'],
      ]), 'error');
    }
    return FALSE;
  }
}
