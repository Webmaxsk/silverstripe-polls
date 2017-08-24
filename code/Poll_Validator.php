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

		if (isset($data['EnableMultiselect']) && $data['EnableMultiselect']
		&& isset($data['Options']) && ($currentOptions = trim($data['Options']))
		&& isset($data['MinOptionsCount']) && isset($data['MaxOptionsCount'])) {
			$minOptionsCount = $data['MinOptionsCount'];
			$maxOptionsCount = $data['MaxOptionsCount'];
			$currentOptionsCount = count(preg_split("/\r\n|\n|\r/", $currentOptions));

			if ($minOptionsCount > $maxOptionsCount
			|| $minOptionsCount == $currentOptionsCount
			|| $minOptionsCount < 0 || $maxOptionsCount < 1
			|| $maxOptionsCount > $currentOptionsCount) {
				$this->validationError(
					'MinOptionsCount',
					_t('Poll_Validator.INCORRECTOPTIONSCOUNTRANGE', 'Incorrect range of choice for minimum and maximum options count'),
					'required'
				);

				$my_validator = false;
			}
		}

		$parent_validator = parent::php($data);

		return $my_validator && $parent_validator;
	}
}