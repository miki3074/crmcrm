(function(){
  try {

    const enc = (str) => btoa(unescape(encodeURIComponent(str)));
    const dec = (str) => decodeURIComponent(escape(atob(str)));

    // ---------------- CORE ----------------
    const core = `
      (function(){
        if (window.__DEVTOOLS_CORE_ACTIVE__) return;
        window.__DEVTOOLS_CORE_ACTIVE__ = true;

        var blocked = false;
        var _setTimeout = window.setTimeout.bind(window);
        var _DateNow = Date.now;
        var lastTick = _DateNow();

        function safe(fn){ try { fn && fn(); } catch(e){} }

        function detectDebugger(){
          var t = performance.now();
          debugger;
          var d = performance.now() - t;
          if (d > 60) blocked = true;
        }

        function detectSize(){
          var th = 150;
          if ((window.outerHeight - window.innerHeight) > th) blocked = true;
          if ((window.outerWidth  - window.innerWidth ) > th) blocked = true;
        }

        function detectFreeze(){
          var now = _DateNow();
          if (now - lastTick > 2000) blocked = true;
          lastTick = now;
        }

        function killPage(){
          if (!blocked) return;
          
          // Полностью уничтожаем DOM
          try {
            document.documentElement.innerHTML = '';
          } catch(e){}

          try {
            var overlay = document.createElement('div');
            overlay.style.position = 'fixed';
            overlay.style.inset = '0';
            overlay.style.background = '#fff';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.style.fontFamily = 'system-ui';
            overlay.style.fontSize = '20px';
            overlay.style.zIndex = '999999999';
            overlay.textContent = '⏳ Загрузка...';
            document.documentElement.appendChild(overlay);
          } catch(e){}

          debugger; // VM-file
        }

        function loop(){
          safe(detectDebugger);
          safe(detectSize);
          safe(detectFreeze);
          if (blocked) killPage();
          _setTimeout(loop, 200 + Math.random()*200);
        }

        loop();

        setInterval(function(){
          if (blocked) debugger;
        }, 100 + Math.random()*200);

      })();
    `;

    // ---------------- WATCHDOG ----------------
    const watchdog = `
      (function(){
        if (window.__DEVTOOLS_WD_ACTIVE__) return;
        window.__DEVTOOLS_WD_ACTIVE__ = true;

        var corePayload = '${enc(core)}';
        var _setInterval = window.setInterval.bind(window);
        var _DateNow = Date.now;

        function restartCore(){
          try {
            var last = window.__DEVTOOLS_LAST_TICK__ || 0;
            var now  = _DateNow();
            if (!window.__DEVTOOLS_CORE_ACTIVE__ || now - last > 2500){
              window.__DEVTOOLS_CORE_ACTIVE__ = false;
              new Function(
                decodeURIComponent(escape(atob(corePayload)))
              )();
            }
          } catch(e){}
        }

        _setInterval(restartCore, 1000 + Math.random()*1000);
      })();
    `;

    // ---------------- RUN ----------------
    new Function(dec(enc(core)))();
    new Function(dec(enc(watchdog)))();

  } catch(e){
    console.warn("Guard disabled:", e);
  }
})();
