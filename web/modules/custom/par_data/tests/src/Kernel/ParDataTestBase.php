<?php

namespace Drupal\Tests\par_data\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\file\Entity\File;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\par_data\Entity\ParDataAdvice;
use Drupal\par_data\Entity\ParDataAdviceType;
use Drupal\par_data\Entity\ParDataAuthority;
use Drupal\par_data\Entity\ParDataAuthorityType;
use Drupal\par_data\Entity\ParDataCoordinatedBusiness;
use Drupal\par_data\Entity\ParDataCoordinatedBusinessType;
use Drupal\par_data\Entity\ParDataDeviationRequest;
use Drupal\par_data\Entity\ParDataDeviationRequestType;
use Drupal\par_data\Entity\ParDataEnforcementAction;
use Drupal\par_data\Entity\ParDataEnforcementActionType;
use Drupal\par_data\Entity\ParDataEnforcementNoticeType;
use Drupal\par_data\Entity\ParDataGeneralEnquiry;
use Drupal\par_data\Entity\ParDataGeneralEnquiryType;
use Drupal\par_data\Entity\ParDataInformationReferral;
use Drupal\par_data\Entity\ParDataInformationReferralType;
use Drupal\par_data\Entity\ParDataInspectionFeedback;
use Drupal\par_data\Entity\ParDataInspectionFeedbackType;
use Drupal\par_data\Entity\ParDataInspectionPlan;
use Drupal\par_data\Entity\ParDataInspectionPlanType;
use Drupal\par_data\Entity\ParDataLegalEntity;
use Drupal\par_data\Entity\ParDataLegalEntityType;
use Drupal\par_data\Entity\ParDataOrganisation;
use Drupal\par_data\Entity\ParDataOrganisationType;
use Drupal\par_data\Entity\ParDataPartnership;
use Drupal\par_data\Entity\ParDataPartnershipType;
use Drupal\par_data\Entity\ParDataPerson;
use Drupal\par_data\Entity\ParDataPersonType;
use Drupal\par_data\Entity\ParDataPremises;
use Drupal\par_data\Entity\ParDataPremisesType;
use Drupal\par_data\Entity\ParDataRegulatoryFunction;
use Drupal\par_data\Entity\ParDataRegulatoryFunctionType;
use Drupal\par_data\Entity\ParDataSicCode;
use Drupal\par_data\Entity\ParDataSicCodeType;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use org\bovigo\vfs\vfsStream;

/**
 * Tests PAR Data test base.
 *
 * @group PAR Data
 */
class ParDataTestBase extends EntityKernelTestBase {

  use MediaTypeCreationTrait;

  static $modules = [
    'user',
    'system',
    'field',
    'text',
    'filter',
    'entity_test',
    'language',
    'content_translation',
    'comment',
    'trance',
    'par_validation',
    'par_data',
    'par_data_config',
    'message',
    'address',
    'datetime',
    'datetime_range',
    'file_test',
    'file',
    'image',
    'media',
    'media_library',
    'views',
    'media_test_views',
    'file_entity',
  ];

  /**
   * @var AccountInterface
   */
  protected $account;

  protected $permissions = [
    'access content',
    'bypass file access',
    'access par_data_advice entities',
    'access par_data_authority entities',
    'access par_data_enforcement_notice entities',
    'access par_data_deviation_request entities',
    'access par_data_inspection_feedback entities',
    'access par_data_general_enquiry entities',
    'access par_data_information_referral entities',
    'access par_data_inspection_plan entities',
    'access par_data_legal_entity entities',
    'access par_data_organisation entities',
    'access par_data_coordinated_business entities',
    'access par_data_partnership entities',
    'access par_data_person entities',
    'access par_data_premises entities',
    'access par_data_regulatory_function entities',
    'access par_data_sic_code entities',
    'edit par_data_advice entities',
    'edit par_data_authority entities',
    'edit par_data_enforcement_notice entities',
    'edit par_data_deviation_request entities',
    'edit par_data_inspection_feedback entities',
    'edit par_data_general_enquiry entities',
    'edit par_data_information_referral entities',
    'edit par_data_inspection_plan entities',
    'edit par_data_legal_entity entities',
    'edit par_data_organisation entities',
    'edit par_data_coordinated_business entities',
    'edit par_data_partnership entities',
    'edit par_data_person entities',
    'edit par_data_premises entities',
    'edit par_data_regulatory_function entities',
    'edit par_data_sic_code entities',
  ];

