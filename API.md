# MarkupCloudflareTurnstile API Reference

This document is a reference for agents/developers writing code that uses
the MarkupCloudflareTurnstile module. It describes the module's public API
only - for installation and configuration instructions see README.md.

## What it is

MarkupCloudflareTurnstile is a `WireData` module implementing `Module` and
`ConfigurableModule` that renders Cloudflare's Turnstile CAPTCHA widget and
verifies the visitor's response server-side. It is designed as a drop-in
alternative to ProcessWire's `MarkupGoogleRecaptcha` module, and will
automatically infer some rendering attributes from MarkupGoogleRecaptcha's
own settings if that module is also installed.

## Getting an instance

```php
$captcha = $modules->get('MarkupCloudflareTurnstile');
```

## Configuration

These are set via the module's config screen (Modules > MarkupCloudflareTurnstile)
and read as properties on the module:

- `siteKey` (string, required) - Cloudflare Turnstile Site Key.
- `secretKey` (string, required) - Cloudflare Turnstile Secret Key, used for
  server-side verification. Kept out of rendered markup.

Both keys are created in your Cloudflare account - see README.md for
details.

## Methods

### render($attrs = [], ?InputfieldForm $form = null)

Renders the Turnstile widget markup, a `<div class="cf-turnstile" ...>`
element that Cloudflare's client-side script initialises.

- `$attrs` (array) - optional HTML/`data-*` attributes to add to the
  rendered `<div>`, e.g. `data-theme`, `data-size`, `data-language`,
  `data-callback`, etc. See
  [client-side rendering options](https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/).
  `data-sitekey` is always set automatically from the module's configured
  `siteKey` and cannot be overridden. If no `class` attribute is given,
  `cf-turnstile` is used by default. All attribute keys/values are escaped
  with `$sanitizer->entities()`.
- `$form` (`InputfieldForm|null`) - if given, the widget is added to the
  form as a `markup` Inputfield and the `$form` object itself is returned
  (rather than a markup string), allowing the widget to be chained into
  form-building code.

If MarkupGoogleRecaptcha is installed, some of its configured settings
(`data_theme`, `data_size`, `data_index`) are used to infer corresponding
Turnstile attributes (`data-theme`, `data-size`, `data-tabindex`) unless
explicitly overridden via `$attrs`.

```php
// Basic usage
echo $captcha->render();

// With custom attributes
echo $captcha->render([
	'class' => 'cf-turnstile your-custom-class', // or 'cf-turnstile-explicit'
	'data-theme' => 'dark',
	'data-size' => 'compact',
	'data-language' => 'de',
]);

// Adding directly to a form
$form = $captcha->render([], $form);
```

### getScript(array $params = [])

Returns the `<script>` tag needed to load Cloudflare's Turnstile client API,
with `async defer` attributes as recommended by Cloudflare.

- `$params` (array) - optional query string parameters appended to the
  script URL (e.g. `['render' => 'explicit', 'onload' => 'onloadCallback']`
  for explicit rendering).

```php
echo $captcha->getScript();
```

### verifyResponse()

Performs server-side verification of the visitor's CAPTCHA response via
Cloudflare's `siteverify` API. Reads the `cf-turnstile-response` POST value
automatically (sanitized as text) - there is no need to pass it manually.

Also sends the visitor's IP address (`$session->getIP()`) as `remoteip` when
available, for stronger validation.

Returns `bool`: `true` if verification succeeded, `false` if the response
was missing, the verification request itself failed (e.g. network error -
logged via `Notice::logOnly`), or Cloudflare reported the response as
invalid.

```php
if($input->post('cf-turnstile-response')) {
	if($captcha->verifyResponse()) {
		// CAPTCHA passed, proceed with form processing
	} else {
		// CAPTCHA failed, handle the error
	}
}
```

## Logging

Verification request failures (e.g. network errors reaching Cloudflare) are
logged via `$this->error(..., Notice::logOnly)` rather than thrown, so check
ProcessWire's logs if `verifyResponse()` unexpectedly returns `false`.