(function ($) {
  var RETRY_BASE_DELAY = 250;
  var RETRY_MAX_DELAY = 8000;
  var RETRY_MAX_ATTEMPTS = 6;
  var DEAD_LETTER_MAX_ITEMS = 100;
  var retryRegistry = {};
  var deadLetterQueue = [];

  function boolData($el, key, fallback) {
    var raw = $el.data(key);
    if (typeof raw === 'undefined') return !!fallback;
    return String(raw) === 'true';
  }

  function intData($el, key, fallback) {
    var value = parseInt($el.data(key), 10);
    return Number.isFinite(value) ? value : fallback;
  }

  function prefersReducedMotion() {
    return typeof window.matchMedia === 'function' &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  function inBuilderContext($el) {
    return !!$el.closest('.brz, .brz-root, .elementor, .et_pb_section, [data-builder]').length;
  }

  function labelSlickControls($el) {
    var i18n = window.clevprcaCarouselI18n || {};

    $el.find('.slick-prev').attr({
      'aria-label': i18n.prevSlide || 'Previous slide',
      title: i18n.prevSlide || 'Previous slide'
    });

    $el.find('.slick-next').attr({
      'aria-label': i18n.nextSlide || 'Next slide',
      title: i18n.nextSlide || 'Next slide'
    });

    $el.find('.slick-dots li button').each(function (index) {
      var label = (i18n.goToSlide || 'Go to slide %d').replace('%d', String(index + 1));
      $(this).attr({ 'aria-label': label, title: label });
    });
  }

  function getCarouselId($el) {
    return String(
      $el.attr('id') ||
      $el.data('carousel-id') ||
      $el.closest('.clevers-product-carousel').attr('id') ||
      ('carousel-' + $el.index())
    );
  }

  function getOrCreateRetryState(carouselId) {
    if (!retryRegistry[carouselId]) {
      retryRegistry[carouselId] = {
        attempts: 0,
        timer: null,
        deadLettered: false,
        deadLetterLogged: false
      };
    }

    return retryRegistry[carouselId];
  }

  function clearRetryState(state) {
    if (state.timer) {
      window.clearTimeout(state.timer);
      state.timer = null;
    }

    state.attempts = 0;
    state.deadLettered = false;
    state.deadLetterLogged = false;
  }

  function markDeadLetter(carouselId, state, reason) {
    state.deadLettered = true;
    if (state.timer) {
      window.clearTimeout(state.timer);
      state.timer = null;
    }

    deadLetterQueue.push({
      carouselId: carouselId,
      reason: reason,
      attempts: state.attempts,
      timestamp: new Date().toISOString()
    });

    if (deadLetterQueue.length > DEAD_LETTER_MAX_ITEMS) {
      deadLetterQueue.shift();
    }
  }

  function scheduleRetry(carouselId, state, retryFn, reason) {
    if (state.deadLettered || state.timer) return;

    if (state.attempts >= RETRY_MAX_ATTEMPTS) {
      markDeadLetter(carouselId, state, reason);
      return;
    }

    state.attempts += 1;

    var expDelay = Math.min(RETRY_BASE_DELAY * Math.pow(2, state.attempts - 1), RETRY_MAX_DELAY);
    var jitter = Math.round(Math.random() * (expDelay * 0.3));
    var delay = expDelay + jitter;

    state.timer = window.setTimeout(function () {
      state.timer = null;
      retryFn();
    }, delay);
  }

  function initSingleCarousel($el) {
    var carouselId = getCarouselId($el);
    var retryState = getOrCreateRetryState(carouselId);

    if (retryState.deadLettered) {
      if (!retryState.deadLetterLogged) {
        retryState.deadLetterLogged = true;
        console.warn('Clevers Product Carousel: skipped dead-lettered carousel', carouselId);
      }
      return;
    }

    if ($el.hasClass('slick-initialized')) {
      clearRetryState(retryState);
      labelSlickControls($el);
      return;
    }

    if (typeof $el.slick !== 'function') {
      console.warn('Clevers Product Carousel: Slick not loaded');
      scheduleRetry(carouselId, retryState, function () {
        initSingleCarousel($el);
      }, 'slick-not-loaded');
      return;
    }

    var slides = Math.max(1, intData($el, 'slides', 4));
    var slidesTablet = Math.max(1, intData($el, 'slides-tablet', Math.min(slides, 2)));
    var slidesMobile = Math.max(1, intData($el, 'slides-mobile', 1));
    var autoplay = boolData($el, 'autoplay', false);
    var speed = Math.max(500, intData($el, 'speed', 3000));
    var dots = boolData($el, 'dots', false);
    var arrows = boolData($el, 'arrows', true);
    var centerMode = boolData($el, 'center', false);
    var pauseOnHover = boolData($el, 'pause-on-hover', true);
    var pauseOnFocus = boolData($el, 'pause-on-focus', true);
    var respectReducedMotion = boolData($el, 'reduced-motion', true);
    var builderCompat = boolData($el, 'builder-compat', false);
    var builderDelay = Math.max(0, intData($el, 'builder-delay', 0));
    var disableCenterOnBuilder = boolData($el, 'disable-center-on-builder', false);

    if (builderCompat && disableCenterOnBuilder && inBuilderContext($el)) {
      centerMode = false;
    }

    if (autoplay && respectReducedMotion && prefersReducedMotion()) {
      autoplay = false;
    }

    $el.attr('aria-live', autoplay ? 'off' : 'polite');

    $el.on('init', function () {
      labelSlickControls($el);
    });

    $el.on('afterChange', function () {
      labelSlickControls($el);
    });

    var initSlick = function () {
      if ($el.hasClass('slick-initialized')) {
        clearRetryState(retryState);
        labelSlickControls($el);
        return;
      }

      try {
        $el.slick({
          slidesToShow: slides,
          slidesToScroll: 1,
          autoplay: autoplay,
          autoplaySpeed: speed,
          dots: dots,
          arrows: arrows,
          centerMode: centerMode,
          pauseOnHover: pauseOnHover,
          pauseOnFocus: pauseOnFocus,
          accessibility: true,
          adaptiveHeight: false,
          lazyLoad: 'ondemand',
          rtl: $('html').attr('dir') === 'rtl',
          responsive: [
            { breakpoint: 1024, settings: { slidesToShow: slidesTablet } },
            { breakpoint: 768, settings: { slidesToShow: slidesMobile } },
            { breakpoint: 480, settings: { slidesToShow: 1 } }
          ]
        });
        clearRetryState(retryState);
      } catch (err) {
        var reason = err && err.message ? err.message : 'slick-init-failed';
        scheduleRetry(carouselId, retryState, function () {
          initSingleCarousel($el);
        }, reason);
      }
    };

    if (builderCompat && builderDelay > 0) {
      window.setTimeout(initSlick, builderDelay);
    } else {
      initSlick();
    }
  }

  function initCleversSliders(ctx) {
    var $wraps = $('.clevers-product-carousel .slick-carousel', ctx || document);
    if (!$wraps.length) return;

    $wraps.each(function () {
      var $el = $(this);
      initSingleCarousel($el);
    });
  }

  $(function () {
    initCleversSliders(document);
  });

  // Expose a manual hook for builders/console debugging.
  window.ClevprcaProductCarouselInit = function (ctx) {
    initCleversSliders(ctx || document);
  };

  window.ClevprcaProductCarouselDeadLetterQueue = function () {
    return deadLetterQueue.slice();
  };

  // Builders like Brizy inject modules after DOM ready; observe and re-init.
  if (typeof MutationObserver === 'function') {
    var observerQueued = false;
    var observer = new MutationObserver(function (mutations) {
      var shouldInit = false;

      for (var i = 0; i < mutations.length; i += 1) {
        var mutation = mutations[i];
        if (!mutation.addedNodes || !mutation.addedNodes.length) continue;

        for (var j = 0; j < mutation.addedNodes.length; j += 1) {
          var node = mutation.addedNodes[j];
          if (!node || node.nodeType !== 1) continue;

          if (
            (node.matches && (node.matches('.clevers-product-carousel') || node.matches('.slick-carousel'))) ||
            (node.querySelector && node.querySelector('.clevers-product-carousel .slick-carousel'))
          ) {
            shouldInit = true;
            break;
          }
        }

        if (shouldInit) break;
      }

      if (!shouldInit || observerQueued) return;
      observerQueued = true;

      window.requestAnimationFrame(function () {
        observerQueued = false;
        initCleversSliders(document);
      });
    });

    $(function () {
      if (document.body) {
        observer.observe(document.body, { childList: true, subtree: true });
      }
    });
  }

  $(document.body).on('updated_wc_div wc_fragments_refreshed post-load', function () {
    initCleversSliders(document);
  });

  // Extra hooks commonly triggered by builders/theme AJAX refreshes.
  $(window).on('load', function () {
    initCleversSliders(document);
    setTimeout(function () { initCleversSliders(document); }, 300);
    setTimeout(function () { initCleversSliders(document); }, 1200);
    setTimeout(function () { initCleversSliders(document); }, 2500);
    setTimeout(function () { initCleversSliders(document); }, 5000);
  });

  $(window).on('pageshow resize message', function () {
    initCleversSliders(document);
  });
})(jQuery);
