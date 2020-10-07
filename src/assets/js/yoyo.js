;(function (global, factory) {
	if (typeof define === 'function' && define.amd) {
		define([], factory)
	} else {
		global.Yoyo = factory()
	}
})(typeof self !== 'undefined' ? self : this, function () {
	return (function () {
		'use strict'

		window.YoyoEngine = window.htmx

		var Yoyo = {
			url: null,
			config(options) {
				Object.keys(options).forEach((key) => {
					YoyoEngine.config[key] = options[key]
				})
			},
			on(name, callback) {
				YoyoEngine.on(window, `yoyo:${name}`, (event) => {
					delete event.detail.elt
					callback(
						event.detail.length > 1 ? event.detail : event.detail[0]
					)
				})
			},
			bootstrap(evt) {
				const elt = evt.target
				const yoyoElt = getYoyoElt(elt)
				const yoyoName = getYoyoName(yoyoElt)

				if (evt.detail.path === document.location.href) {
					evt.detail.path = 'render'
				}

				const action = '' + evt.detail.path
				evt.detail.parameters['component'] = `${yoyoName}/${action}`
				evt.detail.path = Yoyo.url
			},
			yoyoRequestMiddleware(evt) {
				eventsMiddleware(evt)
			},
			processRedirectHeader(xhr) {
				if (xhr.getAllResponseHeaders().match(/Yoyo-Redirect:/i)) {
					const url = xhr.getResponseHeader('Yoyo-Redirect')
					if (url) {
						window.location = url
					}
				}
			},
			processEmitHeader(xhr) {
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

		function eventsMiddleware(evt) {
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
						YoyoEngine.trigger(elt, `yoyo:${eventName}`, params)
					}
				})
			}
		}

		function removeServerEventTransient(elt) {
			elt.removeAttribute('yoyo:transient-event')
		}

		return Yoyo
	})()
})

YoyoEngine.defineExtension('yoyo', {
	onEvent: function (name, evt) {
		if (name === 'htmx:configRequest') {
			if (!evt.target) return

			Yoyo.bootstrap(evt)
			Yoyo.yoyoRequestMiddleware(evt)
		}

		if (name === 'htmx:beforeRequest') {
			if (!Yoyo.url) {
				console.error('The yoyo URL needs to be defined')
				evt.preventDefault()
			}
		}

		if (name === 'htmx:afterRequest') {
			if (!evt.target) return

			Yoyo.afterRequestActions(evt.target)
		}

		if (name === 'htmx:beforeSwap') {
			if (!evt.target) return

			Yoyo.processBrowserEventHeader(evt.detail.xhr)
		}

		if (name === 'htmx:afterSettle') {
			if (!evt.target) return

			Yoyo.processRedirectHeader(evt.detail.xhr)
			Yoyo.processEmitHeader(evt.detail.xhr)
		}
	},
})
