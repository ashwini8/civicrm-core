<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * This class generates form components for relationship.
 */
class CRM_Contact_Form_Relationship extends CRM_Core_Form {

  /**
   * The relationship id, used when editing the relationship
   *
   * @var int
   */
  public $_relationshipId;

  /**
   * The contact id, used when add/edit relationship
   *
   * @var int
   */
  public $_contactId;

  /**
   * This is a string which is either a_b or  b_a  used to determine the relationship between to contacts
   */
  public $_rtype;

  /**
   * This is a string which is used to determine the relationship between to contacts
   */
  public $_rtypeId;

  /**
   * Display name of contact a
   */
  public $_display_name_a;

  /**
   * Display name of contact b
   */
  public $_display_name_b;

  /**
   * The relationship type id
   *
   * @var int
   */
  public $_relationshipTypeId;

  /**
   * An array of all relationship names
   *
   * @var array
   */
  public $_allRelationshipNames;

  /**
   * @var bool
   */
  public $_enabled;

  /**
   * @var bool
   */
  public $_isCurrentEmployer;

  /**
   * @var string
   */
  public $_contactType;

  /**
   * The relationship values if Updating relationship
   */
  public $_values;

  /**
   * Casid if it called from case context
   */
  public $_caseId;

  /**
   * @var mixed
   */
  public $_cdType;

  public function preProcess() {
    //custom data related code
    $this->_cdType = CRM_Utils_Array::value('type', $_GET);
    $this->assign('cdType', FALSE);
    if ($this->_cdType) {
      $this->assign('cdType', TRUE);
      return CRM_Custom_Form_CustomData::preProcess($this);
    }

    $this->_contactId = $this->get('contactId');

    $this->_contactType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'contact_type');

    $this->_relationshipId = $this->get('id');

    $this->_rtype = CRM_Utils_Request::retrieve('rtype', 'String', $this);

    $this->_rtypeId = CRM_Utils_Request::retrieve('relTypeId', 'String', $this);

