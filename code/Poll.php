<?php

class Poll extends DataObject implements PermissionProvider {

	const VIEW_PERMISSION = 'POLL_VIEW';
	const EDIT_PERMISSION = 'POLL_EDIT';
	const DELETE_PERMISSION = 'POLL_DELETE';
	const CREATE_PERMISSION = 'POLL_CREATE';

	private static $singular_name = "Poll";
	private static $plural_name = "Polls";

	protected
		$controller = null;

	private static $db = array(
		'Status' => 'Boolean',
		'Active' => 'Boolean',
		'AllowResults' => 'Boolean',
		'EnableMultiselect' => 'Boolean',
		'MinOptionsCount' => 'Int',
		'MaxOptionsCount' => 'Int',
		'Title' => 'Varchar(100)',
		'Options' => 'Text',
		'AvailableFrom' => 'Date',
		'AvailableTo' => 'Date',

		'SortOrder' => 'Int'
	);

	private static $many_many = array(
		'VisibleGroups' => 'Group',
		'VisibleMembers' => 'Member',

		'AllowedResultsGroups' => 'Group',
		'AllowedResultsMembers' => 'Member'
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
		'EnableMultiselect',
		'Title',
		'Options',
		'VisibleGroups.ID',
		'VisibleMembers.ID',
		'AllowedResultsGroups.ID',
		'AllowedResultsMembers.ID'
	);

	private static $summary_fields = array(
		'Title',
		'ColumnAvailability',
		'Status',
		'Active',
		'AllowResults'
	);

	private static $default_sort = "SortOrder ASC";

	public function fieldLabels($includerelations = true) {
		$cacheKey = $this->class . '_' . $includerelations;

		if(!isset(self::$_cache_field_labels[$cacheKey])) {
			$labels = parent::fieldLabels($includerelations);
			$labels['Status'] = _t('Poll.STATUS', 'Show poll');
			$labels['Active'] = _t('Poll.ACTIVE', 'Active');
			$labels['AllowResults'] = _t('Poll.ALLOWRESULTS', 'Allow voting results');
			$labels['EnableMultiselect'] = _t('Poll.ENABLEMULTISELECT', 'Enable select multiple options');
			$labels['MinOptionsCount'] = _t('Poll.MINOPTIONSCOUNT', 'Min count');
			$labels['MaxOptionsCount'] = _t('Poll.MAXOPTIONSCOUNT', 'Max count');
			$labels['Title'] = _t('Poll.TITLE', 'Title');
			$labels['Options'] = _t('Poll.OPTIONS', 'Options');
			$labels['AvailableFrom'] = _t('Poll.AVAILABLEFROM', 'Available from');
			$labels['AvailableTo'] = _t('Poll.AVAILABLETO', 'Available to');
			$labels['ColumnAvailability'] = _t('Poll.AVAILABILITY', 'Availability');
			$labels['SortOrder'] = _t('Poll.SORTORDER', 'Sort order');
			$labels['VisibleGroups.ID'] = _t('Group.SINGULARNAME', 'Group');
			$labels['VisibleMembers.ID'] = _t('Member.SINGULARNAME', 'Member');
			$labels['AllowedResultsGroups.ID'] = _t('Group.SINGULARNAME', 'Group');
			$labels['AllowedResultsMembers.ID'] = _t('Member.SINGULARNAME', 'Member');

			if($includerelations) {
				$labels['VisibleGroups'] = _t('Group.PLURALNAME', 'Groups');
				$labels['VisibleMembers'] = _t('Member.PLURALNAME', 'Members');
				$labels['AllowedResultsGroups'] = _t('Group.PLURALNAME', 'Groups');
				$labels['AllowedResultsMembers'] = _t('Member.PLURALNAME', 'Members');
			}

			self::$_cache_field_labels[$cacheKey] = $labels;
		}

		return self::$_cache_field_labels[$cacheKey];
	}

	public function ColumnAvailability() {
		$availability = "-";

		if ($this->AvailableFrom && $this->AvailableTo)
			$availability = $this->dbObject('AvailableFrom')->Nice() . ' - ' . $this->dbObject('AvailableTo')->Nice();
		elseif ($this->AvailableFrom)
			$availability =  _t('Poll.FROM', 'From') . ' ' . $this->dbObject('AvailableFrom')->Nice();
		elseif ($this->AvailableTo)
			$availability = _t('Poll.TO', 'To') . ' ' . $this->dbObject('AvailableTo')->Nice();

		return $availability;
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();

		$this->Options = trim($this->Options);
	}

	public function onBeforeDelete() {
		parent::onBeforeDelete();

		$relevantPollSubmissions = PollSubmission::get()->filter('PollID',$this->ID);
		foreach ($relevantPollSubmissions as $pollSubmission)
			$pollSubmission->delete();

		if (class_exists('Widget')) {
			$relevantWidgets = PollWidget::get()->filter('PollID',$this->ID);
			foreach ($relevantWidgets as $widget)
				$widget->delete();
		}
	}

