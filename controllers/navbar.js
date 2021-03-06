// Generated by CoffeeScript 1.9.2
(function() {
  angular.module('metaEditor').controller('NavbarCtrl', [
    '$scope', '$location', 'LP', 'AuthService', '$state', function($scope, $location, LP, AuthService, $state) {
      $scope.m = LP;
      $scope.showBack = function() {
        return $location.path() !== '/' && $location.path() !== '/reports-view';
      };
      $scope.logout = function() {
        if (confirm("Are you sure?")) {
          AuthService.logout();
          return $state.go("login");
        }
      };
      return $scope.isGuest = function() {
        return AuthService.isGuest();
      };
    }
  ]);

}).call(this);
