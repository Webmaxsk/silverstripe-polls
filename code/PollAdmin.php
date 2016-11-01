<?php

class PollAdmin extends ModelAdmin {

	private static $managed_models = array('Poll','PollSubmission');
	private static $url_segment = 'polls';
	private static $menu_title = 'Polls';
	private static $menu_icon = 'silverstripe-polls/images/poll.png';

	public function getEditForm($id = null, $fields = null) {
		$form = parent::getEditForm($id, $fields);

		if (($gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) && $gridField->is_a('GridField') && $this->sanitiseClassName($this->modelClass)=='Poll' && class_exists('GridFieldSortableRows'))
			$gridField->getConfig()->addComponent(new GridFieldSortableRows('SortOrder'));

		return $form;
	}
}