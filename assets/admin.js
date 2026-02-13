
(function ($) {
  function initDatepicker() {
    var $date = $("#wpem_event_date");
    if (!$date.length || typeof $date.datepicker !== "function") return;

    $date.datepicker({
      dateFormat: "yy-mm-dd",
      changeMonth: true,
      changeYear: true
    });
  }

  function initLocationAutocomplete() {
    var $loc = $("#wpem_event_location");
    if (!$loc.length || typeof $loc.autocomplete !== "function") return;

    var source = (window.WPEMCLI_ADMIN && Array.isArray(WPEMCLI_ADMIN.locations))
      ? WPEMCLI_ADMIN.locations
      : [];

    if (!source.length) return;

    $loc.autocomplete({
      source: source,
      minLength: 1,
      delay: 0,
      appendTo: $loc.closest('.inside')
    });
  }

  $(function () {
    initDatepicker();
    initLocationAutocomplete();
  });
})(jQuery);