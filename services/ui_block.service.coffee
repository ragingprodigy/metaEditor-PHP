'use strict'

angular.module 'metaEditor'

.service 'uiBlock', ->

  block: (element, message) ->
    msg = if message? then message else 'Processing...'
    element = if element? then element else '.body'

    window.localStorage.setItem "blockedElement", element

    $( ->
      angular.element(element).block message: "<div class='alert alert-info'>#{msg}</div>", css: height: "38px", border: ""
    )

  clear: ->
    c = window.localStorage.getItem "blockedElement"
    if c? then angular.element(c).unblock()
