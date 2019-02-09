# Commons Booking shortcodes extension

* a collection of shortcodes for usage with Wordpress plugin [Commons Booking](https://github.com/wielebenwir/commons-booking)
* php files can be included in functions.php
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
