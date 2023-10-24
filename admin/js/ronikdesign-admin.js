(function( $ ) {
	'use strict';



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
		}, 250);




	});
})( jQuery );
