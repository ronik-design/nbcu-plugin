/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./public/assets/src/app.js":
/*!**********************************!*\
  !*** ./public/assets/src/app.js ***!
  \**********************************/
/***/ (() => {

function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
(function ($) {
  // 'use strict';
  function dynExtLink() {
    if (!$("body").hasClass("dyn-external-link")) {
      return false;
    }
    // Detect if a link is internal or external
    function link_is_external(link_element) {
      return link_element.host !== window.location.host;
    }
    // If out bound we auto add target _blank.
    var links = document.getElementsByTagName('a');
    for (var i = 0; i < links.length; i++) {
      if (link_is_external(links[i])) {
        // External
        links[i].setAttribute('target', '_blank');
      }
    }
  }
  // Scroll Function On Load
  function showTarget(target) {
    target = target.replace('#', '');
    // var headerHeight = jQuery('#MainHeader').outerHeight() + 75;
    var headerHeight = 100;
    var page = jQuery('html, body');
    if (target) {
      // Stops Animation
      page.on('scroll mousedown wheel DOMMouseScroll mousewheel touchmove', function () {
        page.stop();
      });
      // Plays & Stops Animation, base on ADA guidelines.
      page.animate({
        scrollTop: jQuery('#' + target).offset().top - headerHeight
      }, 1000, function () {
        page.off('scroll mousedown wheel DOMMouseScroll mousewheel touchmove');
        window.location.hash = '#' + target;
      });
      return false;
    }
  }
  /* Smooth Scrolling */
  function initSmoothScrolling($) {
    if (!$("body").hasClass("smooth-scroll")) {
      return false;
    }
    var urlTarget = window.location.hash;
    if (urlTarget) {
      // Url Auto Trigger
      showTarget(urlTarget);
    }
    $('a[href*="#"]:not([href="#"])').click(function () {
      if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
        var target = jQuery(this.hash);
        target = target.length ? target : jQuery('[name=' + this.hash.slice(1) + ']');
        if (target.length) {
          jQuery('html, body').animate({
            scrollTop: target.offset().top - 100
          }, 400);
          return false;
        }
      }
    });
  }
  /* initSvgMigrations */
  function initSvgMigrations($) {
    if (!$("body").hasClass("dyn-svg-migrations")) {
      return false;
    }
    setTimeout(function () {
      $("img.adv-svg-swap").each(function (index) {
        var str = $(this).attr('src');
        if (str.includes('.svg')) {
          if (jQuery("img[src='" + str + "']").length !== 0) {
            var $img = jQuery(this);
            var imgSColor = $img.attr('data-svg-color');
            var imgID = $img.attr('id');
            var imgClass = $img.attr('class');
            var imgURL = $img.attr('src');
            var imgWIDTH = $img.width();
            var imgHEIGHT = $img.height();
            jQuery.get(imgURL, function (data) {
              // Get the SVG tag, ignore the rest
              var $svg = jQuery(data).find('svg');
              // Add replaced image's ID to the new SVG
              if (typeof imgID !== 'undefined') {
                $svg = $svg.attr('id', imgID);
              }
              // Add replaced image's ID to the new SVG
              if (typeof imgSColor !== 'undefined') {
                $svg = $svg.attr('data-svg-color', imgSColor);
              }
              // Add replaced image's classes to the new SVG
              if (typeof imgWIDTH !== 'undefined') {
                $svg = $svg.attr('width', imgWIDTH);
              }
              // Add replaced image's classes to the new SVG
              if (typeof imgHEIGHT !== 'undefined') {
                $svg = $svg.attr('height', imgHEIGHT);
              }
              // Add replaced image's classes to the new SVG
              if (typeof imgClass !== 'undefined') {
                $svg = $svg.attr('class', imgClass + ' replaced-svg');
              }
              // Remove any invalid XML tags as per http://validator.w3.org
              $svg = $svg.removeAttr('xmlns:a');
              // Replace image with new SVG
              $img.replaceWith($svg);
            }, 'xml');
          }
        }
      });

      // lets see if a custom color is available. If so we swap the colors.
      setTimeout(function () {
        $('svg').filter('[data-svg-color]').each(function () {
          var svgItems = ['path', 'polygon', 'polyline', 'line', 'circle'];
          var svgThis = $(this);
          $(svgItems).each(function (index, value) {
            if (svgThis.find(value).css('fill') !== 'none' && typeof svgThis.find(value).css('fill') !== 'undefined') {
              var gradientExist = svgThis.find(value).css('fill').includes("url");
              if (!gradientExist) {
                svgThis.find(value).css({
                  fill: svgThis.attr('data-svg-color')
                });
              }
            }
            if (svgThis.find(value).css('stroke') !== 'none' && typeof svgThis.find(value).css('stroke') !== 'undefined') {
              svgThis.find(value).css({
                stroke: svgThis.attr('data-svg-color')
              });
            }
          });
        });
      }, 50);
    }, 50);
  }

  // Lazy load images in aswell as image compression.
  function lazyLoader() {
    var imageObserver = new IntersectionObserver(function (entries, imgObserver) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var lazyImage = entry.target;
          fetch(lazyImage.dataset.src).then(function (res) {
            return res.blob();
          }) // Gets the response and returns it as a blob
          .then(function (blob) {
            var objectURL = URL.createObjectURL(blob);
            // let myImage = new Image();
            // myImage.src = objectURL;
            function resizeBase64Img(url, width, height) {
              return new Promise(function (resolve, reject) {
                var canvas = document.createElement("canvas");
                canvas.style.width = width.toString() + "px";
                canvas.style.height = height.toString() + "px";
                var context = canvas.getContext("2d");
                context.canvas.width = width;
                context.canvas.height = height;
                var img = document.createElement("img");
                img.src = url;
                img.onload = function () {
                  context.drawImage(img, 0, 0);
                  context.canvas.toBlob(function (blob) {
                    resolve(URL.createObjectURL(blob));
                  }, "image/jpeg");
                };
              });
            }
            resizeBase64Img(objectURL, lazyImage.dataset.width, lazyImage.dataset.height).then(function (newImg) {
              lazyImage.src = newImg;
              //   lazyImage.className = " reveal-enabled";
              lazyImage.classList.remove("lzy_img");
              lazyImage.classList.remove("reveal-disabled");
              lazyImage.classList.add("reveal-enabled");
            });
          });
        }
      });
    });
    var arr = document.querySelectorAll('img.lzy_img');
    arr.forEach(function (v) {
      imageObserver.observe(v);
    });
  }
  function enhanceMouseFocusUpdate($) {
    if (enhanceMouseFocusEnabled) {
      enhanceMouseFocusNewElements = $('button, input[type="submit"], input[type="button"], [tabindex]:not(input, textarea), a').not(enhanceMouseFocusElements);
      // if an element gets focus due to a mouse click, prevent it from keeping focus
      enhanceMouseFocusElements.mousedown(function () {
        enhanceMouseFocusActive = true;
        setTimeout(function () {
          enhanceMouseFocusActive = false;
        }, 50);
      }).on('focus', function () {
        if (enhanceMouseFocusActive) {
          $(this).blur();
        }
      });
    }
  }
  function initEnhanceMouseFocus($) {
    enhanceMouseFocusElements = $();
    enhanceMouseFocusEnabled = true;
    enhanceMouseFocusUpdate($);
    // update focusable elements on Gravity Forms render
    $(document).on('gform_post_render', function () {
      enhanceMouseFocusUpdate($);
    });
  }
  function text_truncate(str, length, ending) {
    if (!str) {
      return str;
    }
    // This will remove the break space.
    str = str.replace(/\s|&nbsp;/, ' ');
    // Remove all multi whitespace in a row.
    str = str.replace(/\s\s+/g, ' ');
    // Remove all multi whitespace in a row.
    str = str.trim();
    str = str.replace(/[\s\n\r]+/g, ' ');
    str = str.replace(/(&nbsp;)*/g, "");
    str = str.replace(/\s+/g, ' ');
    if (length == null) {
      length = 100;
    }
    if (ending == null) {
      ending = '...';
    }
    if (str.length > length) {
      return str.substring(0, length - ending.length) + ending;
    } else {
      return str;
    }
  }
  ;

  // This will dynamically add all the attributes necessary..
  function dynImageAttr($) {
    if (!$("body").hasClass("dyn-image-attr")) {
      return false;
    }
    // Specify image dimensions
    $('img').each(function () {
      if (_typeof($(this).attr('width')) === ( true ? "undefined" : 0)) {
        var findImgWidth = $(this).width();
        $(this).attr('width', findImgWidth);
      }
      if (_typeof($(this).attr('height')) === ( true ? "undefined" : 0)) {
        var findImgHeight = $(this).height();
        $(this).attr('height', findImgHeight);
      }
      if (_typeof($(this).attr('alt')) === ( true ? "undefined" : 0)) {
        var findParent = text_truncate($(this).parent().text(), 100);
        if (!findParent.trim()) {
          findParent = $(this).attr('src').split("/").pop().split(".")[0].replace(/^a-zA-Z0-9 ]/g, '');
        }
        $(this).attr('alt', text_truncate(findParent, 100));
      }
    });
  }

  // This will dynamically add all the attributes necessary..
  function dynAttr($) {
    if (!$("body").hasClass("dyn-button-attr")) {
      return false;
    }
    function getLastPart(url) {
      var parts = url.split('/');
      var lastElement = parts[parts.length - 1];
      if (!lastElement) {
        lastElement = parts[parts.length - 2];
      } else {
        lastElement = parts[parts.length - 3];
      }
      return lastElement;
    }

    // Specify image dimensions
    $('button, a').each(function () {
      if ($(this).children()) {
        if ($(this).text()) {
          if ($(this).prop("tagName") == 'A') {
            var linkLastSegment = function linkLastSegment(URL) {
              var LastSeg = URL.split("/").length - 1;
              newText = URL.split("/")[LastSeg];
              if (!newText) {
                var LastSeg = URL.split("/").length - 2;
                newText = URL.split("/")[LastSeg];
              }
              newText = newText.replace(/[-_]/g, ' ');
              return newText;
            };
            var newText;
            switch ($(this).text().toLowerCase()) {
              case 'learn more':
                newText = linkLastSegment($(this).attr('href'));
                break;
              case 'read more':
                newText = linkLastSegment($(this).attr('href'));
                break;
              case 'click here':
                newText = linkLastSegment($(this).attr('href'));
                break;
              default:
                newText = $(this).text();
            }
            if (text_truncate($(this).text(), 100).trim()) {
              $(this).attr('aria-label', 'A link to ' + text_truncate(newText, 100));
            } else {
              // $(this).attr('aria-label', 'A link to '+text_truncate(getLastPart($(this).attr('href')), 100));
            }
          } else {
            $(this).attr('aria-label', 'A clickable ' + text_truncate($(this).prop("tagName").toLowerCase(), 100) + '.');
          }
        } else {
          if ($(this).prop("tagName") == 'A') {
            $(this).attr('aria-label', 'A link to ' + text_truncate(getLastPart($(this).attr('href')), 100));
          } else {
            $(this).attr('aria-label', 'A clickable ' + text_truncate($(this).prop("tagName").toLowerCase(), 100) + '.');
          }
        }
      }
    });
  }
  function previousLocationUrl() {
    var url = window.location.href;
    var domain = new URL(url);
    if (domain.pathname !== '/password-reset/' && domain.pathname !== '/2fa/') {
      console.log(domain.pathname);
      var urlBuilder;
      if (domain.pathname) {
        urlBuilder = domain.pathname;
        if (domain.search) {
          urlBuilder = domain.pathname + domain.search;
        }
      } else {
        if (domain.search) {
          urlBuilder = domain.hostname + domain.search;
        } else {
          urlBuilder = domain.hostname;
        }
      }
      var urlRester = {
        redirect: urlBuilder
      };
      window.localStorage.setItem('ronik-url-reset', JSON.stringify(urlRester));
    } else {
      // If the pathnames are a match lets remove the items.
      window.localStorage.removeItem("ronik-url-reset");
    }
  }

  // Load JS once windows is loaded. 
  $(window).on('load', function () {
    // SetTimeOut just incase things havent initialized just yet.
    setTimeout(function () {
      lazyLoader($);
      initEnhanceMouseFocus($);
      initSmoothScrolling($);
      initSvgMigrations($);
      dynExtLink($);
      checkPasswordStrength($);
      dynImageAttr($);
      dynAttr($);
      previousLocationUrl($);
    }, 50);
  });
})(jQuery);
window.addEventListener("load", function () {
  // var myElement = document.querySelector("#my-element");
  // myElement.addEventListener("event", handler);
  // We detect if the user is logged in. If so we dont register the service worker.
  if (!document.body.classList.contains('wp-admin') || !document.body.classList.contains('logged-in')) {
    if (document.body.classList.contains('enable-serviceworker')) {
      if (navigator.serviceWorker) {
        // /sw.js
        navigator.serviceWorker.register('/sw.js', {
          scope: './' // <--- THIS BIT IS REQUIRED
        }).then(function (registration) {
          // Registration was successful
          console.log('ServiceWorker registration successful with scope: ', registration.scope);
        }, function (err) {
          // registration failed :(
          console.log('ServiceWorker registration failed: ', err);
        });
      }
    }
  }
});

/***/ }),

/***/ "./public/assets/src/sass/main.scss":
/*!******************************************!*\
  !*** ./public/assets/src/sass/main.scss ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"/public/assets/dist/app": 0,
/******/ 			"public/assets/dist/main": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunkronik_plugin"] = self["webpackChunkronik_plugin"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	__webpack_require__.O(undefined, ["public/assets/dist/main"], () => (__webpack_require__("./public/assets/src/app.js")))
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["public/assets/dist/main"], () => (__webpack_require__("./public/assets/src/sass/main.scss")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;