# Markup Cloudflare Turnstile
This module allows you to add Cloudflare's Turnstile CAPTCHA to your website, providing a user-friendly alternative to Google's reCAPTCHA. It is based on [MarkupGoogleRecaptcha](https://processwire.com/modules/markup-google-recaptcha/), allowing for easy switching between the two CAPTCHA systems. If MarkupGoogleRecaptcha is installed, some of its settings are used to configure Turnstile.

# Requirements
* ProcessWire >= 3
* PHP >= 8.1
* The website must by added as a website in a Cloudflare account

# Useful documentation
* [Client-side rendering](https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/)
* [Server-side validation](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/)

# Installation
1. Download the zip file from Github or clone the repository into your site/modules directory.
2. If using the zip file, extract it in your site/modules directory.
3. In your ProcessWire admin panel, navigate to Modules > Refresh, then Modules > New, then click on the Install button for this module.

# API
You must create an API key pair (Site Key and Secret Key) to use this module. Go to [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/get-started/) for instructions on how to create your API keys. Then add the Site Key and Secret Key to the module's settings in ProcessWire.

# Usage
## Client-side integration
1. Call the module : `$captcha = $modules->get('MarkupCloudflareTurnstile');`
2. Render the widget: `echo $captcha->render();`
3. Render the script tag: `echo $captcha->getScript();`

There are various configuration options listed here: https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/

## Server-side verification
To verify the CAPTCHA response on the server side, call verifyResponse(), eg:

```php
if($captcha->verifyResponse() === true) {
    // CAPTCHA passed, proceed with form processing
} else {
    // CAPTCHA failed, handle the error
}
```

# Console errors
Implementation of this module may generate console errors, probably due to this: https://developers.cloudflare.com/turnstile/troubleshooting/troubleshooting-faqs/#i-am-seeing-a-401-error-in-your-console-during-a-turnstile-security-check-is-this-a-problem. These can be safely ignored.
