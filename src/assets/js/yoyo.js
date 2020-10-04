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
				const componentElt = elt.closest('[yoyo\\:name]')
				const componentName = componentElt.getAttribute('yoyo:name')

				if (evt.detail.path === document.location.href) {
					evt.detail.path = 'render'
				}

				const action = '' + evt.detail.path
				evt.detail.parameters[
					'component'
				] = `${componentName}/${action}`
				evt.detail.path = YoyoFactory.url
			},
			triggerServerEmittedEvent(eventName, detail) {
				let elements
				const selector = detail.selector
				const component = detail.component
				const parentsOnly = detail.parentsOnly
				if (!selector && !component) {
					Yoyo.trigger(document, eventName, detail)
					return
				}

				if (selector) {
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

				;(elements || []).forEach((elt) => {
					// ServerEventName doesn't need the `yoyo` namespace prefix
					const serverEventName = eventName.split('yoyo:').slice(1).join()
					YoyoFactory.addServerEventTransient(elt, serverEventName, detail.params)
					Yoyo.trigger(elt, eventName, detail);
				})
			},
			addServerEventTransient(elt, eventName, params) {
				elt.setAttribute('yoyo:transient-event', JSON.stringify({name: eventName, params: params}));
			},
			removeServerEventTransient(elt) {
				elt.removeAttribute('yoyo:transient-event')
			},
			processServerEvent(evt) {
				const componentElt = Yoyo.closest(
					evt.detail.elt,
					'[yoyo\\:transient-event]'
				)
				
				if (!componentElt) {
					return;
				}

				const eventData = JSON.parse(componentElt.getAttribute('yoyo:transient-event'))

				if (eventData) {
					const eventVars = eventData.params;

					evt.detail.parameters['component'] = `counter/${eventData.name}`

					evt.detail.parameters = {...evt.detail.parameters, ...eventVars}
				}
			},
		}

		function decodeHTMLEntities(text) {
			var textArea = document.createElement('textarea')
			textArea.innerHTML = text
			return textArea.value
		}

		function getParentComponents(selector) {
			let parent = document
				.querySelector(selector)
				.parentElement.closest('[yoyo\\:name]')

			let ancestors = []

			while (parent) {
				ancestors.push(parent)
				parent = parent.parentElement.closest('[yoyo\\:name]')
			}

			return ancestors
		}

		return YoyoFactory
	})()
})

Yoyo.defineExtension('yoyo', {
	onEvent: function (name, evt) {

		if (name === 'htmx:configRequest') {
			if (evt.target) {
				YoyoFactory.bootstrap(evt)
				YoyoFactory.processServerEvent(evt)
			}
		}

		if (name === 'htmx:beforeRequest') {
			if (!YoyoFactory.url) {
				console.error('The yoyo URL needs to be defined')
				evt.preventDefault()
			}
		}

		if (name === 'htmx:afterRequest') {
			if (!evt.target) return;

			YoyoFactory.removeServerEventTransient(evt.target)
		}

		// Process Yoyo server-emitted events targeted at Yoyo components

		const eventParts = name.split(':')

		// Using `events:yoyo` namespace so Yoyo can trigger the events by itself
		// and target Yoyo components listening on the `yoyo` namespace

		if (`${eventParts[0]}:${eventParts[1]}` === 'events:yoyo') {
			const eventName = eventParts.slice(1).join(':')
			YoyoFactory.triggerServerEmittedEvent(eventName, evt.detail)
		}
	},
})
