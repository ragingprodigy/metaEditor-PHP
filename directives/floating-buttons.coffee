angular.module 'metaEditor'

.directive 'hoverRow', [->
  restrict: 'A'
  link: (s, e, a) ->

    e.on('click', ->
      if not e.hasClass 'highlight'
        e.parent().children().removeClass 'highlight'
        e.addClass 'highlight'
      else e.removeClass 'highlight'
    )
]

.directive 'ratioEditor', [ ->
  restrict: 'A'
  link: ($scope, $element, $attr) ->
    $element.find('li').on 'click', ->
      console.log this
      ratioId = $scope.$eval $attr.id
      alert ratioId
]

.directive 'backButton', [ ->
  {
    restrict: 'E'
    template: '<a ng-click="goBack()" class="back-button" title="Back"><i class="glyphicon glyphicon-2x glyphicon-chevron-left"></i></a>'
    controller: ['$scope', '$location', '$window', ($scope, $location, $window) ->
      $scope.goBack = ->
        $window.history.back() if $location.path() isnt '/dashboard'
      return true
    ]
  }
]