angular.module 'metaEditor'

.constant 'LP',
  sc: 'Supreme Court'
  ca: 'Court of Appeal'
  fhc: 'Federal High Court'
  tat: 'Tax Appeal Tribunal'
  nic: 'National Industrial Court'
  uk: 'United Kingdom'

.constant 'CONST',
  changeLegalHead: "Change Legal Head"
  detachRatio: "Detach Ratio"
  mergeIssues: "Merge Issues"
  mergeSubjectMatters: "Merge Subject Matters"
  changeSubjectMatter: "Change Subject Matter"
  changeLegalHead: "Change Legal Head"
  updateIssue: "Update Issue"
  updateSubjectMatter: "Update Subject Matter"
  setIssueAsStandard: "Set Issue as Standard"
  setSubjectMatterAsStandard: "Set Subject Matter as Standard"

.factory 'AppServe', ['$resource', ($resource)->
  $resource 'api/v1/legalHeads/:_id?envelope=false'
]

.factory 'Report', ['$resource', ($resource) ->
  baseUrl = 'api/v1/reports/'

  $resource baseUrl, null,
    summary:
      method: "GET"
      isArray: true
      url: baseUrl + "summary"
    details:
      method: "GET"
      isArray: true
      url: baseUrl + "details"
    staff:
      method: "GET"
      isArray: true
      url: baseUrl + "staff"
]

.factory 'MergeService', ['$http', ($http)->
  mService =
    url: 'api/v1/'
    mergeSubjectMatters: ( parent, mergeSet, court, legal_head ) ->
      $http.post this.url+'mergeSubjectMatters?envelope=false', { parent: parent, mergeSet: mergeSet, court: court, lh: legal_head }

    mergeIssues: ( parent, mergeSet, court, legal_head, subject_matter ) ->
      $http.post this.url+'mergeIssues?envelope=false', { parent: parent, mergeSet: mergeSet, court: court, lh: legal_head, sm: subject_matter }

    updateSubjectMatter: ( old, newSM, court, legal_head ) ->
      $http.post this.url+'updateSubjectMatter?envelope=false', { old: old, new: newSM, court: court, lh: legal_head }

    updateIssue: ( old, newIssue, court, legal_head, subject_matter ) ->
      $http.post this.url+'updateIssue?envelope=false', { old: old, new: newIssue, court: court, lh: legal_head, sm: subject_matter }

    setStandardSM: ( subject_matter, legal_head ) ->
      $http.post this.url+'setStandard?envelope=false', { lh: legal_head, sm: subject_matter, subject_matter: true }

    setStandardIssue: ( issue, subject_matter, legal_head ) ->
      $http.post this.url+'setStandard?envelope=false', { iss: issue, lh: legal_head, sm: subject_matter, issue: true }

    changeLegalHead: ( subject_matter, current_legal_head, new_legal_head, court ) ->
      $http.post this.url+'changeLegalHead?envelope=false', { old: current_legal_head, new: new_legal_head, sm: subject_matter, court: court }

    changeSM: ( issue, current_sm, new_sm, legal_head, court ) ->
      $http.post this.url+'changeSubjectMatter?envelope=false', { old: current_sm, new: new_sm, issue: issue, lh: legal_head, court: court }

    detachRatio: (ratio) ->
      $http.post this.url+'detachRatio?envelope=false', ratio

    removeStandardSM: (legalHead, subjectMatter) ->
      $http.post this.url+'removeStandard?envelope=false', {
        lh: legalHead
        sm: subjectMatter
        subject_matter: true
      }

    removeStandardIssue: (legalHead, subjectMatter, issue) ->
      $http.post this.url+'removeStandard?envelope=false', {
        lh: legalHead
        sm: subjectMatter
        iss: issue
        issue: true
      }

  mService
]

.factory 'AuthToken', ['$window', ($window) ->
  get: ->
    $window.localStorage.getItem("LP-ME_api_key")

  set: (value) ->
    $window.localStorage.setItem("LP-ME_api_key", value)

  clear: ->
    $window.localStorage.removeItem("LP-ME_api_key")
]

.factory 'Session', ['$window', ($window) ->
  get: (key) ->
    $window.sessionStorage.getItem "__#{key}"

  set: (key, value) ->
    $window.sessionStorage.setItem "__#{key}", value

  clear: (key) ->
    $window.sessionStorage.removeItem "__#{key}"
]

.constant 'AuthEvents', {
  loginSuccess: "loginSuccess"
  loginFailed: "loginFailed"
  notAuthenticated: "notAuthenticated"
  notAuthorized: "notAuthorized"
  sessionTimeout: "sessionTimeout"
}

.constant 'AppConstants', {
  guest: "guest"
  authorized: "authorized"
}

.factory 'AuthService', ['$http', 'Session', 'AuthToken', ($http, Session, AuthToken) ->
  login: (username, password) ->
    $http.post('api/v1/users/login/', {
      username: username
      password: password
    }).then (response) ->
      AuthToken.set(response.data.records.privateKey) if response.data._meta.status == 'SUCCESS'
      Session.set "currentUser", JSON.stringify response.data.records.user
      response.data.records.user

  isGuest: ->
    AuthToken.get() is null

  currentUser: ->
    JSON.parse(Session.get "currentUser")

  logout: ->
    Session.clear "currentUser"
    AuthToken.clear()
]

.factory "AuthInterceptor", ['$q', '$injector', 'uiBlock', ($q, $injector, uiBlock) ->
  #This will be called on every outgoing http request
  request: (config)->
    AuthToken = $injector.get("AuthToken")
    token = AuthToken.get()
    config.headers = config?.headers || {}
    if token? and config.url.match(new RegExp('api/v1/')) then config.headers.X_API_KEY = token

    # block UI
    if config.url.match(new RegExp 'api/v1/report') then uiBlock.block()

    config || $q.when(config)

  requestError: (rejectReason) ->
    rejectReason

  response: (response) ->
    #Unblock the UI
    uiBlock.clear()
    response

  # This will be called on every incoming response that has en error status code
  responseError: (response) ->
    AuthEvents = $injector.get('AuthEvents')
    matchesAuthenticatePath = response.config && response.config.url.match( new RegExp 'api/v1/users/login/' )
    if not matchesAuthenticatePath
      $injector.get('$rootScope').$broadcast {
        401: AuthEvents.notAuthenticated,
        403: AuthEvents.notAuthorized,
        409: AuthEvents.sessionTimeout
      }[response.status], response

    $q.reject response
]