<?php

class PollsPage extends Page {

	private static $description = "Displays all polls";
	private static $singular_name = "Polls page";
	private static $plural_name = "Polls pages";

	private static $allowed_children = false;

	public function getPollControllers() {
		$controllers = new ArrayList();

		if (($items = Poll::get()) && $items->exists())
			foreach ($items as $Poll)
				if ($Poll->canView()) {
					$controller = $Poll->getController();

					$controllers->push($controller);
				}

		return $controllers;
	}
}

class PollsPage_Controller extends Page_Controller {

	public function init() {
		parent::init();

		if (!Member::currentUserID())
			return Security::permissionFailure($this);

		Requirements::add_i18n_javascript(POLLS_DIR."/javascript/lang");
		Requirements::javascript(POLLS_DIR."/javascript/ajax_poll.js");
	}
}