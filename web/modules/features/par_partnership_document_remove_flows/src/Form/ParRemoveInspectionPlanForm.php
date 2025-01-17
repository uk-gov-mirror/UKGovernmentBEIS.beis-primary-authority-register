<?php

namespace Drupal\par_partnership_document_remove_flows\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\par_data\Entity\ParDataEntity;
use Drupal\par_data\Entity\ParDataEntityInterface;
use Drupal\par_data\Entity\ParDataPartnership;
use Drupal\par_flows\Form\ParBaseForm;
use Drupal\par_partnership_document_remove_flows\ParFlowAccessTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The form for removing inspection plans from a partnership.
 */
class ParRemoveInspectionPlanForm extends ParBaseForm {

  use ParFlowAccessTrait;

  protected $pageTitle = 'Are you sure you want to remove this inspection plan?';

  /**
   * Load the data for this form.
   */
  public function loadData() {
    $par_data_partnership = $this->getFlowDataHandler()->getParameter('par_data_partnership');
    $par_data_inspection_plan = $this->getFlowDataHandler()->getParameter('par_data_inspection_plan');

    // Set the inspection plan entity if a value was found for this delta.
    if ($par_data_partnership && $par_data_inspection_plan) {
      $inspection_plans = $par_data_partnership->get('field_inspection_plan')->getValue();
      // Note that this will only return the first instance of this $inspection_plans,
      // although this field should be unique so there shouldn't be more than one.
      $key = array_search($par_data_inspection_plan->id(), array_column($inspection_plans, 'target_id'));
      if ($key !== FALSE) {
        $this->getFlowDataHandler()->setFormPermValue('field_inspection_plan_delta', $key);
      }
    }

    parent::loadData();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ParDataPartnership $par_data_partnership = NULL, $par_data_inspection_plan = NULL) {
    $par_data_partnership = $this->getFlowDataHandler()->getParameter('par_data_partnership');
    $par_data_inspection_plan = $this->getFlowDataHandler()->getParameter('par_data_inspection_plan');

    $delta = $this->getFlowDataHandler()->getFormPermValue('field_inspection_plan_delta');

    if ($delta === NULL) {
      $url = $this->getFlowNegotiator()->getFlow()->progress('cancel');
      return new RedirectResponse($url->toString());
    }

    $document_view_builder = $this->getParDataManager()->getViewBuilder('file');
    $documents = $par_data_inspection_plan && $par_data_inspection_plan->hasField('document') && !$par_data_inspection_plan->get('document')->isEmpty()
      ? $document_view_builder->viewMultiple($par_data_inspection_plan->get('document')->referencedEntities(), 'title') : NULL;

    $form['remove'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Are you sure you want to remove the inspection plan @inspection_plan from the @partnership?', ['@inspection_plan' => $par_data_inspection_plan->getTitle(), '@partnership' => $par_data_partnership->label()]),
      '#attributes' => ['class' => ['remove-inspection-plan', 'form-group']],
    ];

    if (!empty($documents)) {
      $form['description'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('This will remove the following documents from this partnership.'),
        '#attributes' => ['class' => ['form-hint']],
      ];
      $form['documents'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->getRenderer()->render($documents),
        '#attributes' => ['class' => ['document-summary', 'form-group']],
      ];
    }

    // Enter the removal reason.
    $form['remove_reason'] = [
      '#title' => $this->t('Enter the reason you are removing this inspection plan'),
      '#type' => 'textarea',
      '#rows' => 5,
      '#default_value' => $this->getFlowDataHandler()->getDefaultValues('remove_reason', FALSE),
    ];

    $form['delta'] = [
      '#type' => 'hidden',
      '#value' => $delta,
    ];

    // Change the main button title to 'remove'.
    $this->getFlowNegotiator()->getFlow()->setPrimaryActionTitle('Remove');

    // Make sure to add the person cacheability data to this form.
    $this->addCacheableDependency($par_data_partnership);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);

    if (!$form_state->getValue('remove_reason')) {
      $id = $this->getElementId('remove_reason', $form);
      $form_state->setErrorByName($this->getElementName(['confirm']), $this->wrapErrorMessage('Please enter the reason you are removing this inspection plan.', $id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $par_data_partnership = $this->getFlowDataHandler()->getParameter('par_data_partnership');
    $par_data_inspection_plan = $this->getFlowDataHandler()->getParameter('par_data_inspection_plan');
    $delta = $this->getFlowDataHandler()->getTempDataValue('delta');

    // Remove the field delta.
    try {
      if (isset($delta)) {
        $par_data_partnership->get('field_inspection_plan')->removeItem($delta);
        $remove_reason = $this->getFlowDataHandler()->getDefaultValues('remove_reason', '');
        $revision_message = $this->t(
          "The inspection plan @inspection_plan was removed from the partnership. The reason given was:" . PHP_EOL . "@reason",
          ['@inspection_plan' => $par_data_inspection_plan->getTitle(), '@reason' => $remove_reason]);
        $par_data_partnership->setNewRevision(TRUE, $revision_message);
      }
      else {
        throw new \InvalidArgumentException('No field delta has been provided.');
      }
    }
    catch (\InvalidArgumentException $e) {

    }

    // Don't save if there are no more inspection plan entities.
    if ($par_data_partnership->save()) {
      $this->getFlowDataHandler()->deleteStore();
    }
    else {
      $message = $this->t('The %inspection_plan could not be removed from the %field on partnership %partnership');
      $replacements = [
        '%inspection_plan' => $par_data_inspection_plan->label(),
        '%field' => $this->getFlowDataHandler()->getTempDataValue('field_inspection_plan'),
        '%partnership' => $par_data_partnership->label(),
      ];
      $this->getLogger($this->getLoggerChannel())->error($message, $replacements);
    }
  }

}
