/**
 * WPCalibrate Membership Access - Admin JavaScript
 */
(function($) {
	'use strict';

	$(function() {
		// Auto-dismiss notices when clicking close button.
		$(document).on('click', '.wpcal-notice .notice-dismiss', function() {
			$(this).closest('.wpcal-notice').fadeTo(100, 0, function() {
				$(this).slideUp(100, function() {
					$(this).remove();
				});
			});
		});

		// Accessible tab keyboard navigation.
		$('.wpcal-nav-tabs .nav-tab').on('keydown', function(e) {
			const $tabs = $('.wpcal-nav-tabs .nav-tab');
			const currentIndex = $tabs.index(this);

			if (e.key === 'ArrowRight') {
				e.preventDefault();
				const nextIndex = (currentIndex + 1) % $tabs.length;
				$tabs.eq(nextIndex).focus();
			} else if (e.key === 'ArrowLeft') {
				e.preventDefault();
				const prevIndex = (currentIndex - 1 + $tabs.length) % $tabs.length;
				$tabs.eq(prevIndex).focus();
			}
		});
	});
})(jQuery);
