# Infinite Post Scroll for WordPress

This is a WordPress plugin for creating an infinite post scroll on the single
post pages. When the user scrolls at the bottom of the post, the previous post
in date order is already waiting there to be read and when the user scrolls
down, the next post will automatically be loaded, creating an infinite stream
of content to read (well, until the posts run out).

The plugin will also automatically swap the correct permalink to the currently
visible post to the browser URL bar, so that the history works and the user
can easily grab the permalink to the current URL if they wish to do so.

I got the inspiration for this from [Pitchfork album
reviews](https://pitchfork.com/reviews/albums/).

## Installing the plugin

*This isn't a plugin you can just install, activate and use*. Using this plugin
requires editing the plugin code. This is meant as a framework you can use to
base your own work.

First step, however, is to install and activate the plugin.

### Editing templates

Next step is to edit the templates where the infinite scrolling will appear.
In the single post template (typically `single.php`), add these:

```
<div class="infinite_post_scroll"></div>

<div class="load_more">Loading more posts...</div>
```

The first `div` is where the posts will appear. Any content inside the `div`
will appear before the posts, so if you like to add a header or some text
there, that's perfectly fine. The `load_more` will appear below the posts.

In your theme style sheet, make both of these invisible:

```
.load_more {
	display: none;
}
.infinite_post_scroll {
	display: none;
}
```

Add any other styles you wish.

Having to chase a footer on a page with infinite scroll is super
frustrating. It's a good idea to replace the footer on an infinite scroll page
with a fixed position footer that remains constantly visible, or something
like that.

### Editing plugin code

Next step is to edit the plugin code to have it work the way you want it to
work. First, adjust the function `infinite_post_scroll_get_previus_post()` that
is used to fetch the previous post ID. What do you want to see? Just the
previous post? The previous post in some specific category, or in the same
category as the current post? Posts sharing tags, or with a specific custom
field? All kinds of filters are possible, just build a MySQL query that does
the job you need.

Then adjust the `infinite_post_scroll_get_rendered_post()` function so that it
returns the fetched post in the right format to fit your theme and style and
so that the necessary elements (title, content, meta data, taxonomy terms,
custom fields and so on) are included.

For the plugin functionality, the only thing that matters is that the post is
wrapped in an `article` tag with parameters that have the post ID and the 
permalink URL, like this:

```
<article data-url="<url>" data-id="<post_id>">
```

As long as these variables are in place, the Infinite Post Scroll plugin
doesn't care about the rest.