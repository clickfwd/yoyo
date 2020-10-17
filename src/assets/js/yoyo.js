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
				YoyoEngine.on(window, name, (event) => {
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

				const action = getActionAndParseArguments(evt.detail)

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

		function getActionAndParseArguments(detail) {
			let path = detail.path
			const match = path.match(/(.*)\((.*)\)/)

			if (match) {
				path = match[1]
				detail.parameters['actionArgs'] = match[2]
			}

			const action = '' + path
			return action
		}

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
			const yoyoElt = getYoyoElt(evt.detail.elt)
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

			if (componentListeningFor.indexOf(event) === -1) {
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
					elements = [document.querySelector(selector)]
				}
			} else if (yoyoName) {
				elements = document.querySelectorAll(
					`[yoyo\\:name="${yoyoName}"]`
				)
			}

			if (elements) {
				elements.forEach((elt) => {
					if (shouldTriggerYoyoEvent(elt.id)) {
						addServerEventTransient(
							getYoyoElt(elt),
							eventName,
							params
						)
						YoyoEngine.trigger(elt, eventName, params)
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
		if (name === 'htmx:processedNode') {
			// For requests targeting a specific ID, create and append to body if not present
			const targetId = evt.srcElement.getAttribute('hx-target')
			if (
				targetId &&
				targetId[0] == '#' &&
				document.querySelector(targetId) === null
			) {
				let targetDiv = document.createElement('div')
				targetDiv.setAttribute('id', targetId.replace('#', ''))
				document.body.appendChild(targetDiv)
			}
		}

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

		if (name === 'htmx:afterOnLoad') {
			if (!evt.target) {
				return
			}

			setTimeout(() => {
				Yoyo.afterRequestActions(evt.target)
			}, 125)

			// afterSwap and afterSettle events are not triggered for targets different than the Yoyo component
			// so we run those actions here
			if (
				evt.target !== evt.detail.target ||
				evt.detail.xhr.status == 204
			) {
				Yoyo.processEmitHeader(evt.detail.xhr)
				Yoyo.processBrowserEventHeader(evt.detail.xhr)
				Yoyo.processRedirectHeader(evt.detail.xhr)
			}
		}

		if (name === 'htmx:beforeSwap') {
			if (!evt.target) return

			Yoyo.processBrowserEventHeader(evt.detail.xhr)
		}

		if (name === 'htmx:afterSettle') {
			if (!evt.target) return

			Yoyo.processEmitHeader(evt.detail.xhr)
			Yoyo.processRedirectHeader(evt.detail.xhr)
		}
	},
})
