<?php

class PollSubmission extends DataObject {

	private static $singular_name = "Hodnotenie";
	private static $plural_name = "Hodnotenia";

	private static $db = array(
		'Option' => 'Varchar'
	);

	private static $has_one = array(
		'Poll' => 'Poll',
		'Member' => 'Member'
	);

	private static $searchable_fields = array( 
		'Poll.Status',
		'Poll.Active',
		'Poll.Title',
		'Option',
		'Member.ID'
	);

	private static $summary_fields = array(
		'Poll.Status',
		'Poll.Active',
		'Poll.Title',
		'Option',
		'Member.Name'
	);

	private static $field_labels = array(
		'Option' => 'Odpoveď',
		'Poll' => 'Anketa',
		'Member' => 'Používateľ',

		'Poll.Status' => 'Viditeľná anketa?',
		'Poll.Active' => 'Aktívna anketa?',
		'Poll.Title' => 'Anketa',
		'Member.ID' => 'Používateľ',
		'Member.Name' => 'Používateľ'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->changeFieldOrder(array('PollID','Option','MemberID'));

		return $fields;
	}

	public function getTitle() {
		return $this->Poll()->Title;
	}

	public function getName() {
		return $this->Title;
	}

	public function canCreate($member = null) {
		return false;
	}

	public function canEdit($member = null) {
		return false;
	}
}