export function startGuard() {

    if (import.meta.env.VITE_ANTI_DEVTOOLS !== 'true') {
    return
  }
  try {
 
    const enc = (str) => btoa(unescape(encodeURIComponent(str)));
    const dec = (str) => decodeURIComponent(escape(atob(str)));

    
    const corePayload = `
      (function(){
        if (window.__ANTI_DEVTOOLS_CORE__) return;
        window.__ANTI_DEVTOOLS_CORE__ = true;

        var _setInterval = window.setInterval.bind(window);
        var _setTimeout  = window.setTimeout.bind(window);
        var _DateNow     = Date.now;

        var blocked = false;
        var lastTick = _DateNow();
        window.__ANTI_DEVTOOLS_LAST_TICK__ = lastTick;

        function safe(fn){
          try { fn && fn(); } catch(e) {}
        }

        
        function detectSize() {
          var th = 160;
          var dh = window.outerHeight - window.innerHeight;
          var dw = window.outerWidth  - window.innerWidth;
          if (dh > th || dw > th) blocked = true;
        }

        
        function detectDebugger() {
          var s = performance.now();
          debugger;
          var d = performance.now() - s;
          if (d > 60) blocked = true;
        }

        
        function detectTimeSkew() {
          var now = _DateNow();
          var diff = now - lastTick;
          if (diff > 2000) blocked = true;
          lastTick = now;
          window.__ANTI_DEVTOOLS_LAST_TICK__ = lastTick;
        }

       
        safe(function(){
          try {
            Object.defineProperty(window, 'setInterval', {
              configurable: false,
              writable: false,
              value: function(fn, t){
                return _setInterval(fn, t);
              }
            });
          } catch(e) {}
          try {
            Object.defineProperty(window, 'clearInterval', {
              configurable: false,
              writable: false,
              value: window.clearInterval
            });
          } catch(e) {}
        });

        
        function freeze() {
          if (!blocked) return;

          // VM-файл с debugger
          debugger;

          try {
            var id = '__anti_devtools_overlay__';
            var el = document.getElementById(id);
            if (!el) {
              el = document.createElement('div');
              el.id = id;
              el.style.position = 'fixed';
              el.style.inset = '0';
              el.style.zIndex = '999999';
              el.style.background = 'rgba(255,255,255,0.96)';
              el.style.display = 'flex';
              el.style.flexDirection = 'column';
              el.style.alignItems = 'center';
              el.style.justifyContent = 'center';
              el.style.backdropFilter = 'blur(3px)';
              el.style.fontFamily = 'system-ui, -apple-system, BlinkMacSystemFont, sans-serif';
              el.innerHTML = '<div style="font-size:42px;margin-bottom:16px;">\\uD83D\\uDEE1️</div>' +
                '<div style="font-size:18px;font-weight:600;margin-bottom:8px;">Работа сайта приостановлена</div>' +
                
              document.body.appendChild(el);
            }
          } catch(e) {}
        }

    
        function mainLoop() {
          safe(detectSize);
          safe(detectDebugger);
          safe(detectTimeSkew);
          if (blocked) freeze();
        }

       
        (function loop(){
          mainLoop();
          _setTimeout(loop, 200 + Math.random()*200);
        })();

       
        _setInterval(function(){
          if (blocked) debugger;
        }, 90 + Math.random()*80);
      })();
    `;

   
    const watchdogPayload = `
      (function(){
        if (window.__ANTI_DEVTOOLS_WATCHDOG__) return;
        window.__ANTI_DEVTOOLS_WATCHDOG__ = true;

        var _setInterval = window.setInterval.bind(window);
        var _DateNow     = Date.now;

      
        var coreEncoded = '${enc(`
          (function(){
            if (window.__ANTI_DEVTOOLS_CORE__) return;
            window.__ANTI_DEVTOOLS_CORE__ = true;
            var _setTimeout = window.setTimeout.bind(window);
            var blocked = false;
            function safe(fn){ try { fn && fn(); } catch(e) {} }
            function detectDebugger(){
              var s = performance.now();
              debugger;
              var d = performance.now() - s;
              if (d > 60) blocked = true;
            }
            function loop(){
              safe(detectDebugger);
              if (blocked) debugger;
              _setTimeout(loop, 200 + Math.random()*200);
            }
            loop();
          })();
        `)}';

        function reviveCore() {
          try {
          
            var last = window.__ANTI_DEVTOOLS_LAST_TICK__ || 0;
            var now  = _DateNow();
            if (!window.__ANTI_DEVTOOLS_CORE__ || now - last > 2500) {
              window.__ANTI_DEVTOOLS_CORE__ = false;
              new Function(
                decodeURIComponent(escape(atob(coreEncoded)))
              )();
            }
          } catch(e) {}
        }

        _setInterval(reviveCore, 1200 + Math.random()*800);
      })();
    `;

   
    const coreEncoded   = enc(corePayload);
    const watchEncoded  = enc(watchdogPayload);

    new Function(dec(coreEncoded))();
    new Function(dec(watchEncoded))();

  } catch (e) {
    
    console?.warn?.('Anti-devtools guard error (disabled):', e);
  }
}
