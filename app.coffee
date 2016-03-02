
angular.module 'metaEditor', ['ngResource','ngMessages','ngRoute','mgcrea.ngStrap','ui.bootstrap']

.config ['$locationProvider','$routeProvider', '$httpProvider', ($locationProvider, $routeProvider, $httpProvider) ->
  $locationProvider.html5Mode true

  $httpProvider.interceptors.push "AuthInterceptor"

  if window.location.hostname.indexOf(".dev") > -1 then baseUrl = "" else baseUrl = "/meta-editor"

  $routeProvider
  .when baseUrl + '/',
    templateUrl: 'views/login.html'
    guestView: true
    controller: 'LoginCtrl'
  .when baseUrl + '/reports-view',
    templateUrl: 'views/reports.html'
    guestView: true
    controller: 'ReportCtrl'
  .when baseUrl + '/:court',
    guestView: false
    templateUrl: 'views/legal-heads.html'
    controller: 'LegalHeadsCtrl'
  .when baseUrl + '/:court/:legal_head',
    guestView: false
    templateUrl: 'views/subject-matters.html'
    controller: 'LegalHeadCtrl'
  .when baseUrl + '/:court/:legal_head/:subject',
    guestView: false
    templateUrl: 'views/issues.html'
    controller: 'IssuesCtrl'
  .otherwise
    redirectTo: baseUrl + '/sc'
]

.run ['$rootScope', 'AuthEvents', 'AuthService', 'AppConstants', '$location', ($rootScope, AuthEvents, AuthService, AppConstants, $location) ->
  $rootScope.$on AuthEvents.notAuthenticated, ->
    toLogin()

  $rootScope.$on AuthEvents.sessionTimeout, ->
    alert AuthEvents.sessionTimeout
    toLogin "Your Session has timed out"

  $rootScope.$on AuthEvents.loginFailed, ->
    alert "Login Failed"

  $rootScope.$on '$routeChangeStart', (event, next) ->
    if AuthService.isGuest() and not next.guestView
      $rootScope.$broadcast AuthEvents.notAuthenticated

  toLogin = ->
    $location.path "/"
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