	public function getCMSFields() {
		$self =& $this;

		$this->beforeUpdateCMSFields(function ($fields) use ($self) {
			$fields->removeByName('VisibleGroups');
			$fields->removeByName('VisibleMembers');
			$fields->removeByName('AllowedResultsGroups');
			$fields->removeByName('AllowedResultsMembers');

			/* Main */
			$fields->addFieldToTab('Root.Main', $fields->dataFieldByName('Title'));
			$fields->addFieldToTab('Root.Main', $fields->dataFieldByName('EnableMultiselect'));

			$minOptionsCountField = $fields->dataFieldByName('MinOptionsCount');
			$maxOptionsCountField = $fields->dataFieldByName('MaxOptionsCount');

			$fields->removeByName('MinOptionsCount');
			$fields->removeByName('MaxOptionsCount');

			$optionsCountField = FieldGroup::create(
				$minOptionsCountField, $maxOptionsCountField
			)->setTitle(_t('Poll.MULTIPLEOPTIONSCOUNT', 'Number of multiple options'));

			if (class_exists('DisplayLogicWrapper'))
				$optionsCountField = DisplayLogicWrapper::create($optionsCountField)
					->displayIf("EnableMultiselect")->isChecked()->end();

			$fields->addFieldToTab('Root.Main', $optionsCountField);

			$fields->addFieldToTab('Root.Main', $fields->dataFieldByName('Options'));
			$fields->addFieldToTab('Root.Main', $fields->dataFieldByName('Active'));

			$availableFromField = $fields->dataFieldByName('AvailableFrom')->setTitle(null);
			$availableToField = $fields->dataFieldByName('AvailableTo')->setTitle(null);

			$fields->removeByName('AvailableFrom');
			$fields->removeByName('AvailableTo');

			$fields->addFieldToTab('Root.Main',FieldGroup::create(
				$availableFromField, $availableToField
			)->setTitle(_t('Poll.AVAILABILITY', 'Availability')));

			/* Visibility */
			$fields->addFieldsToTab('Root.Visibility', array(
				$fields->dataFieldByName('Status'),
				ListboxField::create('VisibleGroups',$this->fieldLabel('VisibleGroups'))
					->setMultiple(true)
					->setSource(Group::get()->map()->toArray())
					->setAttribute('data-placeholder', _t('SiteTree.GroupPlaceholder', 'Click to select group'))
					->setDescription(_t('Poll.VISIBLEGROUPSDESCRIPTION', 'Groups for whom is poll visible.')),
				ListboxField::create('VisibleMembers',$this->fieldLabel('VisibleMembers'))
					->setMultiple(true)
					->setSource(Member::get()->map()->toArray())
					->setAttribute('data-placeholder', _t('Poll.MEMBERPLACEHOLDER', 'Click to select member'))
					->setDescription(_t('Poll.VISIBLEMEMBERSDESCRIPTION', 'Members for whom is poll visible.')),
				new ReadonlyField('VisibilityNote',_t('Poll.VISIBILITYNOTE', 'Note'),_t('Poll.VISIBILITYNOTEDESCRIPTION', 'If there is none selected, poll will be visible for everyone.'))
			));

			$fields->fieldByName('Root.Visibility')->setTitle(_t('Poll.TABVISIBILITY', 'Visibility'));

			/* Voting results */
			$fields->addFieldsToTab('Root.VotingResults', array(
				$fields->dataFieldByName('AllowResults'),
				ListboxField::create('AllowedResultsGroups',$this->fieldLabel('AllowedResultsGroups'))
					->setMultiple(true)
					->setSource(Group::get()->map()->toArray())
					->setAttribute('data-placeholder', _t('SiteTree.GroupPlaceholder', 'Click to select group'))
					->setDescription(_t('Poll.ALLOWEDRESULTSGROUPSDESCRIPTION', 'Groups for whom are voting results allowed.')),
				ListboxField::create('AllowedResultsMembers',$this->fieldLabel('AllowedResultsMembers'))
					->setMultiple(true)
					->setSource(Member::get()->map()->toArray())
					->setAttribute('data-placeholder', _t('Poll.MEMBERPLACEHOLDER', 'Click to select member'))
					->setDescription(_t('Poll.ALLOWEDRESULTSMEMBERSDESCRIPTION', 'Members for whom are voting results allowed.')),
				new ReadonlyField('VotingResultsNote',_t('Poll.VOTINGRESULTSNOTE', 'Note'),_t('Poll.VOTINGRESULTSNOTEDESCRIPTION', 'If there is none selected, voting results will be allowed for everyone.'))
			));

			$fields->fieldByName('Root.VotingResults')->setTitle(_t('Poll.TABVOTINGRESULTS', 'Voting results'));

			if (class_exists('GridFieldSortableRows') || class_exists('GridFieldOrderableRows'))
				$fields->removeByName('SortOrder');
		});

		return parent::getCMSFields();
	}

	public function getCMSValidator() {
		$requiredFields = new Poll_Validator(
			'Title', 'Options'
		);

		return $requiredFields;
	}

