contao-facebook-import
======================
This bundle enables to use Facebook's events and posts as native elements in
Contao Open Source CMS. The following things are available:

* Automatic and on-demand import of Facebook events and posts
* Image scraper that automatically downloads high resolution
  images<sup>*)</sup> of posts and events into Contao's filesystem 
* Content Elements to display events and posts
* Support for multiple Facebook pages simultaneously  
 
 <sup>*)</sup> Note that the largest size of images being used will be smaller
 or equal the maximum size set in the Contao settings. (The system tries to
 download the biggest possible file with this constraint.)  
    
    
    
Installation
------------

#### Step 1: Download the Bundle  

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require mvo/contao-facebook-import
```

#### Step 2: Enable the Bundle

**Skip this point if you are using a *Managed Edition* of Contao.**

Enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new \Mvo\ContaoFacebookImport\MvoContaoFacebookImportBundle(),
        );

        // ...
    }

    // ...
}
```
 
Use the Bundle
--------------

#### Setup & basic usage
Create one or more Facebook nodes in the backend. To make your
application able to connect to Facebook's API, you need to create a
at least one Facebook app and access token.

The latter you can generate doing the following steps:
* Open the [Graph API Explorer][Graph API Explorer]
* Select your app from the `App` dropdown.
* Select your page from the `Get Token` dropdown (under *Page Access
  Tokens*) - you'll therefore have to allow the app to access your page.
* Paste the generated token into the facebook node's field in the
  backend.
* Save the record - the system will then try to generate a token that
  won't expire anymore and replace it with the one you entered.

To test your token and see if it won't expire, you can enter it into the
[Access Token Debugger][Access Token Debugger] and look for the expiry
date.

To limit the maximum amount of API calls you can set a minimum cache time for
each node. Default is 250 seconds. 

If you enable auto import, you're good to go. To manually import posts and
events, see what got imported or hide certain elements head to the respective
posts / events child views of your Facebook node.


#### Make sure the contao cron job is set up
The import system gets triggered by the internal 'minutely cron job'. Disable
the periodic command scheduler to make sure the import only gets triggered by a 
real cron job with the ``_contao/cron`` route and not during regular site
visits.

[Graph API Explorer]: https://developers.facebook.com/tools/explorer/
[Access Token Debugger]: https://developers.facebook.com/tools/debug/accesstoken