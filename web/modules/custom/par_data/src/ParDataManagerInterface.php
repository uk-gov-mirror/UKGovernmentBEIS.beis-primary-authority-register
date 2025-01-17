<?php

namespace Drupal\par_data;

use Drupal\Core\Entity\EntityTypeInterface;

/**
* Interface for the Par Data Manager.
*/
interface ParDataManagerInterface {

  /**
   * Gets a list of all entity types for PAR Data.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
  */
  public function getParEntityTypes();

  /**
   * Get a given PAR Data Entity Type
   *
   * @param string $type
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   A PAR Data Entity Type
   */
  public function getParEntityType(string $type);

  /**
   * An entity query builder.
   *
   *
   * @param string $type
   *   An entity type to query.
   * @param array $conditions
   *   Array of Conditions.
   * @param integer $limit
   *   Limit number of results.
   * @param string $sort
   *   The field to sort by.
   * @param string $direction
   *   The direction to sort in.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entity objects indexed by their IDs. Returns an empty array
   *   if no matching entities are found.
   */
  public function getEntitiesByQuery(string $type, array $conditions, $limit = NULL, $sort = NULL, $direction = 'ASC');

  /**
   * Gets the entity definition for the class that defines an entities bundles.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   */
  public function getEntityBundleDefinition(EntityTypeInterface $definition);

  /**
   * Get the config entity that provides bundles for a given entity.
   *
   * @param string $type
   *   The type of entity to get the bundle entity for.
   * @param mixed $bundle
   *   The bundle to load if there are multiple for a given
   *
   * @return \Drupal\par_data\Entity\ParDataTypeInterface
   *   A PAR Data Bundle Entity
   */
  public function getParBundleEntity(string $type, $bundle = NULL);

  /**
   * @param string $definition
   *   The entity type to get the storage for
   *
   * @return NULL|\Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage for the given definition.
   */
  public function getEntityTypeStorage($definition);

  /**
   * @param string $type
   *   The type of entity to get the bundle entity for.
   * @param mixed $bundle
   *   The bundle to load if there are multiple for a given
   * @param string $field
   *   The field name to retrieve the definition for.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition.
   */
  public function getFieldDefinition(string $type, $bundle = NULL, string $field);

}
