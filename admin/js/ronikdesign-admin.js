(function( $ ) {
	'use strict';

	function log_click_action( action, url ) {
		alert('ddd');
		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'do_init_analytics',
				click_action: action,
				point_origin: url
			}
		});
	}


	function initSVGMigration() {
		// Exit Function if node doesnt exist.
		if ($("#page_svg_migration_ronikdesign").length == 0) {
			return;
		}
		$('.svg-migration_ronikdesign .acf-input .acf-repeater .acf-table tbody .acf-row .acf-row-handle.order').css({'pointer-events':'none', 'opacity':0});
		// $('.svg-migration .acf-input .acf-repeater .acf-table .ui-sortable .acf-row').css({'pointer-events':'none', 'opacity':.5});
		$('<span name="button" class="page_svg_migration__link_ronikdesign" href="#" style="cursor:pointer;background: #7210d4;border: none;padding: 10px;color: #fff;border-radius: 5px;">Init SVG Migration</span>').appendTo( $('#page_svg_migration_ronikdesign') );
		// Trigger rejection.
		$(".page_svg_migration__link_ronikdesign").on('click', function(){
			$.ajax({
				type: 'post',
				url: wpVars.ajaxURL,
				data: {
					action: 'do_init_svg_migration_ronik',
					nonce: wpVars.nonce,
				},
				dataType: 'json',
				success: data => {
					if(data.success){
						console.log('success');
						console.log(data);
						alert('success');
						setTimeout(() => {
							window.location.reload(true);
						}, 500);
					} else{
						console.log('error');
						console.log(data);
						alert('Whoops! Something went wrong! Please try again later!');
						setTimeout(() => {
							window.location.reload(true);
						}, 500);
					}
				},
				error: err => {
					console.log(err);
					alert('Whoops! Something went wrong! Please try again later!');
					// Lets Reload.
					setTimeout(() => {
						window.location.reload(true);
					}, 500);
				}
			});
		});
	}
	initSVGMigration();


	function initUnusedMedia() {
		// Exit Function if node doesnt exist.
		if ($("#page_media_cleaner_field").length == 0) {
			return;
		}
		$('<span name="button" class="page_unused_media__link" href="#" style="cursor:pointer;background: #7210d4;border: none;padding: 10px;color: #fff;border-radius: 5px;">Init Unused Media Migration</span>').appendTo( $('#page_media_cleaner_field') );
		// Trigger rejection.
		$(".page_unused_media__link").on('click', function(){
			alert('Data is processing. Please do not reload!');
			$('.wp-core-ui').css({'pointer-events':'none', 'opacity':.5});

			$.ajax({
				type: 'post',
				url: wpVars.ajaxURL,
				data: {
					action: 'do_init_unused_media_migration',
					nonce: wpVars.nonce,
				},
				dataType: 'json',
				success: data => {
					if(data.success){
						console.log('success');
						console.log(data);
						$('.wp-core-ui').css({'pointer-events':'', 'opacity':''});
						alert('Data processing. Page will reload after processing! Please do not reload!');
						setTimeout(() => {
							window.location.reload(true);
						}, 500);
					} else{
						console.log(data.success);
						console.log(data.data);
						if(data.data == 'No rows found!'){
							alert('Great News! No un-detached images were found. Please try again later!');
						} else{
							alert('Whoops! Something went wrong! Please try again later!');
						}
						$('.wp-core-ui').css({'pointer-events':'', 'opacity':''});
						setTimeout(() => {
							window.location.reload(true);
						}, 500);
					}
				},
				error: err => {
					console.log(err);
					$('.wp-core-ui').css({'pointer-events':'', 'opacity':''});
					alert('Whoops! Something went wrong! Please try again later!');
					// Lets Reload.
					setTimeout(() => {
						window.location.reload(true);
					}, 500);
				}
			});
		});
	}

	function deleteUnusedMedia() {
		// Exit Function if node doesnt exist.
		if ($("#page_media_cleaner_field").length == 0) {
			return;
		}
		$('<span name="button" class="page_delete_media__link" href="#" style="margin-left:10px;cursor:pointer;background: #d4104e;border: none;padding: 10px;color: #fff;border-radius: 5px;">Delete Unused Media</span>').appendTo( $('#page_media_cleaner_field') );
		// Trigger rejection.
		$(".page_delete_media__link").on('click', function(){
			// alert('Please make sure you have reviewed the images listed below. If you see any image that you want to keep please remove from the repeater row and click update and then click the delete button.');
			$('.wp-core-ui').css({'pointer-events':'none', 'opacity':.5});

			$.ajax({
				type: 'post',
				url: wpVars.ajaxURL,
				data: {
					action: 'do_init_remove_unused_media',
					nonce: wpVars.nonce,
				},
				dataType: 'json',
				success: data => {
					if(data.success){
						console.log('success');
						console.log(data);
						$('.wp-core-ui').css({'pointer-events':'', 'opacity':''});
						alert('Data processing. Page will reload after processing! Please do not reload!');
						setTimeout(() => {
							window.location.reload(true);
						}, 500);
					} else{
						console.log('error');
						console.log(data);
						$('.wp-core-ui').css({'pointer-events':'', 'opacity':''});
						alert('Whoops! Something went wrong! Please try again later!');
						setTimeout(() => {
							window.location.reload(true);
						}, 500);
					}
				},
				error: err => {
					console.log(err);
					$('.wp-core-ui').css({'pointer-events':'', 'opacity':''});
					alert('Whoops! Something went wrong! Please try again later!');
					// Lets Reload.
					setTimeout(() => {
						window.location.reload(true);
					}, 500);
				}
			});
		});
	}

	function initPageMigration() {
		// Exit Function if node doesnt exist.
		if ($("#page_url_migration_ronikdesign").length == 0) {
			return;
		}
		$('.migration-status').find('input').css({'pointer-events':'none', 'opacity':.5});
		$('<span name="button" class="page_url_migration__link_ronik" href="#" style="cursor:pointer;background: #7210d4;border: none;padding: 10px;color: #fff;border-radius: 5px;">Init Page Url Migration</span>').appendTo( $('#page_url_migration_ronikdesign') );
		// Trigger rejection.
		$(".page_url_migration__link_ronik").on('click', function(){
			$.ajax({
				type: 'post',
				url: wpVars.ajaxURL,
				data: {
					action: 'do_init_page_migration',
					nonce: wpVars.nonce,
				},
				dataType: 'json',
				success: data => {
					if(data.success){
						console.log('success');
						console.log(data);
						alert('Data processing. Page will reload after processing! Please read migration status below.');
						setTimeout(() => {
							window.location.reload(true);
						}, 500);
					} else{
						console.log('error');
						console.log(data);
						alert('Whoops! Something went wrong! Please try again later!');
						setTimeout(() => {
							window.location.reload(true);
						}, 500);
					}
				},
				error: err => {
					console.log(err);
					alert('Whoops! Something went wrong! Please try again later!');
					// Lets Reload.
					setTimeout(() => {
						window.location.reload(true);
					}, 500);
				}
			});
		});
	}

	// Load JS once windows is loaded. 
	$(window).on('load', function(){
		// SetTimeOut just incase things havent initialized just yet.
		setTimeout(() => {
			initSVGMigration();
			initPageMigration();
			initUnusedMedia();
			deleteUnusedMedia();

			// verificationProcess('email', 'kevin@ronikdesign.com' );
			// setTimeout(() => {
			// 	verificationProcess('phone', '631-617-4271' );
			// }, 2000);

			// jQuery( 'button, a' ).on( 'click', function (ev) {
			// 	// ev.preventDefault();
			// 	// alert("Hello World!");

			// 	var currentLocation = window.location;
			// 	console.log(currentLocation);
			// 	log_click_action( 'my_button_clicked', currentLocation );
			// 	alert('sss');


			// 	setTimeout(() => {
			// 		alert('sss');
			// 	}, 1110);

			// });
		}, 250);




	});
})( jQuery );
