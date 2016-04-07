/* Copyright 2016 Zachary Doll */
jQuery(document).ready(function($) {

  window.sweepComment = function() {
      $(this).val('');
      $('.wysihtml5-sandbox').contents().find("body").html('');
  };
});
