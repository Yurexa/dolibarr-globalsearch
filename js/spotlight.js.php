<?php
// Load Dolibarr environment from either htdocs/custom/globalsearch or htdocs/globalsearch.
$res = 0;
if (!$res && !empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) $res = @include $_SERVER['CONTEXT_DOCUMENT_ROOT'].'/main.inc.php';
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] === $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, $i + 1).'/main.inc.php')) $res = @include substr($tmp, 0, $i + 1).'/main.inc.php';
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, $i + 1)).'/main.inc.php')) $res = @include dirname(substr($tmp, 0, $i + 1)).'/main.inc.php';
if (!$res && file_exists('../../../main.inc.php')) $res = @include '../../../main.inc.php';
if (!$res && file_exists('../../main.inc.php')) $res = @include '../../main.inc.php';
if (!$res) die('Include of main fails');
header('Content-Type: application/javascript; charset=UTF-8');
$langs->load('globalsearch@globalsearch');
if (!isModEnabled('globalsearch') || $user->socid > 0 || !getDolGlobalInt('GLOBALSEARCH_SPOTLIGHT_ENABLED', 1)) {
	exit;
}
$config = array(
	'url' => dol_buildpath('/globalsearch/search.php', 1),
	'shortcut' => getDolGlobalString('GLOBALSEARCH_SHORTCUT', 'CTRL+K'),
	'minChars' => max(1, getDolGlobalInt('GLOBALSEARCH_MIN_CHARS', 2)),
	'placeholder' => html_entity_decode($langs->trans('GlobalSearchSpotlightPlaceholder'), ENT_QUOTES, 'UTF-8'),
	'noResult' => html_entity_decode($langs->trans('GlobalSearchNoResult'), ENT_QUOTES, 'UTF-8'),
	'seeAll' => html_entity_decode($langs->trans('GlobalSearchSeeAll'), ENT_QUOTES, 'UTF-8'),
	'allResults' => html_entity_decode($langs->trans('GlobalSearchSeeAll'), ENT_QUOTES, 'UTF-8'),
);
?>
(function () {
	'use strict';
	var config = <?php print json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	var overlay, input, results, timer, selected = -1;
	function shortcutMatches(event) {
		var parts = config.shortcut.toUpperCase().split('+');
		var key = parts.pop();
		var needsCtrl = parts.includes('CTRL'); var needsCmd = parts.includes('CMD');
		return event.key.toUpperCase() === key && (needsCtrl ? (event.ctrlKey || event.metaKey) : !event.ctrlKey) && (needsCmd ? event.metaKey : true) && (!!event.altKey === parts.includes('ALT')) && (!!event.shiftKey === parts.includes('SHIFT'));
	}
	function close() { overlay.hidden = true; selected = -1; }
	function open() { overlay.hidden = false; input.focus(); input.select(); }
	function selectable() { return Array.prototype.slice.call(results.querySelectorAll('a.gs-result')); }
	function setSelected(index) { var links = selectable(); if (!links.length) return; selected = (index + links.length) % links.length; links.forEach(function (link, i) { link.classList.toggle('selected', i === selected); }); links[selected].scrollIntoView({block: 'nearest'}); }
	function escapeHtml(value) { var node = document.createElement('span'); node.textContent = value || ''; return node.innerHTML; }
	function render(data) {
		var html = ''; selected = -1;
		(data.sections || []).forEach(function (section) {
			if (!section.rows.length) return;
			html += '<section class="gs-section"><h3>' + escapeHtml(section.title) + ' (' + section.count + ')</h3>';
			section.rows.forEach(function (row) { html += '<a class="gs-result" href="' + escapeHtml(row.url) + '"><strong>' + escapeHtml(row.label) + '</strong><span>' + escapeHtml(row.subtitle) + '</span></a>'; });
			if (section.count > section.rows.length && section.more_url) html += '<a class="gs-more" href="' + escapeHtml(section.more_url) + '">' + escapeHtml(config.seeAll) + '</a>';
			html += '</section>';
		});
		html += '<a class="gs-all" href="' + escapeHtml(config.url + '?search_all=' + encodeURIComponent(input.value.trim())) + '">' + escapeHtml(config.allResults) + '</a>';
		results.innerHTML = html;
	}
	function search() {
		var term = input.value.trim();
		if (term.length < config.minChars) { results.innerHTML = ''; return; }
		window.clearTimeout(timer);
		timer = window.setTimeout(function () {
			fetch(config.url + '?format=json&search_all=' + encodeURIComponent(term), {credentials: 'same-origin'})
				.then(function (response) { return response.json(); })
				.then(render)
				.catch(function () { results.innerHTML = '<p class="gs-empty">' + escapeHtml(config.noResult) + '</p>'; });
		}, 200);
	}
	function init() {
		overlay = document.createElement('div'); overlay.id = 'globalsearch-spotlight'; overlay.hidden = true;
		overlay.innerHTML = '<div class="gs-dialog" role="dialog" aria-modal="true"><div class="gs-search"><span>⌕</span><input type="search" autocomplete="off"><kbd>' + escapeHtml(config.shortcut) + '</kbd></div><div class="gs-results"></div><div class="gs-hint">↑ ↓ Naviguer · Entrée Ouvrir · Échap Fermer</div></div>';
		document.body.appendChild(overlay); input = overlay.querySelector('input'); results = overlay.querySelector('.gs-results'); input.placeholder = config.placeholder;
		overlay.addEventListener('click', function (event) { if (event.target === overlay) close(); }); input.addEventListener('input', search);
		document.addEventListener('keydown', function (event) {
			if (shortcutMatches(event)) { event.preventDefault(); open(); return; }
			if (overlay.hidden) return;
			if (event.key === 'Escape') { event.preventDefault(); close(); }
			if (event.key === 'ArrowDown') { event.preventDefault(); setSelected(selected + 1); }
			if (event.key === 'ArrowUp') { event.preventDefault(); setSelected(selected - 1); }
			if (event.key === 'Enter' && selected >= 0) { var links = selectable(); if (links[selected]) links[selected].click(); }
		});
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
}());


