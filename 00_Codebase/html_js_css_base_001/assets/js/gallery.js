/**
 * Reusable gallery: arrow navigation + lightbox on thumb click.
 * Each .gallery on the page is initialized.
 */
(function() {
	'use strict';

	var scrollStep = 140;

	function initGallery(container) {
		var viewport = container.querySelector('.gallery-viewport');
		var track = container.querySelector('.gallery-track');
		var prevBtn = container.querySelector('.gallery-arrow.prev');
		var nextBtn = container.querySelector('.gallery-arrow.next');
		var thumbs = container.querySelectorAll('.gallery-thumb');
		if (!track || !viewport) return;

		function updateArrows() {
			var scrollWidth = track.scrollWidth;
			var clientWidth = track.clientWidth;
			var canScroll = scrollWidth > clientWidth;
			if (prevBtn) {
				prevBtn.classList.toggle('hidden', !canScroll);
				prevBtn.disabled = !canScroll || track.scrollLeft <= 0;
			}
			if (nextBtn) {
				nextBtn.classList.toggle('hidden', !canScroll);
				nextBtn.disabled = !canScroll || track.scrollLeft >= scrollWidth - clientWidth - 1;
			}
		}

		function onScroll() {
			if (prevBtn) prevBtn.disabled = track.scrollLeft <= 0;
			if (nextBtn) nextBtn.disabled = track.scrollLeft >= track.scrollWidth - track.clientWidth - 1;
		}

		if (prevBtn) {
			prevBtn.addEventListener('click', function() {
				track.scrollBy({ left: -scrollStep, behavior: 'smooth' });
			});
		}
		if (nextBtn) {
			nextBtn.addEventListener('click', function() {
				track.scrollBy({ left: scrollStep, behavior: 'smooth' });
			});
		}
		track.addEventListener('scroll', onScroll);

		// Lightbox: find or create one per gallery (by data attribute or first lightbox in DOM)
		var lightboxId = container.getAttribute('data-lightbox-id') || 'gallery-lightbox';
		var lightbox = document.getElementById(lightboxId) || document.querySelector('.gallery-lightbox');
		var lightboxImg = lightbox && lightbox.querySelector('.gallery-lightbox-inner img');
		var closeBtn = lightbox && lightbox.querySelector('.gallery-lightbox-close');

		thumbs.forEach(function(thumb) {
			thumb.addEventListener('click', function(e) {
				e.preventDefault();
				var img = thumb.querySelector('img');
				var src = img && (img.getAttribute('data-full') || img.src);
				if (!lightbox || !lightboxImg || !src) return;
				lightboxImg.src = src;
				lightboxImg.alt = img.alt || '';
				lightbox.classList.add('is-open');
				lightbox.setAttribute('aria-hidden', 'false');
				document.body.style.overflow = 'hidden';
			});
		});

		if (closeBtn) {
			closeBtn.addEventListener('click', closeLightbox);
		}
		if (lightbox) {
			lightbox.addEventListener('click', function(e) {
				if (e.target === lightbox) closeLightbox();
			});
			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape' && lightbox.classList.contains('is-open')) closeLightbox();
			});
		}

		function closeLightbox() {
			if (lightbox) {
				lightbox.classList.remove('is-open');
				lightbox.setAttribute('aria-hidden', 'true');
				document.body.style.overflow = '';
			}
		}

		// Resize observer to show/hide arrows
		if (typeof ResizeObserver !== 'undefined') {
			var ro = new ResizeObserver(updateArrows);
			ro.observe(track);
		}
		updateArrows();
	}

	function init() {
		document.querySelectorAll('.gallery').forEach(initGallery);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
