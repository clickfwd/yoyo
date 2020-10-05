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
				const componentElt = getComponentElt(elt)
				const componentName = getComponentName(componentElt)

				if (evt.detail.path === document.location.href) {
					evt.detail.path = 'render'
				}

				const action = '' + evt.detail.path
				evt.detail.parameters[
					'component'
				] = `${componentName}/${action}`
				evt.detail.path = YoyoFactory.url
			},
			serverComponentEventMiddleware(evt) {
				const componentElt = getComponentElt(evt.detail.target)
				const componentName = getComponentName(componentElt)
				const eventAttr = componentElt.getAttribute(
					'yoyo:transient-event'
				)

				if (!eventAttr) return

				const eventData = JSON.parse(eventAttr)

				if (eventData) {
					evt.detail.parameters[
						'component'
					] = `${componentName}/${eventData.name}`

					evt.detail.parameters = {
						...evt.detail.parameters,
						...{ eventParams: JSON.stringify(eventData.params) },
					}
				}
			},
			processEmitHeader(xhr) {
				if (xhr.getAllResponseHeaders().match(/Yoyo-Emit:/i)) {
					let events = JSON.parse(xhr.getResponseHeader('Yoyo-Emit'))
					clearComponentEventCache()
					events.forEach((event) => {
						this.triggerServerEmittedEvent(event)
					})
				}
			},
			triggerServerEmittedEvent(event) {
				const eventName = event.event
				const params = event.params
				const selector = event.selector || null
				const component = event.component || null
				const parentsOnly = event.parentsOnly || null
				let elements

				if (!selector && !component) {
					elements = getAllComponentsElt()
				} else if (selector) {
					if (parentsOnly) {
						elements = getParentComponents(selector)
					} else {
						elements = document.querySelectorAll(selector)
					}
				} else if (component) {
					elements = document.querySelectorAll(
						`[yoyo\\:name="${component}"]`
					)
				}

				if (elements) {
					elements.forEach((elt) => {
						if (shouldTriggerComponentEvent(elt.id)) {
							this.addServerEventTransient(elt, eventName, params)
							Yoyo.trigger(elt, `yoyo:${eventName}`)
						}
					})
				}
			},
			addServerEventTransient(elt, event, params) {
				// Check if component is listening for the event
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
			},
			removeServerEventTransient(elt) {
				elt.removeAttribute('yoyo:transient-event')
			},
		}

		/**
		 * Track elements with triggered events to only trigger the first one
		 * if multiple emits are found for the same element
		 */
		var componentEventCache = []

		function shouldTriggerComponentEvent(id) {
			if (componentEventCache.indexOf(id) === -1) {
				componentEventCache.push(id)
				return true
			}

			return false
		}

		function clearComponentEventCache() {
			componentEventCache = []
		}

		function getComponentElt(elt) {
			return elt.closest('[yoyo\\:name]')
		}

		function getAllComponentsElt() {
			return document.querySelectorAll('[yoyo\\:name]')
		}

		function getComponentName(elt) {
			return elt.getAttribute('yoyo:name')
		}

		function decodeHTMLEntities(text) {
			var textArea = document.createElement('textarea')
			textArea.innerHTML = text
			return textArea.value
		}

		function getParentComponents(selector) {
			let parent = getComponentElt(document.querySelector(selector))

			let ancestors = []

			while (parent) {
				ancestors.push(parent)
				parent = getComponentElt(parent.parentElement)
			}

			return ancestors
		}

		return YoyoFactory
	})()
})

Yoyo.defineExtension('yoyo', {
	onEvent: function (name, evt) {
		if (name === 'htmx:configRequest') {
			if (!evt.target) return

			YoyoFactory.bootstrap(evt)
			YoyoFactory.serverComponentEventMiddleware(evt)
		}

		if (name === 'htmx:beforeRequest') {
			if (!YoyoFactory.url) {
				console.error('The yoyo URL needs to be defined')
				evt.preventDefault()
			}
		}

		if (name === 'htmx:afterRequest') {
			if (!evt.target) return

			YoyoFactory.removeServerEventTransient(evt.target)
		}

		if (name === 'htmx:afterSettle') {
			if (!evt.target) return

			YoyoFactory.processEmitHeader(evt.detail.xhr)
		}
	},
})
