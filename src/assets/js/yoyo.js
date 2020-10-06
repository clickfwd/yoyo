;(function (global, factory) {
	if (typeof define === 'function' && define.amd) {
		define([], factory)
	} else {
		global.YoyoFactory = factory()
	}
})(typeof self !== 'undefined' ? self : this, function () {
	return (function () {
		'use strict'

		window.Yoyo = window.htmx

		var YoyoFactory = {
			url: null,
			bootstrap(evt) {
				const elt = evt.target
				const yoyoElt = getYoyoElt(elt)
				const yoyoName = getYoyoName(yoyoElt)

				if (evt.detail.path === document.location.href) {
					evt.detail.path = 'render'
				}

				const action = '' + evt.detail.path
				evt.detail.parameters['component'] = `${yoyoName}/${action}`
				evt.detail.path = YoyoFactory.url
			},
			serverYoyoEventMiddleware(evt) {
				const yoyoElt = getYoyoElt(evt.detail.target)
				const yoyoName = getYoyoName(yoyoElt)
				const eventAttr = yoyoElt.getAttribute('yoyo:transient-event')

				if (!eventAttr) return

				const eventData = JSON.parse(eventAttr)

				if (eventData) {
					evt.detail.parameters[
						'component'
					] = `${yoyoName}/${eventData.name}`

					evt.detail.parameters = {
						...evt.detail.parameters,
						...{
							eventParams: eventData.params
								? JSON.stringify(eventData.params)
								: [],
						},
					}
				}
			},
			processYoyoEmitHeader(xhr) {
				if (xhr.getAllResponseHeaders().match(/Yoyo-Emit:/i)) {
					let events = JSON.parse(xhr.getResponseHeader('Yoyo-Emit'))
					clearYoyoEventCache()
					events.forEach((event) => {
						triggerServerEmittedEvent(event)
					})
				}
			},
			processBrowserEventHeader(xhr) {
				if (xhr.getAllResponseHeaders().match(/Yoyo-Browser-Event:/i)) {
					let events = JSON.parse(
						xhr.getResponseHeader('Yoyo-Browser-Event')
					)
					events.forEach((event) => {
						window.dispatchEvent(
							new CustomEvent(event.event, {
								detail: event.params,
							})
						)
					})
				}
			},
			afterRequestActions(elt) {
				removeServerEventTransient(elt)
			},
		}

		/**
		 * Track elements receiving multiple emitted events to only trigger the first one
		 */
		var yoyoEventCache = []

		function shouldTriggerYoyoEvent(id) {
			if (yoyoEventCache.indexOf(id) === -1) {
				yoyoEventCache.push(id)
				return true
			}

			return false
		}

		function clearYoyoEventCache() {
			yoyoEventCache = []
		}

		function getYoyoElt(elt) {
			return elt.closest('[yoyo\\:name]')
		}

		function getAllYoyoElts() {
			return document.querySelectorAll('[yoyo\\:name]')
		}

		function getYoyoName(elt) {
			return elt.getAttribute('yoyo:name')
		}

		function decodeHTMLEntities(text) {
			var textArea = document.createElement('textarea')
			textArea.innerHTML = text
			return textArea.value
		}

		function getAncestorYoyoElts(selector) {
			let ancestor = getYoyoElt(document.querySelector(selector))

			let ancestors = []

			while (ancestor) {
				ancestors.push(ancestor)
				ancestor = getYoyoElt(ancestor.parentElement)
			}

			return ancestors
		}

		function addServerEventTransient(elt, event, params) {
			// Check if Yoyo component is listening for the event
			let componentListeningFor = elt
				.getAttribute('hx-trigger')
				.split(',')
				.filter((name) => name.trim())

			if (componentListeningFor.indexOf(`yoyo:${event}`) === -1) {
				return
			}

			elt.setAttribute(
				'yoyo:transient-event',
				JSON.stringify({ name: event, params: params })
			)
		}

		function triggerServerEmittedEvent(event) {
			const eventName = event.event
			const params = event.params
			const selector = event.selector || null
			const yoyoName = event.component || null
			const ancestorsOnly = event.ancestorsOnly || null
			let elements

			if (!selector && !yoyoName) {
				elements = getAllYoyoElts()
			} else if (selector) {
				if (ancestorsOnly) {
					elements = getAncestorYoyoElts(selector)
				} else {
					elements = document.querySelectorAll(selector)
				}
			} else if (yoyoName) {
				elements = document.querySelectorAll(
					`[yoyo\\:name="${yoyoName}"]`
				)
			}

			if (elements) {
				elements.forEach((elt) => {
					if (shouldTriggerYoyoEvent(elt.id)) {
						addServerEventTransient(elt, eventName, params)
						Yoyo.trigger(elt, `yoyo:${eventName}`)
					}
				})
			}
		}

		function removeServerEventTransient(elt) {
			elt.removeAttribute('yoyo:transient-event')
		}

		return YoyoFactory
	})()
})

Yoyo.defineExtension('yoyo', {
	onEvent: function (name, evt) {
		if (name === 'htmx:configRequest') {
			if (!evt.target) return

			YoyoFactory.bootstrap(evt)
			YoyoFactory.serverYoyoEventMiddleware(evt)
		}

		if (name === 'htmx:beforeRequest') {
			if (!YoyoFactory.url) {
				console.error('The yoyo URL needs to be defined')
				evt.preventDefault()
			}
		}

		if (name === 'htmx:afterRequest') {
			if (!evt.target) return

			YoyoFactory.afterRequestActions(evt.target)
		}

		if (name === 'htmx:beforeSwap') {
			if (!evt.target) return

			YoyoFactory.processBrowserEventHeader(evt.detail.xhr)
		}

		if (name === 'htmx:afterSettle') {
			if (!evt.target) return

			YoyoFactory.processYoyoEmitHeader(evt.detail.xhr)
		}
	},
})
