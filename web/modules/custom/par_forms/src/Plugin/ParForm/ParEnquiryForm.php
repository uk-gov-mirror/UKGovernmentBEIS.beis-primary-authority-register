<?php

namespace Drupal\par_forms\Plugin\ParForm;

use Drupal\par_forms\ParEntityMapping;
use Drupal\par_forms\ParFormPluginBase;

/**
 * A form for submission of general enquiries.
 *
 * @ParForm(
 *   id = "general_enquiry",
 *   title = @Translation("Enquiry form.")
 * )
 */
class ParEnquiryForm extends ParFormPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $entityMapping = [
    ['notes', 'par_data_general_enquiry', 'notes', NULL, NULL, 0, [
      'You must fill in the missing information.' => 'You must enter the details of this enquiry.'
    ]],
  ];

  /**
   * {@inheritdoc}
   */
  public function loadData($cardinality = 1) {
    if ($par_data_general_enquiry = $this->getFlowDataHandler()->getParameter('par_data_general_enquiry')) {
      $this->getFlowDataHandler()->setFormPermValue('notes', $par_data_general_enquiry->getPlain('notes'));
    }

    parent::loadData();
  }

  /**
   * {@inheritdoc}
   */
  public function getElements($form = [], $cardinality = 1) {

    $par_data_general_enquiry_fields = \Drupal::getContainer()->get('entity_field.manager')->getFieldDefinitions('par_data_general_enquiry', 'document');
    $field_definition = $par_data_general_enquiry_fields['document'];
    $file_extensions = $field_definition->getSetting('file_extensions');

    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enter enquiry'),
      '#default_value' => $this->getDefaultValuesByKey('notes', $cardinality),
      '#description' => '<p>Use this section to enter your enquiry, this will be submitted to the primary authority.</p>',
    ];

    // Multiple file field.
    $form['files'] = [
      '#type' => 'managed_file',
      '#title' => t('Upload supporting documents (optional)'),
      '#description' => t('Use Ctrl or cmd to select multiple files'),
      '#upload_location' => 's3private://documents/general_enquiry/',
      '#multiple' => TRUE,
      '#default_value' => $this->getFlowDataHandler()->getDefaultValues("files"),
      '#upload_validators' => [
        'file_validate_extensions' => [
          0 => $file_extensions
        ]
      ]
    ];

    return $form;
  }
}
