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

#### Version 3 ####
This is version 3 of the bundle - to see a list of changes and information on
how to migrate from older versions please refer to the [changelog][CHANGELOG.MD].

    
Setup
-----

#### Step 1: Download (& register) the Bundle

Require the bundle as a dependency and register the `MvoContaoFacebookImportBundle`
in your `AppKernel` (automatically done in the Contao Managed Edition):

```console
$ composer require mvo/contao-facebook-import
```

Update your database.


#### Step 2: Configure (optional)

You can edit the configuration by setting the following parameters in your
`config.yml` - the following values are the defaults:

```yaml
mvo_contao_facebook_import:
    request_limit_per_node: 150
    request_window_per_node: 3600
    max_execution_time: 16
    trigger_type: 'internal'
```

- **request_limit_per_node** defines the maximum amount of requests that
  will be issued in a certain time (request window) to prevent exceeding
  the API limits - this limit is calculated individually for every defined
  Facebook node.

- **request_window_per_node** defines the length of the request window in
  seconds. The evaluation happens on a scrolling window basis.

- **max_execution_time** defines the maximum execution time in seconds that
  the synchronization process is allowed to run. Setting this value to a
  bigger value (that your server is still capable to do) will offer a quicker
  image synchronization.

- **trigger_type** defines how the synchronization should be triggered. By
  default (`internal`) the Contao cron system is used but you can set it to
  `route`. The process can than be started by calling the `/_sync_fb_nodes`
  route. If you want to limit the synchronization a specific node you can
  pass the node id as an optional get parameter.

  *Example to trigger synchronizing node 5:*
    ```
    wget https://mydomain.org/_sync_fb_nodes?node=5
    ```


> Its highly recommended to use a distinct cron job and use the route.
  If you use the internal variant make sure you have disabled the
  periodic command scheduler and you are still triggering the
  ``_contao/cron`` route with a cron job. Otherwise long latency might
  occur when the synchronizer is running.


#### Step 3: Use the Bundle

**Facebook GraphAPI**

Create one or more Facebook nodes in the backend. To make your
application able to connect to Facebook's GraphAPI, you need to create a
at least one Facebook app and access token.

The latter you can generate doing the following steps:
* Open the [Graph API Explorer][Graph API Explorer]
* Select your app from the `App` dropdown.
* Select your page from the `Get Token` dropdown (under *Page Access
  Tokens*) - you'll therefore have to allow the app to access your page.
* Paste the generated token into the facebook node's field in the
  backend.
* Check the belongside checkbox (convert token) and save the record - the
  system will then try to generate a token that won't expire anymore and
  replace it with the one you entered.

To test your token and see if it won't expire, you can enter it into the
[Access Token Debugger][Access Token Debugger] and look for the expiry
date.


**Synchronization**

If you enable auto synchronization, you're good to go. To manually import
posts and events, see what got imported or hide certain elements head to the
respective posts / events child views of your Facebook node.


**Frontend**

To display the data in the frontend use the `Facebook Post List` and
`Facebook Event List` content elements.


[Graph API Explorer]: https://developers.facebook.com/tools/explorer/
[Access Token Debugger]: https://developers.facebook.com/tools/debug/accesstoken