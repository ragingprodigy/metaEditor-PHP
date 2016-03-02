angular.module 'metaEditor'

.controller 'LoginCtrl', ['$scope', 'AuthService', '$rootScope', 'AuthEvents', '$window', '$location', ($scope, AuthService, $rootScope, AuthEvents, $window, $location) ->

  $scope.user = {}

  if not AuthService.isGuest()
    $location.path "/sc"

  $scope.login = (theForm) ->
    if not theForm.$invalid
      AuthService.login($scope.user.username, $scope.user.password).then ->
        $location.path "/sc"
      , ->
        $rootScope.$broadcast(AuthEvents.loginFailed)
]

.controller 'ReportCtrl', ['$scope', 'Report', 'CONST', '$modal', ($scope, Report, CONST, $modal) ->
  $scope.wiki = CONST

  $scope.periods = [
    { value: "day", label: "TODAY" }
    { value: "week", label: "THIS WEEK" }
    { value: "month", label: "THIS MONTH" }
    { value: "span", label: "DATE RANGE" }
  ]

  $scope.staff = [
    { id: -1, name: "EVERYONE" }
  ]

  $scope.activeStaff = -1

  Report.staff envelope: false, (staff) ->
    $scope.staff = _.union $scope.staff, staff

  $scope.getData = ->
    Report.summary
      period: $scope.activePeriod
      envelope: false
      staff: $scope.activeStaff
      from: $scope.from
      to: $scope.to
    , (summary) ->
      $scope.summary = summary

  $scope.activePeriod = $scope.periods[0].value
  $scope.getData()

  $scope.modal = {
    "title": "Title",
    "content": "Hello Modal<br />This is a multiline message!"
  };

  $scope.showDetail = (action) ->
    $scope.action = action
    Report.details
      action: action
      period: $scope.activePeriod
      envelope: false
      staff: $scope.activeStaff
      from: $scope.from
      to: $scope.to
    , (details) ->
      $scope.reportDetails = details
      $scope.user = _.find $scope.staff, (s) ->
        s.id is $scope.activeStaff

      modal = $modal
        scope: $scope
        template: 'views/report-detail.tpl.html'
        show: false
        scope: $scope

      modal.$promise.then modal.show

]

.controller 'LegalHeadsCtrl', ['$scope', '$routeParams', 'LP', 'AppServe', '$location', '$alert', ($scope, $routeParams, LP, AppServe, $location, $alert )->
  $scope.title = LP[$routeParams.court]
  $scope.rowActive = false

  $scope.getLegalHeads = ->
    AppServe.query { getHeads: true, court: $routeParams.court }, (response) ->
      $scope.legalHeads = response

    AppServe.query { getStandard: true }, (response) ->
      $scope.sLegalHeads = response

  $scope.activateRow = (lH, index) ->
    if $scope.activeRow is index
      $scope.rowActive=false
      $scope.activeRow = undefined
    else
      $scope.rowActive = true
      $scope.activeRow = index

  $scope.doReplace = (lh) ->
    if $scope.activeRow isnt undefined
      old = $scope.legalHeads[$scope.activeRow].legalHead
      if confirm "Replace #{$scope.legalHeads[$scope.activeRow].legalHead} with #{lh}?"
        AppServe.query { doReplace: true, old: old, new: lh, court: $routeParams.court }
        .$promise.then ->
          $alert {
            title: 'Info:'
            content: "Records updated!"
            placement: 'top-right'
            type: 'info'
            duration: 3
          }

          $scope.getLegalHeads()

  $scope.showSubjectMatter = (l) ->
    $location.path $routeParams.court+"/"+l.hexEncode()

  $scope.getLegalHeads()
]

