function Parcel() {
  var self = this;

  self.isset = function (is, set) {
    return 'undefined' == typeof(is) ? set : is;
  }

  self.ajax = function (url, data, type) {
    $.ajax({url:url, type:type, data:JSON.stringify(data),
      error: function (response) {
        response = JSON.parse(response);
        $(window).trigger('parcelajax', { res:response[0], req:data, stat:'error' });
      },
      success: function (response) {
        response = JSON.parse(response);
        $(window).trigger('parcelajax', { res:response[0], req:data, stat:'success'});
      }
    });
  }

  self.cloudsrand = function (num) {
    return Math.floor(Math.random()*num) + 1;
  }
  self.cloudpush = function () {
    var asp = 120 + 50 * self.cloudsrand(4);
    $('<img>').attr({'src':'/media/c'+self.cloudsrand(4)+'.png'}).addClass('cloud')
      .css({top:(10+self.cloudsrand(80))+'%',left:$(window).width()+'px',width:asp+'px',height:(asp*150/320)+'px', opacity:Math.random()/2})
      .appendTo('body').animate({left:'-320px'},14628+1216*self.cloudsrand(18),'linear',function(){$(this).remove()});
  }
  self.cloud = {
    start : function (interval) {
      self.cloudpush();
      self.cloudsi = setInterval(function () {
        self.cloudpush();
      }, 9637);
    },
    stop : function () {
      clearTimeout(self.cloudsi);
    }
  }

  self.hud = $('<div>').attr({'id':'hud'}).append($('<img>').attr({src:'/media/load.spin.gif'})).append($('<h1>'));
  self.message = {
    show : function (msg, time) {
      time = self.isset(time, 480);
      if (0 == $('#hud').size()) {
        self.hud.find('h1').html(msg);
        self.hud.appendTo('body').fadeIn(time);
      } else {
        $('#hud').find('h1').fadeOut(60, function () {
          $(this).html(msg).fadeIn(60);
        });
      }
    },
    hide : function (time) {
      time = self.isset(time, 480);
      $('#hud').fadeOut(time, function () {
        $(this).remove();
      });
    },
    push : function (msg, time) {
      time = self.isset(time, 1000);
      self.message.show(msg);
      setTimeout(function() { self.message.hide() }, time);
    }
  }
}
