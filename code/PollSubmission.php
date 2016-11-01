<?php

class PollSubmission extends DataObject {

	private static $singular_name = "Submission";
	private static $plural_name = "Submissions";

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

	public function fieldLabels($includerelations = true) {
		$cacheKey = $this->class . '_' . $includerelations;

		if(!isset(self::$_cache_field_labels[$cacheKey])) {
			$labels = parent::fieldLabels($includerelations);
			$labels['Option'] = _t('PollSubmission.OPTION', 'Answer');

			$labels['Poll.Status'] = _t('PollSubmission.Poll.STATUS', 'Visible poll');
			$labels['Poll.Active'] = _t('PollSubmission.Poll.ACTIVE', 'Active poll');
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

	public function canCreate($member = null) {
		return false;
	}

	public function canEdit($member = null) {
		return false;
	}
}