<?php

class ArchivedPollPage extends Page {

	private static $allow_images = false;
	private static $allow_documents = false;

	private static $description = 'Zobrazí všetky ukončené ankety (widgety).';
	private static $singular_name = "Ukončená anketa";
	private static $plural_name = "Ukončené ankety";

	private static $allowed_children = false;
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