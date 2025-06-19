// mon_compte.js
$(function() {
  $('#account-form').on('submit', function(e) {
    e.preventDefault();
    const data = $(this).serialize();
    $.post('/G11C/G11C_A1/mon_compte/update', data)
      .done(function(resp) {
        $('#account-message').html('<div class="alert alert-success">' + resp.message + '</div>');
        if (resp.success) setTimeout(() => location.reload(), 1200);
      })
      .fail(function(xhr) {
        let msg = 'Erreur inconnue';
        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
        $('#account-message').html('<div class="alert alert-danger">' + msg + '</div>');
      });
  });
  $('#delete-account').on('click', function() {
    if (!confirm('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.')) return;
    $.post('/G11C/G11C_A1/mon_compte/delete')
      .done(function(resp) {
        $('#account-message').html('<div class="alert alert-success">' + resp.message + '</div>');
        if (resp.success && resp.redirect) setTimeout(() => window.location.href = resp.redirect, 1200);
      })
      .fail(function(xhr) {
        let msg = 'Erreur inconnue';
        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
        $('#account-message').html('<div class="alert alert-danger">' + msg + '</div>');
      });
  });
});
