(function () {
  var form = document.getElementById('contact-form');
  if (!form) return;

  var messageEl = document.getElementById('contact-message');
  var submitBtn = form.querySelector('button[type="submit"]');

  function setMessage(text, isError) {
    messageEl.textContent = text;
    messageEl.className = 'contact-message' + (isError ? ' contact-message--error' : ' contact-message--info');
  }

  function showSuccess() {
    form.classList.add('hidden');
    messageEl.className = 'contact-message contact-message--success';
    messageEl.innerHTML = '<span class="contact-message__title">Message sent</span>' +
      '<span class="contact-message__body">Thanks for getting in touch — we\'ll reply as soon as we can.</span>';
  }

  function resolveApiUrl() {
    var base = (typeof window.ROP_API_BASE !== 'undefined' && window.ROP_API_BASE) ? window.ROP_API_BASE : '';
    if (!base && typeof window.location !== 'undefined') {
      var h = window.location.hostname || '';
      if (h === 'dutch.reignofplay.com' || h === 'dutch.mt' || h.endsWith('.dutch.mt') ||
          h === 'reignofplay.com' || h === 'www.reignofplay.com') {
        base = 'https://dashboard.reignofplay.com';
      }
    }
    return base ? (base.replace(/\/$/, '') + '/api/contact.php') : '/api/contact.php';
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    submitBtn.disabled = true;
    setMessage('Sending…', false);

    var body = {
      name: document.getElementById('name').value.trim(),
      email: document.getElementById('email').value.trim(),
      platform: document.getElementById('platform').value,
      message: document.getElementById('message').value.trim(),
      source: form.getAttribute('data-source') || 'Dutch'
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
        submitBtn.disabled = false;
      })
      .catch(function () {
        setMessage('Network error. Please try again.', true);
        submitBtn.disabled = false;
      });
  });
})();
