<?php

class ActivePollPage extends Page {

	private static $allow_images = false;
	private static $allow_documents = false;

	private static $description = 'Zobrazí všetky aktívne ankety (widgety).';
	private static $singular_name = "Aktívna anketa";
	private static $plural_name = "Aktívne ankety";

	private static $allowed_children = false;
}

class ActivePollPage_Controller extends Page_Controller {

	public function Widgets() {
		$widgetcontrollers = new ArrayList();

		$widgetItems = PollWidget::get()->filter(array("Enabled"=>1,"Poll.Active"=>1));

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