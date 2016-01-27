// Generated by CoffeeScript 1.9.2
(function() {
  angular.module('metaEditor').controller('LegalHeadsCtrl', [
    '$scope', '$routeParams', 'LP', 'AppServe', '$location', '$alert', function($scope, $routeParams, LP, AppServe, $location, $alert) {
      $scope.title = LP[$routeParams.court];
      $scope.rowActive = false;
      $scope.getLegalHeads = function() {
        AppServe.query({
          getHeads: true,
          court: $routeParams.court
        }, function(response) {
          return $scope.legalHeads = response.records;
        });
        return AppServe.query({
          getStandard: true
        }, function(response) {
          return $scope.sLegalHeads = response.records;
        });
      };
      $scope.activateRow = function(lH, index) {
        if ($scope.activeRow === index) {
          $scope.rowActive = false;
          return $scope.activeRow = void 0;
        } else {
          $scope.rowActive = true;
          return $scope.activeRow = index;
        }
      };
      $scope.doReplace = function(lh) {
        var old;
        if ($scope.activeRow !== void 0) {
          old = $scope.legalHeads[$scope.activeRow].legalHead;
          if (confirm("Replace " + $scope.legalHeads[$scope.activeRow].legalHead + " with " + lh + "?")) {
            return AppServe.query({
              doReplace: true,
              old: old,
              "new": lh,
              court: $routeParams.court
            }).$promise.then(function(response) {
              $alert({
                title: 'Info:',
                content: "Records updated!",
                placement: 'top-right',
                type: 'info',
                duration: 3
              });
              return $scope.getLegalHeads();
            });
          }
        }
      };
      $scope.showSubjectMatter = function(l) {
        return $location.path($routeParams.court + "/" + l.hexEncode());
      };
      return $scope.getLegalHeads();
    }
  ]).controller('LegalHeadCtrl', [
    '$scope', '$routeParams', 'LP', 'AppServe', 'filterFilter', 'MergeService', '$location', '$alert', 'CONF', function($scope, $routeParams, LP, AppServe, filterFilter, MergeService, $location, $alert, CONF) {
      $scope.legal_head = $routeParams.legal_head.hexDecode();
      $scope.title = $scope.legal_head + " - " + LP[$routeParams.court];
      $scope.mergeSet = [];
      $scope.popover = {};
      $scope.currentSm = $scope.selectedIndex = void 0;
      $scope.legalHeads = AppServe.query({
        getStandard: true
      });
      $scope.changeLH = function(newLh, selectedSM) {
        var selectedSubjectMatter;
        selectedSubjectMatter = "" + selectedSM;
        if (confirm("Change the Legal Head for " + selectedSM + " from " + $scope.legal_head + " to " + newLh)) {
          return MergeService.changeLegalHead(selectedSubjectMatter, $scope.legal_head, newLh, $routeParams.court).then(function(r) {
            var c;
            if (r.data.length === 1) {
              c = r.data[0];
              $alert({
                title: 'Info:',
                content: "Update Complete",
                placement: 'top-right',
                type: 'info',
                duration: 3
              });
              return $scope.getSubjectMatters();
            }
          });
        }
      };
      $scope.updateContent = function(newSubjectMatter) {
        if ($scope.currentSm !== void 0 && confirm("Change " + $scope.currentSm + " to " + newSubjectMatter + "?")) {
          MergeService.updateSubjectMatter($scope.currentSm, newSubjectMatter, $routeParams.court, $scope.legal_head).then(function(response) {
            var c;
            if (response.data.length === 1) {
              c = response.data[0];
              $alert({
                title: 'Info:',
                content: "Update Complete",
                placement: 'top-right',
                type: 'info',
                duration: 3
              });
              return $scope.getSubjectMatters();
            }
          });
        }
        return $scope.currentSm = $scope.selectedIndex = void 0;
      };
      $scope.toggleCurrent = function(sm, index) {
        if ($scope.currentSm === void 0 || $scope.currentSm !== sm) {
          $scope.currentSm = sm;
          return $scope.selectedIndex = index;
        } else {
          return $scope.selectedIndex = $scope.currentSm = void 0;
        }
      };
      $scope.setParent = function(sm, index) {
        $scope.parentSelected = true;
        return $scope.selectedParent = index;
      };
      $scope.unsetParent = function() {
        $scope.mergeSet = [];
        $scope.parentSelected = false;
        return $scope.selectedParent = null;
      };
      $scope.forMerge = function(index) {
        return $scope.mergeSet.indexOf(index) !== -1;
      };
      $scope.toggleMergeSet = function(sm) {
        if (!$scope.forMerge(sm)) {
          return $scope.mergeSet.push(sm);
        } else {
          return $scope.mergeSet = filterFilter($scope.mergeSet, "!" + sm);
        }
      };
      $scope.doMerge = function() {
        var theParent;
        theParent = $scope.subjectMatters[$scope.selectedParent].subject_matter;
        if ($scope.mergeSet.length > 0 && confirm($routeParams.court + " Do you want to merge\n\n " + ($scope.mergeSet.join(', \n')) + " \n\n into " + theParent)) {
          return MergeService.mergeSubjectMatters(theParent, $scope.mergeSet, $routeParams.court, $scope.legal_head).then(function(r) {
            var c;
            if (r.data.length === 1) {
              c = r.data[0];
              $alert({
                title: 'Info:',
                content: c.affectedRows + " records matched. " + c.changedRows + " records updated!",
                placement: 'top-right',
                type: 'info',
                duration: 3
              });
              $scope.unsetParent();
              return $scope.getSubjectMatters();
            }
          });
        }
      };
      $scope.setStandard = function(selectedMatter) {
        if (selectedMatter.standard === "0" && confirm("Are you sure you want to make \n\n " + selectedMatter.subjectMatter + " a Standard Subject Matter?")) {
          return MergeService.setStandardSM(selectedMatter.subjectMatter, $scope.legal_head).then(function(response) {
            var c;
            if (response.data.length === 1) {
              c = response.data[0];
              $alert({
                title: 'Info:',
                content: c.affectedRows + " records matched. " + c.changedRows + " records added!",
                placement: 'top-right',
                type: 'info',
                duration: 3
              });
              return $scope.fetchStandard();
            }
          });
        }
      };
      $scope.perPage = 2;
      $scope.getSubjectMatters = function(page) {
        return AppServe.query({
          getSubjectMatters: true,
          court: $routeParams.court,
          legal_head: $scope.legal_head,
          page: page,
          per_page: $scope.perPage
        }, function(sm) {
          return $scope.subjectMatters = sm.records;
        });
      };
      $scope.fetchStandard = function() {
        return AppServe.query({
          getSSubjectMatters: true,
          lh: $scope.legal_head
        }, function(response) {
          return $scope.standardSubjectMatters = response.records;
        });
      };
      $scope.showIssues = function(sm) {
        var curPath;
        curPath = $location.path();
        return $location.path(curPath + "/" + sm.hexEncode());
      };
      $scope.unsetParent();
      $scope.getSubjectMatters(1);
      $scope.fetchStandard();
      return $scope.pageChanged = function() {
        return $scope.getSubjectMatters($scope.currentPage);
      };
    }
  ]).controller('IssuesCtrl', [
    '$scope', '$routeParams', 'LP', 'AppServe', 'filterFilter', 'MergeService', '$location', '$alert', '$aside', 'CONF', function($scope, $routeParams, LP, AppServe, filterFilter, MergeService, $location, $alert, $aside, CONF) {
      $scope.legal_head = $routeParams.legal_head.hexDecode();
      $scope.subject_matter = $routeParams.subject.hexDecode();
      $scope.title = $scope.subject_matter + " - " + $scope.legal_head + " - " + LP[$routeParams.court];
      $scope.mergeSet = [];
      $scope.popover = {};
      $scope.perPage = 10;
      $scope.currentIss = $scope.selectedIndex = void 0;
      $scope.subjectMatters = AppServe.query({
        getSubjectMatters: true,
        court: $routeParams.court,
        legal_head: $scope.legal_head
      });
      $scope.changeSubjectMatter = function(newSubjectMatter, selectedIssue) {
        var msg;
        selectedIssue = "" + selectedIssue;
        msg = "Change the Subject Matter for \n\n" + selectedIssue + " from \n\n" + $scope.subject_matter + " to \n\n" + newSubjectMatter;
        if (confirm(msg)) {
          return MergeService.changeSM(selectedIssue, $scope.subject_matter, newSubjectMatter, $scope.legal_head, $routeParams.court).then(function(r) {
            if (r.data.length === 1) {
              $alert({
                title: 'Info:',
                content: "Update Complete",
                placement: 'top-right',
                type: 'info',
                duration: 3
              });
              return $scope.getIssues();
            }
          });
        }
      };
      $scope.setStandard = function(selectedIssue) {
        if (!selectedIssue.standard && confirm("Are you sure you want to make \n\n " + selectedIssue.issue + " a Standard Issue?")) {
          return MergeService.setStandardIssue(selectedIssue.issue, $scope.subject_matter, $scope.legal_head).then(function(response) {
            var c;
            if (response.data.length === 1) {
              c = response.data[0];
              $alert({
                title: 'Info:',
                content: c.affectedRows + " records matched. " + c.changedRows + " records added!",
                placement: 'top-right',
                type: 'info',
                duration: 3
              });
              return $scope.fetchStandard();
            }
          });
        }
      };
      $scope.updateContent = function(newIssue) {
        if ($scope.currentIss !== void 0 && confirm("Change " + $scope.currentIss + " to " + newIssue + "?")) {
          MergeService.updateIssue($scope.currentIss, newIssue, $routeParams.court, $scope.legal_head, $scope.subject_matter).then(function(response) {
            var c;
            if (response.data.length === 1) {
              c = response.data[0];
              $alert({
                title: 'Info:',
                content: "Update Complete",
                placement: 'top-right',
                type: 'info',
                duration: 3
              });
              return $scope.getIssues();
            }
          });
        }
        return $scope.currentIss = $scope.selectedIndex = void 0;
      };
      $scope.toggleCurrent = function(iss, index) {
        if ($scope.currentIss === void 0 || $scope.currentIss !== iss) {
          $scope.currentIss = iss;
          return $scope.selectedIndex = index;
        } else {
          return $scope.selectedIndex = $scope.currentIss = void 0;
        }
      };
      $scope.setParent = function(sm, index) {
        $scope.parentSelected = true;
        return $scope.selectedParent = index;
      };
      $scope.unsetParent = function() {
        $scope.mergeSet = [];
        $scope.parentSelected = false;
        return $scope.selectedParent = null;
      };
      $scope.forMerge = function(index) {
        return $scope.mergeSet.indexOf(index) !== -1;
      };
      $scope.toggleMergeSet = function(issue) {
        if (!$scope.forMerge(issue)) {
          return $scope.mergeSet.push(issue);
        } else {
          return $scope.mergeSet = filterFilter($scope.mergeSet, "!" + issue);
        }
      };
      $scope.doMerge = function() {
        var theParent;
        theParent = $scope.issues[$scope.selectedParent].issue;
        if ($scope.mergeSet.length > 0 && confirm("Do you want to merge\n\n " + ($scope.mergeSet.join(', \n')) + " \n\n into " + theParent)) {
          return MergeService.mergeIssues(theParent, $scope.mergeSet, $routeParams.court, $scope.legal_head, $scope.subject_matter).then(function(r) {
            var c;
            if (r.data.length === 1) {
              c = r.data[0];
              $alert({
                title: 'Info:',
                content: c.affectedRows + " records matched. " + c.changedRows + " records updated!",
                placement: 'top-right',
                type: 'info',
                duration: 3
              });
              $scope.unsetParent();
              return $scope.getIssues();
            }
          });
        }
      };
      $scope.getIssues = function(page) {
        return AppServe.query({
          getIssues: true,
          court: $routeParams.court,
          legal_head: $scope.legal_head,
          subject: $scope.subject_matter,
          page: page,
          per_page: $scope.perPage
        }, function(issues, headers) {
          $scope.totalResults = headers(CONF.countHeader);
          $scope.currentPage = headers(CONF.pageHeader);
          $scope.pages = Math.ceil($scope.totalResults / $scope.perPage);
          $scope.issues = issues;
          return _.each($scope.issues, function(issue, index) {
            return $scope.issues[index].principles = AppServe.query({
              getPrinciples: true,
              issue: issue.issue,
              court: $routeParams.court,
              legal_head: $scope.legal_head,
              subject: $scope.subject_matter
            });
          });
        });
      };
      $scope.unsetParent();
      $scope.getIssues(1);
      $scope.fetchStandard = function() {
        $scope.standardIssues = AppServe.query({
          getSIssues: true,
          lh: $scope.legal_head,
          sm: $scope.subject_matter
        });
        return $scope.sLegalHeads = AppServe.query({
          getStandard: true
        });
      };
      $scope.fetchStandard();
      $scope.pageChanged = function() {
        return $scope.getIssues($scope.currentPage);
      };
      $scope.showRatios = function(issue) {
        var myAside;
        $scope.issue = issue;
        myAside = $aside({
          title: "Principles Under Selected Issue",
          scope: $scope,
          template: 'views/modal.html',
          show: false,
          backdrop: 'static'
        });
        return myAside.$promise.then(function() {
          return myAside.show();
        });
      };
      $scope.doDetach = function(ratio, $index) {
        $scope.cancelDetach();
        $scope.theIndex = $index;
        $scope.issue.principles[$scope.theIndex].selected = true;
        $scope.detachingRatio = true;
        $scope.rModel = angular.copy(ratio);
        return $scope.rModel.court = $routeParams.court;
      };
      $scope.cancelDetach = function() {
        $scope.rModel = {};
        $scope.sSMs = [];
        $scope.sIssues = [];
        if ($scope.theIndex !== void 0) {
          $scope.issue.principles[$scope.theIndex].selected = false;
        }
        return $scope.detachingRatio = false;
      };
      $scope.lhChange = function() {
        var lh;
        lh = $scope.rModel.newLegalHead;
        return $scope.sSMs = AppServe.query({
          getSubjectMatters: true,
          court: $routeParams.court,
          legal_head: lh
        });
      };
      $scope.smChange = function() {
        var lh, sm;
        lh = $scope.rModel.newLegalHead;
        sm = $scope.rModel.newSubjectMatter;
        return $scope.sIssues = AppServe.query({
          getIssues: true,
          court: $routeParams.court,
          legal_head: lh,
          subject: sm
        });
      };
      return $scope.detachRatio = function(theForm) {
        if (theForm.$valid && confirm("Are you sure?")) {
          $scope.submitting = true;
          if ($scope.rModel.newIssue.issue != null) {
            $scope.rModel.newIssue = $scope.rModel.newIssue.issue;
          }
          return MergeService.detachRatio($scope.rModel).then(function() {
            $scope.detachingRatio = false;
            $scope.issue.principles = _.filter($scope.issue.principles, function(p) {
              return p.pk !== $scope.rModel.pk;
            });
            $scope.getIssues();
            return $scope.submitting = false;
          }, function() {
            return $scope.submitting = false;
          });
        }
      };
    }
  ]);

}).call(this);