    $this->_display_name_a = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'display_name');

    $this->assign('display_name_a', $this->_display_name_a);

    // Set page title based on action
    switch ($this->_action) {
      case CRM_Core_Action::VIEW:
        CRM_Utils_System::setTitle(ts('View Relationship for %1', array(1 => $this->_display_name_a)));
        break;

      case CRM_Core_Action::ADD:
        CRM_Utils_System::setTitle(ts('Add Relationship for %1', array(1 => $this->_display_name_a)));
        break;

      case CRM_Core_Action::UPDATE:
        CRM_Utils_System::setTitle(ts('Edit Relationship for %1', array(1 => $this->_display_name_a)));
        break;

      case CRM_Core_Action::DELETE:
        CRM_Utils_System::setTitle(ts('Delete Relationship for %1', array(1 => $this->_display_name_a)));
        break;
    }

    $this->_caseId = CRM_Utils_Request::retrieve('caseID', 'Integer', $this);

    //get the relationship values.
    $this->_values = array();
    if ($this->_relationshipId) {
      $params = array('id' => $this->_relationshipId);
      CRM_Core_DAO::commonRetrieve('CRM_Contact_DAO_Relationship', $params, $this->_values);
    }

    if (!$this->_rtypeId) {
      $params = $this->controller->exportValues($this->_name);
      if (isset($params['relationship_type_id'])) {
        $this->_rtypeId = $params['relationship_type_id'];
      }
      elseif (!empty($this->_values)) {
        $this->_rtypeId = $this->_values['relationship_type_id'] . '_' . $this->_rtype;
      }
    }

    //get the relationship type id
    $this->_relationshipTypeId = str_replace(array('_a_b', '_b_a'), array('', ''), $this->_rtypeId);

    //get the relationship type
    if (!$this->_rtype) {
      $this->_rtype = str_replace($this->_relationshipTypeId . '_', '', $this->_rtypeId);
    }

    //need to assign custom data type and subtype to the template - FIXME: explain why
    $this->assign('customDataType', 'Relationship');
    $this->assign('customDataSubType', $this->_relationshipTypeId);
    $this->assign('entityID', $this->_relationshipId);

    //use name as it remain constant, CRM-3336
    $this->_allRelationshipNames = CRM_Core_PseudoConstant::relationshipType('name');

    // Current employer?
    if ($this->_action & CRM_Core_Action::UPDATE) {
      if ($this->_allRelationshipNames[$this->_relationshipTypeId]["name_a_b"] == 'Employee of') {
        $this->_isCurrentEmployer = $this->_values['contact_id_b'] == CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_values['contact_id_a'], 'employer_id');
      }
    }

    // when custom data is included in this page
    if (!empty($_POST['hidden_custom'])) {
      CRM_Custom_Form_CustomData::preProcess($this);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    $defaults = array();

    if ($this->_action & CRM_Core_Action::UPDATE) {
      if (!empty($this->_values)) {
        $defaults['relationship_type_id'] = $this->_rtypeId;
        if (!empty($this->_values['start_date'])) {
          list($defaults['start_date']) = CRM_Utils_Date::setDateDefaults($this->_values['start_date']);
        }
        if (!empty($this->_values['end_date'])) {
          list($defaults['end_date']) = CRM_Utils_Date::setDateDefaults($this->_values['end_date']);
        }
        $defaults['description'] = CRM_Utils_Array::value('description', $this->_values);
        $defaults['is_active'] = CRM_Utils_Array::value('is_active', $this->_values);

        // The javascript on the form will swap these fields if it is a b_a relationship, so we compensate here
        $defaults['is_permission_a_b'] = CRM_Utils_Array::value('is_permission_' . $this->_rtype, $this->_values);
        $defaults['is_permission_b_a'] = CRM_Utils_Array::value('is_permission_' . strrev($this->_rtype), $this->_values);

        $defaults['is_current_employer'] = $this->_isCurrentEmployer;

        // Load info about the related contact
        $contact = new CRM_Contact_DAO_Contact();
        if ($this->_rtype == 'a_b' && $this->_values['contact_id_a'] == $this->_contactId) {
          $contact->id = $this->_values['contact_id_b'];
        }
        else {
          $contact->id = $this->_values['contact_id_a'];
        }
        if ($contact->find(TRUE)) {
          $defaults['related_contact_id'] = $contact->id;
          $this->_display_name_b = $contact->display_name;
          $this->assign('display_name_b', $this->_display_name_b);
        }

        $noteParams = array(
          'entity_id' => $this->_relationshipId,
          'entity_table' => 'civicrm_relationship',
          'limit' => 1,
          'version' => 3,
        );
        $note = civicrm_api('Note', 'getsingle', $noteParams);
        $defaults['note'] = CRM_Utils_Array::value('note', $note);
      }
    }
    else {
      $defaults['is_active'] = $defaults['is_current_employer'] = 1;
      $defaults['relationship_type_id'] = $this->_rtypeId;
    }

    $this->_enabled = $defaults['is_active'];
    return $defaults;
  }

  /**
   * Add the rules for form.
   */
  public function addRules() {
    if ($this->_cdType) {
      return;
    }

    if (!($this->_action & CRM_Core_Action::DELETE)) {
      $this->addFormRule(array('CRM_Contact_Form_Relationship', 'dateRule'));
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::buildQuickForm($this);
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Delete'),
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
      return;
    }
    // Just in case custom data includes a rich text field
    $this->assign('includeWysiwygEditor', TRUE);

    // Select list
    $relationshipList = CRM_Contact_BAO_Relationship::getContactRelationshipType($this->_contactId, $this->_rtype, $this->_relationshipId);

    // Metadata needed on clientside
    $contactTypes = CRM_Contact_BAO_ContactType::contactTypeInfo(TRUE);
    $jsData = array();
    // Get just what we need to keep the dom small
    $whatWeWant = array_flip(array('contact_type_a', 'contact_type_b', 'contact_sub_type_a', 'contact_sub_type_b'));
    foreach ($this->_allRelationshipNames as $id => $vals) {
      if ($vals['name_a_b'] === 'Employee of') {
        $this->assign('employmentRelationship', $id);
      }
      if (isset($relationshipList["{$id}_a_b"]) || isset($relationshipList["{$id}_b_a"])) {
        $jsData[$id] = array_filter(array_intersect_key($this->_allRelationshipNames[$id], $whatWeWant));
        // Add user-friendly placeholder
        foreach (array('a', 'b') as $x) {
          $type = !empty($jsData[$id]["contact_sub_type_$x"]) ? $jsData[$id]["contact_sub_type_$x"] : CRM_Utils_Array::value("contact_type_$x", $jsData[$id]);
          $jsData[$id]["placeholder_$x"] = $type ? ts('- select %1 -', array(strtolower($contactTypes[$type]['label']))) : ts('- select contact -');
        }
      }
    }
    $this->assign('relationshipData', $jsData);

    $this->add(
      'select',
      'relationship_type_id',
      ts('Relationship Type'),
      array('' => ts('- select -')) + $relationshipList,
      TRUE,
      array('class' => 'crm-select2 huge')
    );

    $label = $this->_action & CRM_Core_Action::ADD ? ts('Contact(s)') : ts('Contact');
    $contactField = $this->addEntityRef('related_contact_id', $label, array(
        'multiple' => TRUE,
        'create' => TRUE,
      ), TRUE);
    // This field cannot be updated
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $contactField->freeze();
    }

    $this->add('advcheckbox', 'is_current_employer', $this->_contactType == 'Organization' ? ts('Current Employee') : ts('Current Employer'));

    $this->addDate('start_date', ts('Start Date'), FALSE, array('formatType' => 'searchDate'));
    $this->addDate('end_date', ts('End Date'), FALSE, array('formatType' => 'searchDate'));

    $this->add('advcheckbox', 'is_active', ts('Enabled?'));

    // CRM-14612 - Don't use adv-checkbox as it interferes with the form js
    $this->add('checkbox', 'is_permission_a_b');
    $this->add('checkbox', 'is_permission_b_a');

    $this->add('text', 'description', ts('Description'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Relationship', 'description'));

    CRM_Contact_Form_Edit_Notes::buildQuickForm($this);

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->addButtons(array(
        array(
          'type' => 'cancel',
          'name' => ts('Done'),
        ),
      ));
    }
    else {
      // make this form an upload since we don't know if the custom data injected dynamically is of type file etc.
      $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('Save Relationship'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      ));
    }
  }

  /**
   * This function is called when the form is submitted.
   */
  public function postProcess() {
    // Store the submitted values in an array.
    $params = $this->controller->exportValues($this->_name);

    // action is taken depending upon the mode
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Contact_BAO_Relationship::del($this->_relationshipId);
      return;
    }

    $relationshipTypeParts = explode('_', $params['relationship_type_id']);
    $params['relationship_type_id'] = $relationshipTypeParts[0];
    if (!$this->_rtype) {
      // Do we need to wrap this in an if - when is rtype used & is relationship_type_id always set then?
      $this->_rtype = $params['relationship_type_id'];
    }
    $params['contact_id_' .  $relationshipTypeParts[1]] = $this->_contactId;

    // Update mode (always single)
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_relationshipId;
      $ids['relationship'] = $this->_relationshipId;
      $relation = CRM_Contact_BAO_Relationship::getRelationshipByID($this->_relationshipId);
      if ($relation->contact_id_a == $this->_contactId) {
        $params['contact_id_a'] = $this->_contactId;
        $params['contact_id_b'] = array($params['related_contact_id']);
      }
      else {
        $params['contact_id_b'] = $this->_contactId;
        $params['contact_id_a'] = array($params['related_contact_id']);
      }
      $ids['contactTarget'] = ($relation->contact_id_a == $this->_contactId) ? $relation->contact_id_b : $relation->contact_id_a;

      // @todo this belongs in the BAO.
      if ($this->_isCurrentEmployer) {
        // if relationship type changes, relationship is disabled, or "current employer" is unchecked,
        // clear the current employer. CRM-3235.
        $relChanged = $params['relationship_type_id'] != $this->_values['relationship_type_id'];
        if (!$params['is_active'] || !$params['is_current_employer'] || $relChanged) {
          CRM_Contact_BAO_Contact_Utils::clearCurrentEmployer($this->_values['contact_id_a']);
          // Refresh contact summary if in ajax mode
          $this->ajaxResponse['reloadBlocks'] = array('#crm-contactinfo-content');
        }
      }
    }
    // Create mode (could be 1 or more relationships)
    else {
      $params['contact_id_' .  $relationshipTypeParts[2]] = explode(',', $params['related_contact_id']);
    }

    // Save the relationships.
    $outcome = CRM_Contact_BAO_Relationship::createMultiple($params, $relationshipTypeParts[1]);
    $relationshipIds = $outcome['relationship_ids'];
    $this->setMessage($outcome);

    // if this is called from case view,
    //create an activity for case role removal.CRM-4480
    // @todo this belongs in the BAO.
    if ($this->_caseId) {
      CRM_Case_BAO_Case::createCaseRoleActivity($this->_caseId, $relationshipIds, $params['contact_check'], $this->_contactId);
    }

    // Save notes
    // @todo this belongs in the BAO.
    if ($this->_action & CRM_Core_Action::UPDATE || $params['note']) {
      foreach ($relationshipIds as $id) {
        $noteParams = array(
          'entity_id' => $id,
          'entity_table' => 'civicrm_relationship',
        );
        $existing = civicrm_api3('note', 'get', $noteParams);
        if (!empty($existing['id'])) {
          $noteParams['id'] = $existing['id'];
        }
        $noteParams['note'] = $params['note'];
        $noteParams['contact_id'] = $this->_contactId;
        if (!empty($existing['id']) || $params['note']) {
          $action = $params['note'] ? 'create' : 'delete';
          civicrm_api3('note', $action, $noteParams);
        }
      }
    }

    $params['relationship_ids'] = $relationshipIds;

    // Refresh contact tabs which might have been affected
    $this->ajaxResponse['updateTabs'] = array(
      '#tab_member' => CRM_Contact_BAO_Contact::getCountComponent('membership', $this->_contactId),
      '#tab_contribute' => CRM_Contact_BAO_Contact::getCountComponent('contribution', $this->_contactId),
    );

    // Set current employee/employer relationship, CRM-3532
    if ($params['is_current_employer'] && $this->_allRelationshipNames[$params['relationship_type_id']]["name_a_b"] ==
    'Employee of') {
      $employerParams = array();
      foreach ($relationshipIds as $id) {
        // Fixme this is dumb why do we have to look this up again?
        $rel = CRM_Contact_BAO_Relationship::getRelationshipByID($id);
        $employerParams[$rel->contact_id_a] = $rel->contact_id_b;
      }
      // @todo this belongs in the BAO.
      CRM_Contact_BAO_Contact_Utils::setCurrentEmployer($employerParams);
      // Refresh contact summary if in ajax mode
      $this->ajaxResponse['reloadBlocks'] = array('#crm-contactinfo-content');
    }
  }

  /**
   * Date validation.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return bool|array
   *   mixed true or array of errors
   */
  public static function dateRule($params) {
    $errors = array();

    // check start and end date
    if (!empty($params['start_date']) && !empty($params['end_date'])) {
      $start_date = CRM_Utils_Date::format(CRM_Utils_Array::value('start_date', $params));
      $end_date = CRM_Utils_Date::format(CRM_Utils_Array::value('end_date', $params));
      if ($start_date && $end_date && (int ) $end_date < (int ) $start_date) {
        $errors['end_date'] = ts('The relationship end date cannot be prior to the start date.');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Set Status message to reflect outcome of the update action.
   *
   * @param array $outcome
   *   Outcome of save action - including
   *   - 'valid' : Number of valid relationships attempted.
   *   - 'invalid' : Number of invalid relationships attempted.
   *   - 'duplicate' : Number of duplicate relationships attempted.
   *   - 'saved' : boolean of whether save was successful
   */
  protected function setMessage($outcome) {
    if (!empty($outcome['valid'])) {
      CRM_Core_Session::setStatus(ts('Relationship created.', array(
        'count' => $outcome['valid'],
        'plural' => '%count relationships created.',
      )), ts('Saved'), 'success');
    }
    if (!empty($outcome['invalid'])) {
      CRM_Core_Session::setStatus(ts('%count relationship record was not created due to an invalid contact type.', array(
        'count' => $outcome['invalid'],
        'plural' => '%count relationship records were not created due to invalid contact types.',
      )), ts('%count invalid relationship record', array(
        'count' => $outcome['invalid'],
        'plural' => '%count invalid relationship records',
      )));
    }
    if (!empty($outcome['duplicate'])) {
      CRM_Core_Session::setStatus(ts('One relationship was not created because it already exists.', array(
        'count' => $outcome['duplicate'],
        'plural' => '%count relationships were not created because they already exist.',
      )), ts('%count duplicate relationship', array(
        'count' => $outcome['duplicate'],
        'plural' => '%count duplicate relationships',
      )));
    }
    if (!empty($outcome['saved'])) {
      CRM_Core_Session::setStatus(ts('Relationship record has been updated.'), ts('Saved'), 'success');
    }
  }

}
