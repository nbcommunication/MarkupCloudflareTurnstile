<?php namespace ProcessWire;

/**
 * Markup Cloudflare Turnstile Configuration
 *
 */

class MarkupCloudflareTurnstileConfig extends ModuleConfig {

	/**
	 * Returns default values for module variables
	 *
	 * @return array
	 *
	 */
	public function getDefaults() {
		return [
			'siteKey' => '',
			'secretKey' => '',
		];
	}

	/**
	 * Returns inputs for module configuration
	 *
	 * @return InputfieldWrapper
	 *
	 */
	public function getInputfields() {

		$inputfields = parent::getInputfields();

		$inputfields->add([
			'type' => 'text',
			'name' => 'siteKey',
			'label' => $this->_('Site Key'),
			'required' => true,
			'columnWidth' => 50,
			'icon' => 'key',
		]);

		$inputfields->add([
			'type' => 'text',
			'name' => 'secretKey',
			'label' => $this->_('Secret Key'),
			'required' => true,
			'columnWidth' => 50,
			'icon' => 'eye-slash',
			'attr' => [
				'type' => 'password',
				'autocomplete' => 'off',
			],
		]);

		return $inputfields;
	}
}
