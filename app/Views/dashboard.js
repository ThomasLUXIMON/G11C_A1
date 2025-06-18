// dashboard.js
// Ce fichier gère l'affichage dynamique des manèges et du graphique sur le dashboard après connexion.

// Récupération dynamique des manèges et de leurs détails via une API (ex: /api/stats)
// Ici, on utilise des données statiques comme fallback si l'API n'est pas disponible.

async function fetchDashboardData() {
    try {
        const response = await fetch('/api/stats');
        if (!response.ok) throw new Error('Erreur API');
        const result = await response.json();
        if (result.success && result.data && result.data.maneges) {
            return result.data.maneges;
        }
    } catch (e) {
        // fallback statique
        return [
            { id: 1, nom: "Grand 8", actif: true, nb_passagers: 15, temperature: 25.6 },
            { id: 2, nom: "Tonneaux Volants", actif: false, nb_passagers: 0, temperature: 22.3 },
            { id: 3, nom: "Train Fantôme", actif: true, nb_passagers: 6, temperature: 24.1 },
            { id: 4, nom: "Chaises Volantes", actif: true, nb_passagers: 12, temperature: 26.0 }
        ];
    }
}

function afficherTousLesManeges(maneges) {
    const container = document.getElementById('all-manege-details');
    container.innerHTML = '';
    maneges.forEach(manege => {
        const statut = manege.actif ? 'Actif ✅' : 'Inactif ❌';
        const cardHtml = `
          <div class="col-md-6 col-lg-4">
            <div class="card">
              <h5>${manege.nom}</h5>
              <p><strong>Statut :</strong> ${statut}</p>
              <p><strong>Nombre de passagers :</strong> ${manege.nb_passagers}</p>
              <p><strong>Température :</strong> ${manege.temperature.toFixed(1)} °C</p>
            </div>
          </div>
        `;
        container.insertAdjacentHTML('beforeend', cardHtml);
    });
}

function afficherGraphique(maneges) {
    const labels = maneges.map(m => m.nom);
    const nbPassagers = maneges.map(m => m.nb_passagers);
    const temperatures = maneges.map(m => m.temperature);
    const ctx = document.getElementById('passengersChart').getContext('2d');
    new Chart(ctx, {
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
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Nombre de passagers'
                    },
                    ticks: { stepSize: 1 }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'Température (°C)'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    enabled: true,
                    mode: 'nearest',
                    intersect: false,
                }
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', async () => {
    const maneges = await fetchDashboardData();
    afficherTousLesManeges(maneges);
    afficherGraphique(maneges);
});
