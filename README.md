# Commons Booking shortcodes extension

* a collection of shortcodes for usage with Wordpress plugin [Commons Booking](https://github.com/wielebenwir/commons-booking)
* php files can be included in functions.php of child theme or installed as plugin
* shortcodes can be used in pages, posts and widgets
* table sorting requires Wordpress Plugin [Table Sorter](https://de.wordpress.org/plugins/table-sorter/)

## Summary

### 1. custom-shortcodes-cb-items.php:

* `[cb_items_teaser]` - items teaser (linked thumbnails , sorted by ID asc)

* `[cb_items_teaser_cat]` - items teaser (sorted and grouped by categories), optional parameter:
    * 'cat' (id)

* `[cb_items_date]` - bookable items for 1 date (list), opt. parameters:
    * 'addDays' (checked day after today)
    * 'sort' (sort order for items, default DESC)

* `[cb_items_next_date]` - next date with bookable items (list), opt. parameters:
    * 'days' (max. checked days from today, default 30)
    * 'time' (0-24, time to switch checking from today to tomorrow, default 14)
    * 'sort' (sort order for items, default DESC)

* `[cb_items_available]` - availability of all items for the next 30 days (sortable calendar table), opt. parameter:
    * 'desc' for table description


### 2. custom-shortcodes-statistics1.php:

* `[cb_bookings_summary]` - past bookings summary for all locations (sortable table plus chart)

* `[cb_bookings_months]` - past and future bookings summary for all items per month (table and chart)

### 3. custom-shortcodes-cb-bookings-overviews.php (only for non-public pages!):

* `[cb_bookings_preview]` - coming bookings of all locations with booker's names (abbreviated)

* `[cb_bookings_overview]` - bookings overview of 1 location with booker's contact data, parameters:
    * 'locid' (id of location)
    * 'days'  (max. days +/- today, default 15)

* `[cb_bookings_location]` - bookings overview for location manager, parameters:
    * 'days'  (max. days +/- today, default 15)
    * 'acf'   (ACF fieldname, default 'user_locations')

    #### Dependency

    ACF = Wordpress Plugin [Advanced Custom Fields](https://de.wordpress.org/plugins/advanced-custom-fields) (field 'user_locations': select 1-n locations in user profile)

### 4. custom-shortcodes-cb-users1.php (only for non-public pages!):

* `[cb_bookings_user]` - user bookings summary (all subscriber bookings, sortable table)

## Details

* for more details see description on top of each php-file

## More verbose instructions (added by Markus V.)

### How to install the booking overview shortcode plugin

To prevent that it gets overwritten during a Wordpress update, add the following
line:

```
include_once( ABSPATH . WPINC . '/custom-shortcodes-cb-bookings-overviews.php');
```

either to the end of the `functions.php` file of your theme
(`wp-content/themes/<your-theme-name>/functions.php`) or at the end of
`private function __construct() {` in
`wp-content/plugins/commons-booking/public/class-commons-booking.php`. See also
https://stackoverflow.com/questions/6430855/shortcodes-breaking-wordpress-site.
Be aware that wherever you added it, if you update your theme or
the `commons-booking` plugin through Wordpress, that line will be overwritten
and must be added again.

Copy file `custom-shortcodes-cb-bookings-overviews.php` to `wp-includes`.

### How to create a booking overview page

Create a new page in Wordpress backend and enter for example:

```
[cb_bookings_overview locid=447 days=10]
```

to get a booking overview table for the location with ID 447 (check the
`post=...` in the URL of the edit location page to get the location ID) for 10
days in the future and past (default is 15 days). Repeat this for the other
stations/locations. You need one page per location.

You can also put the shortcode into the description of the location. That page
will be accessible under the URL http://your-domain.tld/cb-locations/<location-name>.
But keep in mind that this page must subsequently be hidden for privacy reasons, so don't put
information here that users should be able to see.

You might want to do this the other way around: first hide all location pages
with "Restrict User Access" (see below), then add the short codes to the pages to
avoid someone peeking on private data.

### Make the booking overviews private

You can use another plugin to make the booking overview pages visible only to
admin users as well certain non-admin users that the admins select (e.g.
station/location managers).

Install the plugin "Restrict User Access - Membership Plugin with Force" (see
https://www.quora.com/How-can-you-make-a-Wordpress-page-visible-only-to-logged-in-users,
https://wordpress.org/plugins/restrict-user-access/#how%20do%20i%20restrict%20some%20content%3F)
and activate it.

In the backend, click on "User Access" -> "Add New" to create an access level.
You need to create one access level per station/location.

Enter the name of the location as name of the access level (for easy
recognition, or choose your own scheme). Under "Members-Only Access", click on
"+ New condition group" and one of the following:
* if you added new pages for the booking overviews, click on "Pages" (in German
  "Seiten"), then select the page that corresponds to the location and contains
  the location's booking overview.
* if you added the overviews to the location descriptions, click on "Locations"
  (in German "Standorte") and select the location.

On the tab "Members" of the access level, click into the field "Search for
Users..." and enter and select the user name currently managing the location.
Click on "Create"/"Save" ("Speichern").

Voilà! Only the user managing the location is able to see the booking overview
when logged in! You only need to provide them with the URL.

When someone else takes over management of the location, just remove the old
user and add the new to the "Members" of the access level. Of course, also
several users can be added.
