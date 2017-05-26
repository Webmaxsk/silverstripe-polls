<?php

class PollAdmin extends ModelAdmin {

	private static $managed_models = array('Poll','PollSubmission');
	private static $url_segment = 'polls';
	private static $menu_title = 'Polls';
	private static $menu_icon = 'silverstripe-polls/images/poll.png';

	public function getList() {
		$list = parent::getList();

		$IDs = array();
		foreach ($list as $item)
			if (!$item->canView())
				$IDs[] = $item->ID;

		$list = $list->exclude('ID',$IDs);

		return $list;
	}

	public function getEditForm($id = null, $fields = null) {
		$form = parent::getEditForm($id, $fields);

		if (($gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) && $gridField->is_a('GridField') && $this->sanitiseClassName($this->modelClass)=='Poll') {
			if (class_exists('GridFieldSortableRows'))
				$gridField->getConfig()->addComponent(new GridFieldSortableRows('SortOrder'));
			elseif (class_exists('GridFieldOrderableRows'))
				$gridField->getConfig()->addComponent(new GridFieldOrderableRows('SortOrder'));
		}

		return $form;
	}
}