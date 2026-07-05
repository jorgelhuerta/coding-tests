(function (Drupal, once) {
  'use strict';

  /**
   * Replaces the plain 1-5 select in the rating form with clickable stars,
   * keeping the select itself (hidden) as the actual submitted value so the
   * form still works with JS disabled.
   */
  Drupal.behaviors.movieRatingWidget = {
    attach: function (context) {
      once('movie-rating-widget', '.movie-rating-form select[name="rating"]', context).forEach(function (select) {
        var widget = document.createElement('div');
        widget.className = 'movie-rating-widget';
        widget.setAttribute('role', 'radiogroup');
        widget.setAttribute('aria-label', Drupal.t('Your rating'));

        var buttons = [];

        function highlight(value) {
          buttons.forEach(function (button, index) {
            button.classList.toggle('is-active', index < value);
          });
        }

        for (var i = 1; i <= 5; i++) {
          (function (value) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'movie-rating-widget-star';
            button.setAttribute('aria-label', Drupal.formatPlural(value, '1 star', '@count stars'));
            button.textContent = '★';
            button.addEventListener('click', function () {
              select.value = value;
              select.dispatchEvent(new Event('change'));
              highlight(value);
            });
            button.addEventListener('mouseenter', function () {
              highlight(value);
            });
            button.addEventListener('mouseleave', function () {
              highlight(parseInt(select.value, 10) || 0);
            });
            widget.appendChild(button);
            buttons.push(button);
          })(i);
        }

        highlight(parseInt(select.value, 10) || 0);
        select.insertAdjacentElement('afterend', widget);
        select.classList.add('visually-hidden');
      });
    },
  };
})(Drupal, once);
