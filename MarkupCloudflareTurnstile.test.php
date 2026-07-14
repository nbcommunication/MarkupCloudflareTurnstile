<?php namespace ProcessWire;

/**
 * WireTest for MarkupCloudflareTurnstile
 *
 * Exercises the module's public API: render() (both the array-attrs and
 * InputfieldForm forms), getScript(), and the "no response submitted" fail-closed
 * path of verifyResponse().
 *
 * Deliberately NOT covered:
 * - verifyResponse()'s live HTTP call to the Cloudflare siteverify endpoint, since
 *   that requires a real Turnstile response token and network access. Treat that
 *   as a manual/integration test - see API.md.
 * - The MarkupGoogleRecaptcha-inference branch of render(), since that module is
 *   not installed on this site. If MarkupGoogleRecaptcha is installed, its
 *   data-theme/data-size/data-tabindex settings are merged into the rendered
 *   attributes - this can be tested manually with that module active.
 *
 */
class WireTest_MarkupCloudflareTurnstile extends WireTest {

	/**
	 * Only run if the module is actually installed
	 *
	 * @return bool
	 *
	 */
	public function allow() {
		return $this->wire()->modules->isInstalled('MarkupCloudflareTurnstile');
	}

	/**
	 * Get a fresh MarkupCloudflareTurnstile instance
	 *
	 * The module is non-singular, so modules->get() constructs a new instance
	 * every time, keeping each test section isolated from the others.
	 *
	 * @return MarkupCloudflareTurnstile
	 *
	 */
	protected function newTurnstile() {
		return $this->wire()->modules->get('MarkupCloudflareTurnstile');
	}

	public function execute() {
		$this->testRenderDefaults();
		$this->testRenderEscapesAttrs();
		$this->testRenderCustomAttrsAndClassOverride();
		$this->testRenderWithFormOnly();
		$this->testRenderWithAttrsAndForm();
		$this->testGetScript();
		$this->testVerifyResponseNoInput();
	}

	protected function testRenderDefaults() {

		$turnstile = $this->newTurnstile();
		$out = $turnstile->render();

		$this->check('render() outputs a div element', true, strpos($out, '<div') === 0, '===');
		$this->check('render() includes default cf-turnstile class', true, strpos($out, 'class="cf-turnstile"') !== false);
		$this->check(
			'render() includes data-sitekey from module config',
			true,
			strpos($out, 'data-sitekey="' . $turnstile->siteKey . '"') !== false
		);
	}

	protected function testRenderEscapesAttrs() {

		$turnstile = $this->newTurnstile();

		// Malicious attribute key/value should be entity-encoded, not output raw
		$out = $turnstile->render([
			'data-test' => '"><script>alert(1)</script>',
		]);

		$this->check('render() does not output an unescaped script tag', false, strpos($out, '<script>alert(1)</script>') !== false);
		$this->check('render() entity-encodes double quotes in attribute values', true, strpos($out, '&quot;') !== false);
	}

	protected function testRenderCustomAttrsAndClassOverride() {

		$turnstile = $this->newTurnstile();

		// Custom attribute is preserved
		$out = $turnstile->render(['data-callback' => 'onTurnstileSuccess']);
		$this->check('render() preserves custom attributes', true, strpos($out, 'data-callback="onTurnstileSuccess"') !== false);

		// Custom class overrides the default, and default isn't also present
		$out = $turnstile->render(['class' => 'my-turnstile']);
		$this->check('render() custom class overrides default', true, strpos($out, 'class="my-turnstile"') !== false);
		$this->check('render() does not duplicate class attribute when overridden', false, strpos($out, 'cf-turnstile') !== false);
	}

	protected function testRenderWithFormOnly() {

		$turnstile = $this->newTurnstile();
		$form = $this->wire()->modules->get('InputfieldForm');

		$result = $turnstile->render($form);

		$this->check('render(InputfieldForm) returns the same form instance', true, $result === $form);
		$this->check(
			'render(InputfieldForm) adds the cf-turnstile field to the form',
			true,
			$form->get('cf-turnstile') !== null
		);
	}

	protected function testRenderWithAttrsAndForm() {

		$turnstile = $this->newTurnstile();
		$form = $this->wire()->modules->get('InputfieldForm');

		$result = $turnstile->render(['data-theme' => 'dark'], $form);

		$this->check('render($attrs, $form) returns the form instance', true, $result === $form);
		$field = $form->get('cf-turnstile');
		$this->check('render($attrs, $form) adds field with data-theme attribute', true, $field !== null && strpos($field->value, 'data-theme="dark"') !== false);
	}

	protected function testGetScript() {

		$turnstile = $this->newTurnstile();

		$script = $turnstile->getScript();
		$this->check(
			'getScript() includes the Turnstile API URL',
			true,
			strpos($script, 'https://challenges.cloudflare.com/turnstile/v0/api.js') !== false
		);
		$this->check('getScript() includes async attribute', true, strpos($script, 'async') !== false);
		$this->check('getScript() includes defer attribute', true, strpos($script, 'defer') !== false);

		$script = $turnstile->getScript(['onload' => 'onloadCallback']);
		$this->check(
			'getScript($params) appends query string built from params',
			true,
			strpos($script, 'api.js?onload=onloadCallback') !== false
		);
	}

	protected function testVerifyResponseNoInput() {

		$turnstile = $this->newTurnstile();

		// No cf-turnstile-response POST value present - should fail closed
		// without attempting any network request.
		$this->check('verifyResponse() returns false when no response token submitted', false, $turnstile->verifyResponse());
	}
}