<?php

class Poll_Controller extends Page_Controller {

	private static $allowed_actions = array('view','PollForm');

	private static $url_handlers = array(
		'PollForm/$ID' => 'PollForm'
	);

	protected
		$Poll = null;

	public function __construct($Poll = null) {
		if ($Poll)
			$this->Poll = $Poll;

		parent::__construct();
	}

	public function init() {
		parent::init();

		if (!Member::currentUserID())
			return Security::permissionFailure($this);
		elseif ($ID = $this->request->param('ID')) {
			if (!is_numeric($ID) || !($poll = Poll::get()->filter(array('ID'=>$ID))->limit(1)->first()))
				return $this->httpError(404);
			elseif (!$poll->canView())
				return Security::permissionFailure($this);
			else {
				if (class_exists('BetterButton')) {
					if ($this->request->getVar('stage') == "Stage")
						$this->redirect(str_replace("Stage", "Live", $_SERVER['REQUEST_URI']));
					elseif (Versioned::current_stage()=="Stage")
						$this->redirect($_SERVER['REQUEST_URI']."?stage=Live");
				}

				$this->Poll = $poll;

				$this->Title = _t('Poll_Controller.POLLTITLE', 'Poll');
				$this->MenuTitle = _t('Poll_Controller.POLLTITLE', 'Poll');
				$this->MetaTitle = _t('Poll_Controller.POLLTITLE', 'Poll');

				Requirements::add_i18n_javascript(POLLS_DIR."/javascript/lang");
				Requirements::javascript(POLLS_DIR."/javascript/ajax_poll.js");
			}
		}
		else {
			$this->Title = _t('Poll_Controller.POLLSTITLE', 'Polls');
			$this->MenuTitle = _t('Poll_Controller.POLLSTITLE', 'Polls');
			$this->MetaTitle = _t('Poll_Controller.POLLSTITLE', 'Polls');

			Requirements::add_i18n_javascript(POLLS_DIR."/javascript/lang");
			Requirements::javascript(POLLS_DIR."/javascript/ajax_poll.js");
		}
	}

	public function view() {
		if ($ID = $this->request->param('ID')) {
			if ($this->request->isAjax())
				return $this->PollDetail();
			else
				return $this->renderWith(array('Polls','Page'),array('PollControllers'=>false));
		}
		else
			return $this->renderWith(array('Polls','Page'),array('PollControllers'=>$this->getPollControllers()));
	}

	public function PollForm() {
		if (!$this->Poll->isPollActive() || $this->Poll->memberVoted(Member::currentUser()))
			return false;

		$fields = $this->Poll->getFrontEndFields();

		$actions = new FieldList(
			new FormAction('doPoll', _t('Poll_Controller.VOTE', 'Vote'))
		);

		$validator = $this->Poll->getFrontEndValidator();

		$form = new Form($this, 'PollForm', $fields, $actions, $validator);
		$form->setHTMLID("Form_PollForm_".$this->Poll->ID);
		$form->addExtraClass('Form_PollForm');

		$form->setFormAction("{$this->Link('PollForm')}");

		return $form;
	}

	public function doPoll($data, $form) {
		$options = isset($data['Option']) ?
			is_array($data['Option']) ? $data['Option'] : array($data['Option'])
		:
			array("");

		foreach ($options as $option) {
			$submission = new PollSubmission();

			$submission->PollID = $this->Poll->ID;
			$submission->MemberID = Member::currentUserID();
			$submission->Option = $option;

			$submission->write();
		}

		if ($this->request->isAjax())
			return json_encode($this->view()->getValue());
		else
			return $this->redirectBack();
	}

	public function PollDetail() {
		return $this->renderWith("Poll_detail");
	}

	private function getPollControllers() {
		$controllers = new ArrayList();

		if (($items = Poll::get()) && $items->exists())
			foreach ($items as $Poll)
				if ($Poll->canView()) {
					$controller = $Poll->getController();

					$controllers->push($controller);
				}

		return $controllers;
	}

	public function Link($action = null) {
		if ($action == null)
			$action = $this->Action;

		return Controller::join_links(Director::baseURL().'polls', $action, $this->Poll && ($ID = $this->Poll->ID) ? $ID : null);
	}
}