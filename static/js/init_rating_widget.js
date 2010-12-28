/*
 * Initializes the rating radio buttons
 * to become a nice star rating widget
 *
 * Not needed if the radio button class is set to star
 *
 */
$(document).ready(function(){
  var selector = 'input[name="rating"]';
  $(selector).addClass('star');
  $(selector).rating();
});