.controller 'LegalHeadCtrl', ['$scope', '$routeParams', 'LP', 'AppServe', 'filterFilter', 'MergeService', '$location', '$alert', 'CONF', ($scope, $routeParams, LP, AppServe, filterFilter, MergeService, $location, $alert, CONF) ->
  $scope.legal_head = $routeParams.legal_head.hexDecode()
  $scope.title = $scope.legal_head + " - " + LP[$routeParams.court]
  $scope.mergeSet = []

  $scope.popover = {}

  $scope.currentSm = $scope.selectedIndex = undefined

  $scope.legalHeads = AppServe.query { getStandard: true }

  $scope.changeLH = (newLh, selectedSM) ->
    selectedSubjectMatter = "#{selectedSM}"
    if confirm "Change the Legal Head for #{selectedSM} from #{$scope.legal_head} to #{newLh}"
      MergeService.changeLegalHead selectedSubjectMatter, $scope.legal_head, newLh, $routeParams.court
      .then (r) ->
        if r.data.length is 3
          $alert
            title: 'Info:'
            content: "Update Complete"
            placement: 'top-right'
            type: 'info'
            duration: 3

          $scope.getSubjectMatters()

  $scope.updateContent = (newSubjectMatter)->
    if $scope.currentSm isnt undefined and confirm "Change #{$scope.currentSm} to #{newSubjectMatter}?"
      MergeService.updateSubjectMatter $scope.currentSm, newSubjectMatter, $routeParams.court, $scope.legal_head
      .then (response) ->
        if response.data.length is 3
          $scope.subjectMatters[$scope.selectedIndex].subjectMatter = newSubjectMatter
          $alert
            title: 'Info:'
            content: "Update Complete"
            placement: 'top-right'
            type: 'info'
            duration: 3

        $scope.currentSm = $scope.selectedIndex = undefined

  $scope.deleteStandard = (sm, index) ->
    if confirm "Delete #{sm} from Standard Subject Matters?"
      MergeService.removeStandardSM $scope.legal_head, sm
      .then (r) ->
        if r.data.length is 1
          $alert
            title: 'Info:'
            content: "Update Complete"
            placement: 'top-right'
            type: 'info'
            duration: 5

          $scope.getSubjectMatters()
          $scope.standardSubjectMatters.splice index, 1

  $scope.toggleCurrent = (sm, index) ->
    if $scope.currentSm is undefined or $scope.currentSm isnt sm
      $scope.currentSm = sm
      $scope.selectedIndex = index
    else
      $scope.selectedIndex = $scope.currentSm = undefined

  $scope.setParent = (sm, index) ->
    $scope.parentSelected = true
    $scope.selectedParent = index

  $scope.unsetParent = ->
    $scope.mergeSet = []
    $scope.parentSelected = false
    $scope.selectedParent = null

  $scope.forMerge = (index) ->
    $scope.mergeSet.indexOf(index) isnt -1

  $scope.toggleMergeSet = (sm) ->
    if not $scope.forMerge sm then $scope.mergeSet.push(sm)
    else
      $scope.mergeSet = filterFilter($scope.mergeSet, "!#{sm}")

  $scope.doMerge = ->
    theParent = $scope.subjectMatters[$scope.selectedParent].subjectMatter
    if $scope.mergeSet.length > 0 and confirm "#{$routeParams.court} Do you want to merge\n\n #{$scope.mergeSet.join(', \n')} \n\n into #{theParent}"

      MergeService.mergeSubjectMatters theParent, $scope.mergeSet, $routeParams.court, $scope.legal_head
      .then (r)->
        if r.data.length is 3
          $alert
            title: 'Info:'
            content: "Records updated!"
            placement: 'top-right'
            type: 'info'
            duration: 3

          $scope.unsetParent()
          $scope.fetchStandard()
          $scope.getSubjectMatters()

  $scope.setStandard = (selectedMatter, index) ->
    if selectedMatter.standard is "0" and confirm "Are you sure you want to make \n\n #{selectedMatter.subjectMatter} a Standard Subject Matter?"
      MergeService.setStandardSM selectedMatter.subjectMatter, $scope.legal_head
      .then (response) ->
        if response.data.length is 1
          $scope.subjectMatters[index].standard = "1" # Hide Action Button
          $alert {
            title: 'Info:'
            content: "Records Updated!"
            placement: 'top-right'
            type: 'info'
            duration: 3
          }
          $scope.fetchStandard()

  $scope.perPage = 2

  $scope.getSubjectMatters = ->
    AppServe.query { getSubjectMatters: true, court: $routeParams.court, legal_head: $scope.legal_head }, (sm) ->
      $scope.subjectMatters  = sm

  $scope.fetchStandard = ->
    AppServe.query { getSSubjectMatters: true, lh: $scope.legal_head }, (response) ->
      $scope.standardSubjectMatters = response

  $scope.showIssues = (sm) ->
    curPath = $location.path()
    $location.path curPath+"/"+sm.hexEncode()

  $scope.unsetParent()
  $scope.getSubjectMatters()
  $scope.fetchStandard()

  # Load Page
  $scope.pageChanged = ->
    $scope.getSubjectMatters $scope.currentPage
]

