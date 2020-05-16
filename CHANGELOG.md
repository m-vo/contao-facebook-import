Changelog
=========

v3.2.0
------
- migrate to graph API version 7.0 [#26]
- fix some scraper issues
- improve logging

v3.1.1
------
- fix image factory path [#20] (@rabauss)
- add missing album type, [#17] (@rabauss)

v3.1.0
------
- raise graph API version [#15]
- prevent service inlining
- improve backend rendering
- fix synchronizer bug that prevented updates
- raise phpstam level

v3.0.4
------
- fix: enforce boolean output in DCAs for strict SQL modes (#12)
- improvement: allow null to be returned in getPageNodes()
  \+ harden synchronizer logic
- improvement: use `version_compare` to compare bundle versions

v3.0.3
------
- fix: request quota was not accounted
- fix: allow request quota log to be nullable (#9)
- small fix in english language file

v3.0.2
------
- fix: make services public that are used in Contao callbacks

v3.0.1
------
- fixed regex to replace plain urls with links in the frontend
- removed unnecessary formatting of ticket urls

v3.0.1
------
- fixed the regex to replace plain urls with links when outputting

v3.0.0
------
Version 3 is a rewrite of most of the synchronization logic and features
lots of overall improvements and bug fixes.

**New features & fixes**
 - Technical
    - Import uses paginated requests (allows any amount of elements)
    - Chunked asynchronous image import
    - Execution time controlled flow
    - Better control over FB API limit
    - Single pixel images won't be scraped anymore (Facebook for instance
      responds with those if an image isn't available anymore in the
      timeline).
    - The Access Token generator for long lived tokens can now be triggered
      explicitly and won't run every time a node record is saved.
 - Content Management
    - A post's *object type* is now stored as well and can be accessed from
      within the template (e.g. *link* / *status* / *photo*).
    - Same goes for the link attribute (that for instance contains the
      target url for *link* or *video* posts).
    - Posts can now be filtered by object type.
    - The number of shown events can now be limited.
    - Similarly the ticket url is now available for events.
 - User Interface
    - Improved rendering in the backend (now includes preview images,
      post types and more content)
    - Allow removing multiple posts/events at once (to be re-synced).

**Changed behaviour**
 - Default value for imported posts is now 100 (was 15)
 - There is no setting for the minimum cache time anymore - the API
   limits are now controlled by the number of requests. There are
   config parameters (see [docs](README.md) available if you need
   to fine tune this.

**Template changes**
 - The `even` and `odd` css classes in the Facebook post and event
   lists have been removed. Use the `:nth-child(2n)` and
   `:nth-child(2n+1)` css selectors instead.
 - The template variable `$post['message']` now contains the full-length
    message. Also, `$post['headline']` has been dropped. Use
   `$post['getExcerpt'](_words [, _offset])` instead.

**Dropped features**
 - Event insert tags<sup>*)</sup>
 - Import events into the Contao calendar<sup>*)</sup>

<sup>*)</sup> This feature unfortunately turned out to be a bad idea
(at least the way it was implemented). The lack of synchronization
between the 'native' Contao events and Facebook events was tried to
be circumvented by using insert tags. Besides others this lead to
unresolved entries as soon as the Facebook events got (automatically)
removed.

The feature is likely to be implemented again (for news archives and
calendars) with a different approach. But it may take some time&hellip;


How to migrate from version 2?
------------------------------
 1. Make sure there is nothing in `tl_mvo_facebook_posts` and
 `tl_mvo_facebook_events` that you want to keep<sup>**)</sup>
 2. Delete existing scraped images if there are any<sup>**)</sup>

<sup>**)</sup> Data in these tables is temporary anyways and will get
pulled from your facebook nodes again. Please let the scraper
re-download the images as old items won't be tracked anymore (and
would just consume space).

 3. Update your requirements to `^3.0`
 4. Run the Contao Install Tool and update the database.
 5. Maybe you need to adjust your templates (see above) if you
    are using custom ones.