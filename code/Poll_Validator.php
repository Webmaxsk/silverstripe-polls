<?php

class Poll_Validator extends RequiredFields {

	public function php($data) {
		$my_validator = true;
		$parent_validator = true;

		if (isset($data['AvailableFrom']) && isset($data['AvailableTo'])
		&& ($availableFrom = $data['AvailableFrom']) && ($availableTo = $data['AvailableTo'])
		&& $availableFrom > $availableTo) {
			$this->validationError(
				'AvailableFrom',
				_t('Poll_Validator.INCORRECTDATERANGE', 'Incorrect date range'),
				'required'
			);

			$my_validator = false;
		}

		$parent_validator = parent::php($data);

		return $my_validator && $parent_validator;
	}
}