  protected $entityTypes = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Must change the bytea_output to the format "escape" before running tests.
    // @see https://www.drupal.org/node/2810049
    //db_query("ALTER DATABASE 'par' SET bytea_output = 'escape';")->execute();
    parent::setUp();

    // Create a new non-admin user.
    $this->account = $this->createUser(['uid' => 2], $this->permissions);
    \Drupal::currentUser()->setAccount($this->account);

    // Mimic some of the functionality in \Drupal\Tests\file\Kernel\FileManagedUnitTestBase
    $this->setUpFilesystem();

    // Install media config.
    $this->installEntitySchema('media');
    $this->installConfig(['media']);

    // Create document media type.
    $this->testMediaType = $this->createMediaType('file', ['id' => 'document', 'label' => 'Document']);
    // Create fake document type that cannot be added to any of the document fields.
    $this->fakeMediaType = $this->createMediaType('file', ['id' => 'fake', 'label' => 'Fake Document']);

    // Install out entity hooks.
    $this->entityTypes = [
      'par_data_advice',
      'par_data_authority',
      'par_data_enforcement_action',
      'par_data_enforcement_notice',
      'par_data_deviation_request',
      'par_data_inspection_feedback',
      'par_data_general_enquiry',
      'par_data_information_referral',
      'par_data_inspection_plan',
      'par_data_legal_entity',
      'par_data_organisation',
      'par_data_coordinated_business',
      'par_data_partnership',
      'par_data_person',
      'par_data_premises',
      'par_data_regulatory_function',
      'par_data_sic_code',
    ];

    foreach ($this->entityTypes as $type) {
      // Set up schema for par_data.
      $this->installEntitySchema($type);
    }

    // Install config for par_data if required.
    $this->installConfig('par_data');

    // Create the entity bundles required for testing.
    $type = ParDataAdviceType::create([
      'id' => 'advice',
      'label' => 'Advice',
    ]);
    $type->save();

    // Create the entity bundles required for testing.
    $type = ParDataAuthorityType::create([
      'id' => 'authority',
      'label' => 'Authority',
    ]);
    $type->save();

    // Create the entity bundles required for testing.
    $type = ParDataEnforcementActionType::create([
      'id' => 'enforcement_action',
      'label' => 'Enforcement Action',
    ]);
    $type->save();

    // Create the entity bundles required for testing.
    $type = ParDataEnforcementNoticeType::create([
      'id' => 'enforcement_notice',
      'label' => 'Enforcement Notice',
    ]);
    $type->save();

    // Create the entity bundles required for testing.
    $type = ParDataDeviationRequestType::create([
      'id' => 'deviation_request',
      'label' => 'Deviation Request',
    ]);
    $type->save();

    // Create the entity bundles required for testing.
    $type = ParDataInspectionFeedbackType::create([
      'id' => 'inspection_feedback',
      'label' => 'Inspection Feedback',
    ]);
    $type->save();

    // Create the entity bundles required for testing.
    $type = ParDataGeneralEnquiryType::create([
      'id' => 'general_enquiry',
      'label' => 'General Enquiry',
    ]);
    $type->save();

    // Create the entity bundles required for testing.
    $type = ParDataInformationReferralType::create([
      'id' => 'information_referral',
      'label' => 'Information Referral',
    ]);
    $type->save();

    // Create the entity bundles required for testing.
    $type = ParDataInspectionPlanType::create([
      'id' => 'inspection_plan',
      'label' => 'Inspection Plan',
    ]);
    $type->save();

    // Create the entity bundles required for testing.
    $type = ParDataLegalEntityType::create([
      'id' => 'legal_entity',
      'label' => 'Legal Entity',
    ]);
    $type->save();

    // Create the entity bundles required for testing.
    $type = ParDataOrganisationType::create([
      'id' => 'organisation',
      'label' => 'Organisation',
    ]);
    $type->save();

