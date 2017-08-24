<?php

class PollSubmission extends DataObject implements PermissionProvider {

	const VIEW_PERMISSION = 'POLLSUBMISSION_VIEW';
	const DELETE_PERMISSION = 'POLLSUBMISSION_DELETE';

	private static $singular_name = "Submission";
	private static $plural_name = "Submissions";

	private static $db = array(
		'Option' => 'Varchar(255)'
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
		'Poll.Title',
		'Poll.Status',
		'Poll.Active',
		'Option',
		'Member.Name'
	);

	private static $default_sort = "PollID DESC, ID DESC";

	public function fieldLabels($includerelations = true) {
		$cacheKey = $this->class . '_' . $includerelations;

		if(!isset(self::$_cache_field_labels[$cacheKey])) {
			$labels = parent::fieldLabels($includerelations);
			$labels['Option'] = _t('PollSubmission.OPTION', 'Answer');

			$labels['Poll.Status'] = _t('PollSubmission.POLL.STATUS', 'Visible poll');
			$labels['Poll.Active'] = _t('PollSubmission.POLL.ACTIVE', 'Active poll');
			$labels['Poll.Title'] = _t('Poll.SINGULARNAME', 'Poll');
			$labels['Member.ID'] = _t('Member.SINGULARNAME', 'Member');
			$labels['Member.Name'] = _t('Member.SINGULARNAME', 'Member');

			if($includerelations) {
				$labels['Poll'] = _t('Poll.SINGULARNAME', 'Poll');
				$labels['Member'] = _t('Member.SINGULARNAME', 'Member');
			}

			self::$_cache_field_labels[$cacheKey] = $labels;
		}

		return self::$_cache_field_labels[$cacheKey];
	}

	public function getCMSFields() {
		$self =& $this;

		$this->beforeUpdateCMSFields(function ($fields) use ($self) {
			$fields->changeFieldOrder(array('PollID','Option','MemberID'));
		});

		return parent::getCMSFields();
	}

	public function getTitle() {
		return $this->Poll()->Title;
	}

	public function getName() {
		return $this->Title;
	}

	public function providePermissions() {
		return array(
			self::VIEW_PERMISSION => array(
				'name' => _t('PollSubmission.PERMISSION_VIEW', 'Read poll submission'),
				'category' => _t('Poll.PERMISSIONS_CATEGORY', 'Poll permissions')
				),

			self::DELETE_PERMISSION => array(
				'name' => _t('PollSubmission.PERMISSION_DELETE', 'Delete poll submission'),
				'category' => _t('Poll.PERMISSIONS_CATEGORY', 'Poll permissions')
				)
			);
	}

	private function isOwner($member = null) {
		return ($member || ($member = Member::currentUser())) && $member->ID == $this->MemberID;
	}

	public function canView($member = null) {
		return Permission::checkMember($member, self::VIEW_PERMISSION)
			|| $this->isOwner();
	}

	public function canEdit($member = null) {
		return false;
	}

	public function canDelete($member = null) {
		return Permission::checkMember($member, self::DELETE_PERMISSION);
	}

	public function canCreate($member = null) {
		return false;
	}
}
