<?php

class ActivePollPage extends Page {

	private static $description = "Displays all active polls (widgets)";
	private static $singular_name = "Active polls page";
	private static $plural_name = "Active polls pages";

	private static $allowed_children = false;

	public function canCreate($member = null) {
		return class_exists('Widget') && parent::canCreate($member);
	}
}

class ActivePollPage_Controller extends Page_Controller {

	public function Widgets() {
		if (!class_exists('Widget'))
			return;

		$widgetcontrollers = new ArrayList();

		$widgetItems = PollWidget::get()->filter(array("Enabled"=>1,"Poll.Active"=>1))->filterByCallback(function($item, $list) {
			return $item->Poll()->isPollActive();
		});

		$pollWidgetsIDs = array();

		if ($widgetItems->exists()) {
			foreach ($widgetItems as $widget) {
				$widgetPollID = $widget->PollID;

				if (!array_key_exists($widgetPollID, $pollWidgetsIDs)) {
					if ($widget->canView()) {
						$controller = $widget->getController();
						$controller->init();

						$widgetcontrollers->push($controller);
					}

					$pollWidgetsIDs[$widgetPollID] = true;
				}
			}
		}

		return $widgetcontrollers;
	}
}