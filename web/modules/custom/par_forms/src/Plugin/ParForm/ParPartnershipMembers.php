<?php

namespace Drupal\par_forms\Plugin\ParForm;

use Drupal\comment\CommentInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Link;
use Drupal\par_data\Entity\ParDataEntityInterface;
use Drupal\par_flows\Entity\ParFlow;
use Drupal\par_flows\ParFlowException;
use Drupal\par_forms\ParEntityMapping;
use Drupal\par_forms\ParFormPluginBase;

/**
 * Partnership member display.
 *
 * @ParForm(
 *   id = "partnership_members",
 *   title = @Translation("Partnership documents display.")
 * )
 */
class ParPartnershipMembers extends ParFormPluginBase {

  /**
   * Available display formats.
   */
  const MEMBER_FORMAT_INLINE = 'member_list';
  const MEMBER_FORMAT_VIEW = 'member_link_view';
  const MEMBER_FORMAT_REQUEST = 'member_link_request';

  /**
   * {@inheritdoc}
   */
  public function loadData($cardinality = 1) {
    $par_data_partnership = $this->getFlowDataHandler()->getParameter('par_data_partnership');

    if ($par_data_partnership instanceof ParDataEntityInterface && $par_data_partnership->isCoordinated()) {
      // If there is a members list uploaded already.
      $membershipCount = $par_data_partnership->countMembers();
      if ($membershipCount > 0) {
        // Display only active members.
        $this->getFlowDataHandler()->setFormPermValue("members", $par_data_partnership->getCoordinatedMember(FALSE, TRUE));
        $this->getFlowDataHandler()->setFormPermValue("number_of_members", $membershipCount);

        // Set display configuration options.
        $available_formats = [self::MEMBER_FORMAT_INLINE, self::MEMBER_FORMAT_VIEW];
        $format = isset($this->getConfiguration()['format']) && array_search($this->getConfiguration()['format'], $available_formats) !== FALSE
          ? $this->getConfiguration()['format'] : self::MEMBER_FORMAT_INLINE;
      }
      elseif ($numberOfMembers = $par_data_partnership->numberOfMembers()) {
        $this->getFlowDataHandler()->setFormPermValue("number_of_members", $numberOfMembers);
      }
    }

    // If there are no members the format must be to request the member list
    // from the coordinator.
    $this->getFlowDataHandler()->setFormPermValue("member_format", $format ?? self::MEMBER_FORMAT_REQUEST);

    parent::loadData();
  }

  /**
   * {@inheritdoc}
   */
  public function getElements($form = [], $cardinality = 1) {
    // This form should only be displayed for coordinated partnerships.
    $par_data_partnership = $this->getFlowDataHandler()->getParameter('par_data_partnership');
    if (!$par_data_partnership instanceof ParDataEntityInterface || !$par_data_partnership->isCoordinated()) {
      return $form;
    }

    $form['members'] = [
      '#type' => 'fieldset',
      '#title' => t('Number of members'),
      '#attributes' => ['class' => 'form-group'],
    ];

    // Show the members count.
    $form['members']['count'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t("There are <strong>@count</strong> active members covered by this partnership.", ['@count' => $this->getDefaultValuesByKey('number_of_members', $cardinality, '0')]),
      '#attributes' => ['class' => ['form-group', 'number-of-members']],
      '#weight' => -5,
    ];

    $members = $this->getDefaultValuesByKey('members', $cardinality, NULL);

    // Show the link to view the full membership list.
    if ($this->getFlowDataHandler()->getFormPermValue("member_format") === self::MEMBER_FORMAT_VIEW) {
      try {
        $member_link = $this->getLinkByRoute('view.members_list.member_list_coordinator', [], [], TRUE);
      } catch (ParFlowException $e) {

      }
      if (isset($member_link) && $member_link instanceof Link) {
        $form['members']['count']['link'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $member_link->setText('show members list')->toString(),
          '#attributes' => ['class' => ['member-list', 'member-list-link']],
        ];
      }
    }
    // Show the member list inline.
    elseif ($this->getFlowDataHandler()->getFormPermValue("member_format") === self::MEMBER_FORMAT_INLINE) {
      // Initialize pager and get current page.
      $number_per_page = 5;
      $pager = $this->getUniquePager()->getPager('partnership_manage_coordinated_members');
      $current_pager = $this->getUniquePager()->getPagerManager()->createPager(count($members), $number_per_page, $pager);

      $form['members']['list'] = [
        '#type' => 'container',
        '#title' => t('Members'),
        '#attributes' => ['class' => ['member-list', 'member-list-inline']],
        'items' => [
          '#type' => 'container',
        ],
        'pager' => [
          '#type' => 'pager',
          '#theme' => 'pagerer',
          '#element' => $pager,
          '#weight' => 100,
          '#config' => [
            'preset' => $this->config('pagerer.settings')
              ->get('core_override_preset'),
          ],
        ],
      ];

      // Split the members up into chunks:
      $chunks = array_chunk($members, $number_per_page);
      foreach ($chunks[$current_pager->getCurrentPage()] as $delta => $entity) {
        if (!$entity instanceof ParDataEntityInterface) {
          continue;
        }

        // Display the member.
        $form['members']['list']['items'][$delta] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['grid-row', 'form-group', 'coordinated-member']],
          '#collapsible' => FALSE,
          '#collapsed' => FALSE,
          'entity' => [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $entity->label(),
            '#attributes' => ['class' => ['column-full']],
          ],
        ];
      }
    }
    // Show the member list inline.
    elseif ($this->getFlowDataHandler()->getFormPermValue("member_format") === self::MEMBER_FORMAT_REQUEST) {
      $form['members']['list'] = [
        '#type' => 'container',
        '#title' => t('Members'),
        '#attributes' => ['class' => 'form-group'],
        'link' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => "Please request a list of members from the coordinator, they will be able to provide a list on request.",
          '#attributes' => ['class' => ['member-list', 'member-list-link']],
        ],
      ];
    }

    // Operation links should not be added for the link format, where the update
    // links will be available on the referenced page.
    if ($this->getFlowDataHandler()->getFormPermValue("member_format") !== self::MEMBER_FORMAT_VIEW) {
      // Add link to add a new member.
      try {
        $member_add_flow = ParFlow::load('member_add');
        $link_label = $members && !empty($members) && count($members) >= 1 ? "add another member" : "add a member";
        $add_member_link = $member_add_flow ?
          $member_add_flow->getStartLink(1, $link_label) : NULL;
      } catch (ParFlowException $e) {

      }
      if (isset($add_member_link) && $add_member_link instanceof Link) {
        $form['members']['add'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $add_member_link ? $add_member_link->toString() : '',
          '#attributes' => ['class' => ['add-member']],
        ];
      }

      // Add link to upload a new csv member list.
      try {
        $member_upload_flow = ParFlow::load('member_upload');
        $upload_member_link = $member_upload_flow ?
          $member_upload_flow->getStartLink(1, 'upload a member list (csv)') : NULL;
      } catch (ParFlowException $e) {

      }
      if (isset($upload_member_link) && $upload_member_link instanceof Link) {
        $form['members']['upload'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $upload_member_link ? $upload_member_link->toString() : '',
          '#attributes' => ['class' => ['upload-member']],
        ];
      }
    }

    return $form;
  }

  /**
   * Return no actions for this plugin.
   */
  public function getElementActions($cardinality = 1, $actions = []) {
    return $actions;
  }

  /**
   * Return no actions for this plugin.
   */
  public function getComponentActions($actions = [], $count = NULL) {
    return $actions;
  }
}
