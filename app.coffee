
angular.module 'metaEditor', ['ngResource','ngMessages','ngRoute','mgcrea.ngStrap','ui.bootstrap']

.config ['$locationProvider','$routeProvider', ($locationProvider, $routeProvider) ->
  $locationProvider.html5Mode true

  $routeProvider
  .when '/',
    redirectTo: '/sc'
  .when '/:court',
    templateUrl: 'views/legal-heads.html'
    controller: 'LegalHeadsCtrl'
  .when '/:court/:legal_head',
    templateUrl: 'views/subject-matters.html'
    controller: 'LegalHeadCtrl'
  .when '/:court/:legal_head/:subject',
    templateUrl: 'views/issues.html'
    controller: 'IssuesCtrl'
  .otherwise
    redirectTo: '/sc'
]

.constant "CONF",
  countHeader: "ME-Count"
  pageHeader: "ME-Page"


String.prototype.hexEncode = ->
  result = ""
  for i in [0..this.length-1]
    hex = this.charCodeAt(i).toString(16)
    result += ("000"+hex).slice(-4)

  result

String.prototype.hexDecode = ->
  hexes = this.match(/.{1,4}/g) || []
  back = ""
  for j in [0..hexes.length-1]
    back += String.fromCharCode(parseInt(hexes[j], 16))

  back