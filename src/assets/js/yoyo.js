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

		window.addEventListener('popstate', (event) => {
			event?.state?.yoyo?.forEach((state) =>
				restoreComponentStateFromHistory(state)
			)
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
			createNonExistentIdTarget(targetId) {
				// Dynamically create non-existent target IDs by appending them to document body
				if (
					targetId &&
					targetId[0] == '#' &&
					document.querySelector(targetId) === null
				) {
					let targetDiv = document.createElement('div')
					targetDiv.setAttribute('id', targetId.replace('#', ''))
					document.body.appendChild(targetDiv)
				}
			},
			afterProcessNode(evt) {
				this.createNonExistentIdTarget(
					evt.srcElement.getAttribute('hx-target')
				)

				// Initialize spinners
				let component

				if (!evt.srcElement || !isComponent(evt.srcElement)) {
					// For innerHTML swap find the component root node
					component = YoyoEngine.closest(
						evt.detail.elt,
						'[hx-swap~=innerHTML]'
					)
				} else {
					component = getComponent(evt.srcElement)
				}

				if (!component) {
					return
				}

				initializeComponentSpinners(component)
			},
			bootstrapRequest(evt) {
				const elt = evt.target
				let component = getComponent(elt)
				const componentName = getComponentName(component)

				if (evt.detail.path === document.location.href) {
					evt.detail.path = 'render'
				}

				// Includes the commonly-used X-Requested-With header that identifies ajax requests in many backend frameworks
				evt.detail.headers['X-Requested-With'] = 'XMLHttpRequest'

				const action = getActionAndParseArguments(evt.detail)

				evt.detail.parameters[
					'component'
				] = `${componentName}/${action}`
				evt.detail.path = Yoyo.url

				// Make request info available to other events
				componentAddYoyoData(component, { action })

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
			processEmitEvents(elt, events) {
				if (!events || events == '[]') return

				events = typeof events == 'string' ? JSON.parse(events) : events

				yoyoEventCache.clear()

				events.forEach((event) => {
					triggerServerEmittedEvent(elt, event)
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
			afterOnLoadActions(evt) {
				const component = getComponentById(evt.detail.target.id)

				if (!component) return

				componentCopyYoyoDataFromTo(evt.detail.target, component)

				// This isn't needed at this time because the CSS classes/attributes are
				// automatically removed when a component is updated from the server
				// however, could be useful to improve transitions in the future. It would
				// be necessary to add back spinner classes before new HTML is swapped in
				// spinningStop(component)

				// Timeout needed for targets outside of Yoyo component
				setTimeout(() => {
					removeEventListenerData(component)
				}, 125)
			},
			afterSettleActions(evt) {
				/// HISTORY
				// At this time, browser history support only works with outerHTML swaps
				const component = getComponentById(evt.detail.elt.id)

				if (!component) return

				const xhr = evt.detail.xhr
				const pushedUrl = xhr.getResponseHeader('Yoyo-Push')

				// Browser history support only works with components modifing the URL queryString

				if (!pushedUrl || component?.__yoyo?.replayingHistory) {
					if (component.yoyo) {
						component.__yoyo.replayingHistory = false
					}
					return
				}

				const url =
					pushedUrl !== null ? pushedUrl : window.location.href

				componentAddYoyoData(component, {
					effects: {
						browserEvents: xhr.getResponseHeader(
							'Yoyo-Browser-Event'
						),
						emitEvents: xhr.getResponseHeader('Yoyo-Emit'),
					},
				})

				const componentName = getComponentName(component)

				// Before pushing a component to the browser history, we need to take a snapshot
				// of its initial rendered-HTML to store it in the current state
				// This also works for components loaded dynamically onto the page, like modals
				if (!componentAlreadyInCurrentHistoryState(component)) {
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
				let args = match[2].split(',').map((value) => {
					const val = value
						.replace(/'(.*)'/, '$1')
						.replace(/"(.*)"/, '$1')
					return isNaN(val) ? val : parseFloat(val)
				})
				path = match[1]
				detail.parameters['actionArgs'] = JSON.stringify(args)
			}

			const action = '' + path
			return action
		}

		function isComponent(elt) {
			return elt?.hasAttribute('yoyo:name')
		}

		function getComponent(elt) {
			return elt.closest('[yoyo\\:name]')
		}

		function getAllcomponents() {
			return document.querySelectorAll('[yoyo\\:name]')
		}

		function getComponentById(componentId) {
			if (!componentId) return null

			const component = document.querySelector(`#${componentId}`)

			return isComponent(component) ? component : null
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

			// Remove the current component
			ancestors.shift()
			return ancestors
		}

		function shouldTriggerYoyoEvent(elt, eventName) {
			let key
			if (isComponent(elt)) {
				key = `${elt.id}${eventName}`
			} else if (elt.selector !== undefined) {
				return true
			}

			if (key && !yoyoEventCache.has(key)) {
				yoyoEventCache.add(key)
				return true
			}

			return false
		}

		function eventsMiddleware(evt) {
			const component = getComponent(evt.detail.elt)
			const componentName = getComponentName(component)
			const eventData = component.__yoyo.eventListener

			if (!eventData) return

			evt.detail.parameters[
				'component'
			] = `${componentName}/${eventData.name}`

			if (eventData.params) {
				delete eventData.params.elt
			}

			evt.detail.parameters = {
				...evt.detail.parameters,
				...{
					eventParams: eventData.params
						? JSON.stringify(eventData.params)
						: [],
				},
			}
		}

		function addEmittedEventParametersToListenerComponent(
			component,
			event,
			params
		) {
			// Check if Yoyo component is listening for the event
			let componentListeningFor = component
				.getAttribute('hx-trigger')
				.split(',')
				.filter((name) => name.trim())

			if (componentListeningFor.indexOf(event) === -1) {
				return
			}

			componentAddYoyoData(component, {
				eventListener: { name: event, params: params },
			})
		}

		function triggerServerEmittedEvent(elt, event) {
			const component = getComponent(elt)
			const eventName = event.event
			const params = event.params
			const selector = event.selector || null
			const componentName = event.component || null
			const propagation = event.propagation || null
			let elements

			// emit
			if (!selector && !componentName) {
				elements = getAllcomponents()
			} else if (componentName) {
				// emitUp
				if (propagation == 'ancestorsOnly') {
					elements = getAncestorcomponents(selector)
					// emitSelf
				} else if (propagation == 'self') {
					elements = [component]
					// emitTo
				} else {
					elements = getComponentsByName(componentName)
				}
				// emitWithSelector, excludes current component to allow replication without udpating the current component twice
			} else if (selector) {
				elements = document.querySelectorAll(selector)
				elements = Array.from(elements).filter(
					(element) => !component.contains(element)
				)
				elements.forEach((elt) => (elt.selector = selector))
			}

			if (elements.length) {
				elements.forEach((elt) => {
					if (shouldTriggerYoyoEvent(elt, eventName)) {
						addEmittedEventParametersToListenerComponent(
							getComponent(elt),
							eventName,
							params
						)
						YoyoEngine.trigger(elt, eventName, params)
					}
				})
			}
		}

		function removeEventListenerData(component) {
			delete component.__yoyo.eventListener
		}

		/**
		 * Component loading state spinners
		 */

		function spinningStart(component) {
			const componentId = component.id

			if (!yoyoSpinners[componentId]) {
				return
			}

			let spinningElts = yoyoSpinners[componentId].generic || []

			spinningElts = spinningElts.concat(
				yoyoSpinners[componentId]?.actions[component.__yoyo.action] ||
					[]
			)

			delete yoyoSpinners[component.id]

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
			while (component.__yoyo_on_finish_loading.length > 0) {
				component.__yoyo_on_finish_loading.shift()()
			}
		}

		function initializeComponentSpinners(component) {
			const componentId = component.id
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
								addActionSpinner(componentId, action, directive)
							})
					} else {
						addGenericSpinner(componentId, directive)
					}
				}
			})
		}

		function checkSpinnerInitialized(componentId, action) {
			yoyoSpinners[componentId] = yoyoSpinners[componentId] || {
				actions: {},
				generic: [],
			}
			if (
				action &&
				yoyoSpinners?.[componentId]?.actions?.[action] === undefined
			) {
				yoyoSpinners[componentId].actions[action] = []
			}
		}

		function addActionSpinner(componentId, action, directive) {
			checkSpinnerInitialized(componentId, action)
			yoyoSpinners[componentId].actions[action].push(directive)
		}

		function addGenericSpinner(componentId, directive) {
			checkSpinnerInitialized(componentId)
			yoyoSpinners[componentId].generic.push(directive)
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
			const effects = component.__yoyo.effects || {}

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

			var parser = new DOMParser()
			var cached = parser.parseFromString(state.html, 'text/html').body
				.firstElementChild

			component.replaceWith(cached)

			htmx.process(cached)

			// Trigger full server refresh when coming back to the original state
			// so server-sent events on the render/refresh method are run
			if (state.initialState) {
				componentAddYoyoData(cached, { replayingHistory: true })
				YoyoEngine.trigger(cached, 'refresh')
			} else {
				Yoyo.processBrowserEvents(state?.effects?.browserEvents)
				Yoyo.processEmitEvents(component, state?.effects?.emitEvents)
			}
		}

		function componentCopyYoyoDataFromTo(from, to) {
			to.__yoyo = from?.__yoyo || {}
			to.__yoyo_on_finish_loading = from?.__yoyo_on_finish_loading
		}

		function componentAddYoyoData(component, data) {
			if (!data) return

			component.__yoyo = component?.__yoyo || {}
			component.__yoyo = Object.assign(component.__yoyo, data)
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
		if (name === 'htmx:afterProcessNode') {
			Yoyo.afterProcessNode(evt)
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
			Yoyo.afterOnLoadActions(evt)

			const xhr = evt.detail.xhr

			Yoyo.processEmitEvents(
				evt.detail.elt,
				xhr.getResponseHeader('Yoyo-Emit')
			)

			Yoyo.processBrowserEvents(
				xhr.getResponseHeader('Yoyo-Browser-Event')
			)

			Yoyo.processRedirectHeader(xhr)

			// Re-spawn targets removed from the page and take into account swap delays
			let modifier = xhr.getResponseHeader('Yoyo-Swap-Modifier')
			if (!modifier) return
			let swap = modifier.match(/swap:([0-9.]+)s/)
			let time = swap[1] ? swap[1] * 1000 + 1 : 0
			setTimeout(() => {
				if (
					!evt.detail.target.isConnected &&
					document.querySelector(
						`[hx-target="#${evt.detail.target.id}"]`
					)
				) {
					Yoyo.createNonExistentIdTarget(`#${evt.detail.target.id}`)
				}
			}, time)
		}

		if (name === 'htmx:beforeSwap') {
			if (!evt.target) return

			const modifier = evt.detail.xhr.getResponseHeader(
				'Yoyo-Swap-Modifier'
			)

			if (modifier) {
				const swap =
					evt.detail.elt.getAttribute('hx-swap') ||
					YoyoEngine.config.defaultSwapStyle
				evt.detail.elt.setAttribute('hx-swap', `${swap} ${modifier}`)
			}

			Yoyo.processBrowserEvents(
				evt.detail.xhr.getResponseHeader('Yoyo-Browser-Event')
			)
		}

		if (name === 'htmx:afterSettle') {
			// Push component response to history cache
			// Make sure we trigger once for the new element - this was failing in Safari mobile
			// Causing a duplicate snapshot
			if (!evt.target || !evt.target.isConnected) return

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
