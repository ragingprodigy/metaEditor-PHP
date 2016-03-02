angular.module 'metaEditor'
.controller 'NavbarCtrl', ['$scope', '$location', 'LP', 'AuthService', ($scope, $location, LP, AuthService)->
  $scope.m = LP

  $scope.showBack = ->
    $location.path() isnt '/' && $location.path() isnt '/reports-view'

  $scope.logout = ->
    if confirm "Are you sure?"
      AuthService.logout()
      window.location.href = "."

  $scope.isGuest = ->
    AuthService.isGuest()
]