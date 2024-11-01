=== Simple Events Calendar ===
Contributors: jgrietveld
Donate link: http://www.studiostacks.com/plugins/simple-events-calendar/
Tags: event, events, calendar, organizer, planner
Requires at least: 4.0
Tested up to: 5.8
Stable tag: 1.4.1

Simple and lightweight events calendar to show events on posts/pages, manage them centrally, hCalendar compliant and stays up to date.

== Description ==

###What does the plugin do?

The Simple Events Calendar is a WordPress plugin developed to allow WordPress site owners to easily add upcoming events to their posts or pages. Every event is published and microformated with the hCalendar standard, thus fully semantic.

Simple Events Calendar adds a new option to the admin Dashboard where users can add new events or delete existing ones. Events can contain URLs to get more info and a separate link for the location. The latter could for example be used at add the link to a Google Map.

Events can be published with a Twitter link to allow your visitors to easily tweet about your event.

###Adding events to your page or post

You can easily place your events in any post or page by adding the following text (shortcode): **[events]**

###Using labels to add specific events

In some cases it might be better to only display certain events. Let's say you're organizing workshops on online marketing and also on UX. If you create a page to explain what people can expect when they attend your UX workshop, you may want to include the dates when this event takes place. But it wouldn't make any sense to display the dates of the online marketing workshop.

To deal with this issue, you can add labels to your events. By calling a label, only the events with that label are displayed. So if we give our UX workshop events the label **UX**, we can display only the UX workshops like so:

**[events label=ux]**

###Upcoming, expired or both

By default only upcoming events will be displayed. If you wish to display both upcoming and expired events you can do so by adding the age element.

**[events age=all]** will display every event stored.

If you wish to display your passed events simply add age=expired:

**[events age=expired]**

###Limit the numer of events displayed

By default all events matching your conditions will be displayed. But what if you only want to display the next 3 upcoming events out of a list of 20? Simply add a limit attribute:

**[events limit=3]**

###Only display the dates of the event, or just the start time

**[events time=no]** will display your events without the time.

**[events time=start]** will display your events with just a starting time.

###Do not show the Twitter button

**[events tweet=no]** will show your events without the Twitter button.

###Label, age, limit, etc. can be used together

**[events label=ux age=all]** would display all upcoming and passed events that have the ux label.


== Installation ==

1. Upload the folder `simple-events` with all its contents to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place the shortcode `[events]` on your posts or pages where you want your events to appear

== Frequently Asked Questions ==

= How do I display only my upcoming events in a post? =

Simply add `[events]` in your post on the exact location where you want your events to appear.

= How can I only display the events that took place in the past? =

Simply add age=expired to the short code, so `[events age=expired]` will do the trick

= Can't I just display all my passed AND upcoming events together? =

Sure! Just add the following to your page or post: `[events age=all]`

= I give workshops on several topics. Can I just show the workshops of one specific topic? =

Yes, you can do this by adding labels to your events. If you give a Photography workshop you could for example give this event the label `photo`.

On the page where you want to show you photography workshops simply add `[events label=photo]`.

= Can I edit a previously stored event? =

Yes you can! Next to the title of your stored events you can find a button called **Edit**. Click it, change the event and press update. That's it!

= I only want to display the next 2 events and not all of them. Is that possible? =

Yes. Simply add the **limit** attribute to **[events]** to set a miximum to the number of events that will be displayed. To limit it to just 2 events, enter this: **[events limit=2]**

= What if I do not want to show the Twitter button with my events? =

Just add tweet=no to the short code, like so `[events tweet=no]`

= Can't I not just disable the twitter functionality in the plugin completely? =

Yes, that's possible through the `Advanced Settings` menu. If you click on the Advanced Settings link you can find a checkbox to disable Twitter. Click save and you're done.

= I want to style the output myself. Can I use my own CSS? =

Yes! Just create a css file and name it simple-events-calendar.css and store it in your theme's folder (/wp-content/themes/your-active-theme). Then go to the Advanced Settings menu of the Simple Events Calendar and change the CSS settings. 

= I see you're using military time. Can I change that to the 12 hour clock? =

Yes. If you open the Advanced Settings, check the box at the top and click safe, your events will be displayed with the 12 hour clock.

= The links of in the events are all opening in the same window and are thus leaving my site, can I stop them? =

You sure can! In the Advanced Settings menu you can set your preferences for how you want links to be opened.

= I am my site's administrator, but I want editors and subscribers to be able to manage events too =

In the Advanced Settings menu you can set the minimum authorization level required to manage events.

= Using the hCalendar microformat is fantastic, but times are all in the Western European Timezone. Can I change this? =

Yes, via the Advanced Settings menu you can set your own timezone.

== Screenshots ==

1. The admin panel for entering new events
2. How upcoming events and expired events look on the admin panel
3. This is how an event looks on your website
4. The Advanced Settings menu gives you more control
5. A reference guide on using the shortcode inside the admin panel

== Changelog ==

= 1.4.1 =
* Critical fix

= 1.4.0 =
* Years continue till 2040
* Tabbed admin interface
* Small visual updates

= 1.3.5 =
* Urls now case sensitive

= 1.3.4 =
* Where and More information only appear and are only clickable when it makes sense
* Some small fixes

= 1.3.3 =
* Added the missing icons (apologies)

= 1.3.2 =
* Replaced field labels with icons
* Set time in 5 minute intervals instead of 15 minute intervals
* Added support for https secure urls

= 1.3.1 =
* DB field size increase
* Some small fixes

= 1.2.4 =
* Fix month issue causing certain months to disappear temporarily

= 1.2.3 =
* Fix missing end time for events that start and end on the same day
* Fix end time and date reset when editing existing events

= 1.2.2 =
* Fix date error on Windows system - strftime()
* 12 hour am/pm display in Current Events
* Full display of the event URL

= 1.2.1 =
* Fix authorization level for managing events not appearing for lower levels
* Only admin can now set the authorization level

= 1.2 =
* End date is back!
* Advanced Settings menu added
* Use 12 hour time notation in stead of 24 hour military time
* Set your own timezone
* Use your own CSS (optional of course)
* Twitter button added to events
* Twitter button can be disabled
* Set your preferences for opening links
* Set the authorization level for managing events
* If end year is same as start, no year is displayed in end date
* If end day is same as start day, the end date is not displayed
* Time can be turned off so only dates are shown
* Only show start time

= 1.1 =
* Fixed error message when no events are stored

= 1.0 =
* Edit previously stored events
* Output localized based on WordPress language settings
* Limit the number of events displayed by adding limit=x to the shortcode
* Discription field now shows a counter telling you how many characters are left
* End date of an event removed

= 0.1.5 =
* Shortcode only returned last event. Now all events are displayed again.

= 0.1.4 =
* Shortcode output was printed in stead of returned. In some cased this caused the output to appear on unpredictable locations.

= 0.1.3 =
* Fixed CSS issue in admin panel
* Only the event info fields that are entered will be retrieved
* hCalendar time fix

= 0.1.2 =
* Fixed issues with broken headers

= 0.1.1 =
* Fixed issues with the version number

= 0.1 =
* First release of the plugin
