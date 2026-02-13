
(function(){
  function qs(form){
    const fd = new FormData(form);
    const params = new URLSearchParams();
    for (const [k,v] of fd.entries()){
      if (v !== null && String(v).trim() !== "") params.append(k, v);
    }
    return params.toString();
  }

  function updateUrl(form){
    const url = new URL(window.location.href);
    url.search = qs(form);
    window.history.replaceState({}, "", url.toString());
  }

  async function submitAjax(form){
    const wrap = document.querySelector("[data-wpemcli-results]");
    if (!wrap) return false;

    wrap.classList.add("wpemcli-loading");

    const params = qs(form);
    const body = new URLSearchParams();
    body.set("action", "wpemcli_filter");
    body.set("nonce", WPEMCLI_FRONTEND.nonce);
    body.set("params", params);

    const res = await fetch(WPEMCLI_FRONTEND.ajax_url, {
      method: "POST",
      headers: {"Content-Type":"application/x-www-form-urlencoded; charset=UTF-8"},
      body: body.toString()
    });

    const data = await res.json().catch(()=>null);
    if (data && data.success && typeof data.data === "string"){
      wrap.innerHTML = data.data;
      wrap.classList.remove("wpemcli-loading");
      updateUrl(form);
      return true;
    }

    wrap.classList.remove("wpemcli-loading");
    return false;
  }

  function onFilterSubmit(e){
    const form = e.target;
    if (!form.matches(".wpemcli-filters")) return;
    if (!WPEMCLI_FRONTEND || !WPEMCLI_FRONTEND.ajax_url) return;
    e.preventDefault();
    const hid = form.querySelector('input[name="paged"]');
    if (hid) hid.value = "1";
    submitAjax(form);
  }

  function onPaginationClick(e){
    const a = e.target.closest(".wpemcli-pagination a");
    if (!a) return;
    const form = document.querySelector(".wpemcli-filters");
    const wrap = document.querySelector("[data-wpemcli-results]");
    if (!form || !wrap) return;

    e.preventDefault();

    const url = new URL(a.href);
    const paged = url.searchParams.get("paged") || "1";

    let hid = form.querySelector('input[name="paged"]');
    if (!hid){
      hid = document.createElement("input");
      hid.type = "hidden";
      hid.name = "paged";
      form.appendChild(hid);
    }
    hid.value = paged;

    submitAjax(form);
  }

  document.addEventListener("submit", onFilterSubmit);
  document.addEventListener("click", onPaginationClick);
})();

(function(){
  function ensureErrorEl(email){
    var parent = email && email.parentElement ? email.parentElement : null;
    if (!parent) return null;
    var el = parent.querySelector(".wpemcli-form-error");
    if (el) return el;
    el = document.createElement("div");
    el.className = "wpemcli-form-error";
    parent.appendChild(el);
    return el;
  }

  document.addEventListener("submit", function(e){
    var form = e.target;
    if (!form || !form.classList || !form.classList.contains("wpemcli-form")) return;

    var email = form.querySelector('input[name="email"]');
    if (!email) return;

    var msg = ensureErrorEl(email);
    if (msg) msg.textContent = "";
    email.classList.remove("wpemcli-input-error");

    if (!email.value || !email.checkValidity()){
      e.preventDefault();
      if (msg) msg.textContent = "Please enter a valid email address.";
      email.classList.add("wpemcli-input-error");
      email.focus();
    }
  });
})();