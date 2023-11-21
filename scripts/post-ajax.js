(function ($) {
	const tagItemCount = 3;

	$doc = $(document);

	$doc.ready(function () {
		/**
		 * Retrieve posts
		 */
		function get_posts($params) {
			$container = $('#container-async');
			$content = $container.find('.content');
			$status = $container.find('.status');
			$pager = $container.find('.infscr-pager a');

			//$status.text('검색중');

			/**
			 * Reset Pager for infinite scroll
			 */
			if ($params.page === 1 && $pager.length) {
				$pager.removeAttr('disabled').text('Load More');
			}

			if ($pager.length) {
				$method = 'infscr';
			} else {
				$method = 'pager';
			}

			/**
			 * Do AJAX
			 */
			$.ajax({
				url: bobz.ajax_url,
				data: {
					action: 'do_filter_posts_mt',
					nonce: bobz.nonce,
					params: $params,
					pager: $method,
				},
				type: 'post',
				dataType: 'json',
				beforeSend: function () {
					$('.loading-spinner').show();
				},
				success: function (data, textStatus, XMLHttpRequest) {
					if (data.status === 200) {
						if (data.method === 'pager' || $params.page === 1) {
							$content.hide().html(data.content).fadeIn();
						} else {
							/**
							 * Append content for infinite scroll
							 */
							$content.append(data.content);

							if (data.next !== 0) {
								$pager.attr('href', '#page-' + data.next);
							}
						}
					} else if (data.status === 201) {
						if (data.message === '검색결과가 없습니다.') {
							console.log('no posts');
							$content.html('');
						}
						if (data.method === 'pager') {
							$content.html(data.message);
						} else {
							$pager
								.attr('disabled', 'disabled')
								.text('You reached the end');
						}
					} else {
						$status.html(data.message);
						$content.html('');
					}

			
				},
				error: function (MLHttpRequest, textStatus, errorThrown) {
					$status.html(textStatus);

				
				},
				complete: function (data, textStatus) {
					$('.loading-spinner').hide();
					msg = textStatus;

					if (textStatus === 'success') {
						msg = data.responseJSON.message;
					}

					$status.html(msg);

			
				},
			});
		}

		/**
		 * Bind get_posts to tag cloud and navigation
		 */
		$('.sc-ajax-filter-multi').on(
			'click',
			'a[data-filter], .pagination a',
			function (event) {
				if (event.preventDefault) {
					event.preventDefault();
				}

				$this = $(this);

				/**
				 * Set filter active
				 */
				if ($this.data('filter')) {
					$page = 1;

					/**
					 * If all terms, then deactivate all other
					 */
					if ($this.data('term') === 'all-terms') {
						$this
							.closest('ul')
							.find('.active')
							.removeClass('active');
					} else {
						$('a[data-term="all-terms"]')
							.parent('li')
							.removeClass('active');
					}

					// Toggle current active
					$this.parent('li').toggleClass('active');

					/**
					 * Get All Active Terms
					 */
					$active = {};
					$terms = $this.closest('ul').find('.active');

					if ($terms.length >= tagItemCount) {
						var off = $this.closest('ul').find('li').not('.active');
						off.css('pointer-events', 'none').css('opacity', '0.5');
					} else {
						var off = $this.closest('ul').find('li').not('.active');
						off.css('pointer-events', 'auto').css('opacity', '1');
					}
					if ($terms.length) {
						$.each($terms, function (index, term) {
							$a = $(term).find('a');
							$tax = $a.data('filter');
							$slug = $a.data('term');

							if ($tax in $active) {
								$active[$tax].push($slug);
							} else {
								$active[$tax] = [];
								$active[$tax].push($slug);
							}
						});
					}
				} else {
					/**
					 * Pagination
					 */
					$page = parseInt($this.attr('href').replace(/\D/g, ''));
					$this = $('.nav-filter .active a');
				}

				$params = {
					page: $page,
					terms: $active,
					qty: $this.closest('#container-async').data('paged'),
				};

				// Run query
				get_posts($params);
			}
		);

		/**
		 * Show all posts on page load
		 */
		//$('a[data-term="all-terms"]').trigger('click');
	});
})(jQuery);
