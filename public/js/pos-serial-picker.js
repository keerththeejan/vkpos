/**
 * POS available IMEI picker helpers
 */
(function ($) {
  'use strict';

  $(document).on('change', '.pos_serial_select', function () {
    var $modal = $(this).closest('.modal');
    var texts = [];
    $(this)
      .find('option:selected')
      .each(function () {
        texts.push($.trim($(this).text()));
      });
    var $note = $modal.find('textarea[name*="[sell_line_note]"]');
    if ($note.length && texts.length) {
      $note.val(texts.join('\n'));
    }
  });
})(jQuery);