.controller 'IssuesCtrl', ['$scope', '$routeParams', 'LP', 'AppServe', 'filterFilter', 'MergeService', '$location', '$alert', '$aside', 'CONF', ($scope, $routeParams, LP, AppServe, filterFilter, MergeService, $location, $alert, $aside, CONF) ->

  $scope.legal_head = $routeParams.legal_head.hexDecode()
  $scope.subject_matter = $routeParams.subject.hexDecode()
  $scope.title = $scope.subject_matter + " - " + $scope.legal_head + " - " + LP[$routeParams.court]

  $scope.mergeSet = []
  $scope.popover = {}

  $scope.perPage = 10

  $scope.currentIss = $scope.selectedIndex = undefined

  $scope.subjectMatters = AppServe.query { getSubjectMatters: true, court: $routeParams.court, legal_head: $scope.legal_head }

  $scope.changeSubjectMatter = (newSubjectMatter, selectedIssue) ->
    selectedIssue = "#{selectedIssue}"
    msg = "Change the Subject Matter for \n\n#{selectedIssue} from \n\n#{$scope.subject_matter} to \n\n#{newSubjectMatter}"
    if confirm msg
      MergeService.changeSM selectedIssue, $scope.subject_matter, newSubjectMatter, $scope.legal_head, $routeParams.court
      .then (r) ->
        if r.data.length is 2
          $alert
            title: 'Info:'
            content: "Update Complete"
            placement: 'top-right'
            type: 'info'
            duration: 3

          $scope.getIssues()
          $scope.fetchStandard()

  $scope.setStandard = (selectedIssue, index) ->
    if selectedIssue.standard is "0" and confirm "Are you sure you want to make \n\n #{selectedIssue.issue} a Standard
 Issue?"
      MergeService.setStandardIssue selectedIssue.issue, $scope.subject_matter, $scope.legal_head
      .then (response) ->
        if response.data.length is 1
          $scope.issues[index].standard = "1"
          $alert
            title: 'Info:'
            content: "Records added!"
            placement: 'top-right'
            type: 'info'
            duration: 3

          $scope.fetchStandard()

  $scope.updateContent = (newIssue)->
    if $scope.currentIss isnt undefined and confirm "Change #{$scope.currentIss} to #{newIssue}?"
      MergeService.updateIssue $scope.currentIss, newIssue, $routeParams.court, $scope.legal_head, $scope.subject_matter
      .then (response) ->
        if response.data.length is 2
          $scope.issues[$scope.selectedIndex].issue = newIssue
          $alert
            title: 'Info:'
            content: "Update Complete"
            placement: 'top-right'
            type: 'info'
            duration: 3

          $scope.getIssues()
          $scope.fetchStandard()
        $scope.currentIss = $scope.selectedIndex = undefined

  $scope.deleteStandard = (issue, index) ->
    if confirm "Delete #{issue} from Standard Issues?"
      MergeService.removeStandardIssue $scope.legal_head, $scope.subject_matter, issue
      .then (r) ->
        if r.data.length is 1
          $alert
            title: 'Info:'
            content: "Update Complete"
            placement: 'top-right'
            type: 'info'
            duration: 5

          $scope.getIssues()
          $scope.standardIssues.splice index, 1

  $scope.toggleCurrent = (iss, index) ->
    if $scope.currentIss is undefined or $scope.currentIss isnt iss
      $scope.currentIss = iss
      $scope.selectedIndex = index
    else
      $scope.selectedIndex = $scope.currentIss = undefined

  $scope.setParent = (sm, index)->
    $scope.parentSelected = true
    $scope.selectedParent = index

  $scope.unsetParent = ->
    $scope.mergeSet = []
    $scope.parentSelected = false
    $scope.selectedParent = null

  $scope.forMerge = (index) ->
    $scope.mergeSet.indexOf(index) isnt -1

  $scope.toggleMergeSet = (issue) ->
    if not $scope.forMerge issue then $scope.mergeSet.push(issue)
    else
      $scope.mergeSet = filterFilter($scope.mergeSet, "!#{issue}")

  $scope.doMerge = ->
    theParent = $scope.issues[$scope.selectedParent].issue
    if $scope.mergeSet.length > 0 and confirm "Do you want to merge\n\n #{$scope.mergeSet.join(', \n')} \n\n into #{theParent}"

      MergeService.mergeIssues theParent, $scope.mergeSet, $routeParams.court, $scope.legal_head, $scope.subject_matter
      .then (r)->
        if r.data.length is 2
          $alert
            title: 'Info:'
            content: "Records updated!"
            placement: 'top-right'
            type: 'info'
            duration: 3

          $scope.unsetParent()
          $scope.getIssues()
          $scope.fetchStandard()

  $scope.getIssues = ->
    AppServe.query { getIssues: true, court: $routeParams.court, legal_head: $scope.legal_head, subject: $scope.subject_matter }, (issues, headers) ->
      $scope.issues = issues

  $scope.unsetParent()
  $scope.getIssues()

  $scope.fetchStandard = ->
    $scope.standardIssues = AppServe.query { getSIssues: true, lh: $scope.legal_head, sm: $scope.subject_matter }
    $scope.sLegalHeads = AppServe.query { getStandard: true }

  $scope.fetchStandard()

  # Load Page
  $scope.pageChanged = ->
    $scope.getIssues $scope.currentPage

  $scope.showRatios = (issue, index) ->
    showAside = ->
      myAside = $aside
        title: "Principles Under Selected Issue"
        scope: $scope
        template: 'views/modal.html'
        show: false
        backdrop: 'static'

      myAside.$promise.then ->
        myAside.show()

    $scope.issue = issue
    if $scope.issues[index].principles?.length
      showAside()
    else
      AppServe.query { getPrinciples: true, issue: issue.issue, court: $routeParams.court, legal_head: $scope.legal_head, subject: $scope.subject_matter }, (principles) ->

        $scope.issues[index].principles = principles
        showAside()

  $scope.doDetach = (ratio, $index) ->
    $scope.cancelDetach()

    $scope.theIndex = $index
    $scope.issue.principles[$scope.theIndex].selected = true
    $scope.detachingRatio = true
    $scope.rModel = angular.copy ratio
    $scope.rModel.court = $routeParams.court

  $scope.cancelDetach = ->
    $scope.rModel = {}
    $scope.sSMs = []
    $scope.sIssues = []
    if $scope.theIndex isnt undefined then $scope.issue.principles[$scope.theIndex].selected = false
    $scope.detachingRatio = false

  $scope.lhChange = ->
    lh = $scope.rModel.newLegalHead
    $scope.sSMs = AppServe.query { getSubjectMatters: true, court: $routeParams.court, legal_head: lh }

  $scope.smChange = ->
    lh = $scope.rModel.newLegalHead
    sm = $scope.rModel.newSubjectMatter
    $scope.sIssues = AppServe.query { getIssues: true, court: $routeParams.court, legal_head: lh, subject: sm }

  $scope.detachRatio = (theForm) ->
    if theForm.$valid and confirm "Are you sure?"
      $scope.submitting = true

      if $scope.rModel.newIssue.issue?
        $scope.rModel.newIssue = $scope.rModel.newIssue.issue

      MergeService.detachRatio $scope.rModel
      .then ->
        $scope.detachingRatio = false
        $scope.issue.principles = _.filter $scope.issue.principles, (p)-> p.pk isnt $scope.rModel.pk
        $scope.getIssues()
        $scope.submitting = false
      , ->
        $scope.submitting = false
]