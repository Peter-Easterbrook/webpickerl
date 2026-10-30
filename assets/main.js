// Current year in the footer; the static year in the HTML is the fallback.
Array.prototype.forEach.call(document.querySelectorAll(".year"), function (el) {
  el.textContent = new Date().getFullYear();
});

// Countdown to a deadline, e.g. the end of PHP 8.2 security updates.
// The date stays in the page as text; the countdown is an extra.
(function () {
  var els = document.querySelectorAll(".countdown[data-until]");
  var today = new Date();
  today.setHours(0, 0, 0, 0);
  Array.prototype.forEach.call(els, function (el) {
    var p = el.getAttribute("data-until").split("-");
    var until = new Date(+p[0], +p[1] - 1, +p[2]);
    var days = Math.round((until - today) / 86400000);
    if (isNaN(days)) return;
    el.textContent = days > 1 ? "noch " + days + " Tage"
      : days === 1 ? "noch 1 Tag"
      : days === 0 ? "heute"
      : "abgelaufen";
    el.hidden = false;
  });
})();

// Form validation and submission for the free website check.
// No tracking, no third-party requests. Sends to the form's action URL once one is set.
(function () {
  var form = document.getElementById("check-form");
  if (!form) return;
  var status = document.getElementById("form-status");

  var rules = [
    { input: "f-name", error: "e-name", valid: function (el) { return el.value.trim().length > 1; } },
    { input: "f-email", error: "e-email", valid: function (el) { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(el.value.trim()); } },
    { input: "f-url", error: "e-url", valid: function (el) { return /^(https?:\/\/)?([\w-]+\.)+[a-z]{2,}(\/\S*)?$/i.test(el.value.trim()); } },
    { input: "f-berechtigt", error: "e-berechtigt", valid: function (el) { return el.checked; } }
  ];

  function check(rule) {
    var el = document.getElementById(rule.input);
    var err = document.getElementById(rule.error);
    var ok = rule.valid(el);
    el.setAttribute("aria-invalid", ok ? "false" : "true");
    err.hidden = ok;
    return ok ? null : el;
  }

  rules.forEach(function (rule) {
    var el = document.getElementById(rule.input);
    el.addEventListener(el.type === "checkbox" ? "change" : "blur", function () {
      if (el.getAttribute("aria-invalid") !== null || el.value) check(rule);
    });
  });

  form.addEventListener("submit", function (event) {
    event.preventDefault();
    status.textContent = "";
    var firstInvalid = null;
    rules.forEach(function (rule) {
      var bad = check(rule);
      if (bad && !firstInvalid) firstInvalid = bad;
    });
    if (firstInvalid) {
      status.textContent = "Bitte prüfen Sie die markierten Felder.";
      firstInvalid.focus();
      return;
    }
    if (document.getElementById("f-hp").value) return; // honeypot filled: likely a bot

    var action = form.getAttribute("action") || "";
    if (action.indexOf("[") === 0 || action === "") {
      status.textContent = "Vorschau: Das Formular ist noch mit keinem Server verbunden. Ihre Angaben wurden nicht gesendet.";
      return;
    }

    var button = form.querySelector("button[type=submit]");
    button.disabled = true;
    fetch(action, { method: "POST", body: new FormData(form), headers: { Accept: "application/json" } })
      .then(function (res) {
        return res.json().then(function (data) {
          if (!res.ok || !data.ok) throw { server: data.message };
          form.reset();
          status.textContent = data.message;
        });
      })
      .catch(function (err) {
        status.textContent = (err && err.server)
          ? err.server
          : "Das Senden ist fehlgeschlagen. Bitte versuchen Sie es in ein paar Minuten noch einmal.";
      })
      .then(function () { button.disabled = false; });
  });
})();
