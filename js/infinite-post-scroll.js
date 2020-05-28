jQuery(document).ready( function($) {
	var currentPostId = 0
	var loadingMore = false
	var lastSwapTime = 0
	var fetchedNextPost = []

	// When the document is ready, preload the first new post.
	var data = {
		action: "infinite_post_scroll_generate_post",
		ID: infinite_post_scroll.post_ID
	}
	$.post( infinite_post_scroll.ajax_url, data,
		function(response) {
			if (response.success) {
				$(".infinite_post_scroll").show()
				$(".infinite_post_scroll").append(response.data.html)
				$(".load_more").show()
				fetchedNextPost[infinite_post_scroll.post_ID] = true
			}
		}
	)

	// When the page is scrolled, check if the articles appear in the
	// viewport.
	$(window).on('resize scroll', function() {
		var timeNow = Date.now()
		$('article').each(function() {
			// Activate if the 10% of the article is visible in the viewport,
			// the current active URL doesn't match the current article and
			// it's been more than 2 seconds since the last URL swap.
			if ($(this).isInViewport(0.1)
				&& window.location.href != $(this).data('url')
				&& timeNow - lastSwapTime > 2000) {
				lastSwapTime = Date.now()
				history.pushState(null, null, $(this).data('url'))
				currentPostId = $(this).data('id')

				// If the next post hasn't been fetched, do it now.
				if ( !fetchedNextPost[currentPostId] && !loadingMore) {
					loadingMore = true
					var data = {
						action: "infinite_post_scroll_generate_post",
						ID: currentPostId
					}
					$.post( infinite_post_scroll.ajax_url, data,
						function(response) {
							if (response.success) {
								$(".infinite_post_scroll").append(response.data.html)
								loadingMore = false
								fetchedNextPost[currentPostId] = true
							}
						}
					)
				}
			}
		})
	})

	// The function returns true if at least y * 100 percent of the element
	// is in the viewport.
	$.fn.isInViewport = function(y) {
		var y = y || 1
		var elementTop = $(this).offset().top
		var elementBottom = elementTop + $(this).outerHeight()
		var elementHeight = $(this).height()
		var viewportTop = $(window).scrollTop()
		var viewportBottom = viewportTop + $(window).height()
		var deltaTop = Math.min(1, (elementBottom - viewportTop) / elementHeight)
		var deltaBottom = Math.min(1, (viewportBottom - elementTop) / elementHeight)
		return deltaTop * deltaBottom >= y
	}
})