    // Create the entity bundles required for testing.
    $type = ParDataCoordinatedBusinessType::create([
      'id' => 'coordinated_business',
      'label' => 'Coordinated Business',
    ]);
    $type->save();

    // Create the entity bundles required for testing.
    $type = ParDataPartnershipType::create([
      'id' => 'partnership',
      'label' => 'Partnership',
    ]);
    $type->save();

    // Create the entity bundles required for testing.
    $type = ParDataPersonType::create([
      'id' => 'person',
      'label' => 'Person',
    ]);
    $type->save();

    // Create the entity bundles required for testing.
    $type = ParDataPremisesType::create([
      'id' => 'premises',
      'label' => 'Premises',
    ]);
    $type->save();

    // Create the entity bundles required for testing.
    $type = ParDataRegulatoryFunctionType::create([
      'id' => 'regulatory_function',
      'label' => 'Regulatory Function',
    ]);
    $type->save();

    // Create the entity bundles required for testing.
    $type = ParDataSicCodeType::create([
      'id' => 'sic_code',
      'label' => 'SIC Code',
    ]);
    $type->save();

    // Install the feature config
    $this->installConfig('par_data_config');

    // Install comment config.
    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);

    // Install file config.
    $this->installConfig(['system']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('file', ['file_usage']);
  }

  public function createFile() {
    // Create a new file entity.
    $filename = "par-test.{$this->randomMachineName()}.txt";
    $file = File::create([
      'uid' => $this->account->id(),
      'filename' => $filename,
      'uri' => "public://$filename",
      'filemime' => 'text/plain',
      'status' => FILE_STATUS_PERMANENT,
    ]);
    file_put_contents($file->getFileUri(), 'This is a par test document');
    $file->save();

    return $file;
  }

  public function getMedia($file) {
    return Media::create([
      'bundle' => $this->testMediaType->id(),
      'name' => $this->randomMachineName(),
      'field_media_file' => [
        'target_id' => $file->id(),
      ],
    ]);
  }

  public function getBaseValues() {
    return [
      'uid' => $this->account,
      'type' => 'UNKNOWN',
      'archive_reason' => "Automated test archive reason",
    ];
  }

  public function getAdviceValues($values = []) {
    // We need to create a Regulatory Function first.
    $regulatory_function = ParDataRegulatoryFunction::create($this->getRegulatoryFunctionValues());
    $regulatory_function->save();

    /** @var \Drupal\file\Entity\File $document */
    $document = $this->createFile();

    $values += [
        'type' => 'advice',
        'advice_type' => 'To Local Authority',
        'notes' => $this->randomString(1000),
        'visible_authority' => TRUE,
        'visible_coordinator' => TRUE,
        'visible_business' => TRUE,
        'document' => [
          $document->id()
        ],
        'field_document' => [
          $this->getMedia($document),
        ],
        'field_regulatory_function' => [
          $regulatory_function->id(),
        ],
      ] + $this->getBaseValues();

    return $values;
  }

  public function getAuthorityValues($values = []) {
    // We need to create a Person first.
    $person = ParDataPerson::create($this->getPersonValues());
    $person->save();

    // We need to create a Regulatory Function first.
    $regulatory_function = ParDataRegulatoryFunction::create($this->getRegulatoryFunctionValues());
    $regulatory_function->save();

    // We need to create an Organisation first.
    $premises = ParDataPremises::create($this->getPremisesValues());
    $premises->save();

    $values += [
        'type' => 'authority',
        'authority_name' => 'Test Authority',
        'authority_type' => 'Local Authority',
        'nation' => 'Wales',
        'ons_code' => '123456',
        'comments' => $this->randomString(1000),
        'field_person' => [
          $person->id(),
        ],
        'field_regulatory_function' => [
          $regulatory_function->id(),
        ],
        'field_allowed_regulatory_fn' => [
          $regulatory_function->id(),
        ],
        'field_premises' => [
          $premises->id(),
        ],
      ] + $this->getBaseValues();

    return $values;
  }

  public function getEnforcementActionValues($values = []) {
    // We need to create an Advice first.
    $advice = ParDataAdvice::create($this->getAdviceValues());
    $advice->save();

    // We need to create a Regulatory Function first.
    $regulatory_function = ParDataRegulatoryFunction::create($this->getRegulatoryFunctionValues());
    $regulatory_function->save();

    $values += [
        'type' => 'enforcement_action',
        'title' => 'Test Enforcement Action',
        'details' => $this->randomString(1000),
        'enforcement_action_status' => 'Enforced',
        'enforcement_action_notes' => $this->randomString(1000),
        'primary_authority_status' => 'Enforced',
        'primary_authority_notes' => $this->randomString(1000),
        'referral_notes' => $this->randomString(1000),
        'field_blocked_advice' => [
          $advice->id(),
        ],
        'field_regulatory_function' => [
          $regulatory_function->id(),
        ],
      ] + $this->getBaseValues();

    return $values;
  }

  public function getEnforcementNoticeValues($values = []) {
    // We need to create an Enforcing Authority first.
    $enforcing_authority = ParDataAuthority::create($this->getAuthorityValues());
    $enforcing_authority->save();

    // We need to create a partnership
    $partnership = ParDataPartnership::create($this->getDirectPartnershipValues());
    $partnership->save();
    $primary_authority = current($partnership->getAuthority());

    // We need to create a Legal Entity first.
    $organisation = ParDataOrganisation::create($this->getOrganisationValues());
    $organisation->save();
    $legal_entity = current($organisation->getLegalEntity());

    // We need to create an Enforcement Action first.
    $enforcement_action = ParDataEnforcementAction::create($this->getEnforcementActionValues());
    $enforcement_action->save();

    // We need to create a Person first.
    $person = ParDataPerson::create($this->getPersonValues());
    $person->save();

    $values += [
        'type' => 'enforcement_notice',
        'notice_type' => 'Closure',
        'notice_date' => '2017-10-01',
        'legal_entity_name' => 'Unassigned Legal Entity Ltd',
        'summary' => $this->randomString(1000),
        'field_enforcing_authority' => [
          $enforcing_authority->id(),
        ],
        'field_organisation' => [
          $organisation->id(),
        ],
        'field_partnership' => [
          $partnership->id(),
        ],
        'field_primary_authority' => [
          $primary_authority->id(),
        ],
        'field_legal_entity' => [
          $legal_entity->id(),
        ],
        'field_enforcement_action' => [
          $enforcement_action->id(),
        ],
        'field_person' => [
          $person->id(),
        ],
      ] + $this->getBaseValues();

    return $values;
  }

  public function getDeviationRequestValues($values = []) {
    // We need to create an Enforcing Authority first.
    $enforcing_authority = ParDataAuthority::create($this->getAuthorityValues());
    $enforcing_authority->save();

    $people = $enforcing_authority->get('field_person')->referencedEntities();
    $person = current($people);

    // We need to create a partnership
    $partnership = ParDataPartnership::create($this->getDirectPartnershipValues());
    $partnership->save();

    $inspection_plans = $partnership->get('field_inspection_plan')
      ->referencedEntities();
    $inspection_plan = current($inspection_plans);

    /** @var \Drupal\file\Entity\File $document */
    $document = $this->createFile();

    $values += [
        'type' => 'deviation_request',
        'request_date' => '2017-10-01',
        'notes' => $this->randomString(1000),
        'primary_authority_status' => 'awaiting',
        'primary_authority_notes' => $this->randomString(1000),
        'document' => [
          $document->id()
        ],
        'field_document' => [
          $this->getMedia($document),
        ],
        'field_enforcing_authority' => [
          $enforcing_authority->id(),
        ],
        'field_partnership' => [
          $partnership->id(),
        ],
        'field_inspection_plan' => [
          $inspection_plan->id(),
        ],
        'field_person' => [
          $person->id(),
        ],
      ] + $this->getBaseValues();

    return $values;
  }

  public function getInspectionFeedbackValues($values = []) {
    // We need to create an Enforcing Authority first.
    $enforcing_authority = ParDataAuthority::create($this->getAuthorityValues());
    $enforcing_authority->save();

    $people = $enforcing_authority->get('field_person')->referencedEntities();
    $person = current($people);

    // We need to create a partnership
    $partnership = ParDataPartnership::create($this->getDirectPartnershipValues());
    $partnership->save();

    $inspection_plans = $partnership->get('field_inspection_plan')
      ->referencedEntities();
    $inspection_plan = current($inspection_plans);

    /** @var \Drupal\file\Entity\File $document */
    $document = $this->createFile();

    $values += [
        'type' => 'inspection_feedback',
        'request_date' => '2017-10-01',
        'notes' => $this->randomString(1000),
        'primary_authority_status' => 'awaiting',
        'primary_authority_notes' => $this->randomString(1000),
        'document' => [
          $document->id()
        ],
        'field_document' => [
          $this->getMedia($document),
        ],
        'field_enforcing_authority' => [
          $enforcing_authority->id(),
        ],
        'field_partnership' => [
          $partnership->id(),
        ],
        'field_inspection_plan' => [
          $inspection_plan->id(),
        ],
        'field_person' => [
          $person->id(),
        ],
      ] + $this->getBaseValues();

    return $values;
  }

  public function getGeneralEnquiryValues($values = []) {
    // We need to create an Enforcing Authority first.
    $enforcing_authority = ParDataAuthority::create($this->getAuthorityValues());
    $enforcing_authority->save();

    $people = $enforcing_authority->get('field_person')->referencedEntities();
    $person = current($people);

    // We need to create a partnership
    $partnership = ParDataPartnership::create($this->getDirectPartnershipValues());
    $partnership->save();

    $primary_authority = $partnership->getAuthority(TRUE);

    /** @var \Drupal\file\Entity\File $document */
    $document = $this->createFile();

    $values += [
        'type' => 'general_enquiry',
        'request_date' => '2017-10-01',
        'notes' => $this->randomString(1000),
        'primary_authority_status' => 'awaiting',
        'primary_authority_notes' => $this->randomString(1000),
        'document' => [
          $document->id()
        ],
        'field_document' => [
          $this->getMedia($document),
        ],
        'field_enforcing_authority' => [
          $enforcing_authority->id(),
        ],
        'field_partnership' => [
          $partnership->id(),
        ],
        'field_primary_authority' => [
          $primary_authority->id(),
        ],
        'field_person' => [
          $person->id(),
        ],
      ] + $this->getBaseValues();

    return $values;
  }

  public function getInspectionPlanValues($values = []) {
    $regulatory_function = ParDataRegulatoryFunction::create($this->getRegulatoryFunctionValues());
    $regulatory_function->save();

    /** @var \Drupal\file\Entity\File $document */
    $document = $this->createFile();

    $values += [
        'type' => 'inspection_plan',
        'valid_date' => [
          'value' => '2016-01-01',
          'end_value' => '2018-01-01',
        ],
        'approved_rd_executive' => TRUE,
        'consulted_national_regulator' => TRUE,
        'inspection_status' => 'Active',
        'document' => [
          $document->id()
        ],
        'field_document' => [
          $this->getMedia($document),
        ],
        'field_regulatory_function' => [
          $regulatory_function->id(),
        ],
      ] + $this->getBaseValues();

    return $values;
  }

  public function getLegalEntityValues($values = []) {
    $values += [
        'type' => 'legal_entity',
        'registered_name' => 'Jo\' Coffee Ltd',
        'registered_number' => '0123456789',
        'legal_entity_type' => 'Limited Company',
      ] + $this->getBaseValues();

    return $values;
  }

  public function getOrganisationValues($values = []) {
    // We need to create an SIC Code first.
    $sic_code = ParDataSicCode::create($this->getSicCodeValues());
    $sic_code->save();

    // We need to create a Person first.
    $person = ParDataPerson::create($this->getPersonValues());
    $person->save();

    // We need to create a Premises first.
    $premises = ParDataPremises::create($this->getPremisesValues());
    $premises->save();

    // We need to create a Legal Entity first.
    $legal_entity = ParDataLegalEntity::create($this->getLegalEntityValues());
    $legal_entity->save();

    $values += [
        'type' => 'organisation',
        'organisation_name' => 'Test Organisation',
        'size' => 'Enormous',
        'employees_band' => '10-50',
        'nation' => 'Wales',
        'comments' => $long_string = $this->randomString(1000),
        'premises_mapped' => TRUE,
        'trading_name' => [
          $this->randomString(255),
          $this->randomString(255),
          $this->randomString(255),
        ],
        'field_sic_code' => [
          $sic_code->id(),
        ],
        'field_person' => [
          $person->id(),
        ],
        'field_premises' => [
          $premises->id(),
        ],
        'field_legal_entity' => [
          $legal_entity->id(),
        ],
        'coordinator_number' => '12345',
        'coordinator_type' => 'Franchise',
      ] + $this->getBaseValues();

    return $values;
  }

  public function getCoordinatedBusinessValues($values = []) {
    // We need to create an Organisation Member first.
    $organisation_1 = ParDataOrganisation::create(['organisation_name' => 'Member ' . rand(0, 1)] + $this->getOrganisationValues());
    $organisation_1->save();

    $values += [
        'type' => 'coordinated_business',
        'valid_date' => [
          'value' => '2016-01-01',
          'end_value' => '2018-01-01',
        ],
        'field_organisation' => [
          $organisation_1->id(),
        ],
      ] + $this->getBaseValues();

    return $values;
  }

  public function getDirectPartnershipValues($values = []) {
    // We need to create an Organisation first.
    $organisation = ParDataOrganisation::create($this->getOrganisationValues());
    $organisation->save();

    // We need to create a Authority first.
    $authority = ParDataAuthority::create($this->getAuthorityValues());
    $authority->save();

    // We need to create an Advice first.
    $advice = ParDataAdvice::create($this->getAdviceValues());
    $advice->save();

    // We need to create an Inspection Plan first.
    $inspection_plan = ParDataInspectionPlan::create($this->getInspectionPlanValues());
    $inspection_plan->save();

    // We need to create a Regulatory Function first.
    $regulatory_function = ParDataRegulatoryFunction::create($this->getRegulatoryFunctionValues());
    $regulatory_function->save();

    // We need to create a Person first.
    $person_1 = ParDataPerson::create($this->getPersonValues());
    $person_1->save();

    // We need to create a Person first.
    $person_2 = ParDataPerson::create($this->getPersonValues());
    $person_2->save();

    $values += [
        'type' => 'partnership',
        'partnership_type' => 'direct',
        'partnership_status' => 'confirmed_rd',
        'about_partnership' => $this->randomString(1000),
        'approved_date' => '2017-06-01',
        'cost_recovery' => 'Cost recovery from partnership',
        'reject_comment' => $this->randomString(1000),
        'revocation_source' => 'RD Executive',
        'revocation_date' => '2017-07-01',
        'revocation_reason' => $this->randomString(1000),
        'authority_change_comment' => $this->randomString(1000),
        'organisation_change_comment' => $this->randomString(1000),
        'terms_organisation_agreed' => TRUE,
        'terms_authority_agreed' => TRUE,
        'coordinator_suitable' => TRUE,
        'partnership_info_agreed_authority' => TRUE,
        'partnership_info_agreed_business' => TRUE,
        'written_summary_agreed' => TRUE,
        'field_organisation' => [
          $organisation->id(),
        ],
        'field_authority' => [
          $authority->id(),
        ],
        'field_advice' => [
          $advice->id(),
        ],
        'field_inspection_plan' => [
          $inspection_plan->id(),
        ],
        'field_regulatory_function' => [
          $regulatory_function->id(),
        ],
        'field_authority_person' => [
          $person_1->id(),
        ],
        'field_organisation_person' => [
          $person_2->id(),
        ],
      ] + $this->getBaseValues();

    return $values;
  }

  public function getCoordinatedPartnershipValues($values = []) {
    // We need to create an Organisation first.
    $organisation = ParDataOrganisation::create($this->getOrganisationValues());
    $organisation->save();

    // We need to create a few memberships first.
    $coordinated_business_1 = ParDataCoordinatedBusiness::create($this->getCoordinatedBusinessValues());
    $coordinated_business_2 = ParDataCoordinatedBusiness::create($this->getCoordinatedBusinessValues());
    $coordinated_business_3 = ParDataCoordinatedBusiness::create($this->getCoordinatedBusinessValues());
    $coordinated_business_1->save();
    $coordinated_business_2->save();
    $coordinated_business_3->save();

    // We need to create a Authority first.
    $authority = ParDataAuthority::create($this->getAuthorityValues());
    $authority->save();

    // We need to create an Advice first.
    $advice = ParDataAdvice::create($this->getAdviceValues());
    $advice->save();

    // We need to create an Inspection Plan first.
    $inspection_plan = ParDataInspectionPlan::create($this->getInspectionPlanValues());
    $inspection_plan->save();

    // We need to create a Regulatory Function first.
    $regulatory_function = ParDataRegulatoryFunction::create($this->getRegulatoryFunctionValues());
    $regulatory_function->save();

    // We need to create a Person first.
    $person_1 = ParDataPerson::create($this->getPersonValues());
    $person_1->save();

    // We need to create a Person first.
    $person_2 = ParDataPerson::create($this->getPersonValues());
    $person_2->save();

    $values += [
        'type' => 'partnership',
        'partnership_type' => 'coordinated',
        'partnership_status' => 'Current',
        'about_partnership' => $this->randomString(1000),
        'approved_date' => '2017-06-01',
        'cost_recovery' => 'Cost recovery from partnership',
        'reject_comment' => $this->randomString(1000),
        'revocation_source' => 'RD Executive',
        'revocation_date' => '2017-07-01',
        'revocation_reason' => $this->randomString(1000),
        'authority_change_comment' => $this->randomString(1000),
        'organisation_change_comment' => $this->randomString(1000),
        'terms_organisation_agreed' => TRUE,
        'terms_authority_agreed' => TRUE,
        'coordinator_suitable' => TRUE,
        'partnership_info_agreed_authority' => TRUE,
        'partnership_info_agreed_business' => TRUE,
        'written_summary_agreed' => TRUE,
        'field_organisation' => [
          $organisation->id(),
        ],
        'field_coordinated_business' => [
          $coordinated_business_1->id(),
          $coordinated_business_2->id(),
          $coordinated_business_3->id(),
        ],
        'field_authority' => [
          $authority->id(),
        ],
        'field_advice' => [
          $advice->id(),
        ],
        'field_inspection_plan' => [
          $inspection_plan->id(),
        ],
        'field_regulatory_function' => [
          $regulatory_function->id(),
        ],
        'field_authority_person' => [
          $person_1->id(),
        ],
        'field_organisation_person' => [
          $person_2->id(),
        ],
      ] + $this->getBaseValues();

    return $values;
  }

  public function getPersonValues($values = []) {
    $values += [
        'type' => 'person',
        'salutation' => 'Mrs',
        'first_name' => 'Smith',
        'last_name' => 'Smith',
        'job_title' => 'Senior Telephone Sanitisation Engineer',
        'work_phone' => '01723456789',
        'mobile_phone' => '0777777777',
        'email' => $this->randomMachineName(20) . '@example.com',
        'communication_email' => TRUE,
        'communication_phone' => TRUE,
        'communication_mobile' => TRUE,
        'communication_notes' => $this->randomString(1000),
        'field_user_account' => [
          $this->account->id(),
        ],
        'field_notification_preferences' => [],
      ] + $this->getBaseValues();

    return $values;
  }

  public function getPremisesValues($values = []) {
    $values += [
        'type' => 'premises',
        'address' => [
          'country_code' => 'GB',
          'address_line1' => '1 High St',
          'address_line2' => 'London',
          'locality' => 'Greater London',
          'administrative_area' => 'GB-GB',
          'postal_code' => 'N11AA',
        ],
        'nation' => 'Wales',
        'uprn' => '10012330060',
      ] + $this->getBaseValues();

    return $values;
  }

  public function getRegulatoryFunctionValues($values = []) {
    $values += [
        'type' => 'regulatory_function',
        'function_name' => 'Health and Safety',
      ] + $this->getBaseValues();

    return $values;
  }

  public function getSicCodeValues($values = []) {
    $values += [
        'type' => 'sic_code',
        'sic_code' => '012345',
        'description' => 'This is an example SIC Code.',
      ] + $this->getBaseValues();

    return $values;
  }
}
