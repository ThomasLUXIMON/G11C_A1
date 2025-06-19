// gestion_manege.js
// Logique de gestion des manèges (exemple CRUD minimal)

$(function() {
  // Exemple : afficher un message ou charger dynamiquement la gestion
  $('#gestion-manege-container').html('<div class="alert alert-info">Gestion des manèges chargée. (À compléter selon besoins CRUD)</div>');

  // Formulaire d'ajout de manège
  const formHtml = `
    <form id="form-ajout-manege" class="mb-4">
      <div class="mb-3">
        <label for="nom_manege" class="form-label">Nom du manège</label>
        <input type="text" class="form-control" id="nom_manege" name="nom_manege" required>
      </div>
      <div class="mb-3">
        <label for="capacite" class="form-label">Capacité</label>
        <input type="number" class="form-control" id="capacite" name="capacite" min="1" required>
      </div>
      <div class="mb-3">
        <label for="type" class="form-label">Type</label>
        <input type="text" class="form-control" id="type" name="type" required>
      </div>
      <div class="mb-3">
        <label for="statut" class="form-label">Statut</label>
        <select class="form-select" id="statut" name="statut" required>
          <option value="actif">Actif</option>
          <option value="inactif">Inactif</option>
          <option value="maintenance">Maintenance</option>
        </select>
      </div>
      <button type="submit" class="btn btn-success">Ajouter le manège</button>
      <div id="ajout-manege-message" class="mt-2"></div>
    </form>
  `;
  $('#gestion-manege-container').html(formHtml);

  // Soumission du formulaire
  $(document).on('submit', '#form-ajout-manege', function(e) {
    e.preventDefault();
    const data = $(this).serialize();
    $.ajax({
      url: '/G11C/G11C_A1/maneges',
      method: 'POST',
      data: data,
      dataType: 'json',
      success: function(resp) {
        if (resp.success) {
          $('#ajout-manege-message').html('<div class="alert alert-success">Manège ajouté avec succès !</div>');
          $('#form-ajout-manege')[0].reset();
        } else {
          $('#ajout-manege-message').html('<div class="alert alert-danger">' + (resp.message || 'Erreur lors de l\'ajout') + '</div>');
        }
      },
      error: function(xhr) {
        let msg = 'Erreur serveur';
        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
        $('#ajout-manege-message').html('<div class="alert alert-danger">' + msg + '</div>');
      }
    });
  });
});
