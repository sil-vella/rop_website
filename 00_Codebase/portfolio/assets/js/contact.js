(function () {
  var form = document.getElementById('contact-form');
  if (!form) return;

  var messageEl = document.getElementById('contact-message');
  var submitBtn = form.querySelector('input[type="submit"], button[type="submit"]');

  function setMessage(text, isError) {
    if (!messageEl) return;
    messageEl.textContent = text;
    messageEl.className = isError ? 'contact-message msg-error' : 'contact-message msg-info';
  }

  function showSuccess() {
    form.classList.add('hidden');
    if (!messageEl) return;
    messageEl.className = 'contact-message msg-success';
    messageEl.innerHTML = '<strong>Message sent</strong><br>I will reply as soon as possible.';
  }

  function resolveApiUrl() {
    var base = (typeof window.ROP_API_BASE !== 'undefined' && window.ROP_API_BASE) ? window.ROP_API_BASE : '';
    if (!base && typeof window.location !== 'undefined') {
      var h = window.location.hostname || '';
      // Prefer local PHP api when this site is served as PHP; fall back to dashboard for static hosts.
      if (h === 'reignofplay.com' || h === 'www.reignofplay.com' ||
          h === 'dutch.reignofplay.com' || h === 'dutch.mt' || h.endsWith('.dutch.mt') ||
          h === 'portfolio.reignofplay.com') {
        base = 'https://dashboard.reignofplay.com';
      }
    }
    return base ? (base.replace(/\/$/, '') + '/api/contact.php') : '/api/contact.php';
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (submitBtn) submitBtn.disabled = true;
    setMessage('Sending…', false);

    var nameEl = document.getElementById('name');
    var emailEl = document.getElementById('email');
    var messageInput = document.getElementById('message');
    var body = {
      name: nameEl ? nameEl.value.trim() : '',
      email: emailEl ? emailEl.value.trim() : '',
      message: messageInput ? messageInput.value.trim() : '',
      source: (form.getAttribute('data-source') || 'Portfolio')
    };
    var recipient = (form.getAttribute('data-recipient') || '').trim();
    if (recipient) {
      body.recipient = recipient;
    }

    fetch(resolveApiUrl(), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { ok: res.ok, data: data };
        });
      })
      .then(function (result) {
        if (result.ok && result.data && result.data.success) {
          showSuccess();
          return;
        }
        var err = (result.data && result.data.error) ? result.data.error : 'Send failed. Please try again.';
        setMessage(err, true);
        if (submitBtn) submitBtn.disabled = false;
      })
      .catch(function () {
        setMessage('Network error. Please try again.', true);
        if (submitBtn) submitBtn.disabled = false;
      });
  });
})();
