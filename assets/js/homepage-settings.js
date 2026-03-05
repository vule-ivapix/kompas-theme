/**
 * Kompas Homepage Settings – Category Grid: dinamičko dodavanje kategorija.
 *
 * Sve logike za post-liste (Hero, Reč urednika, Kolumne, per-cat postovi)
 * su inline u PHP funkciji kompas_settings_post_list().
 * Ovaj fajl obrađuje category autocomplete za Category Grid sekciju.
 */
(function ($) {
	'use strict';

	var catInput = $('#catgrid-cat-search');
	var catAuto  = $('#catgrid-cat-autocomplete');
	var catTimer;

	catInput.on('input', function () {
		clearTimeout(catTimer);
		var q = $(this).val();
		if (q.length < 2) { catAuto.hide().empty(); return; }

		catTimer = setTimeout(function () {
			$.get(kompasSettings.ajaxUrl, {
				action: 'kompas_search_categories',
				q: q,
				nonce: kompasSettings.nonce
			}, function (res) {
				catAuto.empty().show();
				if (!res.success || !res.data.length) {
					catAuto.html('<li style="padding:8px 12px;color:#999">Nema rezultata</li>');
					return;
				}
				$.each(res.data, function (i, item) {
					catAuto.append(
						$('<li>').text(item.name).attr('data-id', item.id)
					);
				});
			});
		}, 300);
	});

	catAuto.on('click', 'li', function () {
		var id   = $(this).data('id');
		var name = $(this).text();

		if ($('#catgrid-cat-blocks .kompas-catgrid-entry[data-cat-id="' + id + '"]').length) {
			catAuto.hide();
			return;
		}

		// Generiši uid za post-listu ove kategorije
		var uid       = 'catgrid_' + id;
		var listId    = 'kompas-list-' + uid;
		var searchId  = 'kompas-search-' + uid;
		var autoId    = 'kompas-auto-' + uid;
		var inputName = 'kompas_catgrid_posts[' + id + '][]';

		var html = '<div class="kompas-catgrid-entry" data-cat-id="' + id + '">' +
			'<input type="hidden" name="kompas_catgrid_selected_ids[]" value="' + id + '" />' +
			'<div class="kompas-catgrid-cat-block">' +
				'<h4>' + $('<div>').text(name).html() +
				' <button type="button" class="kompas-remove-cat button button-small" style="float:right;color:#cc0000">Ukloni kategoriju</button></h4>' +
				'<div class="kompas-post-search-wrap" id="' + searchId + '-wrap">' +
					'<input type="text" id="' + searchId + '" placeholder="Pretraži postove..." autocomplete="off" style="max-width:400px;width:100%" />' +
					'<ul class="kompas-autocomplete-list" id="' + autoId + '" style="display:none"></ul>' +
				'</div>' +
				'<ul class="kompas-selected-list" id="' + listId + '"' +
					' data-max="6" data-single="0" data-input-name="' + inputName + '">' +
				'</ul>' +
			'</div>' +
		'</div>';

		$('#catgrid-cat-blocks').append(html);
		catAuto.hide().empty();
		catInput.val('');

		// Inicijalizuj sortable i search za novu listu
		kompasInitPostList('#' + listId, '#' + searchId, '#' + autoId, '#' + searchId + '-wrap');
	});

	$(document).on('click', function (e) {
		if (!$(e.target).closest('#catgrid-cat-search-wrap').length) {
			catAuto.hide();
		}
	});

	// Ukloni kategoriju
	$(document).on('click', '.kompas-remove-cat', function () {
		$(this).closest('.kompas-catgrid-entry').remove();
	});

	/**
	 * Inicijalizuje post-search i sortable za listu postova.
	 */
	window.kompasInitPostList = function (listSel, searchSel, autoSel, wrapSel) {
		var timer;

		$(document).on('input', searchSel, function () {
			clearTimeout(timer);
			var q = $(this).val();
			if (q.length < 2) { $(autoSel).hide().empty(); return; }

			timer = setTimeout(function () {
				$.get(kompasSettings.ajaxUrl, {
					action: 'kompas_search_posts',
					q: q,
					nonce: kompasSettings.nonce
				}, function (res) {
					$(autoSel).empty().show();
					if (!res.success || !res.data.length) {
						$(autoSel).html('<li style="padding:8px 12px;color:#999">Nema rezultata</li>');
						return;
					}
					$.each(res.data, function (i, item) {
						$(autoSel).append($('<li>').text(item.title).attr('data-id', item.id));
					});
				});
			}, 300);
		});

		$(document).on('click', autoSel + ' li', function () {
			var id        = $(this).data('id');
			var title     = $(this).text();
			var list      = $(listSel);
			var max       = parseInt(list.data('max'), 10);
			var inputName = list.data('input-name');

			if (list.find('li').length >= max) {
				alert('Maksimalni broj postova je ' + max + '.');
				$(autoSel).hide();
				return;
			}
			if (list.find('li[data-id="' + id + '"]').length) {
				$(autoSel).hide();
				return;
			}
			var li = $('<li>').attr('data-id', id).html(
				'<span class="kompas-drag-handle">&#9776;</span>' +
				'<span>' + $('<div>').text(title).html() + '</span>' +
				'<button type="button" class="kompas-remove">&#x2715;</button>' +
				'<input type="hidden" name="' + inputName + '" value="' + id + '" />'
			);
			list.append(li);
			$(autoSel).hide().empty();
			$(searchSel).val('');
		});

		$(document).on('click', function (e) {
			if (!$(e.target).closest(wrapSel).length) {
				$(autoSel).hide();
			}
		});

		$(document).on('click', listSel + ' .kompas-remove', function () {
			$(this).closest('li').remove();
		});

		$(listSel).sortable({ handle: '.kompas-drag-handle', axis: 'y' });
	};

}(jQuery));
