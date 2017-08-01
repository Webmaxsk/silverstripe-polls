<?php

if (class_exists('Widget')) {

	class PollWidget extends Widget {

		private static $title = 'Poll Widget';
		private static $cmsTitle = "Poll Widget";
		private static $description = "Displays a poll.";

		private static $has_one = array(
			'Poll' => 'Poll'
		);

		public function getCMSFields() {
			$self =& $this;

			$this->beforeUpdateCMSFields(function ($fields) use ($self) {
				$fields->merge(array(
					DropdownField::create('PollID', singleton('Poll')->i18n_singular_name(), Poll::get()->map())
				));
			});

			return parent::getCMSFields();
		}

		public function WidgetHolder() {
			if ($this->canView())
				return parent::WidgetHolder();
		}

		public function canView($member = null) {
			return $this->Poll()->canView($member);
		}
	}

	class PollWidget_Controller extends WidgetController {
		public function init() {
			parent::init();

			Requirements::add_i18n_javascript(POLLS_DIR."/javascript/lang");
			Requirements::javascript(POLLS_DIR."/javascript/ajax_poll.js");
		}

		public function WidgetHolder() {
			if ($this->canView())
				return parent::WidgetHolder();
		}
	}

}