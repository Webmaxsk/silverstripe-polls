<?php

class Poll extends DataObject {

	private static $singular_name = "Poll";
	private static $plural_name = "Polls";

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
		'Groups' => 'Group',
		'Members' => 'Member'
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
		'Groups.ID',
		'Members.ID'
	);

	private static $summary_fields = array(
		'Title',
		'Status',
		'Active',
		'AllowResults'
	);

	private static $default_sort = "SortOrder ASC";

	public function fieldLabels($includerelations = true) {
		$cacheKey = $this->class . '_' . $includerelations;

		if(!isset(self::$_cache_field_labels[$cacheKey])) {
			$labels = parent::fieldLabels($includerelations);
			$labels['Status'] = _t('Poll.STATUS', 'Visible');
			$labels['Active'] = _t('Poll.ACTIVE', 'Active');
			$labels['AllowResults'] = _t('Poll.ALLOWRESULTS', 'Show results');
			$labels['Title'] = _t('Poll.TITLE', 'Title');
			$labels['Options'] = _t('Poll.OPTIONS', 'Options');
			$labels['SortOrder'] = _t('Poll.SORTORDER', 'Sort order');
			$labels['Groups.ID'] = _t('Group.SINGULARNAME', 'Group');
			$labels['Members.ID'] = _t('Member.SINGULARNAME', 'Member');

			if($includerelations) {
				$labels['Groups'] = _t('Group.PLURALNAME', 'Groups');
				$labels['Members'] = _t('Member.PLURALNAME', 'Members');
			}

			self::$_cache_field_labels[$cacheKey] = $labels;
		}

		return self::$_cache_field_labels[$cacheKey];
	}

	public function getCMSFields() {
		$self =& $this;

		$this->beforeUpdateCMSFields(function ($fields) use ($self) {
			$fields->removeByName('Groups');
			$fields->removeByName('Members');

			$fields->addFieldToTab('Root.Main',$fields->dataFieldByName('Title'));
			$fields->addFieldToTab('Root.Main',$fields->dataFieldByName('Options'));

			$Status = $fields->dataFieldByName('Status');
			$Active = $fields->dataFieldByName('Active');
			$AllowResults = $fields->dataFieldByName('AllowResults');

			$fields->removeByName('Status');
			$fields->removeByName('Active');
			$fields->removeByName('AllowResults');

			$fields->addFieldToTab('Root.Main',FieldGroup::create(
				$Status,$Active,$AllowResults
			)->setTitle(_t('Poll.CONFIGURATION', 'Configuration')));

			$fields->addFieldToTab('Root.Visibility',
				ListboxField::create('Groups',$this->fieldLabel('Groups'))
					->setMultiple(true)
					->setSource(Group::get()->map()->toArray())
					->setAttribute('data-placeholder', _t('SiteTree.GroupPlaceholder', 'Click to select group'))
					->setDescription(_t('Poll.GROUPSDESCRIPTION', 'Groups for whom are polls visible.')));
			$fields->addFieldToTab('Root.Visibility',
				ListboxField::create('Members',$this->fieldLabel('Members'))
					->setMultiple(true)
					->setSource(Member::get()->map()->toArray())
					->setAttribute('data-placeholder', _t('Poll.MemberPlaceholder', 'Click to select member'))
					->setDescription(_t('Poll.MEMBERSDESCRIPTION', 'Members for whom are polls visible.')));
			$fields->addFieldToTab('Root.Visibility',new ReadonlyField('Note',_t('Poll.NOTE', 'Note'),_t('Poll.NOTEDESCRIPTION', 'If there is noone selected, polls will be visible for everyone.')));

			$fields->fieldByName('Root.Visibility')->setTitle(_t('Poll.TABVISIBILITY', 'Visibility'));

			if (class_exists('GridFieldSortableRows'))
				$fields->removeByName('SortOrder');
		});

		return parent::getCMSFields();
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

		return $submission ? $submission->Option : _t('Poll.NOANSWER', 'No answer');
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
		return Controller::join_links(Director::baseURL().'polls', 'view', $this->ID);
	}

	public function canView($member = null) {
		return (($extended = $this->extendedCan(__FUNCTION__, $member))) !== null ? $extended :
			Permission::check('ADMIN','any',$member) || (($member || ($member = Member::currentUser())) && $this->Status && ((($groups = $this->Groups()) && ($members = $this->Members()) && !$groups->exists() && !$members->exists()) || ($groups->exists() && $member->inGroups($groups)) || ($members->exists() && $members->find('ID',$member->ID))) && ($this->Active || PollSubmission::get()->filter(array('PollID'=>$this->ID, 'MemberID'=>$member->ID))->limit(1)->first()));
	}
}