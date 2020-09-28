(function (global, factory) {
  if (typeof define === "function" && define.amd) {
    define([], factory);
  } else {
    global.YoyoFactory = factory();
  }
})(typeof self !== "undefined" ? self : this, function () {
  return (function () {
    "use strict";

    window.Yoyo = window.htmx;

    var YoyoFactory = {
      url: null,
      bootstrap(evt) {
        const elt = evt.target;
        const componentElt = elt.closest("[yoyo\\:name]");
        const componentName = componentElt.getAttribute("yoyo:name");

        if (evt.detail.path === document.location.href) {
          evt.detail.path = "render";
        }

        const action = "" + evt.detail.path;
        evt.detail.parameters["component"] = `${componentName}/${action}`;
        evt.detail.path = YoyoFactory.url;
      },
      triggerServerEmittedEvent(eventName, evt) {
        let elements;
        const selector = evt.detail.selector;
        const component = evt.detail.component;
        const parentsOnly = evt.detail.parentsOnly;

        if (!selector && !component) {
          Yoyo.trigger(document, eventName, evt.detail);
          return;
        }

        if (selector) {
          if (parentsOnly) {
            elements = getParentComponents(selector);
          } else {
            elements = document.querySelectorAll(selector);
          }
        } else if (component) {
          elements = document.querySelectorAll(`[yoyo\\:name="${component}"]`);
        }

        (elements || []).forEach((elt) => {
          YoyoFactory.addTransientVars(elt, evt.detail.params);
          Yoyo.trigger(elt, eventName, evt.detail);
        });
      },
      addTransientVars(elt, params) {
        if (elt.hasAttribute("yoyo")) {
          elt.setAttribute(
            "yoyo-transient-vars",
            JSON.stringify(params).slice(1, -1)
          );
        }
      },
      removeTransientVars(elt) {
        elt.removeAttribute("yoyo-transient-vars");
      },
      processTransientVars(evt) {
        const transientVarsElt = Yoyo.closest(
          evt.detail.elt,
          "[yoyo-transient-vars]"
        );
        if (transientVarsElt) {
          let transientVars = transientVarsElt.getAttribute(
            "yoyo-transient-vars"
          );
          var varsToInclude = eval("({" + transientVars + "})");
          mergeObjects(evt.detail.parameters, varsToInclude);
        }
      },
    };

    function getParentComponents(selector) {
      let parent = document
        .querySelector(selector)
        .parentElement.closest("[yoyo\\:name]");

      let ancestors = [];

      while (parent) {
        ancestors.push(parent);
        parent = parent.parentElement.closest("[yoyo\\:name]");
      }

      return ancestors;
    }

    function mergeObjects(obj1, obj2) {
      for (var key in obj2) {
        if (obj2.hasOwnProperty(key)) {
          obj1[key] = obj2[key];
        }
      }
      return obj1;
    }

    return YoyoFactory;
  })();
});

Yoyo.defineExtension("yoyo", {
  onEvent: function (name, evt) {
    if (name === "htmx:configRequest") {
      if (evt.target) {
        YoyoFactory.bootstrap(evt);
        YoyoFactory.processTransientVars(evt);
      }
    }

    if (name === "htmx:beforeRequest") {
      if (!YoyoFactory.url) {
        console.error("The yoyo URL needs to be defined");
        evt.preventDefault();
      }
    }

    if (name === "htmx:afterRequest") {
      if (evt.target) {
        YoyoFactory.removeTransientVars(evt.target);
      }
    }

    // Server-emitted events
    if (name.split(":")[0] === "yoyo") {
      const eventName = name.split(":").slice(1).join(":");
      YoyoFactory.triggerServerEmittedEvent(eventName, evt);
    }
  },
});
