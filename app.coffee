
angular.module 'metaEditor', ['ngResource','ngMessages', 'ui.router','mgcrea.ngStrap','ui.bootstrap']

.config ['$urlRouterProvider','$stateProvider', '$httpProvider', ($urlRouterProvider, $stateProvider, $httpProvider) ->
#  $locationProvider.html5Mode true

  $httpProvider.interceptors.push "AuthInterceptor"

#  if window.location.hostname.indexOf(".dev") > -1 then baseUrl = "" else baseUrl = "/meta-editor"

#  $routeProvider
#  .when '/',
#    templateUrl: 'views/login.html'
#    guestView: true
#    controller: 'LoginCtrl'
#  .when '/reports-view',
#    templateUrl: 'views/reports.html'
#    guestView: true
#    controller: 'ReportCtrl'
#  .when '/:court',
#    guestView: false
#    templateUrl: 'views/legal-heads.html'
#    controller: 'LegalHeadsCtrl'
#  .when '/:court/:legal_head',
#    guestView: false
#    templateUrl: 'views/subject-matters.html'
#    controller: 'LegalHeadCtrl'
#  .when '/:court/:legal_head/:subject',
#    guestView: false
#    templateUrl: 'views/issues.html'
#    controller: 'IssuesCtrl'
#  .otherwise
#    redirectTo: '/sc'

  $urlRouterProvider.otherwise '/'

  $stateProvider
    .state 'login',
      url: '/'
      templateUrl: 'views/login.html'
      guestView: true
      controller: 'LoginCtrl'
    .state 'reports',
      url: "/reports-view"
      templateUrl: 'views/reports.html'
      controller: 'ReportCtrl'
    .state 'legal_head',
      url: "/legalHeads/:court"
      templateUrl: 'views/legal-heads.html'
      controller: 'LegalHeadsCtrl'
    .state 'subject_matter',
      url: "/legalHeads/:court/:legal_head"
      templateUrl: 'views/subject-matters.html'
      controller: 'LegalHeadCtrl'
    .state 'issue',
      url: "/legalHeads/:court/:legal_head/:subject"
      templateUrl: 'views/issues.html'
      controller: 'IssuesCtrl'
]

.run ['$rootScope', 'AuthEvents', 'AuthService', 'AppConstants', '$location', '$state', ($rootScope, AuthEvents, AuthService, AppConstants, $location, $state) ->
  $rootScope.$on AuthEvents.notAuthenticated, ->
    toLogin()

  $rootScope.$on AuthEvents.sessionTimeout, ->
    toLogin()

  $rootScope.$on AuthEvents.loginFailed, ->
    alert "Login Failed"

  $rootScope.$on '$stateChangeStart', (event, next) ->
    if AuthService.isGuest() and not next.guestView
      $rootScope.$broadcast AuthEvents.notAuthenticated

  toLogin = ->
    $state.go "login"
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