	public function getFrontEndFields($params = null) {
		$fields = new FieldList();

		if ($this->EnableMultiselect)
			$formField = 'CheckboxSetField';
		else
			$formField = 'OptionsetField';

		$fields->push($formField::create('Option', $this->Title ?: "", $this->getOptionsAsArray()));

		return $fields;
	}

	public function getFrontEndValidator() {
		$required = array();

		if (!$this->EnableMultiselect)
			$required[] = 'Option';

		return new Poll_FrontEndValidator($required);
	}

	private function getOptionsAsArray() {
		$options = preg_split("/\r\n|\n|\r/", $this->getField('Options'));

		return array_combine($options, $options);
	}

	public function getResults() {
		$filter = array(
			'PollID' => $this->ID,
			'MemberID:not' => 0
		);

		$submissions = new GroupedList(PollSubmission::get()->filter($filter));

		$submissionOptions = $submissions->groupBy('Option');
		$memberSubmissionsCount = count($submissions->groupBy('MemberID'));

		$results = new ArrayList();
		$options = $this->getOptionsAsArray();

		foreach ($options as $option) {
			$results->push(new ArrayData(array(
				'Option' => $option,
				'Percentage' => isset($submissionOptions[$option]) ? (int)($submissionOptions[$option]->Count() / $memberSubmissionsCount * 100) : (int)0
			)));
		}

		return new ArrayData(array('Total' => $memberSubmissionsCount, 'Results' => $results));
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

	public function providePermissions() {
		return array(
			self::VIEW_PERMISSION => array(
				'name' => _t('Poll.PERMISSION_VIEW', 'Read poll'),
				'category' => _t('Poll.PERMISSIONS_CATEGORY', 'Poll permissions')
				),

			self::EDIT_PERMISSION => array(
				'name' => _t('Poll.PERMISSION_EDIT', 'Edit poll'),
				'category' => _t('Poll.PERMISSIONS_CATEGORY', 'Poll permissions')
				),

			self::DELETE_PERMISSION => array(
				'name' => _t('Poll.PERMISSION_DELETE', 'Delete poll'),
				'category' => _t('Poll.PERMISSIONS_CATEGORY', 'Poll permissions')
				),

			self::CREATE_PERMISSION => array(
				'name' => _t('Poll.PERMISSION_CREATE', 'Create poll'),
				'category' => _t('Poll.PERMISSIONS_CATEGORY', 'Poll permissions')
				)
			);
	}

	private function getMember($member = null) {
		if (!$member)
			$member = Member::currentUser();

		if (is_numeric($member))
			$member = Member::get()->byID($member);

		return $member;
	}

	private function isPollVisible($member) {
		$visibleGroups = $this->VisibleGroups();
		$visibleMembers = $this->VisibleMembers();

		return $this->exists() && $this->Status
			&& ($member = $this->getMember($member))
			&& (
				(!$visibleGroups->exists() && !$visibleMembers->exists())
				|| ($visibleGroups->exists() && $member->inGroups($visibleGroups))
				|| ($visibleMembers->exists() && $visibleMembers->find('ID',$member->ID))
			);
	}

	public function isPollActive() {
		return ($this->Active
			&& (!$this->AvailableFrom || $this->AvailableFrom <= date('Y-m-d'))
			&& (!$this->AvailableTo || $this->AvailableTo >= date('Y-m-d')));
	}

	public function memberVoted($member) {
		return ($submission = PollSubmission::get()->filter(array('PollID'=>$this->ID, 'MemberID'=>$member->ID))->limit(1)->first()) && $submission->exists();
	}

	public function isAllowedVotingResults($member) {
		$allowedResultsGroups = $this->AllowedResultsGroups();
		$allowedResultsMembers = $this->AllowedResultsMembers();

		return $this->AllowResults
			&& ($member = $this->getMember($member))
			&& (
				(!$allowedResultsGroups->exists() && !$allowedResultsMembers->exists())
				|| ($allowedResultsGroups->exists() && $member->inGroups($allowedResultsGroups))
				|| ($allowedResultsMembers->exists() && $allowedResultsMembers->find('ID',$member->ID))
			);
	}

	private function canViewPoll($member) {
		return Permission::checkMember($member, self::VIEW_PERMISSION)
		|| ($this->isPollVisible($member) && ($this->isPollActive() || $this->memberVoted($member)));
	}

	public function canView($member = null) {
		$member = $this->getMember($member);

		if (!$member || !$member->exists())
			return false;

		return (($extended = $this->extendedCan(__FUNCTION__, $member))) !== null ? $extended :
			$this->canViewPoll($member);
	}

	public function canEdit($member = null) {
		return !$this->exists() || Permission::checkMember($member, self::EDIT_PERMISSION);
	}

	public function canDelete($member = null) {
		return Permission::checkMember($member, self::DELETE_PERMISSION);
	}

	public function canCreate($member = null) {
		return Permission::checkMember($member, self::CREATE_PERMISSION);
	}
}