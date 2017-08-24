<?php

class PollsMemberExtension extends DataExtension {

	private static $has_many = array(
		'PollSubmissions' => 'PollSubmission'
	);

	public function updateFieldLabels(&$labels) {
		$field_labels = Config::inst()->get($this->class, 'field_labels');

		$field_labels['PollSubmissions'] = _t('PollSubmission.PLURALNAME', 'Submissions');

		if ($field_labels)
			$labels = array_merge($labels, $field_labels);
	}

	public function updateCMSFields(FieldList $fields) {
		if ($pollSubmissionsGridField = $fields->dataFieldByName('PollSubmissions')) {
			$pollSubmissionsGridFieldConfig = $pollSubmissionsGridField->getConfig();

			$pollSubmissionsDisplayFields = $pollSubmissionsGridFieldConfig
				->getComponentByType('GridFieldDataColumns')->getDisplayFields($pollSubmissionsGridField);

			unset($pollSubmissionsDisplayFields['Member.Name']);

			$pollSubmissionsGridFieldConfig->getComponentByType('GridFieldDataColumns')->setDisplayFields($pollSubmissionsDisplayFields);
		}
	}

	public function canViewVotingResults($pollID) {
		return ($poll = DataObject::get_by_id('Poll', $pollID)) && $poll->exists() && $poll->isAllowedVotingResults(Member::currentUserID());
	}

	public function getMySubmissions($pollID) {
		$filter = array(
			'MemberID' => Member::currentUserID(),
			'PollID' => $pollID
		);

		return PollSubmission::get()->filter($filter)->sort('ID ASC');
	}
}