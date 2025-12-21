(function ($) {
    'use strict';

    $(function () {
        // Dáta prídu z PHP cez wp_localize_script
        if (typeof eventkvizAutocomplete === 'undefined') {
            return; // Ak dáta nie sú dostupné, ukonči
        }

        var artists = eventkvizAutocomplete.artists;
        var songs = eventkvizAutocomplete.songs;
        var movies = eventkvizAutocomplete.movies;

        // Autocomplete pre artists
        $(".autocomplete1").autocomplete({
            source: Object.keys(artists),
            select: function (event, ui) {
                var key = ui.item.value;
                $(this).next("input[type='hidden']").val(artists[key]);
            }
        });

        // Autocomplete pre songs
        $(".autocomplete2").autocomplete({
            source: Object.keys(songs),
            select: function (event, ui) {
                var key = ui.item.value;
                $(this).next("input[type='hidden']").val(songs[key]);
            }
        });

        // Autocomplete pre movies
        $(".autocomplete3").autocomplete({
            source: Object.keys(movies),
            select: function (event, ui) {
                var key = ui.item.value;
                $(this).next("input[type='hidden']").val(movies[key]);
            }
        });
    });

})(jQuery);