<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Tableau de Bord - Système de Gestion de Manège</title>

  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <style>
    body {
      background: linear-gradient(135deg, #e0f7fa, #ffffff);
      font-family: 'Nunito', sans-serif;
      color: #2d3748;
      min-height: 100vh;
      display: flex;
    }

    .sidebar {
      width: 220px;
      background: #2a69ac;
      color: white;
      display: flex;
      flex-direction: column;
      padding: 20px;
      min-height: 100vh;
    }

    .sidebar h4 {
      margin-bottom: 30px;
      text-align: center;
      font-weight: bold;
    }

    .sidebar a {
      color: white;
      text-decoration: none;
      padding: 10px;
      margin-bottom: 10px;
      display: block;
      border-radius: 8px;
      transition: background 0.2s;
    }

    .sidebar a:hover {
      background-color: #1e4e8c;
    }

    .main-content {
      flex-grow: 1;
      padding: 20px;
    }

    .navbar {
      background: linear-gradient(to right, #2a69ac, #4dabf7);
      border-radius: 8px;
      padding: 10px 20px;
      margin-bottom: 20px;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      margin-bottom: 20px;
    }

    .stat-card {
      padding: 20px;
      text-align: center;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      transition: transform 0.2s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
    }

    .stat-card .stat-number {
      font-size: 2rem;
      font-weight: bold;
      margin-bottom: 5px;
    }

    .stat-card .stat-label {
      font-size: 0.9rem;
      opacity: 0.9;
    }

    .chart-container {
      height: 300px;
      position: relative;
    }

    .manege-status {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px;
      margin-bottom: 10px;
      border-radius: 8px;
      background: #f8f9fa;
    }

    .status-active { border-left: 4px solid #28a745; }
    .status-maintenance { border-left: 4px solid #ffc107; }
    .status-inactive { border-left: 4px solid #dc3545; }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <h4>🎠 Manège</h4>
    <a href="/G11C/G11C_A1/dashboard"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
    <a href="/G11C/G11C_A1/maneges"><i class="fas fa-horse"></i> Manèges</a>
    <a href="/G11C/G11C_A1/sessions"><i class="fas fa-play-circle"></i> Sessions</a>
    <a href="/G11C/G11C_A1/security"><i class="fas fa-shield-alt"></i> Sécurité</a>
    <a href="/G11C/G11C_A1/admin/dashboard"><i class="fas fa-cogs"></i> Administration</a>
    <hr>
    <a href="/G11C/G11C_A1/logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
  </div>

  <!-- Contenu principal -->
  <div class="main-content">
    <!-- Header -->
    <div class="navbar">
      <h5>Tableau de Bord</h5>
      <div>
        <span>Bienvenue, Utilisateur</span>
        <span class="badge bg-light text-dark ms-2">Admin</span>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row" id="stats-cards">
      <div class="col-md-3">
        <div class="card stat-card">
          <div class="stat-number" id="total-maneges">-</div>
          <div class="stat-label">Manèges Total</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card">
          <div class="stat-number" id="maneges-actifs">-</div>
          <div class="stat-label">Manèges Actifs</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card">
          <div class="stat-number" id="sessions-actives">-</div>
          <div class="stat-label">Sessions Actives</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card">
          <div class="stat-number" id="taux-occupation">-</div>
          <div class="stat-label">Taux d'Occupation</div>
        </div>
      </div>
    </div>

    <!-- Graphiques -->
    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h6><i class="fas fa-chart-pie"></i> Répartition par Type</h6>
          </div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="maneges-type-chart"></canvas>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h6><i class="fas fa-chart-bar"></i> Statut des Manèges</h6>
          </div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="maneges-status-chart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- État des manèges en temps réel -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h6><i class="fas fa-horse"></i> État des Manèges en Temps Réel</h6>
            <button class="btn btn-sm btn-outline-primary" onclick="refreshManegesData()">
              <i class="fas fa-sync-alt"></i> Actualiser
            </button>
          </div>
          <div class="card-body" id="maneges-list">
            <p class="text-center">Chargement des données...</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    let typeChart, statusChart;

    // Charger les données au démarrage
    document.addEventListener('DOMContentLoaded', function() {
      loadDashboardData();
      loadManegesData();
      
      // Actualisation automatique toutes les 30 secondes
      setInterval(loadDashboardData, 30000);
      setInterval(loadManegesData, 30000);
    });

    async function loadDashboardData() {
      try {
        const response = await fetch('/G11C/G11C_A1/api/stats');
        const result = await response.json();
        
        if (result.success) {
          updateStatsCards(result.data);
          updateCharts(result.data);
        }
      } catch (error) {
        console.error('Erreur lors du chargement des statistiques:', error);
      }
    }

    async function loadManegesData() {
      try {
        const response = await fetch('/G11C/G11C_A1/getManegesData');
        const result = await response.json();
        
        if (result.success) {
          updateManegesList(result.maneges);
        }
      } catch (error) {
        console.error('Erreur lors du chargement des manèges:', error);
      }
    }

    function updateStatsCards(data) {
      document.getElementById('total-maneges').textContent = data.total_maneges || 0;
      document.getElementById('maneges-actifs').textContent = data.maneges_actifs || 0;
      document.getElementById('sessions-actives').textContent = data.sessions_actives || 0;
      document.getElementById('taux-occupation').textContent = (data.taux_occupation || 0) + '%';
    }

    function updateCharts(data) {
      // Graphique répartition par type
      if (typeChart) typeChart.destroy();
      const typeCtx = document.getElementById('maneges-type-chart').getContext('2d');
      typeChart = new Chart(typeCtx, {
        type: 'pie',
        data: {
          labels: data.maneges_by_type?.map(item => item.type) || [],
          datasets: [{
            data: data.maneges_by_type?.map(item => item.count) || [],
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      });

      // Graphique statut des manèges
      if (statusChart) statusChart.destroy();
      const statusCtx = document.getElementById('maneges-status-chart').getContext('2d');
      statusChart = new Chart(statusCtx, {
        type: 'bar',
        data: {
          labels: ['Actifs', 'Maintenance', 'Inactifs'],
          datasets: [{
            label: 'Nombre de manèges',
            data: [
              data.maneges_actifs || 0,
              data.maneges_maintenance || 0,
              data.maneges_inactifs || 0
            ],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545']
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    }

    function updateManegesList(maneges) {
      const container = document.getElementById('maneges-list');
      
      if (!maneges || maneges.length === 0) {
        container.innerHTML = '<p class="text-muted">Aucun manège trouvé</p>';
        return;
      }

      let html = '';
      maneges.forEach(manege => {
        const statusClass = manege.statut === 'actif' ? 'status-active' : 
                           manege.statut === 'maintenance' ? 'status-maintenance' : 'status-inactive';
        
        const sessionText = manege.sessions_actives > 0 ? 
          `${manege.sessions_actives} session(s) active(s)` : 'Aucune session';

        html += `
          <div class="manege-status ${statusClass}">
            <div>
              <strong>${manege.nom}</strong> (${manege.type})
              <br><small class="text-muted">Capacité: ${manege.capacite_max} personnes</small>
            </div>
            <div class="text-end">
              <span class="badge bg-${manege.statut === 'actif' ? 'success' : manege.statut === 'maintenance' ? 'warning' : 'secondary'}">
                ${manege.statut}
              </span>
              <br><small>${sessionText}</small>
            </div>
          </div>
        `;
      });
      
      container.innerHTML = html;
    }

    function refreshManegesData() {
      loadManegesData();
    }
  </script>
</body>
</html>
