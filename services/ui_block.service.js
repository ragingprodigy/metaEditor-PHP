// Generated by CoffeeScript 1.9.2
(function() {
  'use strict';
  angular.module('metaEditor').service('uiBlock', function() {
    return {
      block: function(element, message) {
        var msg;
        msg = message != null ? message : 'Processing...';
        element = element != null ? element : '.body';
        window.localStorage.setItem("blockedElement", element);
        return $(function() {
          return angular.element(element).block({
            message: "<div class='alert alert-info'>" + msg + "</div>",
            css: {
              height: "38px",
              border: ""
            }
          });
        });
      },
      clear: function() {
        var c;
        c = window.localStorage.getItem("blockedElement");
        if (c != null) {
          return angular.element(c).unblock();
        }
      }
    };
  });

}).call(this);
