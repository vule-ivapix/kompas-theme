/**
 * Kompas Homepage Settings
 *
 * Handles:
 * - Post search + autocomplete za sve .kompas-post-search inpute
 * - Drag & drop sortable za sve .kompas-selected-list liste
 * - Remove dugme za sve liste
 * - Category search + dinamičko dodavanje cat-grid blokova
 */
(function ($) {
	'use strict';

	var searchTimers = {};

	// ── Inicijalizacija na DOM ready ──────────────────────────────
	$(function () {
		initAllSortables();
		initCategorySearch();
		initImagePicker();
	});

	function initAllSortables() {
		$('.kompas-selected-list').sortable({
			axis: 'y',
			placeholder: 'kompas-sortable-placeholder',
		});
	}

	// ── Post search (delegated – važi i za dinamički dodane liste) ─
	$(document).on('input', '.kompas-post-search', function () {
		var input  = $(this);
		var autoId = input.data('auto');
		var uid    = autoId;

		clearTimeout(searchTimers[uid]);
		var q = input.val();
		if (q.length < 2) { $(autoId).hide().empty(); return; }

		searchTimers[uid] = setTimeout(function () {
			$.get(kompasSettings.ajaxUrl, {
				action: 'kompas_search_posts',
				q: q,
				nonce: kompasSettings.nonce
			}, function (res) {
				$(autoId).empty().show();
				if (!res.success || !res.data.length) {
					$(autoId).html('<li style="padding:8px 12px;color:#999">Nema rezultata</li>');
					return;
				}
				$.each(res.data, function (i, item) {
					$(autoId).append($('<li>').text(item.title).attr('data-id', item.id));
				});
			});
		}, 300);
	});

	// ── Klik na autocomplete sugestiju ────────────────────────────
	$(document).on('click', '.kompas-autocomplete-list li', function () {
		var $li    = $(this);
		var autoEl = $li.closest('.kompas-autocomplete-list');
		var input  = $('[data-auto="#' + autoEl.attr('id') + '"]');
		var listEl = $(input.data('list'));

		var id        = $li.data('id');
		var title     = $li.text();
		var max       = parseInt(listEl.data('max'), 10);
		var isSingle  = listEl.data('single') === '1' || listEl.data('single') === 1;
		var inputName = listEl.data('input-name');

		if (isSingle) {
			listEl.empty();
		} else {
			if (listEl.find('li').length >= max) {
				alert('Maksimalni broj postova je ' + max + '.');
				autoEl.hide();
				return;
			}
			if (listEl.find('li[data-id="' + id + '"]').length) {
				autoEl.hide();
				return;
			}
		}

		var newLi = $('<li>').attr('data-id', id).html(
			'<span class="kompas-drag-handle">&#9776;</span>' +
			'<span>' + $('<div>').text(title).html() + '</span>' +
			'<button type="button" class="kompas-remove">&#x2715;</button>' +
			'<input type="hidden" name="' + inputName + '" value="' + id + '" />'
		);
		listEl.append(newLi);
		listEl.sortable({ axis: 'y' });

		autoEl.hide().empty();
		input.val('');
	});

	// ── Zatvori autocomplete pri kliku van ────────────────────────
	$(document).on('click', function (e) {
		if (!$(e.target).closest('.kompas-post-search-wrap').length) {
			$('.kompas-autocomplete-list').hide();
		}
		if (!$(e.target).closest('#catgrid-cat-search-wrap').length) {
			$('#catgrid-cat-autocomplete').hide();
		}
	});

	// ── Remove dugme ─────────────────────────────────────────────
	$(document).on('click', '.kompas-selected-list .kompas-remove', function () {
		$(this).closest('li').remove();
	});

	// ── Ukloni kategoriju ─────────────────────────────────────────
	$(document).on('click', '.kompas-remove-cat', function () {
		$(this).closest('.kompas-catgrid-entry').remove();
	});

	// ── Category search ───────────────────────────────────────────
	function initCategorySearch() {
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
						catAuto.append($('<li>').text(item.name).attr('data-id', item.id));
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

			var uid       = 'catgrid_' + id;
			var listId    = 'kompas-list-' + uid;
			var autoId    = 'kompas-auto-' + uid;
			var inputName = 'kompas_catgrid_posts[' + id + '][]';

			var block = $(
				'<div class="kompas-catgrid-entry" data-cat-id="' + id + '">' +
					'<input type="hidden" name="kompas_catgrid_selected_ids[]" value="' + id + '" />' +
					'<div class="kompas-catgrid-cat-block">' +
						'<h4>' + $('<div>').text(name).html() +
						' <button type="button" class="kompas-remove-cat button button-small" style="float:right;color:#cc0000">Ukloni kategoriju</button></h4>' +
						'<div class="kompas-post-search-wrap">' +
							'<input type="text" class="kompas-post-search"' +
								' data-list="#' + listId + '"' +
								' data-auto="#' + autoId + '"' +
								' placeholder="Pretraži postove..." autocomplete="off"' +
								' style="max-width:400px;width:100%" />' +
							'<ul class="kompas-autocomplete-list" id="' + autoId + '" style="display:none"></ul>' +
						'</div>' +
						'<ul class="kompas-selected-list" id="' + listId + '"' +
							' data-max="6" data-single="0" data-input-name="' + inputName + '">' +
						'</ul>' +
					'</div>' +
				'</div>'
			);

			$('#catgrid-cat-blocks').append(block);
			$('#' + listId).sortable({ axis: 'y' });

			catAuto.hide().empty();
			catInput.val('');
		});
	}

	// ── Image picker (Reč urednika) ───────────────────────────────
	function initImagePicker() {
		var frame;

		$('#kompas-rec-image-btn').on('click', function (e) {
			e.preventDefault();

			if (frame) {
				frame.open();
				return;
			}

			frame = wp.media({
				title: 'Odaberi sliku',
				button: { text: 'Koristi ovu sliku' },
				multiple: false,
				library: { type: 'image' },
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				$('#kompas-rec-image-url').val(attachment.url);
				$('#kompas-rec-image-preview').show().find('img').attr('src', attachment.url);
				$('#kompas-rec-image-btn').text('Promeni sliku');
				$('#kompas-rec-image-remove').show();
			});

			frame.open();
		});

		$('#kompas-rec-image-remove').on('click', function (e) {
			e.preventDefault();
			$('#kompas-rec-image-url').val('');
			$('#kompas-rec-image-preview').hide().find('img').attr('src', '');
			$('#kompas-rec-image-btn').text('Odaberi sliku');
			$(this).hide();
		});
	}

}(jQuery));
