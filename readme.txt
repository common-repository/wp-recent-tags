=== Plugin Name ===
Contributors: WP Recent Tags
Donate link: http://www.mashget.com
Tags: tags, widget, post
Requires at least: 2.5
Tested up to: 2.6.2
Stable tag: 0.1.1

Provide a widget to show the hot tags of your recent posts.

== Description ==

Provide a widget to show the hot tags of your recent posts.

[ChangeLog](http://www.mashget.com/2008/09/18/wp-recent-tags-changelog/)

**What's Tag?**

Tag is simply a word you can use to describe something, with WordPress, 'something' will be your posts or pages. It's very easy to use, think this way, if you want to google your post, what keywords should you use, that's it.

**Why 'Recent Tags'?**

Giving an example:

Olympics were very hot about a month ago, and we might write many posts about this topic, then 'Olympics' was a very hot tag. But Olympics would not always the hot spot, right? It's gone, and we might talk Elections now, so how to let the visitors get this, get what you're talking about now?

Recent Tags! That's the simplest way.

**How to define 'Recent' and something you should know?**

'Recent' is a problem? Yep, actually it is.  Recent tags might be tags used in some recent posts, or it might be the tags used in some recent days, strictly speaking, they're different, but most of the time, the difference can be very small. The point is when you see the tags, you do tell it is the 'recent' ones.

For performance considerations, this plugin will log your tags by days, that means it can tell you what and how many times a tag be used for a specific day, and what the hot tags in some recent days.

But for most of us, we don't write posts very day, right?  So there's a problem here, if you ask hot tags in recent 2 days, you might get a empty box, as you didn't publish anything. To solve this problem, this plugin will still ask to show the hot tags in some recent posts, but the calculation method will be:

Giving an example, to show the hot tags in recent 20 posts, it will get the date of the 20th post first, and calculate the hot tags since that day.

**Worry about performance?**

It's possible to calculate the tag stat directly through wordpress tables, but the server usage  will be extremely high, so this plugin creates a table in your database to track your tags by day. You can treat these data as some pre-calculation, if you're not publishing posts every minute and every post gets lots of tags, I don't think you should worry about this table.

Also, I think you may use the [WP Widget Cache](http://www.mashget.com/2008/09/01/wp-widget-cache-for-wordpress/) to cache the widget output

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure the plugin through Admin -> Design -> Widgets(see the [ScreenShots](http://wordpress.org/extend/plugins/wp-recent-tags/screenshots/))

== Frequently Asked Questions ==

= The tags are not the right ones? =

Please read the [Description](http://wordpress.org/extend/plugins/wp-recent-tags/), part **How to define 'Recent' and something you should know?**. If in this situation, it's not right either, please leave a comment [here](http://www.mashget.com/2008/09/18/wp-recent-tags-for-wordpress/).

== Screenshots ==

1. The WP Recent Tags Widget
2. Edit WP Recent Tags Widget
2. WP Recent Tags Options
