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
		'Title' => 'Varchar(100)',
		'Options' => 'Text',
		'AvailableFrom' => 'Date',
		'AvailableTo' => 'Date',

		'SortOrder' => 'Int'
	);

	private static $many_many = array(
		'VisibleGroups' => 'Group',
		'VisibleMembers' => 'Member'
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
		'VisibleGroups.ID',
		'VisibleMembers.ID'
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
			$labels['Status'] = _t('Poll.STATUS', 'Visible');
			$labels['Active'] = _t('Poll.ACTIVE', 'Active');
			$labels['AllowResults'] = _t('Poll.ALLOWRESULTS', 'Show results');
			$labels['Title'] = _t('Poll.TITLE', 'Title');
			$labels['Options'] = _t('Poll.OPTIONS', 'Options');
			$labels['AvailableFrom'] = _t('Poll.AVAILABLEFROM', 'Available from');
			$labels['AvailableTo'] = _t('Poll.AVAILABLETO', 'Available to');
			$labels['ColumnAvailability'] = _t('Poll.AVAILABILITY', 'Availability');
			$labels['SortOrder'] = _t('Poll.SORTORDER', 'Sort order');
			$labels['VisibleGroups.ID'] = _t('Group.SINGULARNAME', 'Group');
			$labels['VisibleMembers.ID'] = _t('Member.SINGULARNAME', 'Member');

			if($includerelations) {
				$labels['VisibleGroups'] = _t('Group.PLURALNAME', 'Groups');
				$labels['VisibleMembers'] = _t('Member.PLURALNAME', 'Members');
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

	public function onBeforeDelete() {
		parent::onBeforeDelete();

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

			$fields->addFieldToTab('Root.Main',$fields->dataFieldByName('Title'));
			$fields->addFieldToTab('Root.Main',$fields->dataFieldByName('Options'));

			$Status = $fields->dataFieldByName('Status');
			$Active = $fields->dataFieldByName('Active');
			$AllowResults = $fields->dataFieldByName('AllowResults');
			$AvailableFrom = $fields->dataFieldByName('AvailableFrom')->setTitle(null);
			$AvailableTo = $fields->dataFieldByName('AvailableTo')->setTitle(null);

			$fields->removeByName('Status');
			$fields->removeByName('Active');
			$fields->removeByName('AllowResults');
			$fields->removeByName('AvailableFrom');
			$fields->removeByName('AvailableTo');

			$fields->addFieldToTab('Root.Main',FieldGroup::create(
				$AvailableFrom,$AvailableTo
			)->setTitle(_t('Poll.AVAILABILITY', 'Availability')));

			$fields->addFieldToTab('Root.Main',FieldGroup::create(
				$Status,$Active,$AllowResults
			)->setTitle(_t('Poll.CONFIGURATION', 'Configuration')));


			$fields->addFieldToTab('Root.Visibility',
				ListboxField::create('VisibleGroups',$this->fieldLabel('VisibleGroups'))
					->setMultiple(true)
					->setSource(Group::get()->map()->toArray())
					->setAttribute('data-placeholder', _t('SiteTree.GroupPlaceholder', 'Click to select group'))
					->setDescription(_t('Poll.VISIBLEGROUPSDESCRIPTION', 'Groups for whom are polls visible.')));
			$fields->addFieldToTab('Root.Visibility',
				ListboxField::create('VisibleMembers',$this->fieldLabel('VisibleMembers'))
					->setMultiple(true)
					->setSource(Member::get()->map()->toArray())
					->setAttribute('data-placeholder', _t('Poll.MemberPlaceholder', 'Click to select member'))
					->setDescription(_t('Poll.VISIBLEMEMBERSDESCRIPTION', 'Members for whom are polls visible.')));
			$fields->addFieldToTab('Root.Visibility',new ReadonlyField('Note',_t('Poll.NOTE', 'Note'),_t('Poll.NOTEDESCRIPTION', 'If there is none selected, polls will be visible for everyone.')));

			$fields->fieldByName('Root.Visibility')->setTitle(_t('Poll.TABVISIBILITY', 'Visibility'));

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

	private function isPollVisible() {
		return $this->exists() && $this->Status;
	}

	private function isMemberInVisibleRelationsOrTheyAreEmpty($member) {
		return ((($visibleGroups = $this->VisibleGroups()) && ($visibleMembers = $this->VisibleMembers()) && !$visibleGroups->exists() && !$visibleMembers->exists()) || ($visibleGroups->exists() && $member->inGroups($visibleGroups)) || ($visibleMembers->exists() && $visibleMembers->find('ID',$member->ID)));
	}

	public function isPollActive() {
		return ($this->Active
			&& (!$this->AvailableFrom || $this->AvailableFrom <= date('Y-m-d'))
			&& (!$this->AvailableTo || $this->AvailableTo >= date('Y-m-d')));
	}

	public function memberVoted($member) {
		return ($submission = PollSubmission::get()->filter(array('PollID'=>$this->ID, 'MemberID'=>$member->ID))->limit(1)->first()) && $submission->exists();
	}

	private function canViewPoll($member) {
		return Permission::checkMember($member, self::VIEW_PERMISSION)
		|| ($this->isPollVisible() && $this->isMemberInVisibleRelationsOrTheyAreEmpty($member) && ($this->isPollActive() || $this->memberVoted($member)));
	}

	public function canView($member = null) {
		$member = $this->getMember();

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