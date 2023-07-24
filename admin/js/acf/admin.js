/* Update character count for text and textarea fields with a character limit */
acf.field.extend({
	type: 'text',
	events: {
		'input input': 'onChangeValue',
		'change input': 'onChangeValue'
	},
	onChangeValue: function(e){
        var countContainer = e.$el.closest('.acf-input').find('.ronik-character-count');
		if (countContainer.length != 0 && e.$el[0].hasAttribute('maxlength')) {
			var max = e.$el.attr('maxlength');
			var cur = e.$el.val().length;
			countContainer.find('.ronik-character-count__current').text(cur);
		}
	}
});
acf.field.extend({
	type: 'textarea',
	events: {
		'input textarea': 'onChangeValue',
		'change textarea': 'onChangeValue'
	},
	onChangeValue: function(e){
		var countContainer = e.$el.closest('.acf-input').find('.ronik-character-count');
		if (countContainer.length != 0 && e.$el[0].hasAttribute('maxlength')) {
			var max = e.$el.attr('maxlength');
			var cur = e.$el.val().length;
			countContainer.find('.ronik-character-count__current').text(cur);
		}
	}
});

jQuery(document).ready(function($) {
	let blockLoaded = false;
	let blockLoadedInterval = setInterval(function() {

		if ($('.lzy_img')) {
			setTimeout(() => {
				var dataSrc = $('.lzy_img').attr('data-src');
				$('.lzy_img').attr('src', dataSrc);
				$('.lzy_img').removeClass('reveal-disabled');
			}, 400);
			$( ".components-toolbar-button" ).click(function() {
				setTimeout(() => {
					var dataSrc = $('.lzy_img').attr('data-src');
					$('.lzy_img').attr('src', dataSrc);
					$('.lzy_img').removeClass('reveal-disabled');
				}, 400);
			});
		}
		if ( blockLoaded ) {
			clearInterval( blockLoadedInterval );
		}
	}, 50)

});