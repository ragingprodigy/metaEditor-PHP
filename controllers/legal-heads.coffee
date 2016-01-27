angular.module 'metaEditor'

.controller 'LegalHeadsCtrl', ['$scope', '$routeParams', 'LP', 'AppServe', '$location', '$alert', ($scope, $routeParams, LP, AppServe, $location, $alert )->
  $scope.title = LP[$routeParams.court]
  $scope.rowActive = false

  $scope.getLegalHeads = ->
    AppServe.query { getHeads: true, court: $routeParams.court }, (response) ->
      $scope.legalHeads = response.records

    AppServe.query { getStandard: true }, (response) ->
      $scope.sLegalHeads = response.records

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
        .$promise.then (response) ->
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
        if r.data.length is 1
          c = r.data[0]
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
        if response.data.length is 1
          c = response.data[0]
          $alert
            title: 'Info:'
            content: "Update Complete"
            placement: 'top-right'
            type: 'info'
            duration: 3

          $scope.getSubjectMatters()

    $scope.currentSm = $scope.selectedIndex = undefined

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
    theParent = $scope.subjectMatters[$scope.selectedParent].subject_matter
    if $scope.mergeSet.length > 0 and confirm "#{$routeParams.court} Do you want to merge\n\n #{$scope.mergeSet.join(', \n')} \n\n into #{theParent}"

      MergeService.mergeSubjectMatters theParent, $scope.mergeSet, $routeParams.court, $scope.legal_head
      .then (r)->
        if r.data.length is 1
          c = r.data[0]
          $alert {
            title: 'Info:'
            content: "#{c.affectedRows} records matched. #{c.changedRows} records updated!"
            placement: 'top-right'
            type: 'info'
            duration: 3
          }
          $scope.unsetParent()
          $scope.getSubjectMatters()

  $scope.setStandard = (selectedMatter) ->
    if selectedMatter.standard is "0" and confirm "Are you sure you want to make \n\n #{selectedMatter.subjectMatter} a Standard Subject Matter?"
      MergeService.setStandardSM selectedMatter.subjectMatter, $scope.legal_head
      .then (response) ->
        if response.data.length is 1
          c = response.data[0]
          $alert {
            title: 'Info:'
            content: "#{c.affectedRows} records matched. #{c.changedRows} records added!"
            placement: 'top-right'
            type: 'info'
            duration: 3
          }
          $scope.fetchStandard()

  $scope.perPage = 2

  $scope.getSubjectMatters = (page) ->
    AppServe.query { getSubjectMatters: true, court: $routeParams.court, legal_head: $scope.legal_head, page: page, per_page: $scope.perPage }, (sm) ->
      $scope.subjectMatters  = sm.records

  $scope.fetchStandard = ->
    AppServe.query { getSSubjectMatters: true, lh: $scope.legal_head }, (response) ->
      $scope.standardSubjectMatters = response.records

  $scope.showIssues = (sm) ->
    curPath = $location.path()
    $location.path curPath+"/"+sm.hexEncode()

  $scope.unsetParent()
  $scope.getSubjectMatters 1
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
        if r.data.length is 1
          $alert
            title: 'Info:'
            content: "Update Complete"
            placement: 'top-right'
            type: 'info'
            duration: 3

          $scope.getIssues()

  $scope.setStandard = (selectedIssue) ->
    if not selectedIssue.standard and confirm "Are you sure you want to make \n\n #{selectedIssue.issue} a Standard Issue?"
      MergeService.setStandardIssue selectedIssue.issue, $scope.subject_matter, $scope.legal_head
      .then (response) ->
        if response.data.length is 1
          c = response.data[0]
          $alert {
            title: 'Info:'
            content: "#{c.affectedRows} records matched. #{c.changedRows} records added!"
            placement: 'top-right'
            type: 'info'
            duration: 3
          }
          $scope.fetchStandard()

  $scope.updateContent = (newIssue)->
    if $scope.currentIss isnt undefined and confirm "Change #{$scope.currentIss} to #{newIssue}?"
      MergeService.updateIssue $scope.currentIss, newIssue, $routeParams.court, $scope.legal_head, $scope.subject_matter
      .then (response) ->
        if response.data.length is 1
          c = response.data[0]
          $alert
            title: 'Info:'
            content: "Update Complete"
            placement: 'top-right'
            type: 'info'
            duration: 3

          $scope.getIssues()

    $scope.currentIss = $scope.selectedIndex = undefined

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
        if r.data.length is 1
          c = r.data[0]
          $alert {
            title: 'Info:'
            content: "#{c.affectedRows} records matched. #{c.changedRows} records updated!"
            placement: 'top-right'
            type: 'info'
            duration: 3
          }
          $scope.unsetParent()
          $scope.getIssues()

  $scope.getIssues = (page) ->
    AppServe.query { getIssues: true, court: $routeParams.court, legal_head: $scope.legal_head, subject: $scope.subject_matter, page: page, per_page: $scope.perPage }, (issues, headers) ->
      $scope.totalResults = headers CONF.countHeader
      $scope.currentPage = headers CONF.pageHeader
      $scope.pages = Math.ceil($scope.totalResults / $scope.perPage)

      $scope.issues = issues
      _.each $scope.issues, (issue, index) ->
        $scope.issues[index].principles = AppServe.query { getPrinciples: true, issue: issue.issue, court: $routeParams.court, legal_head: $scope.legal_head, subject: $scope.subject_matter }

  $scope.unsetParent()
  $scope.getIssues 1

  $scope.fetchStandard = ->
    $scope.standardIssues = AppServe.query { getSIssues: true, lh: $scope.legal_head, sm: $scope.subject_matter }
    $scope.sLegalHeads = AppServe.query { getStandard: true }

  $scope.fetchStandard()

  # Load Page
  $scope.pageChanged = ->
    $scope.getIssues $scope.currentPage

  $scope.showRatios = (issue) ->
    $scope.issue = issue

    myAside = $aside
      title: "Principles Under Selected Issue"
      scope: $scope
      template: 'views/modal.html'
      show: false
      backdrop: 'static'

    myAside.$promise.then ->
      myAside.show()

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