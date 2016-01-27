angular.module 'metaEditor'
.controller 'NavbarCtrl', ['$scope', '$location', 'LP', ($scope, $location, LP)->
  $scope.m = LP

  $scope.showBack = ->
    $location.path() isnt '/'
]