// dashboard2.js
// JS extrait et adapté depuis dashboard 2.html

let chart = null;
let manegesGlobaux = [];
let manegesAffiches = 3;

function afficherLoading(show) {
  $('#loading').toggle(show);
}

function afficherErreur(message) {
  $('#error-message').text(message);
  $('#error-alert').show();
  setTimeout(() => {
    $('#error-alert').fadeOut();
  }, 5000);
}

function afficherInfo(message) {
  $('#info-message').text(message);
  $('#info-alert').show();
  setTimeout(() => {
    $('#info-alert').fadeOut();
  }, 3000);
}

function chargerDonneesManeges() {
  return $.ajax({
    url: '/G11C/G11C_A1/getManegesData',
    method: 'GET',
    dataType: 'json',
    timeout: 10000,
    cache: false
  });
}

function afficherTousLesManeges(maneges) {
  manegesGlobaux = maneges;
  const container = $('#all-manege-details');
  container.empty();
  if (!maneges || maneges.length === 0) {
    container.append(`
      <div class="col-12">
        <div class="alert alert-warning">
          <i class="fas fa-exclamation-triangle"></i> Aucun manège trouvé dans la base de données.
        </div>
      </div>
    `);
    $('#show-more-btn').remove();
    return;
  }
  const aAfficher = maneges.slice(0, manegesAffiches);
  aAfficher.forEach(manege => {
    const id = manege.id || 'N/A';
    const nom = manege.nom_manege || manege.nom || 'Manège sans nom';
    const statut = manege.statut || 'inconnu';
    const nbPassagers = parseInt(manege.nb_passagers) || 0;
    const temperature = parseFloat(manege.temperature) || 0;
    let statutHtml = '';
    if (statut.toLowerCase() === 'actif') {
      statutHtml = '<span class="status-active">Actif ✅</span>';
    } else if (statut.toLowerCase() === 'inactif') {
      statutHtml = '<span class="status-inactive">Inactif ❌</span>';
    } else {
      statutHtml = `<span class="text-muted">${statut} ❓</span>`;
    }
    const cardHtml = `
      <div class="col-md-6 col-lg-4">
        <div class="card">
          <h5><i class="fas fa-ferris-wheel"></i> ${nom}</h5>
          <p><strong>Nom du manège :</strong> ${nom}</p>
          <p><strong>ID :</strong> ${id}</p>
          <p><strong>Statut :</strong> ${statutHtml}</p>
          <p><strong>Nombre de passagers :</strong> <span class="badge bg-primary">${nbPassagers}</span></p>
          <p><strong>Température :</strong> <span class="badge bg-warning text-dark">${temperature.toFixed(1)} °C</span></p>
        </div>
      </div>
    `;
    container.append(cardHtml);
  });
  if (maneges.length > manegesAffiches) {
    if ($('#show-more-btn').length === 0) {
      container.after('<div class="text-center my-3"><button id="show-more-btn" class="btn btn-primary">Montrer plus</button></div>');
    }
  } else {
    $('#show-more-btn').remove();
  }
}

$(document).on('click', '#show-more-btn', function() {
  manegesAffiches += 3;
  afficherTousLesManeges(manegesGlobaux);
});

function afficherGraphique(maneges) {
  if (!maneges || maneges.length === 0) {
    $('#chart-container').hide();
    return;
  }
  const validManeges = maneges.filter(m =>
    m.nom_manege &&
    !isNaN(parseInt(m.nb_passagers)) &&
    !isNaN(parseFloat(m.temperature))
  );
  if (validManeges.length === 0) {
    $('#chart-container').hide();
    afficherInfo('Aucune donnée valide pour afficher le graphique');
    return;
  }
  const labels = validManeges.map(m => m.nom_manege);
  const nbPassagers = validManeges.map(m => parseInt(m.nb_passagers) || 0);
  const temperatures = validManeges.map(m => parseFloat(m.temperature) || 0);
  const ctx = document.getElementById('passengersChart').getContext('2d');
  if (chart) {
    chart.destroy();
  }
  chart = new Chart(ctx, {
    data: {
      labels: labels,
      datasets: [
        {
          type: 'bar',
          label: 'Nombre de passagers',
          data: nbPassagers,
          backgroundColor: 'rgba(42, 105, 172, 0.7)',
          borderColor: 'rgba(42, 105, 172, 1)',
          borderWidth: 1,
          borderRadius: 5,
          yAxisID: 'y',
        },
        {
          type: 'line',
          label: 'Température (°C)',
          data: temperatures,
          borderColor: '#f97316',
          backgroundColor: '#f9731666',
          yAxisID: 'y1',
          tension: 0.3,
          fill: true,
          pointRadius: 5,
          pointHoverRadius: 7,
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false,
      },
      plugins: {
        title: {
          display: true,
          text: 'Données des manèges en temps réel'
        },
        legend: {
          display: true,
          position: 'top'
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Nombre de passagers'
          }
        },
        y1: {
          beginAtZero: false,
          position: 'right',
          title: {
            display: true,
            text: 'Température (°C)'
          },
          grid: {
            drawOnChartArea: false
          }
        }
      }
    }
  });
  $('#chart-container').show();
}

function initialiserDashboard() {
  afficherLoading(true);
  $('#error-alert').hide();
  $('#info-alert').hide();
  chargerDonneesManeges()
    .done(response => {
      afficherLoading(false);
      if (response.success && response.maneges && response.maneges.length > 0) {
        afficherTousLesManeges(response.maneges);
        afficherGraphique(response.maneges);
        afficherInfo(`${response.maneges.length} manège(s) chargé(s) avec succès`);
      } else {
        afficherTousLesManeges([]);
        afficherInfo('Aucun manège trouvé');
      }
    })
    .fail((xhr, status, error) => {
      afficherLoading(false);
      let errorMessage = 'Impossible de joindre le serveur';
      if (xhr.status === 404) {
        errorMessage = 'Fichier API non trouvé (404)';
      } else if (xhr.status === 500) {
        errorMessage = 'Erreur serveur (500)';
      } else if (status === 'timeout') {
        errorMessage = 'Délai d\'attente dépassé';
      } else if (status === 'parsererror') {
        errorMessage = 'Erreur de format de données';
      }
      afficherErreur(errorMessage + ' - ' + error);
      afficherTousLesManeges([]);
    });
}

function demarrerAutoRefresh() {
  setInterval(() => {
    chargerDonneesManeges()
      .done(response => {
        if (response.success && response.maneges) {
          afficherTousLesManeges(response.maneges);
          afficherGraphique(response.maneges);
        }
      })
      .fail(() => {
        // Optionnel : afficher une alerte ou log
      });
  }, 30000);
}

$(document).ready(() => {
  initialiserDashboard();
  demarrerAutoRefresh();
});
