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

If you are using the Contao Standard-Edition, don't forget to register the App in the app/AppBundle.php

### Step 2: Add a real cronjob
The import system gets triggered by the internal 'minutely cron job'. Disable
the periodic command scheduler to make sure the import only gets triggered by a 
real cron job with the ``_contao/cron`` route and not during regular site
visits.
 
Use the Bundle
--------------

#### Basic usage
Create one or more Facebook nodes in the backend. To make your application able
to connect to Facebook's API, you need to create a Facebook app and an access
token.   

For the latter open the [Graph API Explorer][Graph API Explorer], select your
app in the app drop down menu and then *Request app access token* from the
drop down menu below.

To limit the maximum amount of API calls you can set a minimum cache time for
each node. Default is 250 seconds. 

If you enable auto import, you're good to go. To manually import posts and
events, see what got imported or hide certain elements head to the respective
posts / events child views of your Facebook node.


[Graph API Explorer]: https://developers.facebook.com/tools/explorer/
