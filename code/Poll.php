<?php

class Poll extends DataObject {

	private static $singular_name = "Anketa";
	private static $plural_name = "Ankety";

	protected
		$controller = null;

	private static $db = array(
		'Status' => 'Boolean',
		'Active' => 'Boolean',
		'AllowResults' => 'Boolean',
		'Title' => 'Varchar(100)',
		'Options' => 'Text',

		'SortOrder' => 'Int'
	);

	private static $many_many = array(
		'Groups' => 'Group'
	);

	private static $defaults = array(
		'Status' => '1',
		'Active' => '1',
		'AllowResults' => '1'
	);

	private static $searchable_fields = array( 
		'Status',
		'Active',
		'AllowResults',
		'Title',
		'Options',
		'Groups.ID'
	);

	private static $summary_fields = array(
		'Title',
		'Status',
		'Active',
		'AllowResults'
	);

	private static $field_labels = array(
		'Status' => 'Viditeľná?',
		'Active' => 'Aktívna?',
		'AllowResults' => 'Povoliť výsledky?',
		'Title' => 'Nadpis',
		'Options' => 'Možnosti',
		'SortOrder' => 'Poradie',
		'Groups' => 'Skupiny',
		'Groups.ID' => 'Skupiny'
	);

	private static $default_sort = "SortOrder ASC";

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->changeFieldOrder(array('Status','Active','AllowResults','Title','Options','Groups'));

		$fields->replaceField('Groups',
			ListboxField::create('Groups',$this->fieldLabel('Groups'))
				->setMultiple(true)
				->setSource(Group::get()->map()->toArray())
				->setAttribute('data-placeholder', _t('SiteTree.GroupPlaceholder', 'Click to select group'))
				->setDescription('Skupiny používateľov, pre ktoré sa anketa zobrazuje. Ak nie sú vybraté skupiny, anketa sa zobrazuje pre všetkých používateľov.')
		);

		if (class_exists('GridFieldSortableRows'))
			$fields->removeByName('SortOrder');

		return $fields;
	}

	public function getFrontEndFields($params = null) {
		$fields = new FieldList();

		$fields->push(new OptionsetField('Option',$this->Title ? $this->Title : "",$this->getOptionsAsArray()));

		return $fields;
	}

	public function getFrontEndValidator() {
		$validator = new RequiredFields('Option');

		return $validator;
	}

	private function getOptionsAsArray() {
		$moznosti = preg_split("/\r\n|\n|\r/", $this->getField('Options'));

		return array_combine($moznosti,$moznosti);
	}

	public function getResults() {
		$submissions = new GroupedList(PollSubmission::get()->filter('PollID',$this->ID));

		$options = $this->getOptionsAsArray();
		$total = $submissions->Count();
		$submissionOptions = $submissions->groupBy('Option');
		$list = new ArrayList();

		foreach($options as $option => $pollSubmissions) {
			$list->push(new ArrayData(array(
				'Option' => $option,
				'Percentage' => isset($submissionOptions[$option]) ? (int)($submissionOptions[$option]->Count() / $total * 100) : (int)0
			)));
		}

		return new ArrayData(array('Total' => $total, 'Results' => $list));
	}

	public function getMySubmission() {
		$submission = PollSubmission::get()->filter(array('PollID'=>$this->ID, 'MemberID'=>Member::currentUserID()))->limit(1)->first();

		return $submission ? $submission->Option : "Žiadna odpoveď";
	}

	public function getName() {
		return $this->Title;
	}

	public function getController() {
		if (!$this->controller)
			$this->controller = Injector::inst()->create("{$this->class}_Controller", $this);

		return $this->controller;
	}

	public function Link() {
		return Controller::join_links(Director::baseURL().'poll', 'view', $this->ID);
	}

	public function canView($member = null) {
		return Permission::check('ADMIN','any',$member)
			|| (($member || ($member = Member::currentUser())) && $this->Status && ((($groups = $this->Groups()) && !$groups->exists()) || $member->inGroups($groups)) && ($this->Active || PollSubmission::get()->filter(array('PollID'=>$this->ID, 'MemberID'=>$member->ID))->limit(1)->first()));
	}
}