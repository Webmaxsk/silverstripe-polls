<?php

class Poll_FrontEndValidator extends RequiredFields {

	public function php($data) {
		$my_validator = true;
		$parent_validator = true;

		if (($ID = $this->form->controller->request->param('ID')) && ($poll = DataObject::get_by_id('Poll', $ID)) && $poll->exists() && $poll->EnableMultiselect) {
			$currentOptionsCount = isset($data['Option']) && ($currentOptions = $data['Option']) ? count(explode(',', $currentOptions)) : 0;

			if ($currentOptionsCount < $poll->MinOptionsCount
			|| $currentOptionsCount > $poll->MaxOptionsCount) {
				$this->validationError(
					'Option',
					_t('Poll_FrontEndValidator.SELECTCORRECTOPTIONSCOUNT','Please select at least {minCount}, but no more than {maxCount} options.', array('minCount' => $poll->MinOptionsCount, 'maxCount' => $poll->MaxOptionsCount)),
					'required'
				);

				$my_validator = false;
			}
		}


		$parent_validator = parent::php($data);

		return $my_validator && $parent_validator;
	}
}