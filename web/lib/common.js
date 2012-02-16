var parcel = new Parcel();

$(function () {
  $('a').pjax('#sub');
  $('#sub').bind('pjax:start', function () {
    $(this).animate({opacity:0},120);
    parcel.message.show('Loading..', 120);
  }).bind('pjax:end', function () {
    $(this).animate({opacity:1},240);
    parcel.message.hide(240);
  });
  parcel.cloud.start();
});
