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
			processedNode(evt) {
				// Dynamically create non-existent target IDs by appending them to document body
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

				if (!evt.srcElement || !isComponent(evt.srcElement)) {
					return
				}

				initializeComponentSpinners(getComponent(evt.srcElement))
			},
			bootstrapRequest(evt) {
				const elt = evt.target
				let component = getComponent(elt)
				const componentName = getComponentName(component)

				if (evt.detail.path === document.location.href) {
					evt.detail.path = 'render'
				}

				const action = getActionAndParseArguments(evt.detail)

				evt.detail.parameters[
					'component'
				] = `${componentName}/${action}`
				evt.detail.path = Yoyo.url

				// Make request info available to other events
				component.__yoyo_action = action
			},
			middleware(evt) {
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
				let component = getComponent(elt)
				delete component.__yoyo_action
			},
			spinningStart(evt) {
				let component = getComponent(evt.detail.elt)
				const yoyoId = component.id

				if (!yoyoSpinners[yoyoId]) {
					return
				}

				let spinningElts = yoyoSpinners[yoyoId].generic || []

				spinningElts = spinningElts.concat(
					yoyoSpinners[yoyoId]?.actions[component.__yoyo_action] || []
				)

				spinningElts.forEach((directive) => {
					const spinnerElt = directive.elt
					if (directive.modifiers.includes('class')) {
						let classes = directive.value.split(' ').filter(Boolean)

						doAndSetCallbackOnElToUndo(
							component,
							directive,
							() => directive.elt.classList.add(...classes),
							() => spinnerElt.classList.remove(...classes)
						)
					} else if (directive.modifiers.includes('attr')) {
						doAndSetCallbackOnElToUndo(
							component,
							directive,
							() =>
								directive.elt.setAttribute(
									directive.value,
									true
								),
							() => spinnerElt.removeAttribute(directive.value)
						)
					} else {
						doAndSetCallbackOnElToUndo(
							component,
							directive,
							() => (spinnerElt.style.display = 'inline-block'),
							() => (spinnerElt.style.display = 'none')
						)
					}
				})
			},
			spinningStop(evt) {
				const component = getComponent(evt.detail.elt)

				if (!component.__yoyo_on_finish_loading) {
					return
				}

				while (component.__yoyo_on_finish_loading.length > 0) {
					component.__yoyo_on_finish_loading.shift()()
				}
			},
		}

		/**
		 * Tracking for elements receiving multiple emitted events to only trigger the first one
		 */
		let yoyoEventCache = []

		let yoyoSpinners = {}

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

		function isComponent(elt) {
			return elt.hasAttribute('yoyo:name')
		}

		function getComponent(elt) {
			return elt.closest('[yoyo\\:name]')
		}

		function getAllcomponents() {
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

		function getAncestorcomponents(selector) {
			let ancestor = getComponent(document.querySelector(selector))

			let ancestors = []

			while (ancestor) {
				ancestors.push(ancestor)
				ancestor = getComponent(ancestor.parentElement)
			}

			return ancestors
		}

		function shouldTriggerYoyoEvent(id) {
			if (!yoyoEventCache.includes(id)) {
				yoyoEventCache.push(id)
				return true
			}

			return false
		}

		function clearYoyoEventCache() {
			yoyoEventCache = []
		}

		function eventsMiddleware(evt) {
			const component = getComponent(evt.detail.elt)
			const componentName = getComponentName(component)
			const eventAttr = component.getAttribute('yoyo:transient-event')

			if (!eventAttr) return

			const eventData = JSON.parse(eventAttr)

			if (eventData) {
				evt.detail.parameters[
					'component'
				] = `${componentName}/${eventData.name}`

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
			const componentName = event.component || null
			const ancestorsOnly = event.ancestorsOnly || null
			let elements

			if (!selector && !componentName) {
				elements = getAllcomponents()
			} else if (selector) {
				if (ancestorsOnly) {
					elements = getAncestorcomponents(selector)
				} else {
					elements = [document.querySelector(selector)]
				}
			} else if (componentName) {
				elements = document.querySelectorAll(
					`[yoyo\\:name="${componentName}"]`
				)
			}

			if (elements) {
				elements.forEach((elt) => {
					if (shouldTriggerYoyoEvent(elt.id)) {
						addServerEventTransient(
							getComponent(elt),
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

		function initializeComponentSpinners(component) {
			const yoyoId = component.id
			component.__yoyo_on_finish_loading = []

			walk(component, (elt) => {
				const directive = extractModifiersAndValue(elt, 'spinning')
				if (directive) {
					const yoyoSpinOnAction = elt.getAttribute('yoyo:spin-on')
					if (yoyoSpinOnAction) {
						yoyoSpinOnAction
							.replace(' ', '')
							.split(',')
							.forEach((action) => {
								addActionSpinner(yoyoId, action, directive)
							})
					} else {
						addGenericSpinner(yoyoId, directive)
					}
				}
			})
		}

		function checkSpinnerInitialized(yoyoId, action) {
			yoyoSpinners[yoyoId] = yoyoSpinners[yoyoId] || {
				actions: {},
				generic: [],
			}
			if (
				action &&
				yoyoSpinners?.[yoyoId]?.actions?.[action] === undefined
			) {
				yoyoSpinners[yoyoId].actions[action] = []
			}
		}

		function addActionSpinner(yoyoId, action, directive) {
			checkSpinnerInitialized(yoyoId, action)
			yoyoSpinners[yoyoId].actions[action].push(directive)
		}

		function addGenericSpinner(yoyoId, directive) {
			checkSpinnerInitialized(yoyoId)
			yoyoSpinners[yoyoId].generic.push(directive)
		}

		// https://github.com/livewire/livewire
		function doAndSetCallbackOnElToUndo(
			el,
			directive,
			doCallback,
			undoCallback
		) {
			if (directive.modifiers.includes('remove'))
				[doCallback, undoCallback] = [undoCallback, doCallback]

			if (directive.modifiers.includes('delay')) {
				let timeout = setTimeout(() => {
					doCallback()
					el.__yoyo_on_finish_loading.push(() => undoCallback())
				}, 200)

				el.__yoyo_on_finish_loading.push(() => clearTimeout(timeout))
			} else {
				doCallback()
				el.__yoyo_on_finish_loading.push(() => undoCallback())
			}
		}

		// https://github.com/alpinejs/alpine/
		function walk(el, callback) {
			if (callback(el) === false) return

			let node = el.firstElementChild

			while (node) {
				walk(node, callback)

				node = node.nextElementSibling
			}
		}

		function extractModifiersAndValue(elt, type) {
			const attr = elt
				.getAttributeNames()
				// Filter only the Yoyo spinning directives.
				.filter((name) => name.match(new RegExp(`yoyo:${type}`)))

			if (attr.length) {
				const name = attr[0]
				const [ntype, ...modifiers] = name
					.replace(new RegExp(`yoyo:${type}`), '')
					.split('.')

				const value = elt.getAttribute(name)
				return { elt, name, value, modifiers }
			}

			return false
		}

		return Yoyo
	})()
})

YoyoEngine.defineExtension('yoyo', {
	onEvent: function (name, evt) {
		if (name === 'htmx:processedNode') {
			Yoyo.processedNode(evt)
		}

		if (name === 'htmx:configRequest') {
			if (!evt.target) return

			Yoyo.bootstrapRequest(evt)
			Yoyo.middleware(evt)
		}

		if (name === 'htmx:beforeRequest') {
			if (!Yoyo.url) {
				console.error('The yoyo URL needs to be defined')
				evt.preventDefault()
			}

			Yoyo.spinningStart(evt)
		}

		if (name === 'htmx:afterRequest') {
			Yoyo.spinningStop(evt)
		}

		if (name === 'htmx:afterOnLoad') {
			if (!evt.target) {
				return
			}

			// Timeout needed for targets outside of Yoyo component
			setTimeout(() => {
				Yoyo.afterRequestActions(evt.target)
			}, 125)

			// afterSwap and afterSettle events are not triggered for targets different than the Yoyo component
			// so we run those actions here
			if (
				!evt.target.isSameNode(evt.detail.target) ||
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
