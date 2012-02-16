$(function () {

  $(window).bind('parcelajax', function (e, response) {
    if ('pedit' == response.req.act) {
      $('#edituid').val(response.res.uid);
      $('#editkey').val(response.res.key);
      $('#editval').val(response.res.val);
      $('#editday').val(response.res.day);
    } else {
      parcel.message.show(response.stat+', reload...');
      //console.log(response);
      location.href = '/admin';
    }
  });

  $('#paswdsubmit').click(function () {
    parcel.message.show('Change Password...');
    var data = {
      user : $('#paswdusr').val(),
      pass : $('#paswdpwd').val()
    };
    parcel.ajax('/post/paswd', data, 'POST');
  });

  $('#paramsubmit').click(function () {
    parcel.message.show('Send Params...');
    var param = $('.param'), keys = [], data = {};
    for (var i=0; i<param.length; i++) {
      var key = $(param[i]).find('.key');
      if ('' == key.val()) continue;
      var val = $(param[i]).find('.val');
      if (-1 != keys.indexOf(key.val()) && '' != key.val()) {
        alert('[NOTICE] param key "' + key.val() + '" is duplicated');
        return false;
      }
      keys[i] = key.val();
      data[key.val()] = val.val();
    }
    parcel.ajax('/post/param', data, 'POST');
  });
  $('#pagesubmit').click(function () {
    parcel.message.show('Create Page...');
    var data = { key : $('#pagekey').val(), val : $('#pageval').val() };
    parcel.ajax('/post/pages', data, 'POST');
  });
  $('#editselect').change(function () {
    parcel.message.push('Load Content');
    var val = $(this).val();
    if ('NaN' != val) {
      parcel.ajax('/raw/'+val, {act:'pedit'}, 'GET');
      $('#editsubmit').attr('disabled', false);
    }
  });
  $('#editsubmit').click(function () {
    parcel.message.show('Send Edition...');
    data = {
      uid : $('#edituid').val(),
      key : $('#editkey').val(),
      val : $('#editval').val(),
      day : $('#editday').val()
    }
    parcel.ajax('/post/pedit', data, 'POST');
  });
});
