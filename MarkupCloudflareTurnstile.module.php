<?php namespace ProcessWire;

/**
 * Markup Cloudflare Turnstile
 *
 * @copyright 2025 NB Communication Ltd
 * @license Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 *
 */

class MarkupCloudflareTurnstile extends WireData implements Module, ConfigurableModule {

	/**
	 * Render
	 *
	 * @param array $attrs
	 * @param InputfieldForm $form
	 * @return string
	 *
	 */
	public function render($attrs = [], ?InputfieldForm $form = null) {

		$modules = $this->wire('modules');
		$sanitizer = $this->wire('sanitizer');

		if($attrs instanceof InputfieldForm) {
			$form = $attrs;
			$attrs = [];
		}

		if(!is_array($attrs)) $attrs = [];

		$recaptchaInferredAttrs = [];
		if($modules->isInstalled('MarkupGoogleRecaptcha')) {
			// Piggy back on MarkupGoogleRecaptcha settings
			$markupGoogleRecaptcha = $modules->get('MarkupGoogleRecaptcha');
			$recaptchaInferredAttrs = [
				'data-theme' => $markupGoogleRecaptcha->data_theme ? 'dark' : 'light',
			];
			if($markupGoogleRecaptcha->data_size) {
				$recaptchaInferredAttrs['data-size'] = 'compact';
			}
			if($markupGoogleRecaptcha->data_index) {
				$recaptchaInferredAttrs['data-tabindex'] = $markupGoogleRecaptcha->data_index;
			}
		}

		$attrs = array_merge($recaptchaInferredAttrs, $attrs, [
			'data-sitekey' => $this->siteKey,
		]);

		if(!isset($attrs['class'])) {
			$attrs['class'] = 'cf-turnstile';
		}

		$attrsStr = '';
		foreach($attrs as $key => $value) {
			if(is_array($value)) $value = implode(' ', $value);
			if(is_object($value)) $value = (string) $value;
			$key = $sanitizer->entities($key);
			$attrsStr .= is_bool($value) ? " $key" : " $key=\"" . $sanitizer->entities($value) . "\"";
		}

		$out = "<div{$attrsStr}></div>";

		if($form) {
			$form->add([
				'type' => 'markup',
				'name' => 'cf-turnstile',
				'value' => $out,
			]);
			return $form;
		}

		return $out;
	}

	/**
	 * Get the Cloudflare Turnstile Client API script
	 *
	 * @param array $params
	 * @return string
	 *
	 */
	public function getScript(array $params = []) {
		$url = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
		if(count($params)) $url .= '?' . http_build_query($params);
		return "<script src=\"$url\" async defer></script>";
	}

	/**
	 * Verify the response
	 *
	 * @return bool
	 *
	 */
	public function verifyResponse() {

		$input = $this->wire('input');
		$sanitizer = $this->wire('sanitizer');

		$response = $sanitizer->text((string) $input->post('cf-turnstile-response'));
		if(!$response) return false;

		$data = [
			'secret' => $this->secretKey,
			'response' => $response,
		];

		$remoteIp = $this->wire('session')->getIP();
		if($remoteIp) $data['remoteip'] = $remoteIp;

		$http = $this->wire(new WireHttp());
		$result = $http->post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			$data,
			[
				'use' => 'curl',
			]
		);

		if($result === false) {
			$this->error('MarkupCloudflareTurnstile: ' . $http->getError(), Notice::logOnly);
			return false;
		}

		$result = json_decode($result, true);

		return is_array($result) && !empty($result['success']);
	}
}
