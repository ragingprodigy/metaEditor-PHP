angular.module 'metaEditor'

.constant 'LP',
  sc: 'Supreme Court'
  ca: 'Court of Appeal'
  fhc: 'Federal High Court'
  tat: 'Tax Appeal Tribunal'
  nic: 'National Industrial Court'
  uk: 'United Kingdom'

.factory 'AppServe', ['$resource', ($resource)->
  $resource '/api/v1/legalHeads/:_id', null,
    query:
      isArray: false
]

.factory 'MergeService', ['$http', ($http)->
  mService =
    url: '/api/v1/'
    mergeSubjectMatters: ( parent, mergeSet, court, legal_head ) ->
      $http.post this.url+'mergeSubjectMatters', { parent: parent, mergeSet: mergeSet, court: court, lh: legal_head }

    mergeIssues: ( parent, mergeSet, court, legal_head, subject_matter ) ->
      $http.post this.url+'mergeIssues', { parent: parent, mergeSet: mergeSet, court: court, lh: legal_head, sm: subject_matter }

    updateSubjectMatter: ( old, newSM, court, legal_head ) ->
      $http.post this.url+'updateSubjectMatter', { old: old, new: newSM, court: court, lh: legal_head }

    updateIssue: ( old, newIssue, court, legal_head, subject_matter ) ->
      $http.post this.url+'updateIssue', { old: old, new: newIssue, court: court, lh: legal_head, sm: subject_matter }

    setStandardSM: ( subject_matter, legal_head ) ->
      $http.post this.url+'setStandard', { lh: legal_head, sm: subject_matter, subject_matter: true }

    setStandardIssue: ( issue, subject_matter, legal_head ) ->
      $http.post this.url+'setStandard', { iss: issue, lh: legal_head, sm: subject_matter, issue: true }

    changeLegalHead: ( subject_matter, current_legal_head, new_legal_head, court ) ->
      $http.post this.url+'changeLegalHead', { old: current_legal_head, new: new_legal_head, sm: subject_matter, court: court }

    changeSM: ( issue, current_sm, new_sm, legal_head, court ) ->
      $http.post this.url+'changeSubjectMatter', { old: current_sm, new: new_sm, issue: issue, lh: legal_head, court: court }

    detachRatio: (ratio) ->
      $http.post this.url+'detachRatio', ratio

  mService
]