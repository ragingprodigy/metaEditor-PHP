angular.module 'metaEditor'
.controller 'NavbarCtrl', ['$scope', '$location', 'LP', 'AuthService', '$state', ($scope, $location, LP, AuthService, $state)->
  $scope.m = LP

  $scope.showBack = ->
    $location.path() isnt '/' && $location.path() isnt '/reports-view'

  $scope.logout = ->
    if confirm "Are you sure?"
      AuthService.logout()
      $state.go "login"
#      window.location.href = "."

  $scope.isGuest = ->
    AuthService.isGuest()
]