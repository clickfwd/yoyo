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

		window.addEventListener('DOMContentLoaded', () => {
			window.onpopstate = function (event) {
				event?.state?.yoyo?.forEach((state) =>
					restoreComponentStateFromHistory(state)
				)
			}
		})

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
			processEmitEvents(events) {
				if (!events) return

				events = typeof events == 'string' ? JSON.parse(events) : events

				yoyoEventCache.clear()

				events.forEach((event) => {
					triggerServerEmittedEvent(event)
				})
			},
			processBrowserEvents(events) {
				if (!events) return

				events = typeof events == 'string' ? JSON.parse(events) : events

				events.forEach((event) => {
					window.dispatchEvent(
						new CustomEvent(event.event, {
							detail: event.params,
						})
					)
				})
			},
			beforeRequestActions(elt) {
				let component = getComponent(elt)

				spinningStart(component)
			},
			afterOnLoadActions(elt) {
				let component = getComponent(elt)

				spinningStop(component)

				// Timeout needed for targets outside of Yoyo component
				setTimeout(() => {
					removeServerEventTransient(component)
				}, 125)

				delete component.__yoyo_action
			},
			afterSettleActions(evt) {
				/// HISTORY

				const xhr = evt.detail.xhr
				const pushedUrl = xhr.getResponseHeader('HX-Push')

				// At this time, browser history support only works with components
				// changing the URL queryString
				if (!pushedUrl) return

				// At this time, browser history support only works with outerHTML swaps
				if (!evt.detail.target?.id) return

				if (evt.detail.target.__yoyo_replaying_history) return

				const url =
					pushedUrl !== null ? pushedUrl : window.location.href

				const component = getComponentById(evt.detail.target.id)

				component.__yoyo_effects = {
					browserEvents: xhr.getResponseHeader('Yoyo-Browser-Event'),
					emitEvents: xhr.getResponseHeader('Yoyo-Emit'),
				}

				if (!component) return

				const componentName = getComponentName(component)

				console.log(
					`afterSettle ${componentName}:${getComponentIndex(
						component
					)}`
				)

				// Before pushing a component to the browser history, we need to take a snapshot
				// of its initial rendered-HTML to store it in the current state
				// This also works for components loaded dynamically onto the page, like modals
				if (!componentAlreadyInCurrentHistoryState(component)) {
					console.log(
						`initialState ${getComponentFingerprint(component)}`
					)
					updateState(
						'replaceState',
						document.location.href,
						component,
						true,
						evt.detail.target.outerHTML
					)
				}

				if (
					!history?.state?.yoyo ||
					history?.state?.initialState ||
					url !== window.location.href
				) {
					updateState('pushState', url, component)
				} else {
					updateState('replaceState', url, component)
				}
			},
		}

		/**
		 * Tracking for elements receiving multiple emitted events to only trigger the first one
		 */
		let yoyoEventCache = new Set()

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

		function getComponentById(componentId) {
			return document.querySelector(`#${componentId}`)
		}

		function getComponentName(component) {
			return component.getAttribute('yoyo:name')
		}

		function getComponentFingerprint(component) {
			return `${getComponentName(
				component
			)}:${getComponentIndex(component)}`
		}

		function getComponentsByName(name) {
			return Array.from(
				document.querySelectorAll(`[yoyo\\:name="${name}"]`)
			)
		}

		// Index as it appears on the page relative to other same-named components
		function getComponentIndex(component) {
			const name = getComponentName(component)
			const components = getComponentsByName(name)
			return components.indexOf(component)
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

		function shouldTriggerYoyoEvent(elt) {
			if (isComponent(elt) && !yoyoEventCache.has(elt.id)) {
				yoyoEventCache.add(elt.id)
				return true
			}

			return false
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
					const elt = document.querySelector(selector)
					if (elt) {
						elements = [elt]
					}
				}
			} else if (componentName) {
				elements = document.querySelectorAll(
					`[yoyo\\:name="${componentName}"]`
				)
			}

			if (elements) {
				elements.forEach((elt) => {
					if (shouldTriggerYoyoEvent(elt)) {
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

		/**
		 * Component loading state spinners
		 */

		function spinningStart(component) {
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
						() => directive.elt.setAttribute(directive.value, true),
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
		}

		function spinningStop(component) {
			if (!component.__yoyo_on_finish_loading) {
				return
			}

			while (component.__yoyo_on_finish_loading.length > 0) {
				component.__yoyo_on_finish_loading.shift()()
			}
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

		function componentAlreadyInCurrentHistoryState(component) {
			if (!history?.state?.yoyo) return false

			history.state.yoyo.forEach((state) => {
				if (state.fingerprint == getComponentFingerprint(component)) {
					return true
				}
			})

			return false
		}

		/**
		 * Component state caching for browser history
		 */

		function updateState(
			method,
			url,
			component,
			initialState,
			originalHTML
		) {
			const id = component.id
			const componentName = getComponentName(component)
			const componentIndex = getComponentIndex(component)
			const fingerprint = getComponentFingerprint(component)
			const html = originalHTML ? originalHTML : component.outerHTML
			const effects = component.__yoyo_effects || {}

			const newState = {
				url,
				id,
				componentName,
				componentIndex,
				fingerprint,
				html,
				effects,
				initialState,
			}

			const stateArray =
				method == 'pushState'
					? [newState]
					: replaceStateByComponentIndex(newState)

			console.log(
				`${method} ${newState.fingerprint}`,
				'newState',
				newState,
				'stateArray',
				stateArray
			)

			history[method](
				{ yoyo: stateArray, initialState: initialState },
				'',
				url
			)
		}

		function replaceStateByComponentIndex(newState) {
			let stateArray = history?.state?.yoyo || []
			let fingerprintFound = false
			stateArray.map((state) => {
				if (state.fingerprint == newState.fingerprint) {
					fingerprintFound = true
					return newState
				}

				return state
			})

			if (!fingerprintFound) {
				stateArray.push(newState)
			}

			return stateArray
		}

		function restoreComponentStateFromHistory(state) {
			const componentName = state.componentName
			const componentsWithSameName = getComponentsByName(componentName)
			let component = componentsWithSameName[state.componentIndex]

			// If the component cannot be found by index, try a simple ID check
			// This is needed for components dynamically added to the page, like modals
			// and it works when the component id is pre-determined (i.e. not randomly generated)
			if (!component) {
				component = getComponentById(state.id)

				if (!component) return
			}

			console.log(`restoreComponentStateFromHistory ${state.fingerprint}`)

			var parser = new DOMParser()
			var cached = parser.parseFromString(state.html, 'text/html').body
				.firstElementChild
			component.replaceWith(cached)

			htmx.process(cached)

			// Trigger full server refresh when coming back to the original state
			// so server-sent events on the render/refresh method are run
			if (state.initialState) {
				cached.__yoyo_replaying_history = true
				YoyoEngine.trigger(cached, 'refresh')
			} else {
				Yoyo.processBrowserEvents(state?.effects?.browserEvents)
				Yoyo.processEmitEvents(state?.effects?.emitEvents)
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
		}

		if (name === 'htmx:beforeRequest') {
			if (!Yoyo.url) {
				console.error('The yoyo URL needs to be defined')
				evt.preventDefault()
			}

			Yoyo.beforeRequestActions(evt.detail.elt)
		}

		if (name === 'htmx:afterOnLoad') {
			if (!evt.target) {
				return
			}

			const xhr = evt.detail.xhr

			Yoyo.afterOnLoadActions(evt.target)

			// afterSwap and afterSettle events are not triggered for targets different than the Yoyo component
			// so we run those actions here
			if (
				!evt.target.isSameNode(evt.detail.target) ||
				evt.detail.xhr.status == 204
			) {
				Yoyo.processEmitEvents(xhr.getResponseHeader('Yoyo-Emit'))
				Yoyo.processBrowserEvents(
					xhr.getResponseHeader('Yoyo-Browser-Event')
				)
				Yoyo.processRedirectHeader(xhr)
			}
		}

		if (name === 'htmx:beforeSwap') {
			if (!evt.target) return

			Yoyo.processBrowserEvents(
				evt.detail.xhr.getResponseHeader('Yoyo-Browser-Event')
			)
		}

		if (name === 'htmx:afterSettle') {
			if (!evt.target) return

			const xhr = evt.detail.xhr

			Yoyo.processEmitEvents(xhr.getResponseHeader('Yoyo-Emit'))

			Yoyo.afterSettleActions(evt)
		}
	},

	// Add support for morphdom swap when using Alpine JS to be able to
	// maintain the Alpine component state after a swap
	isInlineSwap: function (swapStyle) {
		return swapStyle === 'morphdom'
	},
	handleSwap: function (swapStyle, target, fragment) {
		if (typeof morphdom === 'function' && swapStyle === 'morphdom') {
			morphdom(target, fragment.outerHTML, {
				onBeforeElUpdated: (from, to) => {
					// From Livewire - deal with Alpine component updates
					if (from.__x) {
						window.Alpine.clone(from.__x, to)
					}
				},
			})

			return [target] // let htmx handle the new content
		}
	},
})
