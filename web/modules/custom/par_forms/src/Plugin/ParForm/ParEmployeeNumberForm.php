<?php

namespace Drupal\par_forms\Plugin\ParForm;

use Drupal\par_forms\ParFormPluginBase;

/**
 * About business form plugin.
 *
 * @ParForm(
 *   id = "employee_number",
 *   title = @Translation("Number of Employees form.")
 * )
 */
class ParEmployeeNumberForm extends ParFormPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $entityMapping = [
    ['employees_band', 'par_data_organisation', 'employees_band', NULL, NULL, 0, [
      'You must fill in the missing information.' => 'You must select how many employees this business has.'
    ]],
  ];


  /**
   * Load the data for this form.
   */
  public function loadData($cardinality = 1) {
    if ($par_data_organisation = $this->getFlowDataHandler()->getParameter('par_data_organisation')) {
      $this->getFlowDataHandler()->setFormPermValue('employees_band', $par_data_organisation->get('employees_band')->getString());
    }

    parent::loadData();
  }

  /**
   * {@inheritdoc}
   */
  public function getElements($form = [], $cardinality = 1) {
    $organisation_bundle = $this->getParDataManager()->getParBundleEntity('par_data_organisation');

    // Business details.
    $form['employees_band'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of employees'),
      '#default_value' => $this->getDefaultValuesByKey('employees_band', $cardinality),
      '#options' => ['' => ''] + $organisation_bundle->getAllowedValues('employees_band'),
    ];

    return $form;
  }
}
