<?php

class ArchivedPollPage extends Page {

	private static $allow_images = false;
	private static $allow_documents = false;

	private static $description = "Displays all non active polls (widgets)";
	private static $singular_name = "Non active poll";
	private static $plural_name = "Non active polls";

	private static $allowed_children = false;

	public function canCreate($member = null) {
		return class_exists('Widget') && parent::canCreate($member);
	}
}

class ArchivedPollPage_Controller extends Page_Controller {

	public function Widgets() {
		$widgetcontrollers = new ArrayList();

		$widgetItems = PollWidget::get()->filter(array("Enabled"=>1,"Poll.Active"=>0));

		if ($widgetItems->exists()) {
			foreach ($widgetItems as $widget) {
				if ($widget->canView()) {
					$controller = $widget->getController();

					$widgetcontrollers->push($controller);
				}
			}
		}

		return $widgetcontrollers;
	}
}